<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    private const REMEMBER_COOKIE = 'ff_remember';
    private const REMEMBER_DAYS = 30;

    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            self::$cachedUser = self::userFromRememberCookie();
            return self::$cachedUser;
        }

        $statement = Database::connection()->prepare('SELECT * FROM users WHERE id = :id AND is_active = TRUE');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch() ?: null;

        if (!$user) {
            unset($_SESSION['user_id']);
        }

        self::$cachedUser = $user;

        return self::$cachedUser;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'admin';
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !$user['is_active']) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        self::$cachedUser = $user;

        if ($remember) {
            self::remember((int) $user['id']);
        } else {
            self::forgetRememberToken();
        }

        return true;
    }

    public static function register(array $data): array
    {
        $errors = [];

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirmation = (string) ($data['password_confirmation'] ?? '');

        if ($firstName === '') {
            $errors[] = 'Укажите имя.';
        }

        if ($lastName === '') {
            $errors[] = 'Укажите фамилию.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Укажите корректный email.';
        }

        try {
            $phone = normalize_phone($phone) ?? '';
        } catch (\RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }

        $errors = array_merge($errors, password_validation_errors($password));

        if ($password !== $passwordConfirmation) {
            $errors[] = 'Подтверждение пароля не совпадает.';
        }

        $exists = Database::connection()->prepare('SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email)');
        $exists->execute(['email' => $email]);

        if ($exists->fetchColumn()) {
            $errors[] = 'Пользователь с таким email уже существует.';
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash)
             VALUES (:first_name, :last_name, :email, :phone, :password_hash)
             RETURNING id'
        );

        $statement->execute([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $_SESSION['user_id'] = (int) $statement->fetchColumn();
        self::$cachedUser = null;

        if (isset($data['remember'])) {
            self::remember((int) $_SESSION['user_id']);
        }

        return ['success' => true, 'errors' => []];
    }

    public static function logout(): void
    {
        self::forgetRememberToken();
        unset($_SESSION['user_id']);
        self::$cachedUser = null;
    }

    private static function userFromRememberCookie(): ?array
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');

        if ($cookie === '' || !str_contains($cookie, ':')) {
            return null;
        }

        [$selector, $token] = explode(':', $cookie, 2);

        if ($selector === '' || $token === '') {
            self::clearRememberCookie();
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT rt.*, u.*
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.selector = :selector
               AND rt.expires_at > NOW()
               AND u.is_active = TRUE
             LIMIT 1'
        );
        $statement->execute(['selector' => $selector]);
        $row = $statement->fetch();

        if (!$row || !hash_equals((string) $row['token_hash'], hash('sha256', $token))) {
            self::clearRememberCookie();
            return null;
        }

        $_SESSION['user_id'] = (int) $row['user_id'];

        $user = [];
        foreach ($row as $key => $value) {
            if (is_string($key) && in_array($key, ['id', 'first_name', 'last_name', 'email', 'phone', 'password_hash', 'role', 'is_active', 'created_at', 'updated_at'], true)) {
                $user[$key] = $value;
            }
        }

        self::$cachedUser = $user;

        return $user;
    }

    private static function remember(int $userId): void
    {
        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:sP', time() + self::REMEMBER_DAYS * 86400);

        Database::connection()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, token_hash, user_agent, expires_at)
             VALUES (:user_id, :selector, :token_hash, :user_agent, :expires_at)'
        )->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'expires_at' => $expiresAt,
        ]);

        self::setRememberCookie($selector . ':' . $token, time() + self::REMEMBER_DAYS * 86400);
    }

    private static function forgetRememberToken(): void
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');

        if ($cookie !== '' && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            Database::connection()->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
                ->execute(['selector' => $selector]);
        }

        self::clearRememberCookie();
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function clearRememberCookie(): void
    {
        self::setRememberCookie('', time() - 3600);
        unset($_COOKIE[self::REMEMBER_COOKIE]);
    }
}
