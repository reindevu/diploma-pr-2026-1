<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class UserRepository
{
    public static function updateProfile(int $userId, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 email = :email,
                 phone = :phone
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $userId,
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'email' => trim((string) $data['email']),
            'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
        ]);
    }

    public static function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $statement = Database::connection()->prepare('SELECT password_hash FROM users WHERE id = :id');
        $statement->execute(['id' => $userId]);
        $hash = $statement->fetchColumn();

        if (!$hash || !password_verify($currentPassword, (string) $hash)) {
            return false;
        }

        Database::connection()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        )->execute([
            'id' => $userId,
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        return true;
    }

    public static function addresses(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public static function saveAddress(int $userId, array $data): void
    {
        $isDefault = isset($data['is_default']);

        if ($isDefault) {
            Database::connection()->prepare('UPDATE user_addresses SET is_default = FALSE WHERE user_id = :user_id')
                ->execute(['user_id' => $userId]);
        }

        Database::connection()->prepare(
            'INSERT INTO user_addresses (
                user_id, label, recipient_name, recipient_phone, full_address, entrance, floor, apartment, comment, is_default
             ) VALUES (
                :user_id, :label, :recipient_name, :recipient_phone, :full_address, :entrance, :floor, :apartment, :comment, :is_default
             )'
        )->execute([
            'user_id' => $userId,
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
            'recipient_name' => trim((string) ($data['recipient_name'] ?? '')) ?: null,
            'recipient_phone' => trim((string) ($data['recipient_phone'] ?? '')) ?: null,
            'full_address' => trim((string) ($data['full_address'] ?? '')),
            'entrance' => trim((string) ($data['entrance'] ?? '')) ?: null,
            'floor' => trim((string) ($data['floor'] ?? '')) ?: null,
            'apartment' => trim((string) ($data['apartment'] ?? '')) ?: null,
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'is_default' => $isDefault,
        ]);
    }
}
