<?php

namespace Database\Seeders;

use App\Enums\PieceStatus;
use App\Models\JewelryCollection;
use App\Models\Piece;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo catalogue for local development, based on the pieces on the current
 * shop. Photos are generated placeholders. Never run this in production:
 * use `php artisan shop:import-prestashop` for the real catalogue.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $mondes = JewelryCollection::query()->updateOrCreate(['slug' => 'les-petits-mondes'], [
            'name_fr' => 'Les Petits Mondes',
            'name_en' => 'Les Petits Mondes',
            'intro_fr' => 'De minuscules mondes en émail : chaque pièce est un paysage tenu dans la main.',
            'intro_en' => 'Tiny worlds in enamel: each piece is a landscape you can hold in your hand.',
            'position' => 1,
        ]);

        $pendeloques = JewelryCollection::query()->updateOrCreate(['slug' => 'les-pendeloques'], [
            'name_fr' => 'Les Pendeloques',
            'name_en' => 'Les Pendeloques',
            'intro_fr' => 'Des formes suspendues, organiques, qui bougent avec celle qui les porte.',
            'intro_en' => 'Suspended, organic forms that move with the wearer.',
            'position' => 2,
        ]);

        $pieces = [
            [$mondes, 'M015', "Boucles d'oreilles Lac Vert", 'Lac Vert earrings', 240000, true, ['#2f7d6d', '#7fb8a4', '#c9a54a'], PieceStatus::Available],
            [$mondes, 'M008', "Boucles d'oreilles Dalmatien", 'Dalmatian earrings', 54000, false, ['#f1f1ec', '#1d1d1d', '#c9a54a'], PieceStatus::Available],
            [$mondes, 'M007', 'Collier Dalmatien', 'Dalmatian necklace', 36000, false, ['#ecebe4', '#202020', '#b98d3a'], PieceStatus::Available],
            [$mondes, 'M004', 'Pendentif Dalmatien', 'Dalmatian pendant', 33000, false, ['#f4f2ea', '#262626', '#c9a54a'], PieceStatus::Sold],
            [$pendeloques, 'P005', 'Parure P005', 'P005 set', 120000, false, ['#1f4f8a', '#6aa3d8', '#d0a847'], PieceStatus::Available],
            [$pendeloques, 'P004', 'Collier Mimétisme sous-marin', 'Underwater mimicry necklace', 38000, false, ['#145f73', '#e2774b', '#c9a54a'], PieceStatus::Available],
            [$pendeloques, 'P003', 'Parure P003', 'P003 set', 96000, false, ['#7a2e3b', '#e7b7a0', '#c9a54a'], PieceStatus::Sold],
            [$pendeloques, 'P002', 'Collier Rayures', 'Stripes necklace', 36000, false, ['#22252b', '#efe9dc', '#c9a54a'], PieceStatus::Available],
        ];

        foreach ($pieces as $i => [$collection, $ref, $titleFr, $titleEn, $price, $featured, $colors, $status]) {
            $piece = Piece::query()->updateOrCreate(['reference' => $ref], [
                'collection_id' => $collection->id,
                'slug' => Str::slug($ref.'-'.$titleEn),
                'title_fr' => $titleFr,
                'title_en' => $titleEn,
                'description_fr' => "Émail grand feu appliqué couche après couche, puis cuit au four à plus de 800 °C.\n\n[Description de démonstration.]",
                'description_en' => "Grand feu enamel applied layer by layer, then fired at over 800 °C.\n\n[Demo description.]",
                'materials_fr' => 'Émail grand feu, argent 925, rehauts d’or',
                'materials_en' => 'Grand feu enamel, sterling silver, gold accents',
                'dimensions' => '[à mesurer]',
                'price_cents' => $price,
                'is_featured' => $featured,
                'position' => $i,
            ]);
            $piece->forceFill(['status' => $status, 'sold_at' => $status === PieceStatus::Sold ? now() : null])->save();

            if ($piece->photos()->doesntExist()) {
                foreach ([0, 1] as $variant) {
                    $path = 'pieces/demo-'.Str::lower($ref).'-'.$variant.'.jpg';
                    Storage::disk('public')->put($path, $this->placeholder($colors, $variant));
                    $piece->photos()->create(['path' => $path, 'position' => $variant]);
                }
            }
        }
    }

    /** An abstract enamel-like shape, so pages look realistic in development. */
    private function placeholder(array $colors, int $variant): string
    {
        [$w, $h] = [1200, 1500];
        $img = imagecreatetruecolor($w, $h);
        imageantialias($img, true);
        $rgb = fn (string $hex) => sscanf($hex, '#%02x%02x%02x');
        $alloc = fn (string $hex) => imagecolorallocate($img, ...$rgb($hex));

        imagefilledrectangle($img, 0, 0, $w, $h, $alloc($variant ? '#d6dfe3' : '#e4ebee'));

        $cx = $w / 2 + ($variant ? 120 : -40);
        $cy = $h / 2 + ($variant ? 60 : 0);
        $size = $variant ? 520 : 640;

        // Soft shadow, body, inner glaze, gold bail.
        imagefilledellipse($img, (int) $cx + 25, (int) $cy + 40, $size, (int) ($size * 1.18), $alloc('#c3ced3'));
        imagefilledellipse($img, (int) $cx, (int) $cy, $size, (int) ($size * 1.18), $alloc($colors[0]));
        mt_srand(crc32(implode('', $colors)) + $variant);
        for ($k = 0; $k < 9; $k++) {
            $r = mt_rand(40, 130);
            imagefilledellipse($img, (int) ($cx + mt_rand(-180, 180)), (int) ($cy + mt_rand(-220, 220)), $r, (int) ($r * 1.2), $alloc($colors[1]));
        }
        imagefilledellipse($img, (int) $cx, (int) ($cy - $size * 0.62), 70, 70, $alloc($colors[2]));
        imagefilledellipse($img, (int) $cx, (int) ($cy - $size * 0.62), 30, 30, $alloc($variant ? '#d6dfe3' : '#e4ebee'));

        ob_start();
        imagejpeg($img, null, 88);

        return (string) ob_get_clean();
    }
}
