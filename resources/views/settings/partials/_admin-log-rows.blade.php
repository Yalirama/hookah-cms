@foreach($logs as $log)
<div class="item" data-id="{{ $log->id }}">
    <div class="itemList"><span>Кто</span><p>{{ $log->user?->name ?? '—' }}</p></div>
    <div class="itemList"><span>Что</span><p>{{ $log->entity }}{{ $log->label ? ': ' . $log->label : '' }}</p></div>
    <div class="itemList" style="grid-column:1/-1;order:3"><span>Действие</span><p>{{ $log->description }}</p></div>
    <div class="itemList" style="order:2"><span>Время</span><p>{{ $log->created_at->format('d.m.Y H:i') }}</p></div>
</div>
@endforeach
