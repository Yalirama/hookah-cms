<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TobaccoBrand extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'country', 'description', 'logo', 'source_slug', 'synced_at'];

    protected $casts = ['synced_at' => 'datetime'];

    /**
     * Ручные исключения для брендов, чьё автосгенерированное имя файла
     * не совпадает с реальным (особенности транслитерации, смешанные алфавиты и т.д.)
     */
    protected const IMAGE_OVERRIDES = [
        'Хулиган' => 'huligan',
        'Табак Шпаковского' => 'tabakshpakovskogo',
        'Nаш'     => 'naw',
    ];

    public function tobaccos()
    {
        return $this->hasMany(Tobacco::class, 'brand_id');
    }

    public function getImageUrlAttribute(): string
    {
        $clean = fn (?string $v) => Str::of((string) $v)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->toString();

        $candidates = array_filter([
            self::IMAGE_OVERRIDES[$this->name] ?? null,
            $clean($this->name),
            $clean($this->source_slug),
        ]);

        foreach ($candidates as $filename) {
            $path = "assets/img/brands/{$filename}.svg";
            if (file_exists(public_path($path))) return "/{$path}?v=" . filemtime(public_path($path));
        }

        return '/assets/img/product-img.svg';
    }
}
