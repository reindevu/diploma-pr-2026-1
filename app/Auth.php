<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    private static ?array $cachedUser = null;

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            self::$cachedUser = null;
            return null;
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

    public static function attempt(string $email, string $password): bool
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

        if (mb_strlen($password) < 8) {
            $errors[] = 'Пароль должен содержать минимум 8 символов.';
        }

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

        return ['success' => true, 'errors' => []];
    }

    public static function logout(): void
    {
        unset($_SESSION['user_id']);
        self::$cachedUser = null;
    }
}
