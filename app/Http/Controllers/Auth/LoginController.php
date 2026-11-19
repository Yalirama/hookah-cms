<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($credentials['login']);
        $user = \App\Models\User::where('login', $login)->first();
        if (!$user && !str_contains($login, '@')) {
            $login .= '@' . \App\Support\AccountRules::DOMAIN;
            $user = \App\Models\User::where('login', $login)->first();
        }
        $credentials['login'] = $login;

        if (!$user || !$user->is_active) {
            return back()->withErrors(['login' => 'Пользователь не найден или деактивирован.']);
        }

        if (!Auth::attempt(['login' => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'Неверный логин или пароль.']);
        }

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->user()?->forceFill(['on_shift' => false])->save();
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}