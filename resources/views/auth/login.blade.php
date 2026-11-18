<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <link rel="apple-touch-icon" href="/assets/img/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/img/icons/icon-192.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <style>
        :root { --resizer: 320; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Nunito", system-ui, sans-serif; background: #FFF3EA; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: calc(12vw / var(--resizer) * 100); }
        .login-box { background: #FFFFFF; border-radius: calc(22vw / var(--resizer) * 100); padding: calc(24vw / var(--resizer) * 100); width: 100%; max-width: calc(300vw / var(--resizer) * 100); border: calc(1vw / var(--resizer) * 100) solid #F1DDD0; box-shadow: 0 calc(6vw / var(--resizer) * 100) calc(18vw / var(--resizer) * 100) rgba(212,64,26,.08); }
        .login-box h1 { color: #3A2218; font-size: calc(26vw / var(--resizer) * 100); font-weight: 800; letter-spacing: -.02em; display: flex; flex-direction: column; }
        .login-box p { color: #7D5F52; font-size: calc(13vw / var(--resizer) * 100); margin-bottom: calc(14vw / var(--resizer) * 100); }
        .login-box h1 span { font-size: calc(12vw / var(--resizer) * 100); font-weight: 600; color: #7D5F52; margin-bottom: calc(10vw / var(--resizer) * 100); letter-spacing: 0; }
        .form-group { margin-bottom: calc(14vw / var(--resizer) * 100); }
        .form-group label { display: block; color: #7D5F52; font-size: calc(11vw / var(--resizer) * 100); font-weight: 700; margin-bottom: calc(5vw / var(--resizer) * 100); }
        .form-group input { width: 100%; min-height: calc(42vw / var(--resizer) * 100); padding: 0 calc(14vw / var(--resizer) * 100); background: #FFF1E8; border: calc(1vw / var(--resizer) * 100) solid #F1DDD0; border-radius: calc(14vw / var(--resizer) * 100); color: #3A2218; font-family: inherit; font-size: calc(14vw / var(--resizer) * 100); outline: none; }
        .form-group input:focus { border-color: #D4401A; }
        .btn { width: 100%; min-height: calc(44vw / var(--resizer) * 100); background: #D4401A; color: #fff; border: none; border-radius: calc(40vw / var(--resizer) * 100); font-family: inherit; font-size: calc(13vw / var(--resizer) * 100); font-weight: 700; cursor: pointer; margin-top: calc(8vw / var(--resizer) * 100); }
        .btn:hover { background: #B93410; }
        .error { background: #FDE4E2; color: #B42318; padding: calc(10vw / var(--resizer) * 100) calc(14vw / var(--resizer) * 100); border-radius: calc(12vw / var(--resizer) * 100); font-size: calc(12vw / var(--resizer) * 100); margin-bottom: calc(14vw / var(--resizer) * 100); }
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