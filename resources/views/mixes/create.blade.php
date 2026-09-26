@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Создать микс</h1></div>
    <a href="{{ route('mixes.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card" style="max-width:780px;">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('mixes.store') }}">
        @csrf
        @include('mixes.partials.form')
        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn btn-primary">Создать микс</button>
            <a href="{{ route('mixes.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection
