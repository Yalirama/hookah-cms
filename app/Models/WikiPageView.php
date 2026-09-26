<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['wiki_page_id', 'user_id', 'viewed_at'];

    protected $casts = ['viewed_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function page()
    {
        return $this->belongsTo(WikiPage::class);
    }
}