<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        self::$cache ??= static::all()->keyBy('key')->all();
        $setting = self::$cache[$key] ?? null;
        if (!$setting) return $default;

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        self::$cache = null;

        $data = ['value' => is_array($value) ? json_encode($value) : $value];

        if (is_array($value)) {
            $data['type'] = 'json';
        }

        static::updateOrCreate(['key' => $key], $data);
    }
}