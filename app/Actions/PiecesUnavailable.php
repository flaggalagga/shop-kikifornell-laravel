<?php

namespace App\Actions;

use RuntimeException;

class PiecesUnavailable extends RuntimeException
{
    /** @param array<int, int> $pieceIds */
    public function __construct(public readonly array $pieceIds)
    {
        parent::__construct('Some pieces are no longer available: '.implode(', ', $pieceIds));
    }
}
