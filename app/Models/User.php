<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\LogsAdminActions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, LogsAdminActions;

    public array $adminLogSkip = ['created'];

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('on_shift')) \App\Models\ShiftLog::sync($user);
            $passwordChanged = $user->wasChanged('password');
            $deactivated = $user->wasChanged('is_active') && !$user->is_active;
            if (!$passwordChanged && !$deactivated) return;

            $own = auth()->id() === $user->id && !$deactivated;
            $q = DB::table('sessions')->where('user_id', $user->id);
            if ($own && session()->isStarted()) $q->where('id', '!=', session()->getId());
            $q->delete();

            if (!$own) {
                $user->forceFill(['remember_token' => Str::random(60), 'on_shift' => false])->saveQuietly();
                \App\Models\ShiftLog::closeFor($user->id);
            }
        });
    }

    public function adminEntity(): string
    {
        return 'Пользователь';
    }

    public function adminLabel(): string
    {
        return trim($this->name . ' ' . $this->surname) . ' (' . $this->login . ')';
    }

    public function adminFields(): array
    {
        return ['name' => 'Имя', 'surname' => 'Фамилия', 'login' => 'Логин', 'password' => 'Пароль', 'is_active' => 'Статус'];
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        if ($field === 'password') return 'Сменил пароль';
        if ($field === 'is_active') return $new ? 'Активировал учётку' : 'Деактивировал учётку (сессии завершены)';
        $labels = $this->adminFields();
        if (!isset($labels[$field])) return null;
        return $labels[$field] . ': ' . ($old ?: '—') . ' → ' . ($new ?: '—');
    }

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