@php $max = max(1, collect($rows)->max('v')); @endphp
<div class="statBars">
    @foreach($rows as $r)
    <div class="statBar">
        <span class="statBarLabel">{{ $r['label'] }}</span>
        <div class="statBarTrack"><i style="width:{{ round(($r['v'] ?? 0) / $max * 100) }}%"></i></div>
        <b>{{ $r['text'] ?? $r['v'] }}</b>
    </div>
    @endforeach
</div>
