@php
    $max = max(array_column($items, 'count') ?: [0]);
    $what = $what ?? 'заказывают';
    $scoped = !empty($scope) ? ' Учитывается выбранный период.' : '';
    $hints = [
        'Дни недели' => "В какие дни недели {$what} чаще всего. Чем выше столбик, тем чаще, число над ним — количество. Самый частый день выделен цветом.",
        'Время суток' => "В какие часы {$what} чаще всего. Сутки разбиты на отрезки по 3 часа, например 18–21. Самый частый отрезок выделен цветом.",
        'По месяцам' => "Последние 12 месяцев, включая текущий. Чем выше столбик, тем чаще {$what} в этом месяце.{$scoped}",
        'Сезонность' => "Зима — декабрь–февраль, весна — март–май, лето — июнь–август, осень — сентябрь–ноябрь. Показаны суммы за сезон, а не средние: сезон, который наблюдался дольше, будет выше.{$scoped}",
        'По годам' => "Сколько всего за каждый год: видно, растёт интерес или падает.{$scoped}",
    ];
    $hintText = ($note ?? null) ? ($hints[$title] ?? null) . ' ' . $note : ($hints[$title] ?? null);
@endphp
<div class="actionTabCnt">
    @if($hintText)
    <div class="hintRow"><p class="analyticsCardTitle">{{ $title }}</p><button type="button" class="hintBtn" data-hint="{{ $hintText }}">?</button></div>
    @else
    <p class="analyticsCardTitle">{{ $title }}</p>
    @endif
    <div class="freqBars" style="--n:{{ count($items) }}">
        @foreach($items as $it)
        <div class="freqBar {{ $max > 0 && $it['count'] === $max ? 'top' : '' }}">
            <b>{{ $it['count'] }}</b>
            <div class="freqTrack"><i style="height:{{ $max > 0 ? max(4, round($it['count'] / $max * 100)) : 4 }}%"></i></div>
            <span>{{ $it['label'] }}</span>
        </div>
        @endforeach
    </div>
</div>
