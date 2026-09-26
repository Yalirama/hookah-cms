<?php

namespace App\Console\Commands;

use App\Models\FlavorCategory;
use App\Models\FlavorTag;
use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use Illuminate\Console\Command;

class ImportTobaccosJson extends Command
{
    protected $signature = 'tobacco:import-json {file}';
    protected $description = 'Импорт табаков из JSON-файла (только новые)';

    public function handle()
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("Файл не найден: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);

        if (!$data) {
            $this->error("Не удалось прочитать JSON");
            return;
        }

        $brandsAdded   = 0;
        $tobaccosAdded = 0;

        foreach ($data as $brandData) {
            $brand = TobaccoBrand::firstOrCreate(
                ['name' => $brandData['name']],
                [
                    'country'     => $brandData['country'] ?? null,
                    'description' => $brandData['description'] ?? null,
                ]
            );

            if ($brand->wasRecentlyCreated) $brandsAdded++;

            foreach ($brandData['tobaccos'] ?? [] as $t) {
                $tobacco = Tobacco::where('brand_id', $brand->id)
                    ->where('name', $t['name'])
                    ->first();

                if (!$tobacco) {
                    $tobacco = Tobacco::create([
                        'brand_id'          => $brand->id,
                        'name'              => $t['name'],
                        'alt_name'          => $t['alt_name'] ?? null,
                        'description_short' => $t['description_short'] ?? null,
                        'description_long'  => $t['description_long'] ?? null,
                        'strength'          => $t['strength'] ?? 'medium',
                    ]);
                    $tobaccosAdded++;
                }

                // Теги вкуса
                foreach ($t['flavor_tags'] ?? [] as $tagData) {
                    $category = null;
                    if (!empty($tagData['category'])) {
                        $category = FlavorCategory::firstOrCreate(
                            ['name' => $tagData['category']],
                            ['color' => $tagData['category_color'] ?? null]
                        );
                    }

                    $tag = null;
                    if (!empty($tagData['external_id'])) {
                        $tag = FlavorTag::where('external_id', $tagData['external_id'])->first();
                    }
                    if (!$tag) {
                        $tag = FlavorTag::where('name', $tagData['name'])->first();
                    }
                    if (!$tag) {
                        $tag = FlavorTag::create([
                            'name'               => $tagData['name'],
                            'external_id'        => $tagData['external_id'] ?? null,
                            'color'              => $tagData['color'] ?? null,
                            'flavor_category_id' => $category?->id,
                        ]);
                    }

                    $tobacco->flavorTags()->syncWithoutDetaching([$tag->id]);
                }
            }
        }

        $this->info("Добавлено брендов: {$brandsAdded}");
        $this->info("Добавлено табаков: {$tobaccosAdded}");
    }
}