@extends('layouts.app')

@push('styles')
<style>
.wiki-content h1 { font-size:26px; font-weight:bold; margin:24px 0 12px; }
.wiki-content h2 { font-size:22px; font-weight:bold; margin:20px 0 10px; }
.wiki-content h3 { font-size:18px; font-weight:bold; margin:16px 0 8px; }
.wiki-content p  { margin:8px 0; line-height:1.7; font-size:15px; }
.wiki-content ul, .wiki-content ol { margin:8px 0 8px 24px; line-height:1.7; }
.wiki-content li { margin:4px 0; font-size:15px; }
.wiki-content table { width:100%; border-collapse:collapse; margin:16px 0; }
.wiki-content table th { background:#f8f8f8; padding:10px 12px; text-align:left; border:1px solid #ddd; font-size:14px; }
.wiki-content table td { padding:10px 12px; border:1px solid #ddd; font-size:14px; }
.wiki-content img { max-width:100%; border-radius:8px; margin:12px 0; }
.wiki-content blockquote { border-left:4px solid #c9a84c; padding:8px 16px; margin:12px 0; background:#fffbe6; border-radius:0 6px 6px 0; }
.wiki-content a { color:#c9a84c; text-decoration:underline; }
</style>
@endpush

@section('content')
<header>
    <div class="headerTop">
        <h1>{{ $wikiPage->title }}</h1>
        <a href="{{ route('wiki.index') }}" class="headerUser">
            <img src="/assets/img/close-icon.svg" alt="">
        </a>
    </div>
</header>

@include('wiki.partials._article-content', ['wikiPage' => $wikiPage, 'views' => $views])
@endsection