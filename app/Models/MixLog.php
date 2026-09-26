<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixLog extends Model
{
    protected $fillable = ['mix_id', 'user_id', 'action', 'comment'];

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}