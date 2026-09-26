<?php

namespace App\Console\Commands;

use App\Models\FlavorCategory;
use App\Models\FlavorTag;
use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class EnrichTobaccoBrand extends Command
{
    protected $signature = 'tobacco:enrich {slug}';
    protected $description = 'Догрузить теги, категории и описание вкусов бренда с htreviews.org';

    public function handle()
    {
        $slug = $this->argument('slug');
        $url  = "https://htreviews.org/tobaccos/{$slug}";

        $this->info("Загружаю {$url}...");
        $html = Http::get($url)->body();
        $crawler = new Crawler($html);

        $brandName = trim($crawler->filter('h1')->first()->text());
        $brand = TobaccoBrand::where('name', $brandName)->first();

        if (!$brand) {
            $this->error("Бренд {$brandName} не найден. Сначала: tobacco:import {$slug}");
            return;
        }

        $objectId   = (int) $crawler->filter('.object_wrapper')->attr('data-id');
        $totalCount = (int) $crawler->filter('.tobacco_list_items')->attr('data-count');

        $this->info("Бренд: {$brandName}, всего вкусов: {$totalCount}");

        $processed = 0;

        $process = function (string $name, ?string $detailSlug) use ($brand, &$processed) {
            if (!$detailSlug) return;

            $tobacco = Tobacco::where('brand_id', $brand->id)->where('name', trim($name))->first();
            if (!$tobacco) return;

            $detailUrl = "https://htreviews.org/tobaccos/{$detailSlug}";
            $detailHtml = Http::get($detailUrl)->body();
            $detailCrawler = new Crawler($detailHtml);

            // Короткое описание из JSON-LD (Product с category вида "brand/line/flavor")
            $description = null;
            $detailCrawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$description) {
                $data = json_decode($node->text(), true);
                if (($data['@type'] ?? null) === 'Product'
                    && isset($data['category'])
                    && substr_count($data['category'], '/') === 2) {
                    $description = $data['description'] ?? null;
                }
            });

            if (!empty($description)) {
                $tobacco->description_short = $description;
                $tobacco->save();
            }

            // Категории (Освежающий, Десертный) с привязкой к цвету
            $categories = [];
            $detailCrawler->filter('.tags_group .group_title')->each(function (Crawler $node) use (&$categories) {
                $style = $node->attr('style') ?? '';
                if (preg_match('/--color:\s*(#[0-9a-fA-F]{3,6})/', $style, $m)) {
                    $categories[$m[1]] = trim($node->filter('span')->text());
                }
            });

            // Теги вкуса (Мята, Шоколад)
            $detailCrawler->filter('.object_card_tag')->each(function (Crawler $node) use ($tobacco, $categories) {
                $href = $node->attr('href') ?? '';
                $externalId = preg_match('/[?&]t=(\d+)/', $href, $m) ? (int) $m[1] : null;

                $tagName = trim($node->filter('span')->text());
                if ($tagName === '') return;

                $style = $node->attr('style') ?? '';
                $color = preg_match('/--bg0:\s*(#[0-9a-fA-F]{3,6})/', $style, $m) ? $m[1] : null;
                $groupColor = preg_match('/--group_color:\s*(#[0-9a-fA-F]{3,6})/', $style, $m) ? $m[1] : null;

                $categoryId = null;
                if ($groupColor && isset($categories[$groupColor])) {
                    $category = FlavorCategory::firstOrCreate(
                        ['name' => $categories[$groupColor]],
                        ['color' => $groupColor]
                    );
                    $categoryId = $category->id;
                }

                $flavorTag = $externalId ? FlavorTag::where('external_id', $externalId)->first() : null;

                if (!$flavorTag) {
                    $flavorTag = FlavorTag::where('name', $tagName)->first();
                }

                if (!$flavorTag) {
                    $flavorTag = FlavorTag::create([
                        'external_id'        => $externalId,
                        'name'               => $tagName,
                        'color'              => $color,
                        'flavor_category_id' => $categoryId,
                    ]);
                } else {
                    $dirty = false;
                    if ($externalId && !$flavorTag->external_id) { $flavorTag->external_id = $externalId; $dirty = true; }
                    if ($color && !$flavorTag->color) { $flavorTag->color = $color; $dirty = true; }
                    if ($categoryId && !$flavorTag->flavor_category_id) { $flavorTag->flavor_category_id = $categoryId; $dirty = true; }
                    if ($dirty) $flavorTag->save();
                }

                $tobacco->flavorTags()->syncWithoutDetaching([$flavorTag->id]);
            });

            $processed++;
        };

        // Первые 20 — из HTML
        $crawler->filter('.tobacco_list_item')->each(function (Crawler $node) use ($process) {
            $spans = $node->filter('.tobacco_list_item_slug span');
            if ($spans->count() < 1) return;

            $name = $spans->eq(0)->text();
            $href = $node->filter('.tobacco_list_item_slug')->count() > 0
                ? $node->filter('.tobacco_list_item_slug')->attr('href')
                : null;

            $detailSlug = $href ? Str::after($href, '/tobaccos/') : null;
            $process($name, $detailSlug);
            usleep(300000);
        });

        // Остальные — через API
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
                $process($item['name'] ?? '', $item['slug'] ?? null);
                usleep(300000);
            }

            $offset += count($response);
        }

        $this->info("Обработано вкусов: {$processed} из {$totalCount}");
    }
}