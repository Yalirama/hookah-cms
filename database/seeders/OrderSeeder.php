<?php

namespace Database\Seeders;

use App\Models\Mix;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\OrderTableHistory;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $masters = User::role('master')->get();
        $tables  = Table::all();
        $mixes   = Mix::all();

        $statuses = [
            ['status' => 'closed',   'days' => 7],
            ['status' => 'closed',   'days' => 5],
            ['status' => 'closed',   'days' => 3],
            ['status' => 'served',   'days' => 1],
            ['status' => 'in_progress', 'days' => 0],
        ];

        foreach ($statuses as $s) {
            $master    = $masters->random();
            $table     = $tables->random();
            $mix       = $mixes->random();
            $createdAt = now()->subDays($s['days'])->subHours(rand(1, 8));

            $order = Order::create([
                'table_id'    => $table->id,
                'created_by'  => $master->id,
                'status'      => $s['status'],
                'guest_comment' => rand(0, 1) ? 'Покрепче пожалуйста' : null,
                'started_at'  => $createdAt,
                'closed_at'   => in_array($s['status'], ['closed']) ? $createdAt->copy()->addHours(rand(1, 3)) : null,
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);

            // Позиция заказа
            OrderItem::create([
                'order_id'     => $order->id,
                'added_by'     => $master->id,
                'itemable_type' => Mix::class,
                'itemable_id'  => $mix->id,
                'created_at'   => $createdAt,
                'updated_at'   => $createdAt,
            ]);

            // История стола
            OrderTableHistory::create([
                'order_id'    => $order->id,
                'table_id'    => $table->id,
                'user_id'     => $master->id,
                'assigned_at' => $createdAt,
            ]);

            // Лог создания
            OrderLog::create([
                'order_id'   => $order->id,
                'user_id'    => $master->id,
                'action'     => 'created',
                'new_value'  => ['status' => 'pending'],
                'created_at' => $createdAt,
            ]);

            // Лог смены статуса
            if ($s['status'] !== 'pending') {
                OrderLog::create([
                    'order_id'   => $order->id,
                    'user_id'    => $masters->random()->id,
                    'action'     => 'status_changed',
                    'old_value'  => ['status' => 'pending'],
                    'new_value'  => ['status' => $s['status']],
                    'created_at' => $createdAt->copy()->addMinutes(rand(10, 30)),
                ]);
            }
        }
    }
}