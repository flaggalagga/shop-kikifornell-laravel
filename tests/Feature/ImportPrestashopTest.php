<?php

namespace Tests\Feature;

use App\Enums\PieceStatus;
use App\Models\JewelryCollection;
use App\Models\Piece;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Runs the import against a miniature PrestaShop schema in SQLite. */
class ImportPrestashopTest extends TestCase
{
    private string $imagesDir;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.prestashop' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('prestashop');
        $s = Schema::connection('prestashop');

        $s->create('ps_lang', fn (Blueprint $t) => [$t->integer('id_lang'), $t->string('iso_code')]);
        $s->create('ps_configuration', fn (Blueprint $t) => [$t->string('name'), $t->string('value')->nullable()]);
        $s->create('ps_category', fn (Blueprint $t) => [$t->integer('id_category'), $t->integer('id_parent'), $t->boolean('active'), $t->integer('position')]);
        $s->create('ps_category_lang', fn (Blueprint $t) => [$t->integer('id_category'), $t->integer('id_shop'), $t->integer('id_lang'), $t->string('name'), $t->text('description')->nullable(), $t->string('link_rewrite')]);
        $s->create('ps_product', fn (Blueprint $t) => [$t->integer('id_product'), $t->string('reference')->nullable(), $t->decimal('price'), $t->boolean('active'), $t->integer('id_category_default'), $t->decimal('weight')->default(0), $t->decimal('width')->default(0), $t->decimal('height')->default(0), $t->decimal('depth')->default(0)]);
        $s->create('ps_product_shop', fn (Blueprint $t) => [$t->integer('id_product'), $t->integer('id_shop'), $t->decimal('price'), $t->boolean('active'), $t->integer('id_category_default'), $t->integer('id_tax_rules_group')->default(0)]);
        $s->create('ps_product_lang', fn (Blueprint $t) => [$t->integer('id_product'), $t->integer('id_shop'), $t->integer('id_lang'), $t->string('name'), $t->text('description')->nullable(), $t->text('description_short')->nullable(), $t->string('link_rewrite'), $t->string('meta_description')->nullable()]);
        $s->create('ps_stock_available', fn (Blueprint $t) => [$t->integer('id_product'), $t->integer('id_product_attribute'), $t->integer('id_shop'), $t->integer('quantity')]);
        $s->create('ps_category_product', fn (Blueprint $t) => [$t->integer('id_category'), $t->integer('id_product'), $t->integer('position')->default(0)]);
        $s->create('ps_image', fn (Blueprint $t) => [$t->integer('id_image'), $t->integer('id_product'), $t->integer('position'), $t->boolean('cover')->nullable()]);
        $s->create('ps_image_lang', fn (Blueprint $t) => [$t->integer('id_image'), $t->integer('id_lang'), $t->string('legend')->nullable()]);

        $db = DB::connection('prestashop');
        $db->table('ps_lang')->insert([['id_lang' => 1, 'iso_code' => 'en'], ['id_lang' => 2, 'iso_code' => 'fr']]);
        $db->table('ps_configuration')->insert([['name' => 'PS_HOME_CATEGORY', 'value' => '2'], ['name' => 'PS_WEIGHT_UNIT', 'value' => 'kg']]);
        // Home (2) > Les Pendeloques (7) > Colliers (12)
        // Home (2) > Necklaces (10)   ← a "type" category, like on the real shop
        $db->table('ps_category')->insert([
            ['id_category' => 2, 'id_parent' => 1, 'active' => 1, 'position' => 0],
            ['id_category' => 7, 'id_parent' => 2, 'active' => 1, 'position' => 1],
            ['id_category' => 10, 'id_parent' => 2, 'active' => 1, 'position' => 2],
            ['id_category' => 12, 'id_parent' => 7, 'active' => 1, 'position' => 0],
        ]);
        $db->table('ps_category_lang')->insert([
            ['id_category' => 7, 'id_shop' => 1, 'id_lang' => 2, 'name' => 'Les Pendeloques', 'description' => '<p>Formes <b>suspendues</b>.</p>', 'link_rewrite' => 'les-pendeloques'],
            ['id_category' => 7, 'id_shop' => 1, 'id_lang' => 1, 'name' => 'Les Pendeloques', 'description' => '<p>Suspended forms.</p>', 'link_rewrite' => 'les-pendeloques'],
            ['id_category' => 10, 'id_shop' => 1, 'id_lang' => 2, 'name' => 'Necklaces', 'description' => null, 'link_rewrite' => 'necklaces'],
            ['id_category' => 10, 'id_shop' => 1, 'id_lang' => 1, 'name' => 'Necklaces', 'description' => null, 'link_rewrite' => 'necklaces'],
        ]);
        $db->table('ps_product')->insert([
            ['id_product' => 21, 'reference' => 'P002', 'price' => 360, 'active' => 1, 'id_category_default' => 10, 'weight' => 0.012, 'width' => 3.5, 'height' => 5],
            ['id_product' => 22, 'reference' => 'P001', 'price' => 300, 'active' => 1, 'id_category_default' => 12, 'weight' => 0, 'width' => 0, 'height' => 0],
        ]);
        $db->table('ps_product_shop')->insert([
            ['id_product' => 21, 'id_shop' => 1, 'price' => 360, 'active' => 1, 'id_category_default' => 10],
            ['id_product' => 22, 'id_shop' => 1, 'price' => 300, 'active' => 1, 'id_category_default' => 12],
        ]);
        $db->table('ps_product_lang')->insert([
            ['id_product' => 21, 'id_shop' => 1, 'id_lang' => 2, 'name' => 'LES PENDELOQUES P002 Collier Rayures', 'description_short' => '<p>Émail &amp; argent.</p>', 'description' => '<p>Ligne 1<br>Ligne 2</p>', 'link_rewrite' => 'collier-rayures', 'meta_description' => null],
            ['id_product' => 21, 'id_shop' => 1, 'id_lang' => 1, 'name' => 'Les Pendeloques P002 – “Stripes” necklace', 'description_short' => '<p>Enamel &amp; silver.</p>', 'description' => null, 'link_rewrite' => 'stripes-necklace', 'meta_description' => null],
            ['id_product' => 22, 'id_shop' => 1, 'id_lang' => 2, 'name' => 'Collier vendu', 'description_short' => null, 'description' => null, 'link_rewrite' => 'collier-vendu', 'meta_description' => null],
        ]);
        $db->table('ps_stock_available')->insert([
            ['id_product' => 21, 'id_product_attribute' => 0, 'id_shop' => 1, 'quantity' => 1],
            ['id_product' => 22, 'id_product_attribute' => 0, 'id_shop' => 1, 'quantity' => 0],
        ]);
        $db->table('ps_category_product')->insert([
            ['id_category' => 10, 'id_product' => 21],
            ['id_category' => 7, 'id_product' => 21],
            ['id_category' => 12, 'id_product' => 22],
        ]);
        $db->table('ps_image')->insert([['id_image' => 123, 'id_product' => 21, 'position' => 1, 'cover' => 1]]);
        $db->table('ps_image_lang')->insert([['id_image' => 123, 'id_lang' => 2, 'legend' => 'Collier porté']]);

        // PrestaShop stores image 123 as img/p/1/2/3/123.jpg
        $this->imagesDir = sys_get_temp_dir().'/ps-img-'.uniqid();
        mkdir($this->imagesDir.'/1/2/3', 0777, true);
        $img = imagecreatetruecolor(800, 1000);
        imagejpeg($img, $this->imagesDir.'/1/2/3/123.jpg');
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('shop:import-prestashop', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('pieces', 0);
        $this->assertDatabaseCount('collections', 0);
    }

    public function test_imports_collections_pieces_and_photos(): void
    {
        $this->artisan('shop:import-prestashop', ['--images' => $this->imagesDir, '--collections' => '7'])->assertSuccessful();

        $collection = JewelryCollection::query()->sole(); // "Necklaces" is not a collection
        $this->assertSame(7, $collection->legacy_id);
        $this->assertSame('Formes suspendues.', $collection->intro_fr);

        $piece = Piece::query()->where('legacy_id', 21)->sole();
        $this->assertSame('P002', $piece->reference);
        // Collection name and reference stripped; multibyte quotes intact.
        $this->assertSame('Collier Rayures', $piece->title_fr);
        $this->assertSame('“Stripes” necklace', $piece->title_en);
        $this->assertSame(36000, $piece->price_cents);
        $this->assertSame(PieceStatus::Available, $piece->status);
        $this->assertSame($collection->id, $piece->collection_id); // main category is "Necklaces", also filed in Les Pendeloques
        $this->assertSame(12, $piece->weight_grams);
        $this->assertSame('3,5 × 5 cm', $piece->dimensions);
        $this->assertSame("Émail & argent.\n\nLigne 1\nLigne 2", $piece->description_fr);
        $this->assertSame('Collier porté', $piece->photos()->sole()->alt_fr);

        $sold = Piece::query()->where('legacy_id', 22)->sole();
        $this->assertSame(PieceStatus::Sold, $sold->status);
        $this->assertSame('Collier vendu', $sold->title_en); // falls back to French

        // Old URL now redirects to the imported piece.
        $this->get('/fr/colliers/21-collier-rayures.html')->assertRedirect("/fr/creations/{$piece->slug}");

        // Running it again does not duplicate anything.
        $this->artisan('shop:import-prestashop', ['--images' => $this->imagesDir, '--collections' => '7'])->assertSuccessful();
        $this->assertDatabaseCount('pieces', 2);
        $this->assertSame(1, $piece->photos()->count());
    }

    public function test_without_a_choice_every_top_level_category_becomes_a_collection(): void
    {
        $this->artisan('shop:import-prestashop', ['--keep-titles' => true])->assertSuccessful();

        $this->assertEqualsCanonicalizing([7, 10], JewelryCollection::query()->pluck('legacy_id')->all());
        $this->assertSame('LES PENDELOQUES P002 Collier Rayures', Piece::query()->where('legacy_id', 21)->value('title_fr'));
    }

    public function test_running_again_keeps_edits_made_in_the_admin(): void
    {
        $this->artisan('shop:import-prestashop', ['--collections' => '7'])->assertSuccessful();

        $piece = Piece::query()->where('legacy_id', 21)->sole();
        $piece->update(['title_fr' => 'Collier Rayures (retouché par Kiki)', 'price_cents' => 39000]);
        $piece->update(['status' => PieceStatus::Sold]);
        JewelryCollection::query()->sole()->update(['intro_fr' => 'Texte réécrit.']);

        // A plain re-run leaves imported pieces and collections alone.
        $this->artisan('shop:import-prestashop', ['--collections' => '7'])
            ->expectsOutputToContain('already imported were left as they are')
            ->assertSuccessful();

        $piece->refresh();
        $this->assertSame('Collier Rayures (retouché par Kiki)', $piece->title_fr);
        $this->assertSame(39000, $piece->price_cents);
        $this->assertSame(PieceStatus::Sold, $piece->status);
        $this->assertSame('Texte réécrit.', JewelryCollection::query()->sole()->intro_fr);
        $this->assertDatabaseCount('pieces', 2);

        // --update brings back the PrestaShop data on purpose.
        $this->artisan('shop:import-prestashop', ['--collections' => '7', '--update' => true])->assertSuccessful();

        $piece->refresh();
        $this->assertSame('Collier Rayures', $piece->title_fr);
        $this->assertSame(36000, $piece->price_cents);
        $this->assertSame(PieceStatus::Available, $piece->status);
    }
}
