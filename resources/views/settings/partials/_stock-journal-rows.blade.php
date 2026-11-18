@foreach($logs as $log)
<div class="item" data-id="{{ $log->id }}">
    <div class="itemList"><span>Табак</span><p>{{ trim(($log->tobacco->brand->name ?? '') . ' ' . ($log->tobacco->name ?? '—')) }}</p></div>
    <div class="itemList" style="order:2"><span>Время</span><p>{{ $log->created_at->format('d.m.Y H:i') }}</p></div>
    <div class="itemList" style="grid-column:1/-1;order:3"><span>{{ \App\Models\StockMovement::TYPES[$log->type] ?? $log->type }}</span><p>{{ $log->grams > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($log->grams, 1, ',', ''), '0'), ',') }} г{{ $log->order_id ? ' · заказ #' . $log->order_id : '' }}{{ $log->note ? ' · ' . $log->note : '' }} · {{ $log->user?->name ?? '—' }}</p></div>
</div>
@endforeach
