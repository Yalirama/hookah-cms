@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div><h1>Добавить гостя</h1></div>
    <a href="{{ route('guests.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card" style="max-width:560px;">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guests.store') }}">
        @csrf
        <div class="form-group">
            <label>Имя <span style="color:red">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Имя гостя">
        </div>
        <div class="form-group">
            <label>Username <span style="color:#999; font-size:12px;">(если не указать — сгенерируется автоматически)</span></label>
            <input type="text" name="username" value="{{ old('username') }}" placeholder="например: alibek_vip">
        </div>
        <div class="form-group">
            <label>Телефон</label>
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+7 777 000 00 00">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="guest@example.com">
        </div>
        <div class="form-group">
            <label>Заметка</label>
            <textarea name="note" rows="3" placeholder="Что-то важное о госте...">{{ old('note') }}</textarea>
        </div>
        <div style="display:flex; gap:10px;">
            <button type="submit" class="btn btn-primary">Добавить</button>
            <a href="{{ route('guests.index') }}" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
</div>
@endsection