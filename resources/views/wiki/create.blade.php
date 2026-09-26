@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Новая статья</h1></div>
    <a href="{{ route('wiki.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('wiki.store') }}">
        @csrf
        @include('wiki.partials.form', ['maxSort' => $maxSort])
        <div style="display:flex; gap:10px; margin-top:16px;">
            <button type="submit" class="btn btn-primary">Создать</button>
            <a href="{{ route('wiki.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection