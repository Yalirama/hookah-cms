<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\TableType;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'VIP',      'description' => 'Отдельный VIP зал'],
            ['name' => 'Стандарт', 'description' => 'Основной зал'],
            ['name' => 'Терраса',  'description' => 'Открытая терраса'],
        ];

        $typeModels = [];
        foreach ($types as $type) {
            $typeModels[$type['name']] = TableType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }

        $tables = [
            ['name' => 'Стол 1',  'type' => 'Стандарт', 'capacity' => 4],
            ['name' => 'Стол 2',  'type' => 'Стандарт', 'capacity' => 4],
            ['name' => 'Стол 3',  'type' => 'Стандарт', 'capacity' => 6],
            ['name' => 'VIP 1',   'type' => 'VIP',      'capacity' => 8],
            ['name' => 'VIP 2',   'type' => 'VIP',      'capacity' => 10],
            ['name' => 'Терраса 1', 'type' => 'Терраса', 'capacity' => 6],
            ['name' => 'Терраса 2', 'type' => 'Терраса', 'capacity' => 4],
        ];

        foreach ($tables as $table) {
            Table::firstOrCreate(
                ['name' => $table['name']],
                [
                    'type_id'   => $typeModels[$table['type']]->id,
                    'capacity'  => $table['capacity'],
                    'is_active' => true,
                ]
            );
        }
    }
}