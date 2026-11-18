<?php

namespace App\Observers;

use App\Models\PiecePhoto;
use App\Services\ImageVariants;

class PiecePhotoObserver
{
    public function __construct(private ImageVariants $variants) {}

    public function saved(PiecePhoto $photo): void
    {
        if (! $photo->wasChanged('path') && ! $photo->wasRecentlyCreated) {
            return;
        }

        if ($photo->wasChanged('path') && ($old = $photo->getOriginal('path'))) {
            $this->variants->delete($old, $photo->variant_format);
        }

        $result = $this->variants->generate($photo->path);

        $photo->forceFill([
            'width' => $result['width'] ?? null,
            'height' => $result['height'] ?? null,
            'variant_format' => $result['format'] ?? null,
        ])->saveQuietly();
    }

    public function deleted(PiecePhoto $photo): void
    {
        $this->variants->delete($photo->path, $photo->variant_format);
    }
}
