<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'entity', 'label', 'description'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public static function record(string $action, string $entity, ?string $label, string $description): void
    {
        $userId = auth()->id();
        if (!$userId) return;

        static::create([
            'user_id'     => $userId,
            'action'      => $action,
            'entity'      => $entity,
            'label'       => $label,
            'description' => $description,
            'created_at'  => now(),
        ]);
    }
}
