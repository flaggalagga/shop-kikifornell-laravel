<?php

namespace App\Models\Concerns;

/**
 * Bilingual fields are stored as plain columns: title_fr, title_en, …
 * Two fixed languages don't need a translation package, and plain columns
 * are easy to query, export and review.
 */
trait HasLocalizedFields
{
    /**
     * Value of "{$field}_{locale}" for the current (or given) locale,
     * falling back to the other language when empty.
     */
    public function localized(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute("{$field}_{$locale}");

        if (filled($value)) {
            return $value;
        }

        foreach (config('shop.locales') as $fallback) {
            $value = $this->getAttribute("{$field}_{$fallback}");
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
