@forelse($logs as $log)
    <div class="actionTabHistoryItem {{ $log->action === 'deactivated' ? '--tabaccoRemove' : '--tabaccoAdd' }}" data-log-id="{{ $log->id }}">
        <div class="actionTabHistoryItemLeft">
            <div class="actionTabHistoryItemCircle"><span></span></div>
            <div class="actionTabHistoryItemInfo">
                <p class="actionTabHistoryItemSubtitle">{{ $log->venueTobacco->tobacco->brand?->name }}: <span>{{ $log->venueTobacco->tobacco->name }}</span></p>
                <div class="actionTabHistoryItemRow">
                    <img src="/assets/img/master-done.svg" alt="">
                    <span>{{ $log->user?->name ?? '—' }}</span>
                </div>
                @if($log->comment)
                <div class="actionTabHistoryItemComment"><p>{{ $log->comment }}</p></div>
                @endif
            </div>
        </div>
        <div class="actionTabHistoryItemTime">
            <span>{{ $log->created_at->format('H:i') }}</span>
            <span>{{ $log->created_at->format('d.m.Y') }}</span>
        </div>
    </div>
@empty
    <p style="color:var(--info); text-align:center; padding:20px;">Действий пока нет</p>
@endforelse

@if($hasMore && $logs->count())
    <button type="button" class="btnOpen" style="margin-bottom:10px;" onclick="loadMoreDeactivationLogs({{ $logs->last()->id }})" id="deactivationLogMoreBtn">Загрузить ещё</button>
@endif