<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TobaccoNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['tobacco_id', 'user_id', 'note'];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}