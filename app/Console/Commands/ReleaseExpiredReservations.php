<?php

namespace App\Console\Commands;

use App\Enums\PieceStatus;
use App\Models\Piece;
use Illuminate\Console\Command;

/**
 * Housekeeping: puts back on sale pieces whose checkout reservation ran out
 * without a Stripe "expired" event (e.g. a webhook that never arrived).
 * Customers already see such pieces as available; this tidies the admin.
 */
class ReleaseExpiredReservations extends Command
{
    protected $signature = 'shop:release-expired-reservations';

    protected $description = 'Put pieces with an expired checkout reservation back on sale';

    public function handle(): int
    {
        $pieces = Piece::query()
            ->where('status', PieceStatus::Reserved)
            ->where('reserved_until', '<', now())
            ->get();

        $pieces->each->releaseReservation();

        $this->info("Released {$pieces->count()} piece(s).");

        return self::SUCCESS;
    }
}
