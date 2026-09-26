<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixRevision extends Model
{
    protected $fillable = [
        'mix_id', 'user_id', 'changed_fields', 'snapshot', 'comment',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'snapshot'       => 'array',
    ];

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}