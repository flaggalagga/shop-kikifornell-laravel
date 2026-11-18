<?php

namespace App\Actions;

use App\Enums\PieceStatus;
use App\Models\Piece;
use Illuminate\Support\Facades\DB;

/** Puts pieces reserved by a checkout session back on sale. */
class ReleaseCheckout
{
    public function handle(string $sessionId): int
    {
        return DB::transaction(function () use ($sessionId) {
            $pieces = Piece::query()
                ->where('status', PieceStatus::Reserved)
                ->where('reserved_by_session', $sessionId)
                ->lockForUpdate()
                ->get();

            $pieces->each->releaseReservation();

            return $pieces->count();
        });
    }

    /** Payment started with a slow method (e.g. bank debit): hold the pieces until it settles. */
    public function holdForPendingPayment(string $sessionId): void
    {
        Piece::query()
            ->where('status', PieceStatus::Reserved)
            ->where('reserved_by_session', $sessionId)
            ->update(['reserved_until' => now()->addDays(10)]);
    }
}
