<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';
    protected $description = 'Создать первого администратора (интерактивно)';

    public function handle()
    {
        $name  = $this->ask('Имя администратора');
        $login = $this->ask('Логин');
        $password = $this->secret('Пароль (мин. 6 символов)');

        if (strlen($password) < 6) {
            $this->error('Пароль слишком короткий.');
            return 1;
        }

        if (User::where('login', $login)->exists()) {
            $this->error("Пользователь с логином {$login} уже существует.");
            return 1;
        }

        $user = User::create([
            'name'      => $name,
            'login'     => $login,
            'password'  => Hash::make($password),
            'is_active' => true,
        ]);

        $user->assignRole('admin');

        $this->info("Администратор {$name} ({$login}) создан.");
        return 0;
    }
}