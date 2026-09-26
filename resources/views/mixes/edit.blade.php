@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Редактировать микс</h1><p>{{ $mix->name }}</p></div>
    <a href="{{ route('mixes.show', $mix) }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card" style="max-width:780px;">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('mixes.update', $mix) }}">
        @csrf @method('PUT')
        @include('mixes.partials.form')
        <div class="form-group">
            <label>Комментарий к правке</label>
            <input type="text" name="comment" placeholder="Что изменили и почему...">
        </div>
        <div style="display:flex; gap:10px; margin-top:8px;">
            <button type="submit" class="btn btn-primary">Сохранить</button>
            <a href="{{ route('mixes.show', $mix) }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection