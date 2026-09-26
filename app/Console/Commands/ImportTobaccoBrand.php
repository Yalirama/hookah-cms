<?php

namespace App\Console\Commands;

use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ImportTobaccoBrand extends Command
{
    protected $signature = 'tobacco:import {slug}';
    protected $description = 'Импорт табаков бренда с htreviews.org';

    public function handle()
    {
        $slug = $this->argument('slug');
        $url  = "https://htreviews.org/tobaccos/{$slug}";

        $this->info("Загружаю {$url}...");
        $html = Http::get($url)->body();
        $crawler = new Crawler($html);

        $strengthMap = [
            'Лёгкая'         => 'light',
            'Средне-лёгкая'  => 'light',
            'Средняя'        => 'medium',
            'Средне-крепкая' => 'strong',
            'Крепкая'        => 'strong',
            'Не указано'     => 'medium',
        ];

        // --- БРЕНД ---
        $brandName = trim($crawler->filter('h1')->first()->text());
        $objectId  = (int) $crawler->filter('.object_wrapper')->attr('data-id');

        $country = null;
        $crawler->filter('.object_info_item')->each(function (Crawler $node) use (&$country) {
            $spans = $node->filter('span');
            if ($spans->count() > 0 && trim($spans->first()->text()) === 'Страна') {
                $country = trim($node->filter('div')->first()->text());
            }
        });

        $description = null;
        $discr = $crawler->filter('.object_card_discr span');
        if ($discr->count() > 0) {
            $description = trim($discr->first()->text());
        }

        $brand = TobaccoBrand::updateOrCreate(
            ['name' => $brandName],
            ['country' => $country, 'description' => $description]
        );

        $this->info("Бренд: {$brand->name} ({$country}), object id: {$objectId}");

        // --- ЛИНЕЙКИ ---
        $lines = [];
        $crawler->filter('.brand_lines_item')->each(function (Crawler $node) use (&$lines, $strengthMap) {
            $href = $node->filter('.lines_item_name')->attr('href');
            $lineSlug = Str::afterLast($href, '/');

            $strengthRaw = trim($node->filter('.lines_item_strength span')->text());
            $strength = $strengthMap[$strengthRaw] ?? 'medium';

            $desc = trim($node->filter('.lines_item_description span')->text());

            $lines[$lineSlug] = [
                'strength'    => $strength,
                'description' => $desc,
            ];
        });

        $this->info('Найдено линеек: ' . count($lines));

        $imported = 0;

        $saveItem = function (string $name, ?string $altName, ?string $lineSlugFull) use ($brand, $lines, &$imported) {
            $name = trim($name);
            if ($name === '') return;

            $altName = $altName !== null ? trim($altName) : null;
            $altName = ($altName === '' || $altName === '&nbsp;') ? null : $altName;

            $lineSlug = $lineSlugFull ? Str::afterLast($lineSlugFull, '/') : null;
            $line = $lines[$lineSlug] ?? ['strength' => 'medium', 'description' => null];

            Tobacco::updateOrCreate(
                ['brand_id' => $brand->id, 'name' => $name],
                [
                    'description_short' => $altName,
                    'description_long'  => $line['description'],
                    'strength'          => $line['strength'],
                ]
            );

            $imported++;
        };

        // --- ВКУСЫ: первые 20 из HTML ---
        $crawler->filter('.tobacco_list_item')->each(function (Crawler $node) use ($saveItem) {
            $spans = $node->filter('.tobacco_list_item_slug span');
            if ($spans->count() < 2) return;

            $name    = $spans->eq(0)->text();
            $altName = $spans->eq(1)->text();

            $lineHref = $node->filter('.tobacco_list_item_line_slug')->count() > 0
                ? $node->filter('.tobacco_list_item_line_slug')->attr('href')
                : null;

            $saveItem($name, $altName, $lineHref);
        });

        // --- ВКУСЫ: остальные через API ---
        $totalCount = (int) $crawler->filter('.tobacco_list_items')->attr('data-count');
        $offset = 20;

        while ($offset < $totalCount) {
            $response = Http::post('https://htreviews.org/postData', [
                'action' => 'objectByBrand',
                'data' => [
                    'id'     => $objectId,
                    'limit'  => 20,
                    'offset' => $offset,
                    'sort'   => ['s' => 'rating', 'd' => 'desc'],
                ],
            ])->json();

            if (empty($response)) break;

            foreach ($response as $item) {
                $saveItem($item['name'] ?? '', $item['alt_name'] ?? null, $item['line_slug'] ?? null);
            }

            $offset += count($response);
            usleep(300000);
        }

        $this->info("Импортировано/обновлено вкусов: {$imported} из {$totalCount}");
    }
}