<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Auth;
use App\Database;
use RuntimeException;

final class OrderRepository
{
    public static function createFromCart(array $payload): string
    {
        $cartData = CartRepository::current();
        $items = $cartData['items'];

        if ($items === []) {
            throw new RuntimeException('Корзина пуста.');
        }

        $deliveryMethod = $payload['delivery_method'] ?? 'delivery';
        $deliveryFee = $deliveryMethod === 'delivery' ? 200.0 : 0.0;
        $subtotal = (float) $cartData['subtotal'];
        $discount = (float) $cartData['discount'];
        $total = max(0, $subtotal - $discount + $deliveryFee);
        $user = Auth::user();

        $recipientName = trim((string) ($payload['recipient_name'] ?? (($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))));
        $recipientPhone = trim((string) ($payload['recipient_phone'] ?? ($user['phone'] ?? '')));
        $recipientEmail = trim((string) ($payload['recipient_email'] ?? ($user['email'] ?? '')));
        $deliveryAddress = trim((string) ($payload['delivery_address'] ?? ''));

        if ($recipientName === '' || $recipientPhone === '') {
            throw new RuntimeException('Укажите имя и телефон получателя.');
        }

        if ($deliveryMethod === 'delivery' && $deliveryAddress === '') {
            throw new RuntimeException('Для доставки нужен адрес.');
        }

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $orderNumber = 'FF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

            $statement = $connection->prepare(
                'INSERT INTO orders (
                    order_number, user_id, cart_id, promo_code_id, status, payment_method, payment_status,
                    delivery_method, recipient_name, recipient_phone, recipient_email, delivery_address,
                    order_comment, subtotal_amount, discount_amount, delivery_fee, total_amount
                 ) VALUES (
                    :order_number, :user_id, :cart_id, :promo_code_id, :status, :payment_method, :payment_status,
                    :delivery_method, :recipient_name, :recipient_phone, :recipient_email, :delivery_address,
                    :order_comment, :subtotal_amount, :discount_amount, :delivery_fee, :total_amount
                 ) RETURNING id'
            );

            $statement->execute([
                'order_number' => $orderNumber,
                'user_id' => Auth::id(),
                'cart_id' => $cartData['cart']['id'],
                'promo_code_id' => $cartData['cart']['promo_code_id'],
                'status' => 'new',
                'payment_method' => $payload['payment_method'] ?? 'cash',
                'payment_status' => 'pending',
                'delivery_method' => $deliveryMethod,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'recipient_email' => $recipientEmail !== '' ? $recipientEmail : null,
                'delivery_address' => $deliveryMethod === 'pickup' ? null : $deliveryAddress,
                'order_comment' => trim((string) ($payload['order_comment'] ?? '')) ?: null,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $total,
            ]);

            $orderId = (int) $statement->fetchColumn();

            $itemStatement = $connection->prepare(
                'INSERT INTO order_items (
                    order_id, product_id, product_variant_id, product_name_snapshot, size_cm_snapshot,
                    quantity, unit_price, line_total
                 ) VALUES (
                    :order_id, :product_id, :product_variant_id, :product_name_snapshot, :size_cm_snapshot,
                    :quantity, :unit_price, :line_total
                 )'
            );

            foreach ($items as $item) {
                $itemStatement->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'] ?? self::productIdByVariant((int) $item['product_variant_id']),
                    'product_variant_id' => $item['product_variant_id'],
                    'product_name_snapshot' => $item['product_name'],
                    'size_cm_snapshot' => $item['size_cm'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $history = $connection->prepare(
                'INSERT INTO order_status_history (order_id, status, comment, changed_by_user_id)
                 VALUES (:order_id, :status, :comment, :changed_by_user_id)'
            );
            $history->execute([
                'order_id' => $orderId,
                'status' => 'new',
                'comment' => 'Заказ создан',
                'changed_by_user_id' => Auth::id(),
            ]);

            $connection->prepare("UPDATE carts SET status = 'converted' WHERE id = :id")
                ->execute(['id' => $cartData['cart']['id']]);

            $connection->commit();

            return $orderNumber;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function byUser(int $userId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM orders WHERE user_id = :user_id ORDER BY placed_at DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM orders ORDER BY placed_at DESC, id DESC')
            ->fetchAll();
    }

    public static function updateStatus(int $orderId, string $status, ?int $userId): void
    {
        $allowed = ['new', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'];

        if (!in_array($status, $allowed, true)) {
            return;
        }

        $fields = ['status = :status'];

        if ($status === 'confirmed') {
            $fields[] = 'confirmed_at = NOW()';
        }

        if ($status === 'completed') {
            $fields[] = 'completed_at = NOW()';
        }

        if ($status === 'cancelled') {
            $fields[] = 'cancelled_at = NOW()';
        }

        $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = :id';

        Database::connection()->prepare($sql)->execute([
            'status' => $status,
            'id' => $orderId,
        ]);

        Database::connection()->prepare(
            'INSERT INTO order_status_history (order_id, status, comment, changed_by_user_id)
             VALUES (:order_id, :status, :comment, :changed_by_user_id)'
        )->execute([
            'order_id' => $orderId,
            'status' => $status,
            'comment' => 'Статус обновлён из админки',
            'changed_by_user_id' => $userId,
        ]);
    }

    private static function productIdByVariant(int $variantId): ?int
    {
        $statement = Database::connection()->prepare('SELECT product_id FROM product_variants WHERE id = :id');
        $statement->execute(['id' => $variantId]);

        $value = $statement->fetchColumn();

        return $value ? (int) $value : null;
    }
}
