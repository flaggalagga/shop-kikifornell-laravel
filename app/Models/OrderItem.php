<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'piece_id', 'reference', 'title', 'price_cents'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }

    public function formattedPrice(?string $locale = null): string
    {
        return Money::format($this->price_cents, $locale);
    }
}
