<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabelLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'label_id', 'user_id', 'action', 'old_value', 'new_value', 'created_at',
    ];

    protected $casts = [
        'old_value'  => 'array',
        'new_value'  => 'array',
        'created_at' => 'datetime',
    ];

    public function label()
    {
        return $this->belongsTo(Label::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}