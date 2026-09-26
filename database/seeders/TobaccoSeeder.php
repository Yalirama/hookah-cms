<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class TobaccoSeeder extends Seeder
{
    public function run(): void
    {
        $file = database_path('seeders/data/tobaccos.json');

        if (!file_exists($file)) {
            $this->command->warn('Файл tobaccos.json не найден, пропускаем.');
            return;
        }

        Artisan::call('tobacco:import-json', ['file' => $file]);
        $this->command->info(Artisan::output());
    }
}