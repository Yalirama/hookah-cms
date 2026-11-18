<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['tobacco_id', 'expected', 'actual', 'diff', 'diff_cost', 'user_id'];

    protected $casts = ['created_at' => 'datetime', 'expected' => 'float', 'actual' => 'float', 'diff' => 'float', 'diff_cost' => 'float'];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
