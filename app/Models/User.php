<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = ['name', 'surname', 'login', 'password', 'avatar', 'is_active'];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Заказы созданные этим мастером
    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    // Логи действий
    public function orderLogs()
    {
        return $this->hasMany(OrderLog::class);
    }

    // Миксы созданные мастером
    public function mixes()
    {
        return $this->hasMany(Mix::class, 'created_by');
    }

    // Заметки к миксам
    public function mixNotes()
    {
        return $this->hasMany(MixNote::class);
    }

    // Заметки к табакам
    public function tobaccoNotes()
    {
        return $this->hasMany(TobaccoNote::class);
    }
}