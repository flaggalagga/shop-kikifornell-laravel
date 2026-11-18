<?php

namespace App\Models;

use App\Enums\PieceStatus;
use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A collection of pieces, e.g. "Les Petits Mondes".
 * (Named JewelryCollection to avoid confusion with Laravel's Collection class.)
 */
class JewelryCollection extends Model
{
    use HasFactory, HasLocalizedFields;

    protected $table = 'collections';

    protected $fillable = [
        'slug', 'name_fr', 'name_en', 'intro_fr', 'intro_en', 'cover_image',
        'position', 'is_published', 'legacy_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(Piece::class, 'collection_id');
    }

    public function publicPieces(): HasMany
    {
        return $this->pieces()
            ->whereIn('status', [PieceStatus::Available, PieceStatus::Reserved])
            ->orderBy('position')
            ->orderByDesc('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position')->orderBy('id');
    }

    public function coverUrl(): ?string
    {
        if ($this->cover_image) {
            return Storage::disk('public')->url($this->cover_image);
        }

        return $this->pieces()
            ->whereIn('status', PieceStatus::public())
            ->orderBy('position')
            ->first()?->mainPhoto()?->url(960);
    }
}
