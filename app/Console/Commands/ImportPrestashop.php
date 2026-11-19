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
 * - Top-level categories (direct children of "Home") become collections,
 *   or only the ones given with --collections=5,6.
 * - Products become pieces, in the collection of their main category or of
 *   any other category they are filed in. Inactive → draft; out of stock → sold.
 * - The collection name and reference are removed from the start of product
 *   names ("LES PETITS MONDES M015 Lac Vert…" → "Lac Vert…"), unless --keep-titles.
 * - Photos are copied from PrestaShop's img/p folder (originals).
 * - The PrestaShop ids are kept in legacy_id so old URLs redirect (301).
 *
 * Safe to run several times: pieces and collections already imported
 * (matched on legacy_id) are left alone, so edits made in the admin are
 * kept; only new ones are added. --update overwrites them with the
 * PrestaShop data again. Photos are only imported for pieces that have none.
 *
 * Checked with a dry run against the real shop (PrestaShop on OVH, Nov 2026).
 * Always do a --dry-run first and check the table it prints.
 */
class ImportPrestashop extends Command
{
    protected $signature = 'shop:import-prestashop
        {--images= : Path to the PrestaShop "img/p" folder, to copy photos}
        {--prefix=ps_ : Table prefix of the PrestaShop database}
        {--shop=1 : PrestaShop shop id}
        {--collections= : Comma-separated PrestaShop category ids to import as collections (default: all top-level categories)}
        {--keep-titles : Keep product names as they are (by default the collection name and reference are removed from the start)}
        {--update : Also overwrite pieces and collections imported before (replaces edits made in the admin)}
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
        $topLevel = $categories->filter(fn ($c) => (int) $c->id_parent === $homeId)->keys()->map(fn ($id) => (int) $id);

        $chosen = filled($this->option('collections'))
            ? collect(explode(',', (string) $this->option('collections')))->map(fn ($id) => (int) trim($id))->filter()->values()
            : $topLevel->values();

        $this->line('Top-level categories in PrestaShop: '.$topLevel->map(fn ($id) => "#{$id} ".($categoryTexts->get($id)?->firstWhere('id_lang', $fr)?->name ?? '?'))->implode(', '));
        if (blank($this->option('collections'))) {
            $this->comment('All of them become collections. To keep only some, pass e.g. --collections='.$topLevel->take(2)->implode(','));
        }

        $collectionIds = [];   // PrestaShop category id => new collection id
        $collectionNames = []; // PrestaShop category id => [fr, en]
        foreach ($chosen as $categoryId) {
            $cat = $categories->get($categoryId);
            if (! $cat) {
                $this->warn("Category #{$categoryId} does not exist in PrestaShop.");

                continue;
            }

            $texts = $categoryTexts->get($cat->id_category, collect())->keyBy('id_lang');
            $nameFr = $texts[$fr]->name ?? "Collection {$cat->id_category}";
            $nameEn = $texts[$en]->name ?? $nameFr;
            $collectionNames[$categoryId] = [$nameFr, $nameEn];
            $this->line("Collection #{$cat->id_category}: {$nameFr}");

            $collection = JewelryCollection::query()->firstOrNew(['legacy_id' => $cat->id_category]);

            if ($dryRun) {
                continue;
            }

            if ($collection->exists && ! $this->option('update')) {
                $collectionIds[$categoryId] = $collection->id; // keep Kiki's edits

                continue;
            }

            $collection->fill([
                'name_fr' => $nameFr,
                'name_en' => $nameEn,
                'intro_fr' => $this->htmlToText($texts[$fr]->description ?? null),
                'intro_en' => $this->htmlToText($texts[$en]->description ?? null),
                'position' => (int) $cat->position,
                'is_published' => (bool) $cat->active,
            ]);
            $collection->slug ??= $this->uniqueSlug(JewelryCollection::class, Str::slug($texts[$fr]->link_rewrite ?? $nameFr), $collection->id);
            $collection->save();
            $collectionIds[$categoryId] = $collection->id;
        }

        // The chosen collection a category belongs to: itself or an ancestor.
        $collectionOf = function (int $categoryId) use ($categories, $chosen): ?int {
            for ($guard = 0; $categoryId && $guard < 20; $guard++) {
                if ($chosen->contains($categoryId)) {
                    return $categoryId;
                }
                $categoryId = (int) ($categories->get($categoryId)?->id_parent ?? 0);
            }

            return null;
        };

        // A product's collection: from its main category first, then from
        // any other category it is filed in (PrestaShop products can be in several).
        $productCategories = $this->ps('category_product')->get()->groupBy('id_product');
        $collectionForProduct = function (int $productId, int $defaultCategory) use ($collectionOf, $productCategories): ?int {
            $candidates = collect([$defaultCategory])
                ->merge($productCategories->get($productId, collect())->pluck('id_category')->map(fn ($id) => (int) $id));

            foreach ($candidates as $categoryId) {
                if ($found = $collectionOf($categoryId)) {
                    return $found;
                }
            }

            return null;
        };

        // Names to strip from the start of product titles, longest first.
        $titlePrefixes = collect($collectionNames)->flatten()->unique()->sortByDesc(fn ($n) => mb_strlen($n))->values()->all();

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
            $reference = trim((string) $product->reference) ?: "PS{$id}";
            $titleFr = trim($texts[$fr]->name ?? "Produit {$id}");
            $titleEn = trim($texts[$en]->name ?? $titleFr);
            if (! $this->option('keep-titles')) {
                $titleFr = $this->cleanTitle($titleFr, $reference, $titlePrefixes);
                $titleEn = $this->cleanTitle($titleEn, $reference, $titlePrefixes);
            }
            $psCollection = $collectionForProduct((int) $id, (int) ($shop->id_category_default ?? $product->id_category_default));
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
                'collection_id' => $psCollection ? ($collectionIds[$psCollection] ?? null) : null,
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

            $existing = Piece::query()->where('legacy_id', $id)->first();
            $action = ! $existing ? 'nouvelle' : ($this->option('update') ? 'mise à jour' : 'déjà là');

            $rows[] = [
                $action,
                $id,
                $reference,
                $psCollection ? $collectionNames[$psCollection][0] : '—',
                Str::limit($titleFr, 40),
                Str::limit($titleEn, 40),
                $data['price_cents'] / 100,
                $status->value,
                $images->get($id, collect())->count(),
            ];

            if ($dryRun) {
                continue;
            }

            $piece = $existing ?? new Piece(['legacy_id' => $id]);

            if (! $existing || $this->option('update')) {
                $piece->fill($data);
                $piece->reference = $piece->reference ?: $this->uniqueReference($reference, $piece->id);
                $piece->slug ??= $this->uniqueSlug(Piece::class, Str::slug($reference.' '.$titleEn), $piece->id);
                $piece->status = $status;
                $piece->save();
            }

            if ($this->option('images') && $piece->photos()->doesntExist()) {
                $this->importPhotos($piece, $images->get($id, collect()), $imageTexts, $fr, $en);
            }
        }

        $this->table(['Action', 'PS id', 'Réf.', 'Collection', 'Titre FR', 'Title EN', 'Prix €', 'Statut', 'Photos'], $rows);

        if ($withTaxRules > 0 && (float) $this->option('tax-rate') === 0.0) {
            $this->warn("{$withTaxRules} product(s) have a tax rule in PrestaShop. PrestaShop stores prices without tax: if customers paid VAT, re-run with --tax-rate=20.");
        }

        $skipped = collect($rows)->where(0, 'déjà là')->count();
        if ($skipped > 0) {
            $this->comment("{$skipped} piece(s) already imported were left as they are. Use --update to overwrite them with the PrestaShop data (this replaces edits made in the admin).");
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

    /**
     * "LES PETITS MONDES M015 Lac Vert Boucles d'oreilles" → "Lac Vert Boucles d'oreilles":
     * the new piece pages show the collection and reference separately.
     *
     * @param  array<int, string>  $prefixes
     */
    private function cleanTitle(string $title, string $reference, array $prefixes): string
    {
        $clean = trim($title);

        foreach ([...$prefixes, $reference] as $prefix) {
            if ($prefix !== '' && mb_stripos($clean, $prefix) === 0) {
                // Drop the prefix and any separator after it (multibyte-safe).
                $clean = preg_replace('/^[\s\-–—:,.|]+/u', '', mb_substr($clean, mb_strlen($prefix)));
            }
        }

        return $clean !== '' ? $clean : trim($title);
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
