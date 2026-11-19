@php $max = max(array_column($items, 'count') ?: [0]); @endphp
<div class="actionTabCnt">
    <p class="analyticsCardTitle">{{ $title }}</p>
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
