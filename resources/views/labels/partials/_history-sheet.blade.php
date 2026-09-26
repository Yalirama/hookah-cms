@php
    $fieldLabels = ['name' => 'Название', 'description' => 'Описание'];
@endphp

<div class="actionTabViewOrderCard actionTabViewOrderCardShow">
    <div class="actionTabHistorySUV">
        <span class="--isEdit">Изменил </span> • <span class="--isBlue">Добавил </span> • <span class="--isRed"> Убрал</span>
    </div>

    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        @forelse($label->logs->sortByDesc('created_at') as $log)
            @php
                $dotClass = '--tabaccoEdit';
                $lines = [];

                if ($log->action === 'created') {
                    $dotClass = '--tabaccoAdd';
                    $lines[] = ['type' => 'plain', 'text' => 'Ярлык создан'];
                } elseif ($log->action === 'updated') {
                    $old = $log->old_value ?? [];
                    $new = $log->new_value ?? [];

                    foreach ($fieldLabels as $field => $label) {
                        if (($old[$field] ?? null) !== ($new[$field] ?? null)) {
                            $lines[] = ['type' => 'diff', 'label' => $label, 'old' => $old[$field] ?: '—', 'new' => $new[$field] ?: '—'];
                        }
                    }
                } elseif ($log->action === 'note_added') {
                    $dotClass = '--tabaccoAdd';
                    $lines[] = ['type' => 'plain', 'text' => 'Заметка: ' . ($log->new_value['note'] ?? '—')];
                } elseif ($log->action === 'note_deleted') {
                    $dotClass = '--tabaccoRemove';
                    $lines[] = ['type' => 'plain', 'text' => 'Заметка удалена: ' . ($log->old_value['note'] ?? '—')];
                } elseif ($log->action === 'activated') {
                    $dotClass = '--tabaccoAdd';
                    $lines[] = ['type' => 'plain', 'text' => 'Ярлык активирован'];
                } elseif ($log->action === 'deactivated') {
                    $dotClass = '--tabaccoRemove';
                    $lines[] = ['type' => 'plain', 'text' => 'Ярлык деактивирован'];
                }
            @endphp

            @foreach($lines as $line)
                <div class="actionTabHistoryItem {{ $dotClass }}">
                    <div class="actionTabHistoryItemLeft">
                        <div class="actionTabHistoryItemCircle"><span></span></div>
                        <div class="actionTabHistoryItemInfo">
                            @if($line['type'] === 'diff')
                                <p class="actionTabHistoryItemSubtitle">
                                    {{ $line['label'] }} <br>
                                    <span>{{ $line['old'] }}</span> → <span>{{ $line['new'] }}</span>
                                </p>
                            @else
                                <p class="actionTabHistoryItemSubtitle">{{ $line['text'] }}</p>
                            @endif
                            <div class="actionTabHistoryItemRow">
                                <img src="/assets/img/master-done.svg" alt="">
                                <span>{{ $log->user?->name ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="actionTabHistoryItemTime">
                        <span>{{ $log->created_at->format('H:i') }}</span>
                        <span>{{ $log->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            @endforeach
        @empty
            <p style="color:var(--info); text-align:center; padding:20px;">Изменений пока нет</p>
        @endforelse
    </div>
</div>