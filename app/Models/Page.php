<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Editable text pages: legal notice, terms of sale, shipping, about… */
class Page extends Model
{
    use HasLocalizedFields;

    protected $fillable = ['slug', 'title_fr', 'title_en', 'body_fr', 'body_en', 'show_in_footer', 'position'];

    protected function casts(): array
    {
        return ['show_in_footer' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Body rendered from Markdown. Raw HTML in the source is escaped. */
    public function html(?string $locale = null): string
    {
        return Str::markdown($this->localized('body', $locale) ?? '', [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }
}
