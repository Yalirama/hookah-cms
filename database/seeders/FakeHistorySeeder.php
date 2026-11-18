<?php

namespace Database\Seeders;

use App\Models\Guest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\OrderTableHistory;
use App\Models\ShiftLog;
use App\Models\Tobacco;
use App\Models\TobaccoStock;
use App\Models\User;
use App\Services\StockService;
use App\Services\StockShortage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FakeHistorySeeder
{
    const START = '2023-11-20';
    const FILE = 'app/fake_ctx.json';

    private static array $users = [];

    public static function run(string $s): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        mt_srand(crc32($s));
        \Illuminate\Database\Eloquent\Model::unguard();
        try {
            return match (true) {
                $s === 'init' => self::init(),
                (bool) preg_match('/^\d{4}-\d{2}$/', $s) => self::month($s),
                $s === 'live' => self::live(),
                $s === 'reset' => self::reset(),
                default => ['error' => 'unknown step'],
            };
        } finally {
            Carbon::setTestNow();
        }
    }

    private static function ctx(?array $set = null): array
    {
        $f = storage_path(self::FILE);
        if ($set !== null) { file_put_contents($f, json_encode($set)); return $set; }

        return json_decode(file_get_contents($f), true);
    }

    private static function rnd(float $a, float $b): float { return $a + mt_rand() / mt_getrandmax() * ($b - $a); }
    private static function chance(float $p): bool { return mt_rand() / mt_getrandmax() < $p; }
    private static function pick(array $a) { return $a[array_rand($a)]; }

    private static function wpick(array $weights)
    {
        $sum = array_sum($weights); $x = self::rnd(0, $sum);
        foreach ($weights as $k => $w) { $x -= $w; if ($x <= 0) return $k; }

        return array_key_last($weights);
    }

    private static function user(int $id): User
    {
        return self::$users[$id] ??= User::withTrashed()->find($id);
    }

    private static function as(int $id): void
    {
        Auth::setUser(self::user($id));
    }

    private static function log(int $orderId, int $uid, string $action, $old = null, $new = null, ?string $comment = null): void
    {
        OrderLog::create(['order_id' => $orderId, 'user_id' => $uid, 'action' => $action, 'old_value' => $old, 'new_value' => $new, 'comment' => $comment, 'created_at' => now()]);
    }

    private static function init(): array
    {
        $svc = app(StockService::class);
        \App\Models\Setting::set('bowl_size', 25);
        \App\Models\Setting::set('default_item_price', 5000);
        Carbon::setTestNow(Carbon::parse(self::START . ' 10:00'));
        self::as(1);

        $defs = [['Антон', 'Ким', '2023-11-20', null, 1], ['Андрей', 'Сей', '2023-11-20', '2025-03-31', 0], ['Дамир', 'Ахмет', '2023-11-20', null, 1],
            ['Алина', 'Нуре', '2024-02-10', null, 1], ['Тима', 'Волков', '2024-06-01', '2025-09-15', 0], ['Саша', 'Орлов', '2024-10-01', null, 1],
            ['Мадина', 'Бек', '2025-04-01', null, 1], ['Ерлан', 'Жум', '2025-10-01', null, 1], ['Руслан', 'Оспан', '2023-11-20', '2024-08-31', 0]];
        $masters = [];
        foreach ($defs as $i => [$n, $sn, $from, $to, $act]) {
            Carbon::setTestNow(Carbon::parse($from . ' 09:00'));
            $u = User::create(['name' => $n, 'surname' => $sn, 'login' => 'fm' . ($i + 1), 'password' => 'secret123', 'is_active' => (bool) $act]);
            $u->assignRole('master');
            if ($n === 'Руслан') { Carbon::setTestNow(Carbon::parse($to . ' 18:00')); $u->delete(); }
            $masters[] = ['id' => $u->id, 'from' => $from, 'to' => $to, 'w' => self::rnd(0.6, 1.6)];
        }

        $names = ['Нурдаулет', 'Асхат', 'Ермек', 'Данияр', 'Айдос', 'Алмас', 'Арман', 'Бауыржан', 'Тимур', 'Руслан', 'Марат', 'Санжар', 'Диас', 'Ильяс', 'Мейрам', 'Айгерим', 'Динара', 'Асель', 'Камила', 'Жанна', 'Алихан', 'Султан', 'Адиль', 'Ержан', 'Нурсултан', 'Мади', 'Азамат', 'Жандос', 'Куаныш', 'Рамазан', 'Олжас', 'Бекзат', 'Кирилл', 'Артём', 'Максим', 'Дмитрий', 'Влад', 'Иван', 'Никита', 'Егор'];
        $guests = [];
        $mids = array_column($masters, 'id');
        for ($i = 0; $i < 160; $i++) {
            $w = $i < 3 ? 40 : ($i < 13 ? 10 : ($i < 33 ? 4 : 0.6));
            $created = $i < 13 ? Carbon::parse(self::START)->addDays(mt_rand(0, 14)) : Carbon::parse(self::START)->addDays((int) (pow(self::rnd(0, 1), 0.7) * 1090));
            Carbon::setTestNow($created->copy()->setTime(mt_rand(15, 23), mt_rand(0, 59)));
            $by = self::chance(0.3) ? 1 : self::pick($mids);
            $g = Guest::create(['name' => $names[$i % count($names)] . ($i >= count($names) ? ' ' . chr(65 + intdiv($i, count($names))) . '.' : ''),
                'phone' => self::chance(0.6) ? sprintf('+7 (7%02d) %03d-%02d-%02d', mt_rand(0, 99), mt_rand(0, 999), mt_rand(0, 99), mt_rand(0, 99)) : null,
                'email' => self::chance(0.2) ? 'guest' . $i . '@mail.test' : null, 'created_by' => $by]);
            DB::table('guest_logs')->insert(['guest_id' => $g->id, 'user_id' => $by, 'action' => 'created', 'new_value' => json_encode(['name' => $g->name, 'username' => $g->username, 'context' => 'guests']), 'created_at' => now()]);
            $guests[] = ['id' => $g->id, 'w' => $w, 'at' => $created->toDateString()];
            if ($i >= 60 && $i < 63) $g->update(['status_override' => 'vip']);
            if ($i >= 63 && $i < 66) $g->update(['status_override' => 'regular']);
            if ($i >= 66 && $i < 68) $g->update(['status_override' => 'new']);
            if (self::chance(0.6)) {
                foreach ((array) array_rand(range(1, 8), mt_rand(1, 3)) as $k) {
                    $add = $created->copy()->addDays(mt_rand(1, 200));
                    if ($add->isFuture()) continue;
                    $rem = self::chance(0.2) ? $add->copy()->addDays(mt_rand(5, 120)) : null;
                    DB::table('guest_labels')->insert(['guest_id' => $g->id, 'label_id' => $k + 1, 'type' => self::pick(['positive', 'positive', 'neutral', 'negative']), 'added_by' => self::pick($mids), 'added_at' => $add,
                        'removed_at' => $rem && $rem->isPast() ? $rem : null, 'removed_by' => $rem && $rem->isPast() ? 1 : null]);
                }
            }
            if (self::chance(0.25)) {
                $nid = DB::table('guest_notes')->insertGetId(['guest_id' => $g->id, 'user_id' => self::pick($mids), 'note' => self::pick(['Любит крепкое', 'Всегда просит холодок', 'Приходит с компанией', 'Не любит мяту', 'Просит подольше угли', 'Оставляет хорошие чаевые']), 'created_at' => $created->copy()->addDays(mt_rand(1, 90)), 'updated_at' => now()]);
                DB::table('guest_logs')->insert(['guest_id' => $g->id, 'user_id' => 1, 'action' => 'note_added', 'new_value' => json_encode(['note_id' => $nid]), 'created_at' => $created->copy()->addDays(mt_rand(1, 90))]);
            }
        }

        $pool = [];
        $vts = DB::table('venue_tobaccos')->where('is_active', 1)->pluck('tobacco_id')->all();
        shuffle($vts);
        $rank = 0;
        Carbon::setTestNow(Carbon::parse(self::START . ' 11:00'));
        foreach ($vts as $tid) {
            $pool[] = ['id' => $tid, 'w' => 1 / pow(++$rank, 0.85), 'p' => mt_rand(40, 75) * 100];
            if (self::chance(0.6)) DB::table('venue_tobaccos')->where('tobacco_id', $tid)->update(['price' => $pool[count($pool) - 1]['p']]);
            $svc->stock($tid)->update(['pack_size' => self::pick([25, 50, 100]), 'min_stock' => self::pick([0, 100, 150]), 'supplier' => self::pick(['ТабакОпт', 'Кальян-Маркет', 'Дым KZ'])]);
            $svc->receive(Tobacco::find($tid), mt_rand(8, 15) * 100, null, self::START, round(self::rnd(3, 7), 2));
        }

        $adj = ['Тропический', 'Ледяной', 'Сладкий', 'Кислый', 'Лёгкий', 'Крепкий', 'Ягодный', 'Цитрусовый', 'Летний', 'Зимний', 'Свежий', 'Дымный', 'Фирменный', 'Яркий', 'Мягкий'];
        $noun = ['закат', 'арбуз', 'микс', 'шторм', 'рай', 'бриз', 'взрыв', 'коктейль', 'десерт', 'лёд', 'сад', 'остров', 'шейк', 'мохито', 'пунш'];
        $mixes = [];
        for ($i = 0; $i < 32; $i++) {
            $created = $i < 20 ? Carbon::parse(self::START)->addDays(mt_rand(0, 20)) : Carbon::parse(self::START)->addDays(mt_rand(30, 1080));
            Carbon::setTestNow($created->copy()->setTime(12, 0));
            $mid = DB::table('mixes')->insertGetId(['name' => $adj[$i % 15] . ' ' . $noun[($i * 7 + intdiv($i, 15)) % 15] . ($i >= 15 ? ' ' . ($i - 14) : ''), 'description_short' => 'Микс №' . ($i + 1), 'description_long' => 'Авторский микс заведения, подбирался по отзывам гостей',
                'strength' => self::pick(['light', 'medium', 'strong']), 'created_by' => self::pick($mids), 'is_active' => 1, 'price' => self::chance(0.7) ? mt_rand(45, 80) * 100 : null, 'created_at' => $created, 'updated_at' => $created]);
            $cnt = mt_rand(2, 4); $ing = array_rand(array_flip(array_column($pool, 'id')), $cnt);
            foreach ((array) $ing as $k => $tid) {
                $iid = DB::table('mix_ingredients')->insertGetId(['mix_id' => $mid, 'tobacco_id' => $tid, 'grams' => round(25 / $cnt, 1), 'unit' => 'g', 'sort_order' => $k, 'created_at' => $created, 'updated_at' => $created]);
                if (self::chance(0.3)) DB::table('mix_ingredient_alts')->insert(['ingredient_id' => $iid, 'tobacco_id' => self::pick(array_column($pool, 'id')), 'grams' => round(25 / $cnt, 1), 'unit' => 'g', 'created_at' => $created, 'updated_at' => $created]);
            }
            $snap = json_encode(['id' => $mid, 'name' => 'rev', 'strength' => 'medium', 'is_active' => true]);
            for ($r = 0; $r < mt_rand(1, 3); $r++) DB::table('mix_revisions')->insert(['mix_id' => $mid, 'user_id' => self::pick($mids), 'changed_fields' => json_encode(['action' => 'updated']), 'snapshot' => $snap, 'comment' => self::pick(['Поправили граммовки', 'Заменили табак', null]), 'created_at' => $created->copy()->addDays($r * 30 + 3), 'updated_at' => now()]);
            if (self::chance(0.3)) DB::table('mix_notes')->insert(['mix_id' => $mid, 'user_id' => self::pick($mids), 'note' => self::pick(['Гости хвалят', 'Горчит если передержать', 'Лучше с холодком']), 'created_at' => $created->copy()->addDays(10), 'updated_at' => now()]);
            $mixes[] = ['id' => $mid, 'w' => 1 / pow($i + 1, 0.7), 'at' => $created->toDateString(), 'to' => $i >= 30 ? $created->copy()->addDays(200)->toDateString() : (in_array($i, [27, 28, 29]) ? $created->copy()->addDays(120)->toDateString() : null), 'p' => 0];
            if ($i === 30 || $i === 31) { DB::table('mixes')->where('id', $mid)->update(['deleted_at' => $created->copy()->addDays(200)]); }
            if (in_array($i, [27, 28, 29])) {
                DB::table('mixes')->where('id', $mid)->update(['is_active' => 0, 'deactivated_at' => $created->copy()->addDays(120), 'deactivated_by' => 1, 'deactivation_comment' => 'Сезонный, снят']);
                DB::table('mix_logs')->insert(['mix_id' => $mid, 'user_id' => 1, 'action' => 'deactivated', 'comment' => 'Сезонный, снят', 'created_at' => $created->copy()->addDays(120), 'updated_at' => now()]);
            }
        }

        $tables = DB::table('tables')->where('is_active', 1)->whereNull('deleted_at')->pluck('id')->all();
        self::ctx(['masters' => $masters, 'guests' => $guests, 'pool' => $pool, 'mixes' => $mixes, 'tables' => $tables]);

        return ['masters' => count($masters), 'guests' => count($guests), 'pool' => count($pool), 'mixes' => count($mixes), 'tables' => count($tables)];
    }

    private static function reset(): array
    {
        $svc = app(StockService::class);
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['order_items', 'order_logs', 'order_status_histories', 'order_table_history', 'orders', 'stock_movements', 'tobacco_receipts', 'stock_audits', 'shift_logs', 'tobacco_notes', 'venue_tobacco_logs', 'tobacco_stocks'] as $t) DB::table($t)->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::table('users')->update(['on_shift' => 0]);
        $c = self::ctx();
        $ids = array_column($c['pool'], 'id');
        DB::table('venue_tobaccos')->whereIn('tobacco_id', $ids)->update(['is_active' => 1, 'deactivated_at' => null, 'deactivated_by' => null, 'deactivation_comment' => null]);
        Carbon::setTestNow(Carbon::parse(self::START . ' 11:00'));
        self::as(1);
        foreach ($ids as $tid) {
            $svc->stock($tid)->update(['pack_size' => self::pick([25, 50, 100]), 'min_stock' => self::pick([0, 100, 150]), 'supplier' => self::pick(['ТабакОпт', 'Кальян-Маркет', 'Дым KZ'])]);
            $svc->receive(Tobacco::find($tid), mt_rand(8, 15) * 100, null, self::START, round(self::rnd(3, 7), 2));
        }

        return ['reset' => count($ids)];
    }

    private static function month(string $ym): array
    {
        $c = self::ctx();
        $from = Carbon::parse($ym . '-01')->startOfDay();
        $start = $from->lt(Carbon::parse(self::START)) ? Carbon::parse(self::START) : $from;
        $end = $from->copy()->endOfMonth()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        if ($end->gt($yesterday)) $end = $yesterday;
        $env = ['c' => $c, 'svc' => app(StockService::class), 'guestNames' => DB::table('guests')->pluck('name', 'id')->all(), 'tobName' => []];
        $env['mixPrice'] = DB::table('mixes')->pluck('price', 'id')->all();
        $env['poolPrice'] = collect($c['pool'])->pluck('p', 'id')->all();
        $env['alts'] = [];
        foreach (DB::table('mix_ingredients')->join('mix_ingredient_alts as a', 'a.ingredient_id', '=', 'mix_ingredients.id')->get(['mix_ingredients.mix_id as m', 'mix_ingredients.id as i', 'a.tobacco_id as t']) as $r) $env['alts'][$r->m][$r->i] = $r->t;
        $stat = ['days' => 0, 'orders' => 0];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $stat['orders'] += self::day($d->copy(), $env);
            $stat['days']++;
        }

        return $stat + ['month' => $ym];
    }

    private static function factor(Carbon $t): float
    {
        return 1 + 0.07 * Carbon::parse(self::START)->diffInDays($t, false) / 365;
    }

    private static function day(Carbon $d, array $env): int
    {
        $c = $env['c'];
        $ds = $d->toDateString();
        $dow = (int) $d->dayOfWeek;
        $present = [];
        foreach ($c['masters'] as $m) if ($m['from'] <= $ds && (!$m['to'] || $m['to'] >= $ds) && self::chance(0.7)) $present[] = $m;
        if (count($present) < 2) { foreach ($c['masters'] as $m) if ($m['from'] <= $ds && (!$m['to'] || $m['to'] >= $ds)) $present[] = $m; $present = array_slice(array_unique($present, SORT_REGULAR), 0, 2); }
        if (!$present) return 0;

        foreach ($present as $m) {
            $s = $d->copy()->setTime(13, 30)->addMinutes(mt_rand(-20, 30));
            $e = $d->copy()->addDay()->setTime(2, 0)->addMinutes(mt_rand(-90, 45));
            ShiftLog::create(['user_id' => $m['id'], 'started_at' => $s, 'ended_at' => $e]);
        }

        $dowW = [0 => 1.2, 1 => 0.6, 2 => 0.6, 3 => 0.75, 4 => 1.0, 5 => 1.6, 6 => 1.9][$dow];
        $season = in_array((int) $d->month, [12, 1]) ? 1.15 : (in_array((int) $d->month, [7, 8]) ? 0.9 : 1.0);
        $n = (int) round(7.5 * $dowW * $season * self::factor($d) * self::rnd(0.7, 1.3));
        if (self::chance(0.02)) $n = 0;
        $times = [];
        for ($i = 0; $i < $n; $i++) $times[] = max(0, min(700, (int) (420 + (self::rnd(-1, 1) + self::rnd(-1, 1) + self::rnd(-1, 1)) * 150)));
        sort($times);
        foreach ($times as $off) {
            $uid = self::chance(0.03) ? 1 : self::wpick(array_column($present, 'w', 'id'));
            self::order($env, $d->copy()->setTime(14, 0)->addMinutes($off)->addSeconds(mt_rand(0, 59)), $uid);
        }

        self::extras($d, $env, $dow);

        return $n;
    }

    private static function topup(array $env, int $tid): void
    {
        $t = Tobacco::find($tid);
        if (!$t) return;
        $env['svc']->receive($t, mt_rand(6, 12) * 100, null, now()->toDateString(), round(self::rnd(3, 7) * self::factor(now()), 2));
    }

    private static function extras(Carbon $d, array $env, int $dow): void
    {
        $c = $env['c']; $svc = $env['svc'];
        $ids = array_column($c['pool'], 'id');
        Carbon::setTestNow($d->copy()->setTime(12, 30));
        self::as(1);
        if ($dow === 1) {
            foreach (array_rand(array_flip($ids), 6) as $tid) {
                $st = TobaccoStock::where('tobacco_id', $tid)->first();
                if ($st) $svc->audit(Tobacco::find($tid), max(0, round((float) $st->on_hand + self::rnd(-9, 4), 1)));
            }
        }
        if ($d->day === 1 || $d->day === 15) {
            foreach (TobaccoStock::where('on_hand', '<', 350)->pluck('tobacco_id') as $tid) self::topup($env, $tid);
        }
        if ($d->day === 3) {
            foreach (array_rand(array_flip($ids), 2) as $tid) { $st = TobaccoStock::where('tobacco_id', $tid)->first(); if ($st && $st->on_hand > 40) $svc->writeOff(Tobacco::find($tid), mt_rand(10, 30), self::pick(['Отсырел', 'Пролили', 'Истёк срок'])); }
            DB::table('tobacco_notes')->insert(['tobacco_id' => self::pick($ids), 'user_id' => self::pick(array_column($c['masters'], 'id')), 'note' => self::pick(['Хорошо раскрывается', 'Быстро выгорает', 'Гости просят чаще', 'Слабый дым']), 'created_at' => now(), 'updated_at' => now()]);
        }
        if ($d->day === 8) {
            $tid = self::pick($ids); $vt = DB::table('venue_tobaccos')->where('tobacco_id', $tid)->first();
            if ($vt) {
                $act = !$vt->is_active;
                DB::table('venue_tobaccos')->where('id', $vt->id)->update(['is_active' => $act, 'deactivated_at' => $act ? null : now(), 'deactivated_by' => $act ? null : 1, 'deactivation_comment' => $act ? null : 'Нет в поставке']);
                DB::table('venue_tobacco_logs')->insert(['venue_tobacco_id' => $vt->id, 'user_id' => 1, 'action' => $act ? 'activated' : 'deactivated', 'comment' => $act ? null : 'Нет в поставке', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        if ($d->day === 20 && self::chance(0.5)) {
            $m = self::pick($c['mixes']);
            DB::table('mix_revisions')->insert(['mix_id' => $m['id'], 'user_id' => 1, 'changed_fields' => json_encode(['action' => 'updated']), 'snapshot' => json_encode(['id' => $m['id']]), 'comment' => 'Правка состава', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('mixes')->where('id', $m['id'])->update(['updated_at' => now(), 'updated_by' => 1]);
        }
    }

    private static function order(array $env, Carbon $t0, int $uid, ?string $mode = null, float $speed = 1.0): Order
    {
        $c = $env['c']; $svc = $env['svc']; $ds = $t0->toDateString();
        $t = $t0->copy();
        $adv = function (float $a, float $b) use (&$t, $speed) { $t->addSeconds((int) (self::rnd($a, $b) * 60 * $speed)); Carbon::setTestNow($t); };
        Carbon::setTestNow($t);
        self::as($uid);

        $gw = [];
        foreach ($c['guests'] as $g) if ($g['at'] <= $ds) $gw[$g['id']] = $g['w'];
        $guestId = $gw && self::chance(0.6) ? self::wpick($gw) : null;
        $tableW = [];
        foreach ($c['tables'] as $i => $tid) $tableW[$tid] = $i % 5 === 3 ? 0.5 : 1;
        $table = self::wpick($tableW);

        $order = Order::create(['table_id' => $table, 'created_by' => $uid, 'status' => 'pending', 'guest_id' => $guestId, 'started_at' => $t,
            'guest_comment' => self::chance(0.1) ? self::pick(['Без мяты', 'Покрепче', 'Подольше угли', 'Холодок посильнее']) : null]);
        OrderTableHistory::create(['order_id' => $order->id, 'table_id' => $table, 'user_id' => $uid, 'assigned_at' => $t]);
        self::log($order->id, $uid, 'created', null, ['status' => 'pending', 'table_id' => $table]);
        if ($guestId) self::log($order->id, $uid, 'guest_changed', null, ['guest_id' => $guestId, 'guest_name' => $env['guestNames'][$guestId] ?? '']);

        $mixes = array_values(array_filter($c['mixes'], fn ($m) => $m['at'] <= $ds && (!$m['to'] || $m['to'] >= $ds)));
        $items = [];
        $inf = self::factor($t);
        if ($mixes && self::chance(0.6)) {
            $m = $mixes[self::wpick(array_column($mixes, 'w'))];
            $alt = null;
            if (!empty($env['alts'][$m['id']]) && self::chance(0.3)) $alt = array_map(fn ($x) => $x, $env['alts'][$m['id']]);
            $base = $env['mixPrice'][$m['id']] ?: 5000;
            $items[] = ['App\\Models\\Mix', $m['id'], round($base * $inf / 100) * 100, 'mix', DB::table('mixes')->where('id', $m['id'])->value('name'), $alt];
        } else {
            $cnt = self::wpick([1 => 6, 2 => 3, 3 => 1]);
            $pw = array_column($c['pool'], 'w', 'id');
            $got = [];
            for ($i = 0; $i < $cnt; $i++) {
                $tid = self::wpick($pw);
                if (isset($got[$tid])) continue;
                $got[$tid] = 1;
                $nm = $env['tobName'][$tid] ??= DB::table('tobaccos')->where('id', $tid)->value('name');
                $items[] = ['App\\Models\\Tobacco', $tid, round(($env['poolPrice'][$tid] ?? 5000) * $inf / 100) * 100, 'tobacco', $nm, null];
            }
        }
        $made = [];
        foreach ($items as [$type, $id, $price, $kind, $name, $alt]) {
            $adv(0.05, 0.6);
            $made[] = OrderItem::create(['order_id' => $order->id, 'added_by' => $uid, 'itemable_type' => $type, 'itemable_id' => $id, 'price' => $price, 'alt_choices' => $alt, 'fixed_percent' => ($kind === 'tobacco' && count($items) > 1 && count($made) === 0 && self::chance(0.2)) ? 35 : null]);
            self::log($order->id, $uid, 'item_added', null, ['item_type' => $kind, 'item_name' => $name, 'grams' => null]);
        }
        if (self::chance(0.08)) $order->update(['bowl_size' => self::pick([20, 30, 35])]);
        if (!$guestId && self::chance(0.08)) {
            $adv(1, 6); $gid = self::wpick($gw ?: [1 => 1]);
            if ($gw) { $order->update(['guest_id' => $gid]); self::log($order->id, $uid, 'guest_changed', null, ['guest_id' => $gid, 'guest_name' => $env['guestNames'][$gid] ?? '']); }
        }
        if (self::chance(0.07)) {
            $old = $order->guest_comment; $new = self::pick(['Без мяты', 'Покрепче', 'Дольше угли']);
            $order->update(['guest_comment' => $new]); self::log($order->id, $uid, 'guest_comment_changed', ['comment' => $old], ['comment' => $new]);
        }

        $status = 'pending';
        $go = function (string $new, float $a, float $b, ?string $comment = null) use (&$status, &$order, $adv, $svc, $uid, $env) {
            $adv($a, $b);
            if (in_array($new, ['in_progress', 'ready'])) {
                $calc = $svc->calcBowl($order);
                foreach ($calc['totals'] ?? [] as $tid => $g) {
                    $st = TobaccoStock::where('tobacco_id', $tid)->first();
                    if ($st && $st->available < $g + 5) self::topup($env, $tid);
                }
            }
            try { $svc->onStatusChange($order, $status, $new); } catch (StockShortage $e) {
                foreach ($svc->calcBowl($order)['totals'] ?? [] as $tid => $g) self::topup($env, $tid);
                $svc->onStatusChange($order, $status, $new);
            }
            $order->update(['status' => $new, 'closed_at' => in_array($new, ['closed', 'cancelled', 'redo']) ? now() : $order->closed_at]);
            self::log($order->id, $uid, 'status_changed', ['status' => $status], ['status' => $new], $comment);
            $status = $new;
        };

        $mode ??= self::wpick(['closed' => 86, 'cancelled' => 6, 'redo' => 5, 'rollback' => 3]);
        if ($mode === 'pending') return $order;
        if ($mode === 'cancelled_early' || ($mode === 'cancelled' && self::chance(0.4))) {
            $go('cancelled', 1, 6, self::pick(['Гость ушёл', 'Передумали', 'Ошибочный заказ']));
            return $order;
        }
        $go('in_progress', 0.5, 5);
        if ($mode === 'in_progress') return $order;
        if ($mode === 'cancelled') { $go('cancelled', 2, 10, self::pick(['Гость ушёл', 'Нет табака', 'Передумали'])); return $order; }
        $go('ready', 7, 16);
        if ($mode === 'ready') return $order;
        if ($mode === 'rollback') {
            $svc->onStatusChange($order, 'ready', 'in_progress');
            $adv(0.3, 2);
            $order->update(['status' => 'in_progress', 'closed_at' => null]);
            self::log($order->id, $uid, 'rolled_back', ['status' => 'ready'], ['status' => 'in_progress'], 'Ошибка в составе');
            $status = 'in_progress';
            $go('ready', 4, 9);
        }
        if (self::chance(0.07)) {
            $adv(0.5, 3); $new = self::pick(array_diff($c['tables'], [$table]));
            DB::table('order_table_history')->where('order_id', $order->id)->whereNull('removed_at')->update(['removed_at' => now()]);
            $order->update(['table_id' => $new]);
            OrderTableHistory::create(['order_id' => $order->id, 'table_id' => $new, 'user_id' => $uid, 'assigned_at' => now()]);
            self::log($order->id, $uid, 'table_changed', ['table_id' => $table], ['table_id' => $new]);
        }
        $go('served', 1, 5);
        if ($mode === 'served' || $mode === 'served_service') {
            if ($mode === 'served_service') { $adv(10, 15); self::log($order->id, $uid, 'serviced'); }
            return $order;
        }
        $dur = self::rnd(45, 150);
        for ($m = 25; $m < $dur; $m += 25) { $adv(20, 28); if (self::chance(0.8)) self::log($order->id, $uid, 'serviced'); }
        if (count($made) > 1 && self::chance(0.04)) {
            $it = $made[1]; $it->delete();
            self::log($order->id, $uid, 'item_removed', ['item_name' => 'позиция'], null, 'Гость отказался');
        }
        if (self::chance(0.02)) {
            $other = $c['masters'][array_rand($c['masters'])]['id']; $old = self::user($uid);
            DB::table('order_logs')->where('order_id', $order->id)->where('action', 'created')->update(['user_id' => $other]);
            $order->update(['created_by' => $other]);
            self::log($order->id, 1, 'master_changed', ['user_id' => $uid, 'user_name' => $old->name], ['user_id' => $other, 'user_name' => self::user($other)->name]);
        }
        if ($mode === 'redo') { $go('redo', 5, 20, self::pick(['Не понравился вкус', 'Слабый дым', 'Горчит'])); return $order; }
        $go('closed', 3, 10);

        return $order;
    }

    private static function live(): array
    {
        Carbon::setTestNow();
        $c = self::ctx();
        $env = ['c' => $c, 'svc' => app(StockService::class), 'guestNames' => DB::table('guests')->pluck('name', 'id')->all(), 'tobName' => [], 'mixPrice' => DB::table('mixes')->pluck('price', 'id')->all(), 'poolPrice' => collect($c['pool'])->pluck('p', 'id')->all(), 'alts' => []];
        $on = array_slice(array_values(array_filter($c['masters'], fn ($m) => !$m['to'])), 0, 3);
        $now = now();
        foreach ($on as $m) {
            ShiftLog::create(['user_id' => $m['id'], 'started_at' => $now->copy()->subDay()->setTime(14, mt_rand(0, 20)), 'ended_at' => null]);
            DB::table('users')->where('id', $m['id'])->update(['on_shift' => 1]);
        }
        $made = [];
        foreach (['closed' => 150, 'cancelled' => 125, 'redo' => 110, 'served_service' => 95, 'served' => 70, 'ready' => 45, 'in_progress' => 25, 'pending' => 8, 'closed ' => 190] as $mode => $ago) {
            $o = self::order($env, $now->copy()->subMinutes($ago), self::pick(array_column($on, 'id')), trim($mode), 0.3);
            $made[] = $o->id . ':' . $o->fresh()->status;
        }
        Carbon::setTestNow();

        return ['on_shift' => count($on), 'orders' => $made];
    }
}
