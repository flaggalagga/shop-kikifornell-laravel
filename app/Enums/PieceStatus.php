<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle of a one-of-a-kind piece.
 *
 * draft → available → reserved (on Stripe checkout) → sold
 *                   ↖ reservation expires / is cancelled
 * archived = hidden from the shop entirely.
 */
enum PieceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Available => 'En vente',
            self::Reserved => 'Réservé (paiement en cours)',
            self::Sold => 'Vendu',
            self::Archived => 'Archivé (masqué)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Available => 'success',
            self::Reserved => 'warning',
            self::Sold => 'info',
            self::Archived => 'gray',
        };
    }

    /** Statuses visible on the public site. */
    public static function public(): array
    {
        return [self::Available, self::Reserved, self::Sold];
    }
}
