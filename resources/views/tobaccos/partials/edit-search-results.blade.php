@forelse($groups as $group)
    @include('tobaccos.partials.edit-brand-card', ['brand' => $group->first()->brand, 'tobaccos' => $group, 'lazy' => false])
@empty
    <p style="padding:20px; color:var(--info); text-align:center;">Ничего не найдено</p>
@endforelse
@if($truncated)
    <p style="padding:12px; color:var(--info); text-align:center;">Показаны первые {{ $limit }}, уточните запрос</p>
@endif