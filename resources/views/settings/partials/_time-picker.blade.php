@php
    [$vh, $vm] = explode(':', $value ?: '00:00');
@endphp
<div class="defaultForm customTime --customTime-l2e30" data-target="{{ $id }}">
    <div class="customTimeValue">
        <span class="customTimeHH">{{ $vh }}</span><span>:</span><span class="customTimeMM">{{ $vm }}</span>
    </div>
    <svg class="customTimeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M12 7v5l3 2"></path>
    </svg>
    <input type="hidden" id="{{ $id }}" value="{{ $value }}">

    <div class="customTimeDropdown">
        <div class="customTimeCols">
            <div class="customTimeCol" data-type="hh">
                @for($h = 0; $h < 24; $h++)
                    <div class="customTimeOption" data-value="{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</div>
                @endfor
            </div>
            <div class="customTimeCol" data-type="mm">
                @for($m = 0; $m < 60; $m++)
                    <div class="customTimeOption" data-value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</div>
                @endfor
            </div>
        </div>
        <button type="button" class="customTimeConfirm">Готово</button>
    </div>
</div>