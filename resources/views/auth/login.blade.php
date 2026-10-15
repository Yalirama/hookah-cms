<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="apple-touch-icon" href="/assets/img/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/icons/icon-192.png">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; background: #0F0E0C; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-box { background: #181612; border-radius: 12px; padding: 40px; width: 360px; border: 1px solid #231F18; }
        .login-box h1 { color: #fff; font-size: 22px;display: flex;flex-direction:column;}
        .login-box p { color: #999; font-size: 14px; margin-bottom: 12px; }
        .login-box h1 span {font-size: 14px; color: #CDAA7D; margin-bottom: 12px;}
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; color: #ccc; font-size: 13px; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px 14px; background: #221E1A; border: 1px solid #231F18; border-radius: 6px; color: #fff; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: #c9a84c; }
        .btn { width: 100%; padding: 12px; background: #c9a84c; color: #fff; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; margin-top: 8px; }
        .btn:hover { background: #b8943d; }
        .error { background: #3a1f1f; color: #e07070; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>Hookah CRM<span>by Your Solutions</span></h1>
        <p>Войдите в систему</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" value="{{ old('login') }}" placeholder="Введите логин" autofocus>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Введите пароль">
            </div>
            <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="remember" id="remember" value="1" style="width:auto;">
                <label for="remember" style="margin:0; cursor:pointer;">Запомнить меня</label>
            </div>
            <button type="submit" class="btn">Войти</button>
        </form>
    </div>
</body>
</html>