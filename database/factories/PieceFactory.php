<?php

namespace Database\Factories;

use App\Enums\PieceStatus;
use App\Models\Piece;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Piece> */
class PieceFactory extends Factory
{
    protected $model = Piece::class;

    public function definition(): array
    {
        $ref = 'T'.$this->faker->unique()->numberBetween(100, 999);
        $title = $this->faker->words(2, true);

        return [
            'reference' => $ref,
            'slug' => Str::slug($ref.' '.$title),
            'title_fr' => 'Collier '.$title,
            'title_en' => ucfirst($title).' necklace',
            'description_fr' => 'Émail grand feu.',
            'description_en' => 'Grand feu enamel.',
            'price_cents' => 36000,
            'status' => PieceStatus::Available,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PieceStatus::Draft]);
    }

    public function sold(): static
    {
        return $this->state(['status' => PieceStatus::Sold]);
    }
}
