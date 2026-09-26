<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabelNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['label_id', 'user_id', 'note'];

    public function label()
    {
        return $this->belongsTo(Label::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}