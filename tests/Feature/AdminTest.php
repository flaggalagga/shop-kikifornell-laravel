<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Pieces\Pages\CreatePiece;
use App\Mail\OrderShipped;
use App\Models\Inquiry;
use App\Models\JewelryCollection;
use App\Models\Order;
use App\Models\Piece;
use App\Models\User;
use App\Services\ImageVariants;
use Database\Seeders\PageSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PageSeeder::class);
        $this->admin = User::factory()->create();
    }

    private function order(): Order
    {
        $piece = Piece::factory()->sold()->create();
        $order = Order::create([
            'number' => 'KF-2026-0001',
            'stripe_checkout_session_id' => 'cs_1',
            'stripe_payment_intent_id' => 'pi_1',
            'status' => OrderStatus::Paid,
            'locale' => 'en',
            'customer_name' => 'Ada Lovelace',
            'customer_email' => 'ada@example.test',
            'shipping_name' => 'Ada Lovelace',
            'shipping_line1' => '1 Main St',
            'shipping_city' => 'London',
            'shipping_postal_code' => 'N1',
            'shipping_country' => 'GB',
            'shipping_method' => 'Envoi international',
            'subtotal_cents' => 36000,
            'shipping_cents' => 3500,
            'total_cents' => 39500,
            'paid_at' => now(),
        ]);
        $order->items()->create(['piece_id' => $piece->id, 'reference' => $piece->reference, 'title' => $piece->title_en, 'price_cents' => 36000]);

        return $order;
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/exports/livre-des-recettes')->assertRedirect('/admin/login');
    }

    public function test_every_admin_page_loads(): void
    {
        $piece = Piece::factory()->create();
        $collection = JewelryCollection::factory()->create();
        $order = $this->order();
        $inquiry = Inquiry::create(['name' => 'A', 'email' => 'a@example.test', 'message' => 'Hello there']);

        $this->actingAs($this->admin);

        foreach ([
            '/admin',
            '/admin/pieces',
            '/admin/pieces/create',
            "/admin/pieces/{$piece->id}/edit",
            '/admin/collections',
            "/admin/collections/{$collection->id}/edit",
            '/admin/pages',
            '/admin/orders',
            "/admin/orders/{$order->id}",
            '/admin/inquiries',
            "/admin/inquiries/{$inquiry->id}",
            '/admin/profile',
        ] as $url) {
            $this->assertSame(200, $this->get($url)->status(), "GET {$url}");
        }
    }

    public function test_creating_a_piece_with_a_photo_generates_resized_copies(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreatePiece::class)
            ->fillForm([
                'reference' => 'M020',
                'title_fr' => 'Collier Glacier',
                'title_en' => 'Glacier necklace',
                'price_cents' => '450.50',
                'status' => 'available',
                'slug' => 'm020-glacier-necklace',
                'photos' => [['path' => [UploadedFile::fake()->image('photo.jpg', 2000, 2500)], 'alt_fr' => 'Collier porté', 'alt_en' => 'Necklace worn']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $piece = Piece::query()->where('reference', 'M020')->sole();
        $this->assertSame(45050, $piece->price_cents);

        $photo = $piece->photos()->sole();
        $this->assertNotNull($photo->variant_format);
        // The 2000×2500 upload is capped to 2400 px on its longest side.
        $this->assertSame([1920, 2400], [$photo->width, $photo->height]);
        foreach (config('shop.image_sizes') as $size) {
            Storage::disk('public')->assertExists(ImageVariants::variantPath($photo->path, $size, $photo->variant_format));
        }

        $this->get('/fr/creations/m020-glacier-necklace')->assertOk()->assertSee('Collier porté');
    }

    public function test_marking_an_order_shipped_emails_the_customer(): void
    {
        Mail::fake();
        $order = $this->order();
        $this->actingAs($this->admin);

        Livewire::test(ViewOrder::class, ['record' => $order->id])
            ->callAction('mark_shipped', data: ['carrier' => 'Colissimo', 'tracking_number' => '6A123', 'notify' => true])
            ->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Shipped, $order->status);
        $this->assertSame('https://www.laposte.fr/outils/suivre-vos-envois?code=6A123', $order->tracking_url);
        Mail::assertSent(OrderShipped::class, fn ($mail) => $mail->hasTo('ada@example.test') && $mail->locale === 'en');
    }

    public function test_invoice_and_packing_slip(): void
    {
        $order = $this->order();
        $this->actingAs($this->admin);

        $this->get("/admin/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertSee('Facture')
            ->assertSee('KF-2026-0001')
            ->assertSee("395\u{a0}€")
            ->assertSee('TVA non applicable, art. 293 B du CGI');

        $this->get("/admin/orders/{$order->id}/packing-slip")
            ->assertOk()
            ->assertSee('Bon de livraison')
            ->assertDontSee("395\u{a0}€");
    }

    public function test_revenue_ledger_export(): void
    {
        $order = $this->order();
        $this->actingAs($this->admin);

        $csv = $this->get('/admin/exports/livre-des-recettes?year='.now()->year)
            ->assertOk()
            ->assertDownload('livre-des-recettes-'.now()->year.'.csv')
            ->streamedContent();

        $this->assertStringContainsString('KF-2026-0001;"Ada Lovelace"', $csv);
        $this->assertStringContainsString('395,00', $csv);
    }
}
