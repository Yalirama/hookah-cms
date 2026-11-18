@if($tableMoves->count() > 0)
    <div class="{{ $wrapClass }}">
        <p class="analyticsCardTitle">Пересадки со столов</p>
        <div class="tableMovesList">
            @foreach($tableMoves as $row)
                <div class="tableMovesItem">
                    <button type="button" class="tableMovesHead" onclick="this.parentElement.classList.toggle('open')">
                        <span class="tableMovesName">{{ $row['name'] }}</span>
                        <span class="tableMovesMeta">{{ $row['count'] }} {{ \App\Support\Plural::ru($row['count'], 'пересадка', 'пересадки', 'пересадок') }} · {{ $row['pct'] }}% заказов</span>
                        <img src="/assets/img/arrow-down.svg" alt="">
                    </button>
                    <div class="tableMovesBar"><span style="width:{{ min(100, $row['pct']) }}%"></span></div>
                    <div class="tableMovesBody">
                        @foreach($row['moves'] as $m)
                            <div class="tableMovesRow">
                                <div class="tableMovesRowTop">
                                    <span class="tableMovesOrder">#{{ $m['order_id'] }}</span>
                                    <span class="tableMovesTo">→ {{ $m['to'] }}</span>
                                    <button type="button" class="tableMovesOpen" onclick="{{ $openFn ?? 'openOrderSheet' }}({{ $m['order_id'] }})">Открыть</button>
                                </div>
                                <p class="tableMovesWhen">{{ $m['when'] }} · {{ $m['who'] }}</p>
                                <p class="tableMovesReason {{ $m['reason'] === '' ? 'none' : '' }}">{{ $m['reason'] !== '' ? $m['reason'] : 'Причина не указана' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

