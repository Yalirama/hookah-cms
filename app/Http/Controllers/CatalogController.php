<?php

namespace App\Http\Controllers;

use App\Models\Tobacco;
use App\Models\TobaccoBrand;
use App\Services\TobaccoCatalog;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private TobaccoCatalog $catalog) {}

    private function guard(callable $fn)
    {
        try {
            return response()->json($fn());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Табак не найден или не является ручным'], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e instanceof \RuntimeException ? $e->getMessage() : 'Не удалось связаться с сайтом-источником. Попробуйте позже.'], 422);
        }
    }

    public function panel()
    {
        $brands = TobaccoBrand::withCount('tobaccos')->orderBy('name')->get();
        $manual = Tobacco::with('brand')->where('is_manual', true)->orderBy('name')->get();

        return view('settings.partials._catalog-panel', compact('brands', 'manual'));
    }

    public function search(Request $r)
    {
        return $this->guard(fn () => ['brands' => $this->catalog->searchBrands((string) $r->get('q'))]);
    }

    public function brand(Request $r)
    {
        $slug = $r->get('slug');
        if (!$slug && $r->filled('brand_id')) {
            $b = TobaccoBrand::findOrFail($r->get('brand_id'));
            $slug = $b->source_slug ?: ($this->catalog->searchBrands($b->name)[0]['slug'] ?? null);
            if ($slug) {
                $found = collect($this->catalog->searchBrands($b->name))->first(fn ($x) => TobaccoCatalog::norm($x['name']) === TobaccoCatalog::norm($b->name));
                $slug = $b->source_slug ?: ($found['slug'] ?? null);
            }
        }

        return $this->guard(function () use ($slug, $r) {
            if (!$slug) throw new \RuntimeException('Бренд не найден на сайте-источнике. Найдите его через поиск по названию.');
            $d = $this->catalog->diff($slug, $r->boolean('fresh'));
            if (!$d) throw new \RuntimeException('Бренд не найден на сайте-источнике');

            return $d;
        });
    }

    public function import(Request $r)
    {
        return $this->guard(function () use ($r) {
            $data = $r->validate(['slug' => 'required|string|max:120', 'items' => 'required|array|min:1|max:10', 'items.*' => 'string|max:200'], ['items.required' => 'Отметьте хотя бы одну позицию', 'items.min' => 'Отметьте хотя бы одну позицию']);

            return $this->catalog->importBatch($data['slug'], $data['items']);
        });
    }

    public function manual(Request $r)
    {
        return $this->guard(function () use ($r) {
            $d = $r->validate([
                'brand_id' => 'nullable|exists:tobacco_brands,id', 'brand_name' => 'required_without:brand_id|nullable|string|max:100',
                'name' => 'required|string|max:120', 'alt_name' => 'nullable|string|max:120', 'strength' => 'nullable|in:light,medium,strong', 'description' => 'nullable|string|max:2000',
            ], ['brand_name.required_without' => 'Выберите бренд или впишите новый', 'name.required' => 'Укажите название вкуса']);
            $t = $this->catalog->createManual($d);

            return ['id' => $t->id, 'name' => $t->name];
        });
    }

    public function link(Request $r)
    {
        return $this->guard(function () use ($r) {
            $d = $r->validate(['manual_id' => 'required|exists:tobaccos,id', 'slug' => 'required|string|max:120', 'item' => 'required|string|max:200']);
            $manual = Tobacco::where('is_manual', true)->findOrFail($d['manual_id']);
            $res = $this->catalog->importBatch($d['slug'], [$d['item']]);
            if (!$res['done']) throw new \RuntimeException('Не удалось загрузить позицию из каталога');
            $this->catalog->link($manual, Tobacco::findOrFail($res['done'][0]['id']));

            return ['linked' => $res['done'][0]['name']];
        });
    }

    public function linkLocal(Request $r)
    {
        return $this->guard(function () use ($r) {
            $d = $r->validate(['manual_id' => 'required|exists:tobaccos,id', 'target_id' => 'required|exists:tobaccos,id']);
            $this->catalog->link(Tobacco::where('is_manual', true)->findOrFail($d['manual_id']), Tobacco::findOrFail($d['target_id']));

            return ['ok' => true];
        });
    }
}
