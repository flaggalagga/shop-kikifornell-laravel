<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A message sent from the "Ask about this piece" form. */
class Inquiry extends Model
{
    protected $fillable = ['piece_id', 'name', 'email', 'message', 'locale', 'handled_at'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(Piece::class);
    }
}
