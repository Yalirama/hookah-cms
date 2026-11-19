<?php

namespace Database\Seeders;

use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use App\Models\VenueTobacco;
use Illuminate\Database\Seeder;

class TestBrandsSeeder extends Seeder
{
    private const BRANDS = [
        ['Kraken', 'Россия'], ['Satyr', 'Россия'], ['Bonche', 'Россия'], ["Trofimoff's", 'Россия'],
        ['World Tobacco Original', 'Россия'], ['AHF Tobacco', 'Россия'], ['Rustpunk', 'Россия'],
        ['Morpheus', 'Россия'], ['Snobless', 'Россия'], ['Hypreme', 'Россия'], ['Palitra', 'Россия'],
        ['Total Flame', 'Россия'], ['Dead Horse', 'Россия'], ['CBX', 'Россия'], ['Bezdna', 'Россия'],
        ['Dokhaman', 'Россия'], ['Mad Leaf', 'Россия'], ['Antagonist', 'Россия'], ['Reverse', 'Россия'],
        ['Marinage', 'Россия'], ['Noar', 'Россия'], ['Heven', 'Россия'], ['Zapp', 'Россия'],
        ['Carlivan', 'Россия'], ['Relic F', 'Россия'], ['GDS', 'Россия'], ['ON CHILL', 'Россия'],
        ['Layalina', 'Россия'], ['Fairytale Mist', 'Россия'], ['Kvist', 'Россия'], ['Element', 'Россия'],
        ['Duft', 'Россия'], ['Khan Burley', 'Россия'], ['Mr. Brew', 'Россия'], ['Muassel', 'Россия'],
        ['База', 'Россия'], ['Догма', 'Россия'], ['Северный', 'Россия'], ['Адам и Ева', 'Россия'],
        ['Табак Шпаковского', 'Россия'],
        ['Chabacco', 'Россия'], ['Jent', 'Россия'], ['JOY.', 'Россия'], ['Spectrum', 'Россия'],
        ['Хулиган', 'Россия'], ["N\u{0430}\u{0448}", 'Россия'], ['Adalya', 'Турция'], ['Al Fakher', 'ОАЭ'],
        ['Black Burn', 'Россия'], ['Brusko', 'Россия'], ['Fumari', 'США'], ['Hit', 'Россия'],
        ['Sapphire Crown', 'Россия'], ['Starline', 'Россия'],
    ];

    private const FLAVORS = [
        ['Тест Яблоко', 'light'], ['Тест Персик', 'medium'], ['Тест Мята', 'strong'],
    ];

    public function run(): void
    {
        $existing = TobaccoBrand::withTrashed()->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();
        $added = 0;

        foreach (self::BRANDS as [$name, $country]) {
            if (in_array(mb_strtolower($name), $existing, true)) continue;

            $brand = TobaccoBrand::create(['name' => $name, 'country' => $country, 'description' => 'Тестовый бренд']);

            foreach (self::FLAVORS as [$flavor, $strength]) {
                $t = Tobacco::create([
                    'brand_id' => $brand->id,
                    'name' => $flavor,
                    'description_short' => 'Тестовая позиция',
                    'strength' => $strength,
                    'is_manual' => true,
                ]);
                VenueTobacco::updateOrCreate(['tobacco_id' => $t->id], ['is_active' => true]);
            }
            $added++;
        }

        $this->command?->info("Добавлено брендов: $added");
    }
}
