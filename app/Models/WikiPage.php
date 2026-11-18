<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WikiPage extends Model
{
    use \App\Models\Concerns\LogsAdminActions;

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

    public function adminEntity(): string
    {
        return 'Страница памятки';
    }

    public function adminLabel(): string
    {
        return (string) $this->title;
    }

    public function adminFields(): array
    {
        return ['title' => 'Заголовок', 'description' => 'Описание', 'content' => 'Содержимое', 'is_published' => 'Публикация'];
    }

    public function adminFieldText(string $field, $old, $new): ?string
    {
        if ($field === 'is_published') return $new ? 'Опубликовал' : 'Скрыл';
        if ($field === 'content' || $field === 'description') return 'Изменил содержимое';
        return $field === 'title' ? 'Заголовок: ' . $old . ' → ' . $new : null;
    }
}
