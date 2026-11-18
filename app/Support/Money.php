<?php

namespace App\Support;

use Illuminate\Support\Number;

class Money
{
    /** 36000 → "360 €" (fr) / "€360" (en); keeps cents only when needed. */
    public static function format(int $cents, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $amount = $cents / 100;
        $precision = $cents % 100 === 0 ? 0 : 2;

        return Number::currency($amount, in: strtoupper(config('shop.currency')), locale: $locale, precision: $precision);
    }

    /** 36000 → "360,00" — for CSV exports read by French spreadsheets. */
    public static function plain(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}
