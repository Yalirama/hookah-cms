@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Создать ярлык</h1></div>
    <a href="{{ route('labels.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card" style="max-width:560px;">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('labels.store') }}">
        @csrf
        <div class="form-group">
            <label>Название <span style="color:red">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Например: Любит холодок">
        </div>
        <div class="form-group">
            <label>Описание</label>
            <textarea name="description" rows="3" placeholder="Что означает этот ярлык...">{{ old('description') }}</textarea>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">Создать</button>
            <a href="{{ route('labels.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection