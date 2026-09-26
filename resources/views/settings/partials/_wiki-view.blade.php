<div class="articleTabCnt">
    <div class="wiki-content">
        {!! $wikiPage->content !!}
    </div>

    @if($views->count() > 0)
    <div class="row02c23">
        <span class="title">Просмотры</span>
        @foreach($views as $view)
        <div class="item">
            <p>{{ $view->user?->name ?? '—' }}</p>
            <span>{{ $view->viewed_at->format('d.m.Y H:i:s') }}</span>
        </div>
        @endforeach
    </div>
    @endif
</div>