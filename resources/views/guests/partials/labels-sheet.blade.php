@php $types = ['positive' => 'Положительно', 'neutral' => 'Нейтрально', 'negative' => 'Отрицательно']; @endphp

<form onsubmit="return false;" class="actionTabViewOrderCardBtm">
    <div class="search">
        <img src="/assets/img/search-icon.svg" alt="">
        <input type="text" id="guestLabelSearch" placeholder="Название..." oninput="filterGuestLabels(this.value)">
    </div>
</form>

<div class="actionTabViewTabaccosList  --actionTabViewTabaccosList-8sz43" id="guestLabelsListWrap" data-guest-id="{{ $guest->id }}">
    @foreach($types as $type => $typeLabel)
    <div class="actionTabViewOrderCard actionTabViewOrderCardHide {{ $loop->first ? 'actionTabViewOrderCardShow' : '' }} guestLabelBrandCard">
        <div class="actionTabViewOrderCardTop" onclick="toggleCollapse(this)">
            <p class="actionTabTitle">{{ $typeLabel }}</p>
            <button type="button" class="arrowDown"><img src="/assets/img/arrow-down.svg" alt=""></button>
        </div>

        <div class="actionTabSearchSectionResult">
            @foreach($labels as $label)
                @php
                    $current = $assigned->get($label->id);
                    $isActiveHere = $current && $current->type === $type;
                @endphp
                <div class="ordersCardTop guestLabelRow {{ $isActiveHere ? '--actionTabSearchSectionResultActive' : '' }}"
+                     data-label-name="{{ mb_strtolower($label->name, 'UTF-8') }}"
+                     data-guest-id="{{ $guest->id }}"
+                     data-label-id="{{ $label->id }}"
+                     data-type="{{ $type }}"
+                     data-assignment="{{ $current?->id }}"
+                     onclick="toggleGuestLabel(this)">
                     <div class="ordersCardUser">
                        <div class="ordersCardUserInfo">
                            <div class="actionTabViewOrderProductListBody"><p>{{ $label->name }}</p></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>