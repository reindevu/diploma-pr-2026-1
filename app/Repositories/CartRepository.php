<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Auth;
use App\Database;

final class CartRepository
{
    public static function current(): array
    {
        $cart = self::findOrCreate();
        $items = self::items((int) $cart['id']);
        $promoCode = self::promoCode($cart);
        $subtotal = 0.0;
        $itemsCount = 0;

        foreach ($items as &$item) {
            $item['line_total'] = (float) $item['unit_price'] * (int) $item['quantity'];
            $subtotal += $item['line_total'];
            $itemsCount += (int) $item['quantity'];
        }

        $discount = self::discountAmount($subtotal, $promoCode);

        return [
            'cart' => $cart,
            'items' => $items,
            'promo_code' => $promoCode,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'items_count' => $itemsCount,
        ];
    }

    public static function cartCount(): int
    {
        $connection = Database::connection();
        $userId = Auth::id();

        if ($userId) {
            $statement = $connection->prepare(
                "SELECT COALESCE(SUM(ci.quantity), 0)
                 FROM carts c
                 LEFT JOIN cart_items ci ON ci.cart_id = c.id
                 WHERE c.user_id = :user_id AND c.status = 'active'"
            );
            $statement->execute(['user_id' => $userId]);

            return (int) $statement->fetchColumn();
        }

        $statement = $connection->prepare(
            "SELECT COALESCE(SUM(ci.quantity), 0)
             FROM carts c
             LEFT JOIN cart_items ci ON ci.cart_id = c.id
             WHERE c.guest_token = :guest_token AND c.status = 'active'"
        );
        $statement->execute(['guest_token' => $_SESSION['guest_token'] ?? '']);

        return (int) $statement->fetchColumn();
    }

    public static function addItem(int $variantId, int $quantity): void
    {
        $quantity = max(1, $quantity);
        $cart = self::findOrCreate();

        $variantStatement = Database::connection()->prepare(
            'SELECT id, price FROM product_variants WHERE id = :id AND is_active = TRUE'
        );
        $variantStatement->execute(['id' => $variantId]);
        $variant = $variantStatement->fetch();

        if (!$variant) {
            return;
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO cart_items (cart_id, product_variant_id, quantity, unit_price)
             VALUES (:cart_id, :variant_id, :quantity, :unit_price)
             ON CONFLICT (cart_id, product_variant_id)
             DO UPDATE SET quantity = cart_items.quantity + EXCLUDED.quantity, unit_price = EXCLUDED.unit_price'
        );

        $statement->execute([
            'cart_id' => $cart['id'],
            'variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price' => $variant['price'],
        ]);
    }

    public static function updateItem(int $itemId, int $quantity): void
    {
        $cart = self::findOrCreate();

        if ($quantity <= 0) {
            self::removeItem($itemId);
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE cart_items SET quantity = :quantity WHERE id = :id AND cart_id = :cart_id'
        );
        $statement->execute([
            'quantity' => $quantity,
            'id' => $itemId,
            'cart_id' => $cart['id'],
        ]);
    }

    public static function removeItem(int $itemId): void
    {
        $cart = self::findOrCreate();
        $statement = Database::connection()->prepare('DELETE FROM cart_items WHERE id = :id AND cart_id = :cart_id');
        $statement->execute([
            'id' => $itemId,
            'cart_id' => $cart['id'],
        ]);
    }

    public static function applyPromoCode(string $code): array
    {
        $cart = self::current();
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            return ['success' => false, 'message' => 'Введите промокод.'];
        }

        $statement = Database::connection()->prepare(
            'SELECT * FROM promo_codes WHERE UPPER(code) = UPPER(:code) AND is_active = TRUE LIMIT 1'
        );
        $statement->execute(['code' => $normalizedCode]);
        $promoCode = $statement->fetch();

        if (!$promoCode) {
            return ['success' => false, 'message' => 'Промокод не найден.'];
        }

        $nowCheck = Database::connection()->prepare(
            'SELECT 1
             WHERE (:starts_at IS NULL OR :starts_at <= NOW())
               AND (:ends_at IS NULL OR :ends_at >= NOW())'
        );
        $nowCheck->execute([
            'starts_at' => $promoCode['starts_at'],
            'ends_at' => $promoCode['ends_at'],
        ]);

        if (!$nowCheck->fetchColumn()) {
            return ['success' => false, 'message' => 'Срок действия промокода истёк или ещё не начался.'];
        }

        if ((float) $cart['subtotal'] < (float) $promoCode['min_order_amount']) {
            return ['success' => false, 'message' => 'Сумма заказа слишком мала для этого промокода.'];
        }

        $usageCount = Database::connection()->prepare('SELECT COUNT(*) FROM orders WHERE promo_code_id = :promo_code_id');
        $usageCount->execute(['promo_code_id' => $promoCode['id']]);
        $totalUsages = (int) $usageCount->fetchColumn();

        if ($promoCode['usage_limit'] !== null && $totalUsages >= (int) $promoCode['usage_limit']) {
            return ['success' => false, 'message' => 'Лимит использования промокода исчерпан.'];
        }

        if ($promoCode['per_user_limit'] !== null && Auth::id()) {
            $perUserUsage = Database::connection()->prepare(
                'SELECT COUNT(*) FROM orders WHERE promo_code_id = :promo_code_id AND user_id = :user_id'
            );
            $perUserUsage->execute([
                'promo_code_id' => $promoCode['id'],
                'user_id' => Auth::id(),
            ]);

            if ((int) $perUserUsage->fetchColumn() >= (int) $promoCode['per_user_limit']) {
                return ['success' => false, 'message' => 'Вы уже использовали этот промокод максимально допустимое число раз.'];
            }
        }

        $update = Database::connection()->prepare('UPDATE carts SET promo_code_id = :promo_code_id WHERE id = :id');
        $update->execute([
            'promo_code_id' => $promoCode['id'],
            'id' => $cart['cart']['id'],
        ]);

        return ['success' => true, 'message' => 'Промокод применён.'];
    }

    public static function clearPromoCode(): void
    {
        $cart = self::findOrCreate();
        Database::connection()->prepare('UPDATE carts SET promo_code_id = NULL WHERE id = :id')->execute(['id' => $cart['id']]);
    }

    private static function findOrCreate(): array
    {
        $connection = Database::connection();
        $userId = Auth::id();

        if ($userId) {
            $statement = $connection->prepare(
                "SELECT * FROM carts WHERE user_id = :user_id AND status = 'active' LIMIT 1"
            );
            $statement->execute(['user_id' => $userId]);
            $cart = $statement->fetch();

            if ($cart) {
                return $cart;
            }

            $guestCartStatement = $connection->prepare(
                "SELECT * FROM carts WHERE guest_token = :guest_token AND status = 'active' LIMIT 1"
            );
            $guestCartStatement->execute(['guest_token' => guest_token()]);
            $guestCart = $guestCartStatement->fetch();

            if ($guestCart) {
                $connection->prepare(
                    'UPDATE carts SET user_id = :user_id, guest_token = NULL WHERE id = :id'
                )->execute([
                    'user_id' => $userId,
                    'id' => $guestCart['id'],
                ]);

                $guestCart['user_id'] = $userId;
                $guestCart['guest_token'] = null;

                return $guestCart;
            }
        } else {
            $statement = $connection->prepare(
                "SELECT * FROM carts WHERE guest_token = :guest_token AND status = 'active' LIMIT 1"
            );
            $statement->execute(['guest_token' => guest_token()]);

            $cart = $statement->fetch();

            if ($cart) {
                return $cart;
            }
        }

        $insert = $connection->prepare(
            'INSERT INTO carts (user_id, guest_token, status)
             VALUES (:user_id, :guest_token, :status)
             RETURNING *'
        );
        $insert->execute([
            'user_id' => $userId,
            'guest_token' => $userId ? null : guest_token(),
            'status' => 'active',
        ]);

        return $insert->fetch();
    }

    private static function items(int $cartId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT ci.*, p.id AS product_id, p.name AS product_name, p.slug AS product_slug, p.short_description,
                    p.image_path, pv.size_cm
             FROM cart_items ci
             JOIN product_variants pv ON pv.id = ci.product_variant_id
             JOIN products p ON p.id = pv.product_id
             WHERE ci.cart_id = :cart_id
             ORDER BY ci.id DESC'
        );
        $statement->execute(['cart_id' => $cartId]);

        return $statement->fetchAll();
    }

    private static function promoCode(array $cart): ?array
    {
        if (empty($cart['promo_code_id'])) {
            return null;
        }

        $statement = Database::connection()->prepare('SELECT * FROM promo_codes WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $cart['promo_code_id']]);

        return $statement->fetch() ?: null;
    }

    public static function discountAmount(float $subtotal, ?array $promoCode): float
    {
        if (!$promoCode || $subtotal <= 0) {
            return 0.0;
        }

        if ((float) $promoCode['min_order_amount'] > $subtotal) {
            return 0.0;
        }

        if ($promoCode['discount_type'] === 'percent') {
            return round($subtotal * ((float) $promoCode['discount_value'] / 100), 2);
        }

        return min($subtotal, (float) $promoCode['discount_value']);
    }
}
