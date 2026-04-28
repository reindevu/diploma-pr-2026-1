<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class UserRepository
{
    private static function normalizeAddressData(array $data): array
    {
        $fullAddress = trim((string) ($data['full_address'] ?? ''));

        if ($fullAddress === '') {
            throw new \RuntimeException('Укажите полный адрес.');
        }

        return [
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
            'recipient_name' => trim((string) ($data['recipient_name'] ?? '')) ?: null,
            'recipient_phone' => normalize_phone($data['recipient_phone'] ?? null),
            'full_address' => $fullAddress,
            'entrance' => trim((string) ($data['entrance'] ?? '')) ?: null,
            'floor' => trim((string) ($data['floor'] ?? '')) ?: null,
            'apartment' => trim((string) ($data['apartment'] ?? '')) ?: null,
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'is_default' => isset($data['is_default']),
        ];
    }

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
            'phone' => normalize_phone($data['phone'] ?? null),
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
        $address = self::normalizeAddressData($data);
        $isDefault = $address['is_default'];

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
            'label' => $address['label'],
            'recipient_name' => $address['recipient_name'],
            'recipient_phone' => $address['recipient_phone'],
            'full_address' => $address['full_address'],
            'entrance' => $address['entrance'],
            'floor' => $address['floor'],
            'apartment' => $address['apartment'],
            'comment' => $address['comment'],
            'is_default' => $isDefault ? 'true' : 'false',
        ]);
    }

    public static function updateAddress(int $userId, int $addressId, array $data): void
    {
        $address = self::normalizeAddressData($data);
        $isDefault = $address['is_default'];
        $connection = Database::connection();

        $exists = $connection->prepare('SELECT 1 FROM user_addresses WHERE id = :id AND user_id = :user_id');
        $exists->execute([
            'id' => $addressId,
            'user_id' => $userId,
        ]);

        if (!$exists->fetchColumn()) {
            throw new \RuntimeException('Адрес не найден.');
        }

        if ($isDefault) {
            $connection->prepare('UPDATE user_addresses SET is_default = FALSE WHERE user_id = :user_id')
                ->execute(['user_id' => $userId]);
        }

        $connection->prepare(
            'UPDATE user_addresses
             SET label = :label,
                 recipient_name = :recipient_name,
                 recipient_phone = :recipient_phone,
                 full_address = :full_address,
                 entrance = :entrance,
                 floor = :floor,
                 apartment = :apartment,
                 comment = :comment,
                 is_default = :is_default
             WHERE id = :id AND user_id = :user_id'
        )->execute([
            'id' => $addressId,
            'user_id' => $userId,
            'label' => $address['label'],
            'recipient_name' => $address['recipient_name'],
            'recipient_phone' => $address['recipient_phone'],
            'full_address' => $address['full_address'],
            'entrance' => $address['entrance'],
            'floor' => $address['floor'],
            'apartment' => $address['apartment'],
            'comment' => $address['comment'],
            'is_default' => $isDefault ? 'true' : 'false',
        ]);
    }

    public static function deleteAddress(int $userId, int $addressId): void
    {
        $connection = Database::connection();
        $statement = $connection->prepare(
            'SELECT id, is_default FROM user_addresses WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute([
            'id' => $addressId,
            'user_id' => $userId,
        ]);

        $address = $statement->fetch();

        if (!$address) {
            throw new \RuntimeException('Адрес не найден.');
        }

        $connection->prepare('DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id')
            ->execute([
                'id' => $addressId,
                'user_id' => $userId,
            ]);

        if (!empty($address['is_default'])) {
            $nextDefault = $connection->prepare(
                'SELECT id FROM user_addresses WHERE user_id = :user_id ORDER BY id DESC LIMIT 1'
            );
            $nextDefault->execute(['user_id' => $userId]);
            $nextDefaultId = $nextDefault->fetchColumn();

            if ($nextDefaultId) {
                $connection->prepare('UPDATE user_addresses SET is_default = TRUE WHERE id = :id AND user_id = :user_id')
                    ->execute([
                        'id' => (int) $nextDefaultId,
                        'user_id' => $userId,
                    ]);
            }
        }
    }
}
