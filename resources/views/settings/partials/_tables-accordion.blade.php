@php $tablesByType = $tables->groupBy('type_id'); @endphp
@foreach($tableTypes->whereNull('deleted_at') as $type)
    @php $typeTables = $tablesByType->get($type->id, collect()); @endphp
    <div class="labelGuestAccordionCard" data-open="0" data-type-key="{{ $type->id }}">
        <button type="button" class="labelGuestAccordionHead" onclick="toggleGuestAccordion(this)">
            <span>{{ $type->name }} ({{ $typeTables->count() }})</span>
            <img src="/assets/img/arrow-down.svg" class="labelGuestAccordionArrow" alt="">
        </button>
        <div class="labelGuestAccordionBody">
            <div class="labelGuestAccordionBodyInner" data-tables-group="{{ $type->id }}">
                @forelse($typeTables as $table)
                    @include('settings.partials._table-row', ['table' => $table])
                @empty
                    <p style="color:var(--info); font-size:13px; padding:12px 0;">Нет столов</p>
                @endforelse
            </div>
        </div>
    </div>
@endforeach

@php $noTypeTables = $tablesByType->get(null, collect()); @endphp
<div class="labelGuestAccordionCard" data-open="0" data-type-key="none">
    <button type="button" class="labelGuestAccordionHead" onclick="toggleGuestAccordion(this)">
        <span>Без типа ({{ $noTypeTables->count() }})</span>
        <img src="/assets/img/arrow-down.svg" class="labelGuestAccordionArrow" alt="">
    </button>
    <div class="labelGuestAccordionBody">
        <div class="labelGuestAccordionBodyInner" data-tables-group="none">
            @forelse($noTypeTables as $table)
                @include('settings.partials._table-row', ['table' => $table])
            @empty
                <p style="color:var(--info); font-size:13px; padding:12px 0;">Нет столов</p>
            @endforelse
        </div>
    </div>
</div>