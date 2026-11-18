<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use App\Observers\PiecePhotoObserver;
use App\Services\ImageVariants;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(PiecePhotoObserver::class)]
class PiecePhoto extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['piece_id', 'path', 'alt_fr', 'alt_en', 'position'];

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }

    /** URL of the resized copy closest to $size (falls back to the original). */
    public function url(int $size = 960): string
    {
        $disk = Storage::disk('public');

        if ($this->variant_format) {
            $sizes = config('shop.image_sizes');
            $chosen = collect($sizes)->first(fn ($s) => $s >= $size) ?? max($sizes);

            return $disk->url(ImageVariants::variantPath($this->path, $chosen, $this->variant_format));
        }

        return $disk->url($this->path);
    }

    /** "url 480w, url 960w, …" for <img srcset>. */
    public function srcset(): string
    {
        if (! $this->variant_format) {
            return '';
        }

        $disk = Storage::disk('public');

        return collect(config('shop.image_sizes'))
            ->map(fn ($s) => $disk->url(ImageVariants::variantPath($this->path, $s, $this->variant_format))." {$s}w")
            ->implode(', ');
    }

    public function altText(?string $locale = null): string
    {
        return $this->localized('alt', $locale) ?? $this->piece?->localized('title', $locale) ?? '';
    }
}
