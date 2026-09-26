@php $countFn = $countFn ?? fn($i) => $i->total; $maxCount = $items->max($countFn) ?: 1; @endphp
<div class="actionTabCnt">
    <p class="analyticsCardTitle">{{ $title }}</p>
    <div class="analyticsCardTopRow actionTabCntRow0dn32 topListRow">
        @foreach($items as $i => $item)
        <div class="analyticsCardTopItem {{ $i >= 5 ? 'topListHidden' : '' }}" style="{{ $i >= 5 ? 'display:none;' : '' }}">
            <div class="analyticsCardTopItemLeft">
                <div class="analyticsCardTopItemCount"><span>{{ $i + 1 }}</span></div>
                <p class="analyticsCardTopItemTitle">{{ $labelFn($item) }}</p>
            </div>
            <div class="analyticsCardTopItemRight">
                <div class="analyticsCardTopItemBar"><span style="width:{{ round($countFn($item) / $maxCount * 100) }}%;"></span></div>
                <span class="analyticsCardTopItemDesc">x{{ $countFn($item) }}</span>
            </div>
        </div>
        @endforeach

        @if($items->count() > 5)
        <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
            <div class="actionTabViewOrderCardTop">
                <button type="button" onclick="expandTopList(this)">
                    <img src="/assets/img/arrow-down.svg" alt="">
                    <span>Показать больше</span>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>