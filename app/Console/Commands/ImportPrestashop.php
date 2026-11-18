<?php

namespace App\Console\Commands;

use App\Enums\PieceStatus;
use App\Models\JewelryCollection;
use App\Models\Piece;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * One-off import of the catalogue from the old PrestaShop shop.
 *
 *   php artisan shop:import-prestashop --images=/path/to/prestashop/img/p --dry-run
 *
 * - Top-level categories (direct children of "Home") become collections.
 * - Products become pieces. Inactive → draft; out of stock → sold.
 * - Photos are copied from PrestaShop's img/p folder (originals).
 * - The PrestaShop ids are kept in legacy_id so old URLs redirect (301).
 *
 * Safe to run several times: records are matched on legacy_id and photos
 * are only imported for pieces that have none yet.
 *
 * Written against the standard PrestaShop 1.7 / 8 schema, but not yet run
 * on the real database: do a --dry-run first and check the output.
 */
class ImportPrestashop extends Command
{
    protected $signature = 'shop:import-prestashop
        {--images= : Path to the PrestaShop "img/p" folder, to copy photos}
        {--prefix=ps_ : Table prefix of the PrestaShop database}
        {--shop=1 : PrestaShop shop id}
        {--tax-rate=0 : Percentage to add to PrestaShop prices if they were stored without VAT}
        {--dry-run : Show what would be imported without writing anything}';

    protected $description = 'Import collections, pieces and photos from the old PrestaShop database';

    private Connection $ps;

    private string $p;

    public function handle(): int
    {
        $this->ps = DB::connection('prestashop');
        $this->p = (string) $this->option('prefix');
        $shopId = (int) $this->option('shop');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $this->ps->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot connect to the PrestaShop database. Set PRESTASHOP_DB_* in .env. '.$e->getMessage());

            return self::FAILURE;
        }

        $langs = $this->ps('lang')->pluck('id_lang', 'iso_code');
        $fr = $langs['fr'] ?? $langs->first();
        $en = $langs['en'] ?? $fr;
        $this->line("Languages: fr = {$fr}, en = {$en}");

        $homeId = (int) ($this->ps('configuration')->where('name', 'PS_HOME_CATEGORY')->value('value') ?: 2);
        $weightUnit = strtolower((string) $this->ps('configuration')->where('name', 'PS_WEIGHT_UNIT')->value('value') ?: 'kg');
        $dimensionUnit = (string) $this->ps('configuration')->where('name', 'PS_DIMENSION_UNIT')->value('value') ?: 'cm';

        // ---- Categories → collections -------------------------------------
        $categories = $this->ps('category')->get()->keyBy('id_category');
        $categoryTexts = $this->ps('category_lang')->where('id_shop', $shopId)->get()->groupBy('id_category');
        $topLevel = $categories->filter(fn ($c) => (int) $c->id_parent === $homeId);

        $collectionIds = [];
        foreach ($topLevel as $cat) {
            $texts = $categoryTexts->get($cat->id_category, collect())->keyBy('id_lang');
            $nameFr = $texts[$fr]->name ?? "Collection {$cat->id_category}";
            $data = [
                'name_fr' => $nameFr,
                'name_en' => $texts[$en]->name ?? $nameFr,
                'intro_fr' => $this->htmlToText($texts[$fr]->description ?? null),
                'intro_en' => $this->htmlToText($texts[$en]->description ?? null),
                'position' => (int) $cat->position,
                'is_published' => (bool) $cat->active,
            ];
            $slug = Str::slug($texts[$fr]->link_rewrite ?? $nameFr);
            $this->line("Collection #{$cat->id_category}: {$nameFr}");

            if (! $dryRun) {
                $collection = JewelryCollection::query()->firstOrNew(['legacy_id' => $cat->id_category]);
                $collection->fill($data);
                $collection->slug ??= $this->uniqueSlug(JewelryCollection::class, $slug, $collection->id);
                $collection->save();
                $collectionIds[$cat->id_category] = $collection->id;
            }
        }

        // Walk up from a product's default category to its top-level one.
        $collectionFor = function (?int $categoryId) use ($categories, $homeId, $collectionIds): ?int {
            for ($guard = 0; $categoryId && $guard < 20; $guard++) {
                $cat = $categories->get($categoryId);
                if (! $cat) {
                    return null;
                }
                if ((int) $cat->id_parent === $homeId) {
                    return $collectionIds[$categoryId] ?? null;
                }
                $categoryId = (int) $cat->id_parent;
            }

            return null;
        };

        // ---- Products → pieces ---------------------------------------------
        $products = $this->ps('product')->get()->keyBy('id_product');
        $productShop = $this->ps('product_shop')->where('id_shop', $shopId)->get()->keyBy('id_product');
        $productTexts = $this->ps('product_lang')->where('id_shop', $shopId)->get()->groupBy('id_product');
        $stock = $this->ps('stock_available')
            ->where('id_product_attribute', 0)
            ->whereIn('id_shop', [0, $shopId])
            ->get()->keyBy('id_product');
        $images = $this->ps('image')->orderByDesc('cover')->orderBy('position')->get()->groupBy('id_product');
        $imageTexts = $this->ps('image_lang')->get()->groupBy('id_image');

        $taxMultiplier = 1 + ((float) $this->option('tax-rate')) / 100;
        $withTaxRules = 0;
        $rows = [];

        foreach ($products as $id => $product) {
            $shop = $productShop->get($id) ?? $product;
            $texts = $productTexts->get($id, collect())->keyBy('id_lang');
            $titleFr = trim($texts[$fr]->name ?? "Produit {$id}");
            $titleEn = trim($texts[$en]->name ?? $titleFr);
            $reference = trim((string) $product->reference) ?: "PS{$id}";
            $quantity = (int) ($stock->get($id)->quantity ?? 0);
            $active = (bool) ($shop->active ?? $product->active);

            if ((int) ($shop->id_tax_rules_group ?? 0) > 0) {
                $withTaxRules++;
            }

            $status = ! $active ? PieceStatus::Draft : ($quantity > 0 ? PieceStatus::Available : PieceStatus::Sold);

            $dimensions = collect([$product->width ?? 0, $product->height ?? 0, $product->depth ?? 0])
                ->filter(fn ($v) => (float) $v > 0)
                ->map(fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ','))
                ->implode(' × ');

            $weight = (float) ($product->weight ?? 0);
            $weightGrams = $weight > 0 ? (int) round($weightUnit === 'kg' ? $weight * 1000 : $weight) : null;

            $data = [
                'collection_id' => $dryRun ? null : $collectionFor((int) ($shop->id_category_default ?? $product->id_category_default)),
                'title_fr' => $titleFr,
                'title_en' => $titleEn,
                'description_fr' => $this->joinTexts($texts[$fr] ?? null),
                'description_en' => $this->joinTexts($texts[$en] ?? null),
                'meta_description_fr' => Str::limit((string) ($texts[$fr]->meta_description ?? ''), 155, '') ?: null,
                'meta_description_en' => Str::limit((string) ($texts[$en]->meta_description ?? ''), 155, '') ?: null,
                'dimensions' => $dimensions ? "{$dimensions} {$dimensionUnit}" : null,
                'weight_grams' => $weightGrams,
                'price_cents' => (int) round(((float) ($shop->price ?? $product->price)) * $taxMultiplier * 100),
                'position' => (int) $id,
            ];

            $rows[] = [$id, $reference, $titleFr, $data['price_cents'] / 100, $status->value, $images->get($id, collect())->count()];

            if ($dryRun) {
                continue;
            }

            $piece = Piece::query()->firstOrNew(['legacy_id' => $id]);
            $piece->fill($data);
            $piece->reference = $piece->reference ?: $this->uniqueReference($reference, $piece->id);
            $piece->slug ??= $this->uniqueSlug(Piece::class, Str::slug($reference.' '.$titleEn), $piece->id);
            $piece->status = $status;
            $piece->save();

            if ($this->option('images') && $piece->photos()->doesntExist()) {
                $this->importPhotos($piece, $images->get($id, collect()), $imageTexts, $fr, $en);
            }
        }

        $this->table(['PS id', 'Référence', 'Titre', 'Prix €', 'Statut', 'Photos'], $rows);

        if ($withTaxRules > 0 && (float) $this->option('tax-rate') === 0.0) {
            $this->warn("{$withTaxRules} product(s) have a tax rule in PrestaShop. PrestaShop stores prices without tax: if customers paid VAT, re-run with --tax-rate=20.");
        }

        $this->info($dryRun ? 'Dry run: nothing was written.' : 'Import finished. Check the pieces in /admin before going live.');

        return self::SUCCESS;
    }

    private function ps(string $name): Builder
    {
        return $this->ps->table($this->p.$name);
    }

    private function importPhotos(Piece $piece, Collection $images, Collection $imageTexts, int $fr, int $en): void
    {
        $base = rtrim((string) $this->option('images'), '/');

        foreach ($images->values() as $position => $image) {
            // PrestaShop stores image 123 as img/p/1/2/3/123.jpg
            $folder = implode('/', str_split((string) $image->id_image));
            $source = "{$base}/{$folder}/{$image->id_image}.jpg";

            if (! is_file($source)) {
                $this->warn("Missing photo {$source}");

                continue;
            }

            $path = "pieces/ps-{$image->id_image}.jpg";
            Storage::disk('public')->put($path, file_get_contents($source));
            $legends = $imageTexts->get($image->id_image, collect())->keyBy('id_lang');

            $piece->photos()->create([
                'path' => $path,
                'position' => $position,
                'alt_fr' => $legends[$fr]->legend ?? null,
                'alt_en' => $legends[$en]->legend ?? null,
            ]);
        }
    }

    private function joinTexts(?object $texts): ?string
    {
        if (! $texts) {
            return null;
        }

        $parts = array_filter([
            $this->htmlToText($texts->description_short ?? null),
            $this->htmlToText($texts->description ?? null),
        ]);

        return $parts ? implode("\n\n", array_unique($parts)) : null;
    }

    /** PrestaShop stores HTML; the new shop uses Markdown-friendly plain text. */
    private function htmlToText(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $text = preg_replace(['#<br\s*/?>#i', '#</p>#i', '#<li[^>]*>#i'], ["\n", "\n\n", '- '], $html);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", trim($text));

        return $text !== '' ? $text : null;
    }

    private function uniqueSlug(string $model, string $slug, ?int $ignoreId): string
    {
        $slug = $slug ?: 'piece';
        $candidate = $slug;
        for ($i = 2; $model::query()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = "{$slug}-{$i}";
        }

        return $candidate;
    }

    private function uniqueReference(string $reference, ?int $ignoreId): string
    {
        $candidate = $reference;
        for ($i = 2; Piece::query()->where('reference', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $candidate = "{$reference}-{$i}";
        }

        return $candidate;
    }
}
