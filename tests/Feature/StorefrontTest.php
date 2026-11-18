<?php

namespace Tests\Feature;

use App\Mail\NewInquiryNotification;
use App\Models\JewelryCollection;
use App\Models\Piece;
use Database\Seeders\PageSeeder;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PageSeeder::class);
    }

    public function test_root_redirects_to_the_browser_language(): void
    {
        $this->get('/', ['Accept-Language' => 'en-GB,en;q=0.9'])->assertRedirect('/en');
        $this->get('/', ['Accept-Language' => 'fr-FR'])->assertRedirect('/fr');
        $this->get('/', ['Accept-Language' => 'de-DE'])->assertRedirect('/fr');
    }

    public function test_main_pages_render_in_both_languages(): void
    {
        $collection = JewelryCollection::factory()->create(['name_fr' => 'Les Petits Mondes', 'name_en' => 'Les Petits Mondes']);
        $piece = Piece::factory()->for($collection, 'collection')->create(['title_fr' => 'Collier Lac Vert', 'title_en' => 'Lac Vert necklace']);
        Piece::factory()->sold()->create(['title_fr' => 'Pièce vendue']);

        foreach (['fr', 'en'] as $locale) {
            $this->get("/{$locale}")->assertOk()->assertSee($piece->reference);
            $this->get("/{$locale}/creations")->assertOk();
            $this->get("/{$locale}/archive")->assertOk();
            $this->get("/{$locale}/collections/{$collection->slug}")->assertOk()->assertSee('Les Petits Mondes');
            $this->get("/{$locale}/cart")->assertOk();
            $this->get("/{$locale}/info/cgv")->assertOk();
        }

        $this->get("/fr/creations/{$piece->slug}")
            ->assertOk()
            ->assertSee('Collier Lac Vert')
            ->assertSee('hreflang="en"', false)
            ->assertSee('"@type":"Product"', false);

        $this->get("/en/creations/{$piece->slug}")->assertOk()->assertSee('Lac Vert necklace');
        $this->get('/fr/archive')->assertSee('Pièce vendue');
    }

    public function test_draft_and_archived_pieces_are_not_public(): void
    {
        $draft = Piece::factory()->draft()->create();

        $this->get("/fr/creations/{$draft->slug}")->assertNotFound();
        $this->get('/fr/creations')->assertDontSee($draft->reference);
    }

    public function test_unknown_locale_and_pages_return_404(): void
    {
        $this->get('/de/creations')->assertNotFound();
        $this->get('/fr/does-not-exist')->assertNotFound();
    }

    public function test_sitemap_lists_both_languages(): void
    {
        $piece = Piece::factory()->create();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(url("/fr/creations/{$piece->slug}"), false)
            ->assertSee(url("/en/creations/{$piece->slug}"), false);
    }

    public function test_robots_txt_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.url('/sitemap.xml'))->assertSee('Disallow: /admin');
    }

    public function test_old_prestashop_urls_redirect_to_new_pages(): void
    {
        $piece = Piece::factory()->create(['legacy_id' => 12]);
        $collection = JewelryCollection::factory()->create(['legacy_id' => 7]);

        $this->get('/en/colliers/12-p002-necklace.html')->assertRedirect("/en/creations/{$piece->slug}")->assertStatus(301);
        $this->get('/fr/7-les-pendeloques')->assertRedirect("/fr/collections/{$collection->slug}")->assertStatus(301);
        $this->get('/index.php?id_product=12&controller=product')->assertRedirect("/fr/creations/{$piece->slug}");
        $this->get('/fr/colliers/999-unknown.html')->assertRedirect('/fr/creations');
    }

    public function test_inquiry_is_saved_and_emailed(): void
    {
        Mail::fake();
        $piece = Piece::factory()->create();

        $this->post("/fr/creations/{$piece->slug}/question", [
            'name' => 'Anna',
            'email' => 'anna@example.test',
            'message' => 'Est-ce que la chaîne est réglable ?',
        ])->assertRedirect();

        $this->assertDatabaseHas('inquiries', ['piece_id' => $piece->id, 'email' => 'anna@example.test', 'locale' => 'fr']);
        Mail::assertSent(NewInquiryNotification::class);
    }

    public function test_inquiry_honeypot_drops_spam_silently(): void
    {
        $piece = Piece::factory()->create();

        $this->post("/fr/creations/{$piece->slug}/question", [
            'name' => 'Bot', 'email' => 'bot@example.test', 'message' => 'Buy now', 'website' => 'http://spam.test',
        ])->assertRedirect();

        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_inquiry_validation_messages_are_in_french(): void
    {
        $piece = Piece::factory()->create();

        $this->from("/fr/creations/{$piece->slug}")
            ->post("/fr/creations/{$piece->slug}/question", ['name' => '', 'email' => 'nope', 'message' => ''])
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    }
}
