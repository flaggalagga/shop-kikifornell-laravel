<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Created by the Stripe webhook once a payment succeeds — never by hand.
 * The order number doubles as the invoice number (KF-2026-0001).
 */
class Order extends Model
{
    protected $fillable = [
        'number', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'status',
        'needs_attention', 'locale', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_name', 'shipping_line1', 'shipping_line2', 'shipping_postal_code',
        'shipping_city', 'shipping_state', 'shipping_country', 'shipping_zone', 'shipping_method',
        'subtotal_cents', 'shipping_cents', 'total_cents', 'currency', 'paid_at',
        'carrier', 'tracking_number', 'tracking_url', 'shipped_at', 'delivered_at', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'needs_attention' => 'boolean',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Next sequential number for the current year. Call inside a transaction. */
    public static function nextNumber(): string
    {
        $prefix = config('shop.order_number_prefix').'-'.now()->year.'-';

        $last = static::query()
            ->where('number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('number')
            ->value('number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function money(string $field): string
    {
        return Money::format($this->{$field}, $this->locale);
    }

    /** @return array<int, string> */
    public function shippingAddressLines(): array
    {
        return array_values(array_filter([
            $this->shipping_name,
            $this->shipping_line1,
            $this->shipping_line2,
            trim($this->shipping_postal_code.' '.$this->shipping_city),
            $this->shipping_state,
            $this->shipping_country ? (locale_get_display_region('-'.$this->shipping_country, $this->locale) ?: $this->shipping_country) : null,
        ]));
    }

    public function stripePaymentUrl(): ?string
    {
        return $this->stripe_payment_intent_id
            ? rtrim(config('shop.stripe.dashboard_url'), '/').'/payments/'.$this->stripe_payment_intent_id
            : null;
    }
}
