<?php

namespace App\Services;

use App\Models\FlavorCategory;
use App\Models\FlavorTag;
use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class TobaccoCatalog
{
    public const BASE = 'https://htreviews.org';

    private const STRENGTH = [
        'Лёгкая' => 'light', 'Средне-лёгкая' => 'light', 'Средняя' => 'medium',
        'Средне-крепкая' => 'strong', 'Крепкая' => 'strong', 'Не указано' => 'medium',
    ];

    private function http(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; HookahCMS-catalog/1.0)', 'Accept-Language' => 'ru'])->timeout(25)->retry(2, 600, throw: false);
    }

    public static function norm(?string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace('ё', 'е', mb_strtolower((string) $s))));
    }

    private function pace(): void
    {
        usleep(350000);
    }

    public function searchBrands(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) return [];
        $r = $this->http()->get(self::BASE . '/search/', ['text' => $q]);
        if (!$r->ok()) throw new \RuntimeException('Сайт-источник недоступен (код ' . $r->status() . ')');

        $c = new Crawler($r->body());
        $brands = [];
        $c->filter('.tobacco_list_wrapper[data-type="brands"] .tobacco_list_item')->each(function (Crawler $n) use (&$brands) {
            $a = $n->filter('.tobacco_list_item_slug');
            if (!$a->count()) return;
            $slug = Str::after($a->attr('href'), '/tobaccos/');
            if (!$slug || str_contains($slug, '/')) return;
            $spans = $a->filter('span');
            $brands[$slug] = ['slug' => $slug, 'name' => trim($spans->eq(0)->text()), 'country' => $spans->count() > 1 ? trim($spans->eq(1)->text()) : null];
        });
        $c->filter('.tobacco_list_item_brand_slug')->each(function (Crawler $n) use (&$brands) {
            $slug = Str::after($n->attr('href'), '/tobaccos/');
            if ($slug && !str_contains($slug, '/') && !isset($brands[$slug])) $brands[$slug] = ['slug' => $slug, 'name' => trim($n->text()), 'country' => null];
        });

        return array_values(array_map(function ($b) {
            $local = $this->localBrand($b['slug'], $b['name']);
            $b['local_id'] = $local?->id;
            $b['local_count'] = $local ? $local->tobaccos()->count() : 0;

            return $b;
        }, $brands));
    }

    public function localBrand(string $slug, ?string $name = null): ?TobaccoBrand
    {
        $b = TobaccoBrand::where('source_slug', $slug)->first();
        if ($b || !$name) return $b;
        $n = self::norm($name);

        return TobaccoBrand::all()->first(fn ($x) => self::norm($x->name) === $n);
    }

    private function listItems(Crawler $wrap): array
    {
        $items = [];
        $wrap->filter('.tobacco_list_item')->each(function (Crawler $n) use (&$items) {
            $a = $n->filter('.tobacco_list_item_slug');
            if (!$a->count()) return;
            $slug = Str::after($a->attr('href'), '/tobaccos/');
            $spans = $a->filter('span');
            $line = $n->filter('.tobacco_list_item_line_slug');
            $items[] = [
                'slug' => $slug, 'name' => trim($spans->eq(0)->text()), 'alt' => $spans->count() > 1 ? trim($spans->eq(1)->text()) : null,
                'line_slug' => $line->count() ? Str::afterLast($line->attr('href'), '/') : (substr_count($slug, '/') >= 2 ? explode('/', $slug)[1] : null),
            ];
        });

        return $items;
    }

    public function fetchBrand(string $slug, bool $fresh = false): ?array
    {
        $key = 'catalog:brand:' . $slug;
        if (!$fresh && ($cached = Cache::get($key))) return $cached;

        $r = $this->http()->get(self::BASE . '/tobaccos/' . $slug);
        if (!$r->ok()) return null;
        $c = new Crawler($r->body());
        if (!$c->filter('h1')->count() || !$c->filter('.object_wrapper')->count()) return null;

        $country = null;
        $c->filter('.object_info_item')->each(function (Crawler $n) use (&$country) {
            $sp = $n->filter('span');
            if ($sp->count() && trim($sp->first()->text()) === 'Страна') $country = trim($n->filter('div')->first()->text());
        });
        $desc = $c->filter('.object_card_discr span')->count() ? trim($c->filter('.object_card_discr span')->first()->text()) : null;

        $lines = [];
        $c->filter('.brand_lines_item')->each(function (Crawler $n) use (&$lines) {
            $href = $n->filter('.lines_item_name')->attr('href');
            $raw = $n->filter('.lines_item_strength span')->count() ? trim($n->filter('.lines_item_strength span')->text()) : '';
            $lines[Str::afterLast($href, '/')] = [
                'strength' => self::STRENGTH[$raw] ?? 'medium',
                'description' => $n->filter('.lines_item_description span')->count() ? trim($n->filter('.lines_item_description span')->text()) : null,
            ];
        });

        $items = $this->listItems($c);
        $objectId = (int) $c->filter('.object_wrapper')->attr('data-id');
        $total = $c->filter('.tobacco_list_items')->count() ? (int) $c->filter('.tobacco_list_items')->attr('data-count') : count($items);
        $offset = 20;
        $guard = 0;
        while ($offset < $total && $guard++ < 60) {
            $this->pace();
            $resp = $this->http()->post(self::BASE . '/postData', ['action' => 'objectByBrand', 'data' => ['id' => $objectId, 'limit' => 20, 'offset' => $offset, 'sort' => ['s' => 'rating', 'd' => 'desc']]])->json();
            if (empty($resp) || !is_array($resp)) break;
            foreach ($resp as $it) {
                $items[] = ['slug' => $it['slug'] ?? '', 'name' => trim($it['name'] ?? ''), 'alt' => ($it['alt_name'] ?? null) ?: null, 'line_slug' => isset($it['line_slug']) ? Str::afterLast($it['line_slug'], '/') : null];
            }
            $offset += count($resp);
        }

        $seen = [];
        $items = array_values(array_filter($items, function ($i) use (&$seen) {
            if ($i['slug'] === '' || $i['name'] === '' || isset($seen[$i['slug']])) return false;
            return $seen[$i['slug']] = true;
        }));

        $data = ['slug' => $slug, 'name' => trim($c->filter('h1')->first()->text()), 'country' => $country, 'description' => $desc, 'total' => $total, 'lines' => $lines, 'items' => $items];
        Cache::put($key, $data, 900);

        return $data;
    }

    public function diff(string $slug, bool $fresh = false): ?array
    {
        $remote = $this->fetchBrand($slug, $fresh);
        if (!$remote) return null;
        $brand = $this->localBrand($slug, $remote['name']);
        $local = $brand ? Tobacco::where('brand_id', $brand->id)->get() : collect();
        $bySlug = $local->filter(fn ($t) => $t->source_slug)->keyBy('source_slug');
        $byName = $local->keyBy(fn ($t) => self::norm($t->name));

        $matched = [];
        $new = []; $exists = [];
        foreach ($remote['items'] as $it) {
            $t = $bySlug[$it['slug']] ?? $byName[self::norm($it['name'])] ?? null;
            if ($t) { $matched[$t->id] = true; $exists[] = $it + ['local_id' => $t->id]; } else $new[] = $it;
        }
        $localOnly = $local->filter(fn ($t) => !isset($matched[$t->id]) && !$t->merged_into)->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'manual' => (bool) $t->is_manual])->values()->all();

        return ['brand' => ['slug' => $slug, 'name' => $remote['name'], 'country' => $remote['country'], 'total' => $remote['total'], 'local_id' => $brand?->id, 'local_count' => $local->count()],
            'new' => $new, 'exists' => $exists, 'local_only' => $localOnly];
    }

    public function ensureBrand(array $remote): TobaccoBrand
    {
        $brand = $this->localBrand($remote['slug'], $remote['name']);
        if (!$brand) {
            $brand = TobaccoBrand::create(['name' => $remote['name'], 'country' => $remote['country'], 'description' => $remote['description'], 'source_slug' => $remote['slug']]);
        } else {
            $brand->fill(['source_slug' => $remote['slug']]);
            if (!$brand->country && $remote['country']) $brand->country = $remote['country'];
            if (!$brand->description && $remote['description']) $brand->description = $remote['description'];
        }
        $brand->synced_at = now();
        $brand->save();

        return $brand;
    }

    public function importItem(TobaccoBrand $brand, array $remote, array $item): Tobacco
    {
        $line = $remote['lines'][$item['line_slug'] ?? ''] ?? ['strength' => 'medium', 'description' => null];
        $t = Tobacco::where('source_slug', $item['slug'])->first()
            ?? Tobacco::where('brand_id', $brand->id)->whereNull('merged_into')->get()->first(fn ($x) => self::norm($x->name) === self::norm($item['name']));

        if (!$t) {
            $t = Tobacco::create(['brand_id' => $brand->id, 'name' => $item['name'], 'alt_name' => $item['alt'], 'description_long' => $line['description'], 'strength' => $line['strength'], 'source_slug' => $item['slug']]);
        } elseif ($t->source_slug && $t->source_slug !== $item['slug']) {
            return $t;
        } else {
            $t->source_slug = $item['slug'];
            if (!$t->alt_name && $item['alt']) $t->alt_name = $item['alt'];
            if (!$t->description_long && $line['description']) $t->description_long = $line['description'];
            $t->save();
        }
        $this->enrich($t, $item['slug']);

        return $t;
    }

    public function enrich(Tobacco $t, string $detailSlug): void
    {
        $this->pace();
        $r = $this->http()->get(self::BASE . '/tobaccos/' . $detailSlug);
        if (!$r->ok()) return;
        $c = new Crawler($r->body());

        $description = null;
        $c->filter('script[type="application/ld+json"]')->each(function (Crawler $n) use (&$description) {
            $d = json_decode($n->text(), true);
            if (($d['@type'] ?? null) === 'Product' && isset($d['category']) && substr_count($d['category'], '/') === 2) $description = $d['description'] ?? null;
        });
        if ($description) { $t->description_short = $description; $t->save(); }

        $cats = [];
        $c->filter('.tags_group .group_title')->each(function (Crawler $n) use (&$cats) {
            if (preg_match('/--color:\s*(#[0-9a-fA-F]{3,6})/', $n->attr('style') ?? '', $m)) $cats[$m[1]] = trim($n->filter('span')->text());
        });
        $c->filter('.object_card_tag')->each(function (Crawler $n) use ($t, $cats) {
            $href = $n->attr('href') ?? '';
            $ext = preg_match('/[?&]t=(\d+)/', $href, $m) ? (int) $m[1] : null;
            $name = trim($n->filter('span')->text());
            if ($name === '') return;
            $style = $n->attr('style') ?? '';
            $color = preg_match('/--bg0:\s*(#[0-9a-fA-F]{3,6})/', $style, $m) ? $m[1] : null;
            $gc = preg_match('/--group_color:\s*(#[0-9a-fA-F]{3,6})/', $style, $m) ? $m[1] : null;
            $catId = ($gc && isset($cats[$gc])) ? FlavorCategory::firstOrCreate(['name' => $cats[$gc]], ['color' => $gc])->id : null;

            $tag = ($ext ? FlavorTag::where('external_id', $ext)->first() : null) ?? FlavorTag::where('name', $name)->first();
            if (!$tag) $tag = FlavorTag::create(['external_id' => $ext, 'name' => $name, 'color' => $color, 'flavor_category_id' => $catId]);
            $t->flavorTags()->syncWithoutDetaching([$tag->id]);
        });
    }

    public function importBatch(string $slug, array $slugs): array
    {
        $remote = $this->fetchBrand($slug);
        if (!$remote) throw new \RuntimeException('Бренд не найден на сайте-источнике');
        $brand = $this->ensureBrand($remote);
        $byS = collect($remote['items'])->keyBy('slug');
        $done = []; $failed = [];
        foreach ($slugs as $s) {
            if (!isset($byS[$s])) { $failed[] = $s; continue; }
            try {
                $t = $this->importItem($brand, $remote, $byS[$s]);
                $done[] = ['id' => $t->id, 'name' => $t->name];
            } catch (\Throwable $e) {
                $failed[] = $byS[$s]['name'];
            }
        }

        return ['done' => $done, 'failed' => $failed, 'brand_id' => $brand->id];
    }

    public function createManual(array $d): Tobacco
    {
        return DB::transaction(function () use ($d) {
            $brand = !empty($d['brand_id']) ? TobaccoBrand::findOrFail($d['brand_id'])
                : TobaccoBrand::all()->first(fn ($b) => self::norm($b->name) === self::norm($d['brand_name'])) ?? TobaccoBrand::create(['name' => trim($d['brand_name'])]);
            if (Tobacco::where('brand_id', $brand->id)->get(['name'])->contains(fn ($x) => self::norm($x->name) === self::norm($d['name']))) {
                throw new \RuntimeException('Табак «' . trim($d['name']) . '» у бренда «' . $brand->name . '» уже есть');
            }
            $t = Tobacco::create(['brand_id' => $brand->id, 'name' => trim($d['name']), 'alt_name' => $d['alt_name'] ?? null, 'description_short' => $d['description'] ?? null,
                'strength' => $d['strength'] ?? 'medium', 'is_manual' => true]);
            \App\Models\VenueTobacco::updateOrCreate(['tobacco_id' => $t->id], ['is_active' => true]);

            return $t;
        });
    }

    public function link(Tobacco $manual, Tobacco $target): void
    {
        if ($manual->id === $target->id) throw new \RuntimeException('Нельзя привязать табак к самому себе');
        DB::transaction(function () use ($manual, $target) {
            $m = $manual->id; $t = $target->id;
            $mt = 'App\\Models\\Tobacco';

            DB::table('order_items')->where('itemable_type', $mt)->where('itemable_id', $m)->update(['itemable_id' => $t]);
            foreach (DB::table('order_items')->whereNotNull('alt_choices')->get(['id', 'alt_choices']) as $it) {
                $ch = json_decode($it->alt_choices, true);
                if (!is_array($ch)) continue;
                $changed = false;
                foreach ($ch as $k => $v) if ((int) $v === $m) { $ch[$k] = $t; $changed = true; }
                if ($changed) DB::table('order_items')->where('id', $it->id)->update(['alt_choices' => json_encode($ch)]);
            }
            DB::table('mix_ingredients')->where('tobacco_id', $m)->update(['tobacco_id' => $t]);
            DB::table('mix_ingredient_alts')->where('tobacco_id', $m)->update(['tobacco_id' => $t]);
            foreach (['tobacco_notes', 'stock_movements', 'tobacco_receipts', 'stock_audits'] as $tbl) DB::table($tbl)->where('tobacco_id', $m)->update(['tobacco_id' => $t]);

            $ms = DB::table('tobacco_stocks')->where('tobacco_id', $m)->first();
            $ts = DB::table('tobacco_stocks')->where('tobacco_id', $t)->first();
            if ($ms && !$ts) {
                DB::table('tobacco_stocks')->where('tobacco_id', $m)->update(['tobacco_id' => $t]);
            } elseif ($ms && $ts) {
                $sum = (float) $ms->on_hand + (float) $ts->on_hand;
                $avg = $sum > 0 ? ((float) $ms->on_hand * (float) $ms->avg_cost + (float) $ts->on_hand * (float) $ts->avg_cost) / $sum : ($ts->avg_cost ?? $ms->avg_cost);
                DB::table('tobacco_stocks')->where('tobacco_id', $t)->update(['avg_cost' => $avg, 'min_stock' => max((float) $ms->min_stock, (float) $ts->min_stock)]);
                DB::table('tobacco_stocks')->where('tobacco_id', $m)->delete();
            }
            if ($ms || $ts) app(StockService::class)->recalc($t);

            $mv = DB::table('venue_tobaccos')->where('tobacco_id', $m)->first();
            $tv = DB::table('venue_tobaccos')->where('tobacco_id', $t)->first();
            if ($mv && !$tv) {
                DB::table('venue_tobaccos')->where('id', $mv->id)->update(['tobacco_id' => $t]);
            } elseif ($mv && $tv) {
                DB::table('venue_tobaccos')->where('id', $tv->id)->update(['is_active' => $mv->is_active || $tv->is_active, 'price' => $tv->price ?? $mv->price]);
                DB::table('venue_tobacco_logs')->where('venue_tobacco_id', $mv->id)->update(['venue_tobacco_id' => $tv->id]);
                DB::table('venue_tobaccos')->where('id', $mv->id)->delete();
            }

            DB::table('tobacco_flavors')->where('tobacco_id', $m)->delete();
            $manual->update(['merged_into' => $t, 'is_manual' => false]);
            $manual->delete();
        });
    }
}
