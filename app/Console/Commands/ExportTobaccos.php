<?php

namespace App\Console\Commands;

use App\Models\TobaccoBrand;
use Illuminate\Console\Command;

class ExportTobaccos extends Command
{
    protected $signature = 'tobacco:export';
    protected $description = 'Экспорт всех табаков в JSON-файл';

    public function handle()
    {
        $brands = TobaccoBrand::withTrashed()
            ->with(['tobaccos' => function ($q) {
                $q->withTrashed()->with('flavorTags.category');
            }])
            ->get()
            ->map(function ($brand) {
                return [
                    'name'        => $brand->name,
                    'country'     => $brand->country,
                    'description' => $brand->description,
                    'tobaccos'    => $brand->tobaccos->map(function ($t) {
                        return [
                            'name'              => $t->name,
                            'alt_name'          => $t->alt_name,
                            'description_short' => $t->description_short,
                            'description_long'  => $t->description_long,
                            'strength'          => $t->strength,
                            'flavor_tags'       => $t->flavorTags->map(fn($tag) => [
                                'name'        => $tag->name,
                                'external_id' => $tag->external_id,
                                'color'       => $tag->color,
                                'category'    => $tag->category?->name,
                                'category_color' => $tag->category?->color,
                            ])->values(),
                        ];
                    })->values(),
                ];
            });

        $path = storage_path('app/tobaccos_export.json');
        file_put_contents($path, json_encode($brands, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->info("Экспортировано брендов: {$brands->count()}");
        $this->info("Файл: {$path}");
    }
}