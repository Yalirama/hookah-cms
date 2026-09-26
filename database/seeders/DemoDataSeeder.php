<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TableSeeder::class,
            MixSeeder::class,
            OrderSeeder::class,
        ]);
    }
}