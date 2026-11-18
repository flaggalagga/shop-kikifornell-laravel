<?php

namespace App\Support;

use App\Models\Piece;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Session\Store;

/**
 * Session cart. Pieces are unique, so the cart is just a list of piece ids
 * plus the shipping destination zone.
 */
class Cart
{
    private const KEY = 'cart.pieces';

    private const ZONE = 'cart.zone';

    private const LAST_SESSION = 'cart.checkout_session';

    public function __construct(private Store $session) {}

    /** @return array<int, int> */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', $this->session->get(self::KEY, []))));
    }

    public function add(Piece $piece): void
    {
        $this->session->put(self::KEY, array_values(array_unique([...$this->ids(), $piece->id])));
    }

    public function remove(int $pieceId): void
    {
        $this->session->put(self::KEY, array_values(array_diff($this->ids(), [$pieceId])));
    }

    public function has(Piece $piece): bool
    {
        return in_array($piece->id, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function clear(): void
    {
        $this->session->forget([self::KEY, self::LAST_SESSION]);
    }

    /** @return Collection<int, Piece> */
    public function pieces(): Collection
    {
        if ($this->ids() === []) {
            return new Collection;
        }

        return Piece::query()
            ->whereIn('id', $this->ids())
            ->visible()
            ->with('photos', 'collection')
            ->get();
    }

    public function zone(): ?string
    {
        $zone = $this->session->get(self::ZONE);

        return array_key_exists((string) $zone, config('shop.shipping')) ? $zone : null;
    }

    public function setZone(string $zone): void
    {
        if (array_key_exists($zone, config('shop.shipping'))) {
            $this->session->put(self::ZONE, $zone);
        }
    }

    public function rememberCheckoutSession(string $sessionId): void
    {
        $this->session->put(self::LAST_SESSION, $sessionId);
    }

    public function lastCheckoutSession(): ?string
    {
        return $this->session->get(self::LAST_SESSION);
    }

    public function forgetCheckoutSession(): void
    {
        $this->session->forget(self::LAST_SESSION);
    }
}
