<?php

namespace App\Models;

use App\Enums\PieceStatus;
use App\Models\Concerns\HasLocalizedFields;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A one-of-a-kind piece of jewellery. There is no stock: a piece is either
 * for sale, reserved during checkout, or sold.
 */
class Piece extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $fillable = [
        'collection_id', 'reference', 'slug',
        'title_fr', 'title_en', 'description_fr', 'description_en',
        'materials_fr', 'materials_en', 'dimensions', 'weight_grams',
        'price_cents', 'status', 'is_featured', 'position',
        'meta_description_fr', 'meta_description_en', 'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PieceStatus::class,
            'is_featured' => 'boolean',
            'price_cents' => 'integer',
            'reserved_until' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Delete photos one by one so their files are removed from disk too.
        static::deleting(fn (Piece $piece) => $piece->photos->each->delete());

        // Keep dates and reservation fields consistent when the status is
        // changed by hand in the admin (e.g. a piece sold at a market).
        static::saving(function (Piece $piece) {
            if ($piece->status === PieceStatus::Sold && ! $piece->sold_at) {
                $piece->sold_at = now();
            }
            if ($piece->status !== PieceStatus::Sold) {
                $piece->sold_at = null;
            }
            if ($piece->status !== PieceStatus::Reserved) {
                $piece->reserved_until = null;
                $piece->reservation_token = null;
                $piece->reserved_by_session = null;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(JewelryCollection::class, 'collection_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(PiecePhoto::class)->orderBy('position')->orderBy('id');
    }

    public function mainPhoto(): ?PiecePhoto
    {
        return $this->relationLoaded('photos') ? $this->photos->first() : $this->photos()->first();
    }

    /** Pieces that may appear on the public site (for sale, reserved or sold). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', PieceStatus::public());
    }

    /** Pieces currently offered for sale, including expired reservations. */
    public function scopeOnSale(Builder $query): Builder
    {
        return $query->whereIn('status', [PieceStatus::Available, PieceStatus::Reserved]);
    }

    public function reservationExpired(): bool
    {
        return $this->status === PieceStatus::Reserved
            && ($this->reserved_until === null || $this->reserved_until->isPast());
    }

    /** Can a customer start a checkout for this piece right now? */
    public function isPurchasable(): bool
    {
        return $this->status === PieceStatus::Available || $this->reservationExpired();
    }

    /** Status as customers should see it (an expired reservation is for sale again). */
    public function publicStatus(): PieceStatus
    {
        return $this->reservationExpired() ? PieceStatus::Available : $this->status;
    }

    public function formattedPrice(?string $locale = null): string
    {
        return Money::format($this->price_cents, $locale);
    }

    public function releaseReservation(): void
    {
        if ($this->status !== PieceStatus::Reserved) {
            return;
        }

        $this->forceFill([
            'status' => PieceStatus::Available,
            'reserved_until' => null,
            'reservation_token' => null,
            'reserved_by_session' => null,
        ])->save();
    }
}
