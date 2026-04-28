<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class ProductRepository
{
    public static function featured(int $limit = 4): array
    {
        return self::fetchProducts($limit);
    }

    public static function allActive(): array
    {
        return self::fetchProducts();
    }

    public static function allForAdmin(): array
    {
        return self::fetchProducts(null, true);
    }

    public static function categories(): array
    {
        return Database::connection()
            ->query('SELECT * FROM product_categories ORDER BY sort_order, name')
            ->fetchAll();
    }

    public static function tags(): array
    {
        return Database::connection()
            ->query(
                "SELECT *
                 FROM product_tags
                 WHERE code IN ('new', 'hit', 'spicy', 'vegetarian', 'cheesy', 'meat', 'recommended', 'sale', 'signature')
                 ORDER BY CASE code
                   WHEN 'new' THEN 10
                   WHEN 'hit' THEN 20
                   WHEN 'spicy' THEN 30
                   WHEN 'vegetarian' THEN 40
                   WHEN 'cheesy' THEN 50
                   WHEN 'meat' THEN 60
                   WHEN 'recommended' THEN 70
                   WHEN 'sale' THEN 80
                   WHEN 'signature' THEN 90
                   ELSE 100
                 END"
            )
            ->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug
             FROM products p
             JOIN product_categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.is_active = TRUE
               AND EXISTS (
                   SELECT 1
                   FROM product_variants pv
                   WHERE pv.product_id = p.id AND pv.is_active = TRUE
               )
             LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $product = $statement->fetch();

        if (!$product) {
            return null;
        }

        $product['variants'] = self::variants((int) $product['id']);
        $product['tags'] = self::tagsByProduct((int) $product['id']);
        $product['min_price'] = self::minPrice($product['variants']);

        return $product;
    }

    public static function delete(int $productId): void
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $exists = $connection->prepare('SELECT 1 FROM products WHERE id = :id');
            $exists->execute(['id' => $productId]);

            if (!$exists->fetchColumn()) {
                throw new \RuntimeException('Пицца не найдена.');
            }

            $connection->prepare(
                'DELETE FROM cart_items
                 WHERE product_variant_id IN (
                     SELECT id FROM product_variants WHERE product_id = :product_id
                 )'
            )->execute(['product_id' => $productId]);

            $connection->prepare('DELETE FROM products WHERE id = :id')
                ->execute(['id' => $productId]);

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public static function save(array $data, ?int $productId = null): int
    {
        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $slug = trim((string) ($data['slug'] ?? ''));
            if ($slug === '') {
                $slug = self::slugify((string) ($data['name'] ?? 'product'));
            }

            if ($productId) {
                $statement = $connection->prepare(
                    'UPDATE products
                     SET category_id = :category_id,
                         name = :name,
                         slug = :slug,
                         short_description = :short_description,
                         full_description = :full_description,
                         image_path = :image_path,
                         is_active = :is_active,
                         sort_order = :sort_order
                     WHERE id = :id'
                );

                $statement->execute([
                    'id' => $productId,
                    'category_id' => (int) $data['category_id'],
                    'name' => trim((string) $data['name']),
                    'slug' => $slug,
                    'short_description' => trim((string) $data['short_description']),
                    'full_description' => trim((string) $data['full_description']),
                    'image_path' => trim((string) $data['image_path']),
                    'is_active' => self::pgBool(isset($data['is_active'])),
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                ]);
            } else {
                $statement = $connection->prepare(
                    'INSERT INTO products (category_id, name, slug, short_description, full_description, image_path, is_active, sort_order)
                     VALUES (:category_id, :name, :slug, :short_description, :full_description, :image_path, :is_active, :sort_order)
                     RETURNING id'
                );

                $statement->execute([
                    'category_id' => (int) $data['category_id'],
                    'name' => trim((string) $data['name']),
                    'slug' => $slug,
                    'short_description' => trim((string) $data['short_description']),
                    'full_description' => trim((string) $data['full_description']),
                    'image_path' => trim((string) $data['image_path']),
                    'is_active' => self::pgBool(isset($data['is_active'])),
                    'sort_order' => (int) ($data['sort_order'] ?? 0),
                ]);

                $productId = (int) $statement->fetchColumn();
            }

            $connection->prepare('DELETE FROM product_tag_links WHERE product_id = :product_id')
                ->execute(['product_id' => $productId]);

            $submittedTagIds = is_array($data['tag_ids'] ?? null) ? $data['tag_ids'] : [];
            $tagIds = array_unique(array_map('intval', $submittedTagIds));
            $allowedTagIds = array_map('intval', array_column(self::tags(), 'id'));
            $tagIds = array_intersect($tagIds, $allowedTagIds);

            foreach ($tagIds as $tagId) {
                if ($tagId <= 0) {
                    continue;
                }

                $connection->prepare(
                    'INSERT INTO product_tag_links (product_id, tag_id) VALUES (:product_id, :tag_id)'
                )->execute([
                    'product_id' => $productId,
                    'tag_id' => $tagId,
                ]);
            }

            $variantSizes = $data['variant_size'] ?? [];
            $variantPrices = $data['variant_price'] ?? [];
            $variantActiveFlags = $data['variant_active'] ?? [];
            $submittedSizes = [];

            foreach ($variantSizes as $index => $size) {
                $size = (float) $size;
                $price = (float) ($variantPrices[$index] ?? 0);
                $isActive = (bool) ((int) ($variantActiveFlags[$index] ?? 0));

                if ($size <= 0 || $price < 0) {
                    continue;
                }

                $submittedSizes[] = $size;

                if ($price <= 0 && $isActive) {
                    throw new \RuntimeException('У активного размера должна быть указана цена больше нуля.');
                }

                $sku = strtoupper($slug) . '-' . (int) $size;

                $connection->prepare(
                    'INSERT INTO product_variants (product_id, sku, size_cm, price, is_active)
                     VALUES (:product_id, :sku, :size_cm, :price, :is_active)
                     ON CONFLICT (product_id, size_cm)
                     DO UPDATE SET sku = EXCLUDED.sku, price = EXCLUDED.price, is_active = EXCLUDED.is_active'
                )->execute([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'size_cm' => $size,
                    'price' => $price,
                    'is_active' => self::pgBool($isActive),
                ]);
            }

            if ($productId !== null) {
                $deactivate = $connection->prepare(
                    'UPDATE product_variants
                     SET is_active = FALSE
                     WHERE product_id = :product_id AND size_cm = :size_cm'
                );

                foreach ([30.0, 35.0, 40.0] as $knownSize) {
                    if (in_array($knownSize, $submittedSizes, true)) {
                        continue;
                    }

                    $deactivate->execute([
                        'product_id' => $productId,
                        'size_cm' => $knownSize,
                    ]);
                }
            }

            $connection->commit();

            return $productId;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private static function fetchProducts(?int $limit = null, bool $includeInactive = false): array
    {
        $sql = 'SELECT p.*, c.name AS category_name
                FROM products p
                JOIN product_categories c ON c.id = p.category_id';

        if (!$includeInactive) {
            $sql .= ' WHERE p.is_active = TRUE
                      AND EXISTS (
                          SELECT 1
                          FROM product_variants pv
                          WHERE pv.product_id = p.id AND pv.is_active = TRUE
                      )';
        }

        $sql .= ' ORDER BY p.sort_order, p.name';

        if ($limit !== null) {
            $sql .= ' LIMIT :limit';
        }

        $statement = Database::connection()->prepare($sql);

        if ($limit !== null) {
            $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        }

        $statement->execute();
        $products = $statement->fetchAll();

        foreach ($products as &$product) {
            $product['variants'] = self::variants((int) $product['id'], $includeInactive);
            $product['tags'] = self::tagsByProduct((int) $product['id']);
            $product['min_price'] = self::minPrice($product['variants']);
        }

        return $products;
    }

    private static function variants(int $productId, bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM product_variants WHERE product_id = :product_id';

        if (!$includeInactive) {
            $sql .= ' AND is_active = TRUE';
        }

        $sql .= ' ORDER BY size_cm';

        $statement = Database::connection()->prepare($sql);
        $statement->execute(['product_id' => $productId]);

        return $statement->fetchAll();
    }

    private static function tagsByProduct(int $productId): array
    {
        $statement = Database::connection()->prepare(
            "SELECT t.*
             FROM product_tags t
             JOIN product_tag_links l ON l.tag_id = t.id
             WHERE l.product_id = :product_id
               AND t.code IN ('new', 'hit', 'spicy', 'vegetarian', 'cheesy', 'meat', 'recommended', 'sale', 'signature')
             ORDER BY CASE t.code
               WHEN 'new' THEN 10
               WHEN 'hit' THEN 20
               WHEN 'spicy' THEN 30
               WHEN 'vegetarian' THEN 40
               WHEN 'cheesy' THEN 50
               WHEN 'meat' THEN 60
               WHEN 'recommended' THEN 70
               WHEN 'sale' THEN 80
               WHEN 'signature' THEN 90
               ELSE 100
             END"
        );
        $statement->execute(['product_id' => $productId]);

        return $statement->fetchAll();
    }

    private static function minPrice(array $variants): float
    {
        if ($variants === []) {
            return 0;
        }

        return (float) min(array_column($variants, 'price'));
    }

    private static function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'product';
        $value = trim($value, '-');

        return $value !== '' ? $value : 'product';
    }

    private static function pgBool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
