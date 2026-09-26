<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuestNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['guest_id', 'user_id', 'note'];

    public function guest()
    {
        return $this->belongsTo(Guest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}