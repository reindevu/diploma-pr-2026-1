<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;

final class PromoCodeRepository
{
    public static function all(): array
    {
        $statement = Database::connection()->query(
            'SELECT
                pc.*,
                (SELECT COUNT(*) FROM orders o WHERE o.promo_code_id = pc.id) AS usage_count
             FROM promo_codes pc
             ORDER BY pc.created_at DESC, pc.id DESC'
        );

        return $statement->fetchAll();
    }

    public static function save(array $data, ?int $promoCodeId = null): void
    {
        $payload = self::normalize($data);
        $connection = Database::connection();

        if ($promoCodeId !== null) {
            $exists = $connection->prepare('SELECT 1 FROM promo_codes WHERE id = :id');
            $exists->execute(['id' => $promoCodeId]);

            if (!$exists->fetchColumn()) {
                throw new \RuntimeException('Промокод не найден.');
            }

            $statement = $connection->prepare(
                'UPDATE promo_codes
                 SET code = :code,
                     description = :description,
                     discount_type = :discount_type,
                     discount_value = :discount_value,
                     min_order_amount = :min_order_amount,
                     usage_limit = :usage_limit,
                     per_user_limit = :per_user_limit,
                     starts_at = :starts_at,
                     ends_at = :ends_at,
                     is_active = :is_active
                 WHERE id = :id'
            );

            $statement->execute([
                'id' => $promoCodeId,
                ...$payload,
            ]);

            return;
        }

        $statement = $connection->prepare(
            'INSERT INTO promo_codes (
                code, description, discount_type, discount_value, min_order_amount,
                usage_limit, per_user_limit, starts_at, ends_at, is_active
             ) VALUES (
                :code, :description, :discount_type, :discount_value, :min_order_amount,
                :usage_limit, :per_user_limit, :starts_at, :ends_at, :is_active
             )'
        );

        $statement->execute($payload);
    }

    public static function delete(int $promoCodeId): void
    {
        $statement = Database::connection()->prepare('DELETE FROM promo_codes WHERE id = :id');
        $statement->execute(['id' => $promoCodeId]);

        if ($statement->rowCount() === 0) {
            throw new \RuntimeException('Промокод не найден.');
        }
    }

    private static function normalize(array $data): array
    {
        $code = mb_strtoupper(trim((string) ($data['code'] ?? '')));
        $description = trim((string) ($data['description'] ?? '')) ?: null;
        $discountType = trim((string) ($data['discount_type'] ?? 'percent'));
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $minOrderAmount = max(0, (float) ($data['min_order_amount'] ?? 0));
        $usageLimit = trim((string) ($data['usage_limit'] ?? ''));
        $perUserLimit = trim((string) ($data['per_user_limit'] ?? ''));
        $startsAt = trim((string) ($data['starts_at'] ?? ''));
        $endsAt = trim((string) ($data['ends_at'] ?? ''));
        $isActive = isset($data['is_active']) && (string) $data['is_active'] !== '0';

        if ($code === '') {
            throw new \RuntimeException('Укажите код промокода.');
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            throw new \RuntimeException('Код промокода может содержать только латинские буквы, цифры, дефис и нижнее подчёркивание.');
        }

        if (!in_array($discountType, ['percent', 'fixed'], true)) {
            throw new \RuntimeException('Некорректный тип скидки.');
        }

        if ($discountValue <= 0) {
            throw new \RuntimeException('Сумма скидки должна быть больше нуля.');
        }

        if ($discountType === 'percent' && $discountValue > 100) {
            throw new \RuntimeException('Процент скидки не может быть больше 100.');
        }

        $usageLimitValue = $usageLimit !== '' ? (int) $usageLimit : null;
        $perUserLimitValue = $perUserLimit !== '' ? (int) $perUserLimit : null;

        if ($usageLimitValue !== null && $usageLimitValue <= 0) {
            throw new \RuntimeException('Общий лимит использований должен быть больше нуля.');
        }

        if ($perUserLimitValue !== null && $perUserLimitValue <= 0) {
            throw new \RuntimeException('Лимит на пользователя должен быть больше нуля.');
        }

        $startsAtValue = $startsAt !== '' ? str_replace('T', ' ', $startsAt) : null;
        $endsAtValue = $endsAt !== '' ? str_replace('T', ' ', $endsAt) : null;

        if ($startsAtValue !== null && $endsAtValue !== null && strtotime($endsAtValue) < strtotime($startsAtValue)) {
            throw new \RuntimeException('Дата окончания не может быть раньше даты начала.');
        }

        return [
            'code' => $code,
            'description' => $description,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_order_amount' => $minOrderAmount,
            'usage_limit' => $usageLimitValue,
            'per_user_limit' => $perUserLimitValue,
            'starts_at' => $startsAtValue,
            'ends_at' => $endsAtValue,
            'is_active' => $isActive ? 'true' : 'false',
        ];
    }
}
