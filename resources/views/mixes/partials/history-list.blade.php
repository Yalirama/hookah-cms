<div class="actionTabViewOrderCard actionTabViewOrderCardShow">
    <div class="actionTabViewOrderCardBtm actionTabViewOrderCardBtmColumn">
        <div class="actionTabHistorySUV">
            <span class="--isEdit">Изменил </span> • <span class="--isBlue">Добавил </span> • <span class="--isRed"> Убрал</span>
        </div>

        @forelse($revisions as $rev)
            @if(($rev->changed_fields['action'] ?? null) === 'created')
                <div class="actionTabHistoryItem --tabaccoAdd">
                    <div class="actionTabHistoryItemLeft">
                        <div class="actionTabHistoryItemCircle"><span></span></div>
                        <div class="actionTabHistoryItemInfo">
                            <p class="actionTabHistoryItemSubtitle">Микс создан</p>
                            <div class="actionTabHistoryItemRow">
                                <img src="/assets/img/master-done.svg" alt="">
                                <span>{{ $rev->user?->name ?? '—' }}</span>
                            </div>
                            @if($rev->comment)<div class="actionTabHistoryItemComment"><p>{{ $rev->comment }}</p></div>@endif
                        </div>
                    </div>
                    <div class="actionTabHistoryItemTime">
                        <span>{{ $rev->created_at->format('H:i') }}</span>
                        <span>{{ $rev->created_at->format('d.m.Y') }}</span>
                    </div>
                </div>
            @else
                @foreach($rev->changed_fields as $change)
                    @php
                        $dotClass = match($change['type'] ?? '') {
                            'added' => '--tabaccoAdd',
                            'removed' => '--tabaccoRemove',
                            default => '--tabaccoEdit',
                        };
                    @endphp
                    <div class="actionTabHistoryItem {{ $dotClass }}">
                        <div class="actionTabHistoryItemLeft">
                            <div class="actionTabHistoryItemCircle"><span></span></div>
                            <div class="actionTabHistoryItemInfo">
                                @if(($change['type'] ?? '') === 'edit')
                                    <p class="actionTabHistoryItemSubtitle">
                                        {{ $change['label'] }} <br>
                                        <span>{{ $change['old'] }}</span> → <span>{{ $change['new'] }}</span>
                                    </p>
                                @else
                                    <p class="actionTabHistoryItemSubtitle">
                                        {{ $change['label'] }} <br>
                                        <span>{{ $change['value'] }}</span>
                                    </p>
                                @endif
                                <div class="actionTabHistoryItemRow">
                                    <img src="/assets/img/master-done.svg" alt="">
                                    <span>{{ $rev->user?->name ?? '—' }}</span>
                                </div>
                                @if($rev->comment)<div class="actionTabHistoryItemComment"><p>{{ $rev->comment }}</p></div>@endif
                            </div>
                        </div>
                        <div class="actionTabHistoryItemTime">
                            <span>{{ $rev->created_at->format('H:i') }}</span>
                            <span>{{ $rev->created_at->format('d.m.Y') }}</span>
                        </div>
                    </div>
                @endforeach
            @endif
        @empty
            <p style="color:var(--info); text-align:center; padding:20px;">Изменений пока нет</p>
        @endforelse
    </div>
</div>