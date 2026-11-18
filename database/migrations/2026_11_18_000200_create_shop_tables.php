<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_fr');
            $table->string('name_en');
            $table->text('intro_fr')->nullable();
            $table->text('intro_en')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('legacy_id')->nullable()->unique(); // PrestaShop id_category
            $table->timestamps();
        });

        Schema::create('pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();          // e.g. M015
            $table->string('slug')->unique();
            $table->string('title_fr');
            $table->string('title_en');
            $table->text('description_fr')->nullable();     // Markdown
            $table->text('description_en')->nullable();
            $table->string('materials_fr')->nullable();
            $table->string('materials_en')->nullable();
            $table->string('dimensions')->nullable();       // free text, e.g. "4,5 × 2 cm"
            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('price_cents');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->string('meta_description_fr')->nullable();
            $table->string('meta_description_en')->nullable();
            // Reservation while a customer is on the Stripe checkout page.
            $table->timestamp('reserved_until')->nullable();
            $table->string('reservation_token')->nullable()->index();
            $table->string('reserved_by_session')->nullable()->index();
            $table->timestamp('sold_at')->nullable();
            $table->unsignedInteger('legacy_id')->nullable()->unique(); // PrestaShop id_product
            $table->timestamps();
        });

        Schema::create('piece_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->constrained()->cascadeOnDelete();
            $table->string('path');                          // original, on the "public" disk
            $table->string('alt_fr')->nullable();
            $table->string('alt_en')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('variant_format', 8)->nullable(); // webp or jpg, set once variants exist
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();              // also the invoice number
            $table->string('stripe_checkout_session_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('status')->default('paid')->index();
            $table->boolean('needs_attention')->default(false);
            $table->string('locale', 2)->default('fr');
            $table->string('customer_name')->nullable();
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('shipping_name')->nullable();
            $table->string('shipping_line1')->nullable();
            $table->string('shipping_line2')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country', 2)->nullable();
            $table->string('shipping_zone')->nullable();
            $table->string('shipping_method')->nullable();
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('shipping_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('currency', 3)->default('eur');
            $table->timestamp('paid_at')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('piece_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->string('title');
            $table->unsignedInteger('price_cents');
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_fr');
            $table->string('title_en');
            $table->longText('body_fr')->nullable();         // Markdown
            $table->longText('body_en')->nullable();
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('piece_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->string('locale', 2)->default('fr');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('piece_photos');
        Schema::dropIfExists('pieces');
        Schema::dropIfExists('collections');
    }
};
