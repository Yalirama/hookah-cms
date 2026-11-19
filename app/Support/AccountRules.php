<?php

namespace App\Support;

class AccountRules
{
    public const DOMAIN = 'iys.kz';

    public static function login(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [$value, 'Введите логин.'];
        }
        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            if (strtolower($domain) !== self::DOMAIN) {
                return [$value, 'Домен не допустим. Пишите логин без домена или с @' . self::DOMAIN . '.'];
            }
        } else {
            $local = $value;
        }
        if (preg_match('/\p{Lu}/u', $local)) {
            return [$value, 'Логин только строчными буквами.'];
        }
        if (preg_match('/[^a-z0-9_.\-]/u', $local, $m)) {
            return [$value, 'Символ «' . $m[0] . '» не допустим. Разрешены строчные латинские буквы, цифры и _ . -'];
        }
        if (strlen($local) < 3) {
            return [$value, 'Логин не короче 3 символов.'];
        }

        return [$local . '@' . self::DOMAIN, null];
    }

    public static function password(string $password, ?string $login = null): ?string
    {
        $missing = [];
        if (mb_strlen($password) < 8) $missing[] = 'не менее 8 символов';
        if (!preg_match('/\p{Lu}/u', $password)) $missing[] = 'заглавная буква';
        if (!preg_match('/\d/', $password)) $missing[] = 'цифра';
        if (!preg_match('/[^\p{L}\d]/u', $password)) $missing[] = 'символ';
        if ($missing) {
            return 'В пароле нужно: ' . implode(', ', $missing) . '.';
        }
        $local = $login ? strtolower(explode('@', $login)[0]) : '';
        if (strlen($local) >= 3 && str_contains(strtolower($password), $local)) {
            return 'Пароль не должен содержать логин.';
        }

        return null;
    }
}
