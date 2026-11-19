@php $targets = \App\Models\OrderLog::whereIn('id', $logs->map(fn($l) => $l->new_value['log_id'] ?? null)->filter()->all())->get()->keyBy('id'); @endphp
@foreach($logs as $log)
<div class="item" data-created="{{ $log->created_at->toDateTimeString() }}">
    <div class="itemList"><span>Кто изменил</span><p>{{ $log->user?->name ?? '—' }}</p></div>
    <div class="itemList"><span>Действие</span><p>Смена мастера</p></div>
    <div class="itemList"><span>Что изменено</span><p>{{ ($targets[$log->new_value['log_id'] ?? 0] ?? null)?->describeAction() ?? 'не указано' }}</p></div>
    <div class="itemList"><span>Было</span><p>{{ $log->old_value['user_name'] ?? '—' }}</p></div>
    <div class="itemList"><span>Стало</span><p>{{ $log->new_value['user_name'] ?? '—' }}</p></div>
    <div class="itemList"><span>Время</span><p>{{ $log->created_at->format('d.m.Y H:i') }}</p></div>
    <div class="itemList">
        <p>{{ $log->order ? 'Заказ #' . $log->order->id : '—' }}</p>
        @if($log->order)
        <button type="button" onclick="openOrderSheet({{ $log->order->id }})" class="btnOpen">Открыть заказ</button>
        @endif
    </div>
</div>
@endforeach