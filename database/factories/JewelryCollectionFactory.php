<?php

namespace Database\Factories;

use App\Models\JewelryCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JewelryCollection> */
class JewelryCollectionFactory extends Factory
{
    protected $model = JewelryCollection::class;

    public function definition(): array
    {
        $name = 'Les '.ucfirst($this->faker->unique()->word());

        return [
            'slug' => Str::slug($name),
            'name_fr' => $name,
            'name_en' => $name,
            'intro_fr' => 'Une collection.',
            'intro_en' => 'A collection.',
            'is_published' => true,
        ];
    }
}
