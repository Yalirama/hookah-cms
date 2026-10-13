<?php

namespace Database\Seeders;

use App\Models\Mix;
use App\Models\Tobacco;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('orders')->exists()) {
            $this->command->warn('В базе уже есть заказы, сидер пропущен.');
            return;
        }

        if (!DB::table('tobaccos')->exists()) {
            $this->command->warn('Нет табаков. Сначала выполните TobaccoSeeder.');
            return;
        }

        mt_srand(42);
        $now = Carbon::now();
        $days = 120;

        $pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
        $weighted = function (array $items, callable $weight) {
            $total = 0;
            foreach ($items as $i) {
                $total += $weight($i);
            }
            $r = mt_rand() / mt_getrandmax() * $total;
            foreach ($items as $i) {
                $r -= $weight($i);
                if ($r <= 0) {
                    return $i;
                }
            }
            return end($items);
        };

        Role::findOrCreate('admin');
        Role::findOrCreate('master');

        $masterIds = [];
        foreach ([['Асхат', 'Нуров'], ['Диана', 'Серикова'], ['Тимур', 'Белов'], ['Алия', 'Касымова']] as $i => [$n, $s]) {
            $login = 'master' . ($i + 1);
            $id = DB::table('users')->where('login', $login)->value('id');
            if (!$id) {
                $id = DB::table('users')->insertGetId([
                    'name' => $n,
                    'surname' => $s,
                    'login' => $login,
                    'password' => Hash::make('demo12345'),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                User::find($id)->assignRole('master');
            }
            $masterIds[] = $id;
        }
        $adminId = User::role('admin')->value('id') ?? $masterIds[0];

        if (!DB::table('table_types')->exists()) {
            foreach (['Основной зал', 'VIP-зона', 'Терраса'] as $t) {
                DB::table('table_types')->insert(['name' => $t, 'description' => '', 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $typeIds = DB::table('table_types')->pluck('id')->all();

        if (!DB::table('tables')->exists()) {
            for ($i = 1; $i <= 12; $i++) {
                DB::table('tables')->insert([
                    'name' => 'Стол ' . $i,
                    'type_id' => $typeIds[$i <= 7 ? 0 : ($i <= 10 ? 1 % count($typeIds) : 2 % count($typeIds))],
                    'capacity' => $pick([2, 4, 4, 6, 8]),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        $tableIds = DB::table('tables')->where('is_active', 1)->pluck('id')->all();

        $labelNames = ['Любит кислое', 'Любит сладкое', 'Не любит холодок', 'Только лёгкие миксы', 'Любит крепкое', 'Приходит компанией', 'Просит сменить угли', 'Ценит быстрый сервис'];
        if (DB::table('labels')->count() < count($labelNames)) {
            foreach ($labelNames as $ln) {
                DB::table('labels')->insert(['name' => $ln, 'description' => '', 'is_active' => 1, 'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        $labelIds = DB::table('labels')->where('is_active', 1)->pluck('id')->all();

        $tobaccoIds = DB::table('venue_tobaccos')->where('is_active', 1)->pluck('tobacco_id')->all();
        if (count($tobaccoIds) < 50) {
            $tobaccoIds = DB::table('tobaccos')->inRandomOrder()->limit(160)->pluck('id')->all();
            $existing = DB::table('venue_tobaccos')->whereIn('tobacco_id', $tobaccoIds)->pluck('tobacco_id')->all();
            $rows = [];
            foreach (array_diff($tobaccoIds, $existing) as $tid) {
                $rows[] = ['tobacco_id' => $tid, 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('venue_tobaccos')->insert($chunk);
            }
        }

        $mixDefs = [
            ['Тропический шторм', 'Манго, маракуйя и лёгкая кислинка'],
            ['Ягодный холодок', 'Лесные ягоды с мятой'],
            ['Цитрусовый взрыв', 'Лимон, апельсин, грейпфрут'],
            ['Сладкая вата', 'Мягкий десертный микс'],
            ['Кислый персик', 'Персик с кислой ноткой'],
            ['Арбузный лёд', 'Арбуз с освежающим холодком'],
            ['Кола с вишней', 'Газировка и вишня'],
            ['Яблочный пирог', 'Яблоко, корица, ваниль'],
            ['Малина-мята', 'Классика летнего сезона'],
            ['Гранатовый чай', 'Чай с гранатом и бергамотом'],
            ['Дыня-ананас', 'Сладкий тропический микс'],
            ['Чёрная смородина', 'Насыщенный ягодный вкус'],
            ['Кокосовый рай', 'Кокос, ананас, сливки'],
            ['Крепкий ментол', 'Для тех, кто любит покрепче'],
        ];
        $gramsSets = [
            2 => [[12, 8], [10, 10], [14, 6]],
            3 => [[10, 5, 5], [8, 6, 6], [12, 4, 4]],
        ];
        $mixIds = [];
        foreach ($mixDefs as [$mname, $short]) {
            $mid = DB::table('mixes')->insertGetId([
                'name' => $mname,
                'description_short' => $short,
                'description_long' => $short . '. Средняя забивка, умеренный прогрев.',
                'strength' => $pick(['light', 'medium', 'strong']),
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'is_active' => 1,
                'created_at' => $now->copy()->subDays(mt_rand(60, 115)),
                'updated_at' => $now,
            ]);
            $mixIds[] = $mid;
            $cnt = mt_rand(2, 3);
            $chosen = array_rand(array_flip($tobaccoIds), $cnt);
            shuffle($chosen);
            $grams = $pick($gramsSets[$cnt]);
            foreach ($chosen as $k => $tid) {
                $iid = DB::table('mix_ingredients')->insertGetId([
                    'mix_id' => $mid,
                    'tobacco_id' => $tid,
                    'grams' => $grams[$k],
                    'unit' => 'g',
                    'sort_order' => $k,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                if (mt_rand(1, 100) <= 35) {
                    $altId = $pick(array_values(array_diff($tobaccoIds, $chosen)));
                    DB::table('mix_ingredient_alts')->insert([
                        'ingredient_id' => $iid,
                        'tobacco_id' => $altId,
                        'grams' => $grams[$k],
                        'unit' => 'g',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $firstNames = ['Алибек', 'Данияр', 'Арман', 'Тимур', 'Ерлан', 'Нурлан', 'Азамат', 'Руслан', 'Максим', 'Артём', 'Илья', 'Дамир', 'Санжар', 'Бауыржан', 'Кирилл', 'Игорь', 'Асель', 'Дана', 'Айгерим', 'Мадина', 'Алина', 'Камила', 'Динара', 'Салтанат', 'Виктория', 'Анастасия', 'Жанна', 'Аружан', 'Сергей', 'Андрей', 'Влад', 'Марат', 'Олжас', 'Ренат', 'Эльдар', 'Адиль', 'Ержан', 'Диас', 'Али', 'Рамиль'];
        $initials = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'К', 'Л', 'М', 'Н', 'О', 'С', 'Т', 'У', 'Х', 'Ш'];
        $guestTypes = [
            ['count' => 8, 'from' => [110, 90], 'to' => [0, 0], 'w' => 12],
            ['count' => 22, 'from' => [100, 40], 'to' => [0, 0], 'w' => 4],
            ['count' => 28, 'from' => [110, 10], 'to' => [0, 0], 'w' => 1],
            ['count' => 14, 'from' => [110, 60], 'to' => [45, 30], 'w' => 3],
            ['count' => 8, 'from' => [18, 1], 'to' => [0, 0], 'w' => 3],
        ];
        $used = [];
        $guests = [];
        foreach ($guestTypes as $gt) {
            for ($c = 0; $c < $gt['count']; $c++) {
                do {
                    $name = $pick($firstNames) . ' ' . $pick($initials) . '.';
                } while (isset($used[$name]));
                $used[$name] = true;

                $from = $now->copy()->startOfDay()->subDays(mt_rand($gt['from'][1], $gt['from'][0]));
                $to = $gt['to'][1] === 0 ? $now->copy() : $now->copy()->subDays(mt_rand($gt['to'][1], $gt['to'][0]))->endOfDay();

                $gid = DB::table('guests')->insertGetId([
                    'name' => $name,
                    'username' => Str::slug(explode(' ', $name)[0], '_') . mt_rand(1, 99),
                    'phone' => '+77' . mt_rand(0, 9) . mt_rand(1000000, 9999999),
                    'email' => mt_rand(1, 100) <= 30 ? Str::slug(explode(' ', $name)[0], '') . mt_rand(1, 99) . '@mail.kz' : null,
                    'status_override' => null,
                    'created_by' => $pick($masterIds),
                    'created_at' => $from,
                    'updated_at' => $now,
                ]);
                $guests[] = ['id' => $gid, 'name' => $name, 'from' => $from, 'to' => $to, 'w' => $gt['w']];

                if (mt_rand(1, 100) <= 45) {
                    $picked = array_rand(array_flip($labelIds), mt_rand(1, min(3, count($labelIds))));
                    foreach ((array) $picked as $lid) {
                        DB::table('guest_labels')->insert([
                            'guest_id' => $gid,
                            'label_id' => $lid,
                            'type' => $weighted(['positive', 'neutral', 'negative'], fn($t) => $t === 'positive' ? 6 : ($t === 'neutral' ? 3 : 1)),
                            'added_by' => $pick($masterIds),
                            'added_at' => $from->copy()->addDays(mt_rand(0, 5))->min($now),
                        ]);
                    }
                }
            }
        }

        $morphMix = (new Mix)->getMorphClass();
        $morphTobacco = (new Tobacco)->getMorphClass();
        $comments = ['Без мяты', 'Покрепче', 'Угли попозже', 'Компания на 5 человек', 'Нужен кальян к 21:00', 'Не слишком сладкий'];

        $items = [];
        $logs = [];
        $hist = [];
        $tableHist = [];
        $orderCount = 0;

        $make = function (array $steps, int $master, int $tableId, ?array $guest) use (&$items, &$logs, &$hist, &$tableHist, &$orderCount, $mixIds, $tobaccoIds, $morphMix, $morphTobacco, $pick, $comments) {
            $created = $steps[0][1];
            $last = end($steps);
            $startedAt = null;
            foreach ($steps as [$s, $t]) {
                if ($s === 'in_progress' && !$startedAt) {
                    $startedAt = $t;
                }
            }
            $final = in_array($last[0], ['closed', 'cancelled']);

            $orderId = DB::table('orders')->insertGetId([
                'name' => $guest['name'] ?? 'Гость',
                'table_id' => $tableId,
                'created_by' => $master,
                'guest_id' => $guest['id'] ?? null,
                'status' => $last[0],
                'guest_comment' => mt_rand(1, 100) <= 10 ? $pick($comments) : null,
                'started_at' => $startedAt,
                'closed_at' => $final ? $last[1] : null,
                'created_at' => $created,
                'updated_at' => $last[1],
            ]);
            $orderCount++;

            $logs[] = [
                'order_id' => $orderId,
                'user_id' => $master,
                'action' => 'created',
                'old_value' => null,
                'new_value' => json_encode(['table_id' => $tableId, 'guest_id' => $guest['id'] ?? null]),
                'comment' => null,
                'created_at' => $created,
            ];

            $n = mt_rand(1, 100) <= 80 ? 1 : 2;
            for ($j = 0; $j < $n; $j++) {
                if (mt_rand(1, 100) <= 75) {
                    $type = $morphMix;
                    $id = $pick($mixIds);
                } else {
                    $type = $morphTobacco;
                    $id = $pick($tobaccoIds);
                }
                $at = $created->copy()->addMinutes(mt_rand(0, 2));
                $items[] = [
                    'order_id' => $orderId,
                    'added_by' => $master,
                    'itemable_type' => $type,
                    'itemable_id' => $id,
                    'grams' => 20.0,
                    'note' => null,
                    'deleted_at' => null,
                    'created_at' => $at,
                    'updated_at' => $at,
                ];
                $logs[] = [
                    'order_id' => $orderId,
                    'user_id' => $master,
                    'action' => 'item_added',
                    'old_value' => null,
                    'new_value' => json_encode(['itemable_type' => $type, 'itemable_id' => $id]),
                    'comment' => null,
                    'created_at' => $at,
                ];
            }

            foreach ($steps as $k => [$s, $t]) {
                $hist[] = ['order_id' => $orderId, 'status' => $s, 'started_at' => $t];
                if ($k === 0) {
                    continue;
                }
                $logs[] = [
                    'order_id' => $orderId,
                    'user_id' => $master,
                    'action' => $s === 'redo' ? 'redo_requested' : 'status_changed',
                    'old_value' => json_encode(['status' => $steps[$k - 1][0]]),
                    'new_value' => json_encode(['status' => $s]),
                    'comment' => $s === 'redo' ? 'Переделать' : null,
                    'created_at' => $t,
                ];
            }

            $tableHist[] = [
                'order_id' => $orderId,
                'table_id' => $tableId,
                'user_id' => $master,
                'assigned_at' => $created,
                'removed_at' => $final ? $last[1] : null,
            ];
        };

        $eligibleGuest = function (Carbon $at) use ($guests, $weighted) {
            $eligible = array_values(array_filter($guests, fn($g) => $g['from']->lte($at) && $g['to']->gte($at)));
            return $eligible ? $weighted($eligible, fn($g) => $g['w']) : null;
        };

        $dayWeight = [0 => 1.2, 1 => 0.6, 2 => 0.6, 3 => 0.7, 4 => 0.9, 5 => 1.6, 6 => 2.0];
        $hours = [15 => 1, 16 => 2, 17 => 3, 18 => 5, 19 => 7, 20 => 9, 21 => 10, 22 => 10, 23 => 8, 24 => 5, 25 => 2];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->copy()->startOfDay()->subDays($i);
            $count = max(2, (int) round(10 * $dayWeight[$day->dayOfWeek] * mt_rand(70, 130) / 100));
            $onShift = [$masterIds[$i % count($masterIds)], $masterIds[($i + 1) % count($masterIds)]];

            for ($c = 0; $c < $count; $c++) {
                $h = $weighted(array_keys($hours), fn($x) => $hours[$x]);
                $created = $day->copy()->addHours($h)->addMinutes(mt_rand(0, 59))->addSeconds(mt_rand(0, 59));
                if ($created->gt($now->copy()->subMinutes(150))) {
                    continue;
                }

                $guest = mt_rand(1, 100) <= 85 ? $eligibleGuest($created) : null;

                $t = $created->copy();
                $steps = [['pending', $t->copy()]];
                $roll = mt_rand(1, 100);
                if ($roll <= 5) {
                    $t->addMinutes(mt_rand(1, 6));
                    $steps[] = ['cancelled', $t->copy()];
                } else {
                    $t->addMinutes(mt_rand(1, 4));
                    $steps[] = ['in_progress', $t->copy()];
                    $t->addMinutes(mt_rand(6, 14));
                    $steps[] = ['ready', $t->copy()];
                    $t->addMinutes(mt_rand(1, 4));
                    $steps[] = ['served', $t->copy()];
                    if ($roll <= 12) {
                        $t->addMinutes(mt_rand(5, 15));
                        $steps[] = ['redo', $t->copy()];
                        $t->addMinutes(mt_rand(3, 8));
                        $steps[] = ['ready', $t->copy()];
                        $t->addMinutes(mt_rand(1, 3));
                        $steps[] = ['served', $t->copy()];
                    }
                    $t->addMinutes(mt_rand(35, 110));
                    $steps[] = ['closed', $t->copy()];
                }

                $make($steps, $pick($onShift), $pick($tableIds), $guest);
            }
        }

        $offsets = ['pending' => 0, 'in_progress' => 3, 'ready' => 10, 'served' => 14];
        $liveTables = $tableIds;
        shuffle($liveTables);
        foreach ([[2, 'pending'], [8, 'in_progress'], [20, 'ready'], [45, 'served'], [80, 'served']] as $li => [$ago, $upTo]) {
            $created = $now->copy()->subMinutes($ago);
            $steps = [];
            foreach ($offsets as $status => $off) {
                $steps[] = [$status, $created->copy()->addMinutes($off)];
                if ($status === $upTo) {
                    break;
                }
            }
            $make($steps, $pick($masterIds), $liveTables[$li % count($liveTables)], $eligibleGuest($created));
        }

        foreach (array_chunk($items, 500) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }
        foreach (array_chunk($logs, 500) as $chunk) {
            DB::table('order_logs')->insert($chunk);
        }
        foreach (array_chunk($hist, 500) as $chunk) {
            DB::table('order_status_histories')->insert($chunk);
        }
        foreach (array_chunk($tableHist, 500) as $chunk) {
            DB::table('order_table_history')->insert($chunk);
        }

        $this->command->info("Демо-данные: заказов {$orderCount}, позиций " . count($items) . ', гостей ' . count($guests) . ', миксов ' . count($mixIds) . '. Мастера master1..master4, пароль demo12345.');
    }
}