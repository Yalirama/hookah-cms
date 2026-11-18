<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AddAdmin extends Command
{
    protected $signature = 'admin:add {login} {password} {name=Администратор}';

    protected $description = 'Создать пользователя с ролью admin';

    public function handle(): int
    {
        if (User::withTrashed()->where('login', $this->argument('login'))->exists()) {
            $this->error('Логин занят');
            return self::FAILURE;
        }

        $user = User::create([
            'name'      => $this->argument('name'),
            'login'     => $this->argument('login'),
            'password'  => Hash::make($this->argument('password')),
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        $this->info("Админ #{$user->id} создан");
        return self::SUCCESS;
    }
}
