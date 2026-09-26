<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TobaccoBrand extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'country', 'description', 'logo'];

    /**
     * Ручные исключения для брендов, чьё автосгенерированное имя файла
     * не совпадает с реальным (особенности транслитерации, смешанные алфавиты и т.д.)
     */
    protected const IMAGE_OVERRIDES = [
        'Хулиган' => 'huligan',
        'Nаш'     => 'naw',
    ];

    public function tobaccos()
    {
        return $this->hasMany(Tobacco::class, 'brand_id');
    }

    public function getImageUrlAttribute(): string
    {
        $filename = self::IMAGE_OVERRIDES[$this->name]
            ?? Str::of($this->name)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '')->toString();

        $path = "assets/img/brands/{$filename}.svg";

        return file_exists(public_path($path))
            ? "/{$path}"
            : '/assets/img/product-img.svg';
    }
}