<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'name', 'username', 'phone', 'email', 'status_override', 'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Guest $guest) {
            if (!$guest->username) {
                $guest->username = self::generateUsername();
            }
        });
    }

    private static function generateUsername(): string
    {
        $adjectives = [
            'cool', 'dark', 'swift', 'wild', 'calm', 'bold', 'epic', 'lazy', 'mad', 'old',
            'new', 'hot', 'icy', 'sly', 'wise', 'keen', 'fast', 'iron', 'neon', 'void',
            'grim', 'lone', 'lost', 'free', 'pure', 'raw', 'slim', 'soft', 'tame', 'true',
            'vast', 'warm', 'zany', 'acid', 'aged', 'arch', 'bare', 'base', 'blue', 'cold',
            'dead', 'deep', 'dire', 'dusk', 'dust', 'fern', 'fire', 'flat', 'gold', 'gray',
            'hard', 'high', 'hush', 'jade', 'kind', 'last', 'late', 'lean', 'left', 'live',
            'loud', 'mint', 'mist', 'moon', 'moss', 'myth', 'next', 'nice', 'noir', 'nord',
            'oak', 'pale', 'peak', 'pine', 'pink', 'plus', 'prey', 'prime', 'proud', 'quick',
            'real', 'red', 'reft', 'rich', 'rife', 'rise', 'rock', 'rogue', 'royal', 'rust',
            'safe', 'sage', 'salt', 'sand', 'sharp', 'shin', 'silent', 'silver', 'sleek', 'smoke',
        ];

        $nouns = [
            'tiger', 'eagle', 'shark', 'wolf', 'bear', 'lion', 'hawk', 'fox', 'owl', 'cat',
            'dog', 'ray', 'ant', 'bee', 'elk', 'crow', 'deer', 'frog', 'gull', 'hare',
            'ibis', 'kite', 'lynx', 'mole', 'newt', 'pike', 'puma', 'rook', 'seal', 'slug',
            'swan', 'toad', 'vole', 'wasp', 'wren', 'yak', 'bison', 'cobra', 'dingo', 'drake',
            'finch', 'gecko', 'goose', 'hyena', 'jackal', 'koala', 'lemur', 'llama', 'moose', 'otter',
            'panda', 'quail', 'raven', 'rhino', 'skunk', 'sloth', 'snail', 'squid', 'stork', 'tapir',
            'viper', 'walrus', 'xerus', 'zebra', 'adder', 'bison', 'camel', 'dhole', 'eland', 'fossa',
            'genet', 'hippo', 'impala', 'jaguar', 'krait', 'liger', 'mamba', 'narwhal', 'ocelot', 'puffin',
            'quokka', 'saiga', 'takin', 'urubu', 'visón', 'wombat', 'xurel', 'yapok', 'zorilla', 'alpaca',
            'bonito', 'condor', 'dugong', 'ermine', 'falcon', 'gibbon', 'hornet', 'indri', 'jerboa', 'kakapo',
        ];

        do {
            $number   = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $username = $adjectives[array_rand($adjectives)] . '_' . $nouns[array_rand($nouns)] . '_' . $number;
        } while (static::where('username', $username)->exists());

        return $username;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function notes()
    {
        return $this->hasMany(GuestNote::class);
    }

    public function logs()
    {
        return $this->hasMany(GuestLog::class)->orderBy('created_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Авто-статус на основе визитов
    public function getStatusAttribute(): string
    {
        if ($this->status_override) {
            return $this->status_override;
        }

        $visits = $this->orders()->whereIn('status', ['closed'])->count();

        $vipThreshold       = (int) Setting::get('guest_vip_threshold', 400);
        $permanentThreshold = (int) Setting::get('guest_permanent_threshold', 50);
        $regularThreshold   = (int) Setting::get('guest_regular_threshold', 10);

        if ($visits >= $vipThreshold)       return 'vip';
        if ($visits >= $permanentThreshold) return 'permanent';
        if ($visits >= $regularThreshold)   return 'regular';
        return 'new';
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'new'       => 'Новый',
            'regular'   => 'Регулярный',
            'permanent' => 'Постоянный',
            'vip'       => 'VIP',
        ][$this->status] ?? '—';
    }

    // Статистика
    public function getVisitsCountAttribute(): int
    {
        return $this->orders()->whereIn('status', ['closed', 'redo', 'cancelled'])->count();
    }

    public function getLastVisitDaysAttribute(): ?int
    {
        $last = $this->orders()->whereIn('status', ['closed'])->latest('closed_at')->first();
        return $last ? now()->diffInDays($last->closed_at) : null;
    }

    public function labels()
    {
        return $this->hasMany(GuestLabel::class)
            ->whereNull('removed_at')
            ->whereHas('label', fn($q) => $q->where('is_active', true))
            ->with('label');
    }

    public function labelsByType(string $type)
    {
        return $this->labels()->where('type', $type)->get();
    }

    public function allLabels()
    {
        return $this->hasMany(GuestLabel::class)->with('label', 'addedBy', 'removedBy');
    }
}