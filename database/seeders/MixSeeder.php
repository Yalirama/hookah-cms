<?php

namespace Database\Seeders;

use App\Models\Mix;
use App\Models\MixIngredient;
use App\Models\MixIngredientAlt;
use App\Models\Tobacco;
use App\Models\User;
use Illuminate\Database\Seeder;

class MixSeeder extends Seeder
{
    public function run(): void
    {
        $master = User::role('master')->first();

        $mixes = [
            [
                'name'              => 'Тропический закат',
                'description_short' => 'Сладкий тропический микс',
                'description_long'  => 'Идеальное сочетание манго и кокоса с лёгкой ноткой ванили',
                'strength'          => 'light',
                'ingredients' => [
                    ['tobacco' => 'Ambrosia',    'brand' => 'Fumari',   'grams' => 15],
                    ['tobacco' => 'Cosmo',       'brand' => 'Darkside', 'grams' => 10],
                ],
            ],
            [
                'name'              => 'Ледяной арбуз',
                'description_short' => 'Освежающий летний микс',
                'description_long'  => 'Арбуз с холодной мятой — идеально для жаркого вечера',
                'strength'          => 'medium',
                'ingredients' => [
                    ['tobacco' => 'Watermelon', 'brand' => 'Tangiers',  'grams' => 20],
                    ['tobacco' => 'Cane Mint',  'brand' => 'Tangiers',  'grams' => 5],
                ],
            ],
            [
                'name'              => 'Клубничный сезон',
                'description_short' => 'Ягодный микс с лёгкой кислинкой',
                'description_long'  => 'Клубника и малина с тонкой ноткой черники',
                'strength'          => 'medium',
                'ingredients' => [
                    ['tobacco' => 'Strawberry', 'brand' => 'Fumari',   'grams' => 15],
                    ['tobacco' => 'Love 66',    'brand' => 'Adalya',   'grams' => 10],
                    ['tobacco' => 'Blackberry', 'brand' => 'Tangiers', 'grams' => 5],
                ],
            ],
        ];

        foreach ($mixes as $mixData) {
            $mix = Mix::create([
                'name'              => $mixData['name'],
                'description_short' => $mixData['description_short'],
                'description_long'  => $mixData['description_long'],
                'strength'          => $mixData['strength'],
                'created_by'        => $master->id,
                'is_active'         => true,
            ]);

            foreach ($mixData['ingredients'] as $i => $ing) {
                $tobacco = Tobacco::whereHas('brand', fn($q) => $q->where('name', $ing['brand']))
                    ->where('name', $ing['tobacco'])
                    ->first();

                if ($tobacco) {
                    MixIngredient::create([
                        'mix_id'     => $mix->id,
                        'tobacco_id' => $tobacco->id,
                        'grams'      => $ing['grams'],
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }
}