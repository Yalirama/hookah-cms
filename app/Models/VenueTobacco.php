<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VenueTobacco extends Model
{
    protected $fillable = ['tobacco_id', 'is_active', 'deactivated_at', 'deactivated_by', 'deactivation_comment'];

    protected $casts = [
        'is_active'      => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    public function tobacco()
    {
        return $this->belongsTo(Tobacco::class)->withTrashed();
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deactivated_by')->withTrashed();
    }

    public function logs()
    {
        return $this->hasMany(VenueTobaccoLog::class);
    }
}