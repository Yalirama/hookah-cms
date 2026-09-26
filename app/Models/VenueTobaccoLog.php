<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueTobaccoLog extends Model
{
    protected $fillable = ['venue_tobacco_id', 'user_id', 'action', 'comment'];

    public function venueTobacco()
    {
        return $this->belongsTo(VenueTobacco::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}