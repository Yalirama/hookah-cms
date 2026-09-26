<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPage extends Model
{
    protected $fillable = [
        'title', 'description', 'content',
        'sort_order', 'is_published', 'created_by', 'updated_by',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function views()
    {
        return $this->hasMany(WikiPageView::class)->orderByDesc('viewed_at');
    }

    public function uniqueViewers()
    {
        return $this->hasMany(WikiPageView::class)
            ->select('user_id')
            ->distinct();
    }
}