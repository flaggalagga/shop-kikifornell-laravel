<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paid => 'Payée — à expédier',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
            self::Refunded => 'Remboursée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Paid => 'warning',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Refunded, self::Cancelled => 'gray',
        };
    }
}
