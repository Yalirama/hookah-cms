<p style="color:var(--info); font-size:13px; margin-bottom:16px; padding:0 20px;">
    {{ $wikiPage->creator?->name ?? '—' }} · {{ $wikiPage->created_at->format('d.m.Y H:i') }}
    @if($wikiPage->updater)
        · обновил {{ $wikiPage->updater->name }} {{ $wikiPage->updated_at->format('d.m.Y H:i') }}
    @endif
</p>

@role('admin')
<a href="{{ route('wiki.edit', $wikiPage) }}" class="actionTabActionsButton --buttonPrimary" style="display:inline-block; margin:0 20px 16px;">✏️ Редактировать</a>
@endrole

<div class="wiki-content articleTabCnt" style="padding:0 20px;">
    {!! $wikiPage->content !!}
</div>

@role('admin')
@if($views && $views->count() > 0)
<div style="padding:0 20px; margin-top:20px;">
    <p class="analyticsCardTitle">Просмотры</p>
    <div style="max-height:300px; overflow-y:auto;">
        @foreach($views as $view)
        <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid rgba(255,255,255,0.05); font-size:13px;">
            <strong>{{ $view->user?->name ?? '—' }}</strong>
            <span style="color:var(--info);">{{ $view->viewed_at->format('d.m.Y H:i:s') }}</span>
        </div>
        @endforeach
    </div>
    <div style="margin-top:12px; font-size:13px; color:var(--info);">
        Всего просмотров: <strong>{{ $views->count() }}</strong> ·
        Уникальных мастеров: <strong>{{ $views->unique('user_id')->count() }}</strong>
    </div>
</div>
@endif
@endrole