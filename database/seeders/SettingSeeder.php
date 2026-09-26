<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'         => 'work_hours',
                'value'       => json_encode(['from' => '14:00', 'to' => '02:00']),
                'type'        => 'json',
                'description' => 'Время работы заведения',
            ],
            [
                'key'         => 'time_slots',
                'value'       => json_encode([
                    'morning' => ['label' => 'Утро',   'from' => '06:00', 'to' => '12:00'],
                    'day'     => ['label' => 'Обед',   'from' => '12:00', 'to' => '17:00'],
                    'evening' => ['label' => 'Вечер',  'from' => '17:00', 'to' => '23:00'],
                    'night'   => ['label' => 'Ночь',   'from' => '23:00', 'to' => '06:00'],
                ]),
                'type'        => 'json',
                'description' => 'Временные слоты для аналитики',
            ],
            [
                'key'         => 'notifications_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'description' => 'Включены ли уведомления',
            ],
            [
                'key'         => 'venue_name',
                'value'       => 'Hookah Bar',
                'type'        => 'string',
                'description' => 'Название заведения',
            ],
            [
                'key'         => 'guest_vip_threshold',
                'value'       => '400',
                'type'        => 'string',
                'description' => 'Кол-во визитов для статуса VIP',
            ],
            [
                'key'         => 'guest_regular_threshold',
                'value'       => '100',
                'type'        => 'string',
                'description' => 'Кол-во визитов для статуса Регуляр',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}