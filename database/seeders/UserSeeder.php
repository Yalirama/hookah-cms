<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name'      => 'Администратор',
            'login'     => 'admin',
            'password'  => Hash::make('admin123'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $masters = [
            ['name' => 'Антон Николаев',   'login' => 'anton'],
            ['name' => 'Андрей Смирнов',   'login' => 'andrey'],
            ['name' => 'Дамир Касымов',    'login' => 'damir'],
            ['name' => 'Алина Петрова',    'login' => 'alina'],
        ];

        foreach ($masters as $masterData) {
            $master = User::create([
                'name'      => $masterData['name'],
                'login'     => $masterData['login'],
                'password'  => Hash::make('master123'),
                'is_active' => true,
            ]);
            $master->assignRole('master');
        }
    }
}