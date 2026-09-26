<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MixNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['mix_id', 'user_id', 'note'];

    public function mix()
    {
        return $this->belongsTo(Mix::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}