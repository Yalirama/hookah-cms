@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Редактировать статью</h1><p>{{ $wikiPage->title }}</p></div>
    <a href="{{ route('wiki.show', $wikiPage) }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('wiki.update', $wikiPage) }}">
        @csrf @method('PUT')
        @include('wiki.partials.form', ['page' => $wikiPage])
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="{{ route('wiki.show', $wikiPage) }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection