<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Auth;
use App\Repositories\OrderRepository;
use App\Repositories\PromoCodeRepository;
use App\Repositories\ProductRepository;

require_admin();

$products = [];
$orders = [];
$promoCodes = [];
$categories = [];
$tags = [];
$dbError = null;

try {
    $products = ProductRepository::allForAdmin();
    $orders = OrderRepository::all();
    $promoCodes = PromoCodeRepository::all();
    $categories = ProductRepository::categories();
    $tags = ProductRepository::tags();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$defaultCategoryId = (int) ($categories[0]['id'] ?? 0);
$variantTemplate = ['30', '35', '40'];
$variantRowsForProduct = static function (array $product) use ($variantTemplate): array {
    $variantMap = [];

    foreach ($product['variants'] as $variant) {
        $size = (string) (int) round((float) $variant['size_cm']);
        $variantMap[$size] = [
            'price' => (string) (int) round((float) $variant['price']),
            'is_active' => (bool) $variant['is_active'],
        ];
    }

    $rows = [];
    foreach ($variantTemplate as $size) {
        $rows[] = [
            'size' => $size,
            'price' => $variantMap[$size]['price'] ?? '',
            'is_active' => $variantMap[$size]['is_active'] ?? true,
        ];
    }

    return $rows;
};

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if (isset($_POST['delete_product_id'])) {
            $action = 'delete_product';
        }

        if ($action === 'save_product') {
            $productId = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? (int) $_POST['product_id'] : null;
            $payload = $_POST;
            $variantSizes = array_map('strval', $payload['variant_size'] ?? []);
            $variantPrices = $payload['variant_price'] ?? [];
            $variantActiveFlags = $payload['variant_active'] ?? [];
            $uploadedImagePath = save_uploaded_image($_FILES['image_file'] ?? []);

            if ($defaultCategoryId <= 0) {
                throw new RuntimeException('Не найдена категория для товаров.');
            }

            $payload['category_id'] = $payload['category_id'] ?? $defaultCategoryId;
            $payload['sort_order'] = $payload['sort_order'] ?? 0;
            $payload['full_description'] = trim((string) ($payload['full_description'] ?? $payload['short_description'] ?? ''));
            $payload['image_path'] = $uploadedImagePath
                ?? (trim((string) ($payload['existing_image_path'] ?? '')) ?: 'images/logo.png');
            $payload['variant_size'] = [];
            $payload['variant_price'] = [];
            $payload['is_active'] = '1';

            $seenSizes = [];
            foreach ($variantSizes as $index => $size) {
                $size = trim($size);
                $price = max(0, (float) ($variantPrices[$index] ?? 0));
                $isActive = (bool) ((int) ($variantActiveFlags[$index] ?? 0));

                if ($size === '') {
                    continue;
                }

                if (isset($seenSizes[$size])) {
                    throw new RuntimeException('Размеры не должны повторяться.');
                }

                $seenSizes[$size] = true;
                $payload['variant_size'][] = $size;
                $payload['variant_price'][] = (string) $price;
                $payload['variant_active'][] = $isActive ? '1' : '0';
            }

            if ($payload['variant_size'] === []) {
                throw new RuntimeException('Укажи хотя бы один вариант размера с ценой.');
            }

            ProductRepository::save($payload, $productId);
            flash('success', 'Товар сохранён.');
        }

        if ($action === 'delete_product') {
            ProductRepository::delete((int) ($_POST['delete_product_id'] ?? $_POST['product_id'] ?? 0));
            flash('success', 'Пицца удалена.');
        }

        if ($action === 'update_order_status') {
            OrderRepository::updateStatus((int) $_POST['order_id'], (string) $_POST['status'], Auth::id());
            flash('success', 'Статус заказа обновлён.');
        }

        if ($action === 'save_promo_code') {
            $promoCodeId = isset($_POST['promo_code_id']) && $_POST['promo_code_id'] !== '' ? (int) $_POST['promo_code_id'] : null;
            PromoCodeRepository::save($_POST, $promoCodeId);
            flash('success', $promoCodeId ? 'Промокод обновлён.' : 'Промокод создан.');
        }

        if ($action === 'delete_promo_code') {
            PromoCodeRepository::delete((int) ($_POST['promo_code_id'] ?? 0));
            flash('success', 'Промокод удалён.');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect(route('admin'));
}

$pageTitle = page_title('Админ-панель');
$pageDescription = 'Панель администратора пиццерии Flour and Fire. Управление меню, заказами и пользователями.';
$pageRobots = 'noindex, nofollow';
$activePage = 'admin';
$showTopInfoBar = false;

require view_path('header.php');
?>
<section class="container py-5">
  <header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0">Админ-панель</h1>
  </header>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php else: ?>
    <div class="row justify-content-center">
      <div class="col-lg-12">
        <div class="card mb-5">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Новая пицца</h5>
          </div>
          <div class="card-body">
            <form method="post" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_product">
              <input type="hidden" name="category_id" value="<?= $defaultCategoryId ?>">
              <input type="hidden" name="existing_image_path" value="images/logo.png">

              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label for="pizzaName" class="form-label">Название пиццы</label>
                  <input type="text" class="form-control" id="pizzaName" name="name" required />
                </div>
                <div class="col-md-6">
                  <label for="pizzaImage" class="form-label">Изображение</label>
                  <input type="file" class="form-control" id="pizzaImage" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif" />
                  <div class="form-text">Файл будет сохранён в `public/uploads/products`.</div>
                </div>
              </div>

              <div class="mb-4">
                <label for="pizzaDescription" class="form-label">Описание</label>
                <textarea class="form-control" id="pizzaDescription" name="short_description" rows="3" required></textarea>
              </div>

              <?php if ($tags !== []): ?>
                <div class="mb-4">
                  <label class="form-label d-block">Теги</label>
                  <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($tags as $tag): ?>
                      <?php $tagId = (int) $tag['id']; ?>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tag_ids[]" value="<?= $tagId ?>" id="newTag<?= $tagId ?>">
                        <label class="form-check-label" for="newTag<?= $tagId ?>"><?= e($tag['name']) ?></label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <h5 class="mb-3">Варианты товара</h5>
              <?php foreach ($variantTemplate as $index => $size): ?>
                <div class="row g-3 mb-2">
                  <div class="col-md-4">
                    <label class="form-label">Цена</label>
                    <input type="number" class="form-control" name="variant_price[<?= $index ?>]" min="0" placeholder="Цена для <?= e($size) ?> см" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Размер</label>
                    <select class="form-select" name="variant_size[<?= $index ?>]">
                      <option value="30"<?= $size === '30' ? ' selected' : '' ?>>30 см</option>
                      <option value="35"<?= $size === '35' ? ' selected' : '' ?>>35 см</option>
                      <option value="40"<?= $size === '40' ? ' selected' : '' ?>>40 см</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label d-block">Активен</label>
                    <input type="hidden" name="variant_active[<?= $index ?>]" value="0" />
                    <div class="form-check pt-2">
                      <input class="form-check-input" type="checkbox" name="variant_active[<?= $index ?>]" value="1"<?= $size === '35' ? ' checked' : '' ?>>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="text-end mt-4">
                <button type="submit" class="btn btn-dark">Добавить пиццу</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card mb-5">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Существующие пиццы</h5>
          </div>
          <div class="card-body">
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                  <?php $formId = 'product-form-' . (int) $product['id']; ?>
                  <?php $variantRows = $variantRowsForProduct($product); ?>
                  <?php $productTagIds = array_map(static fn (array $tag): int => (int) $tag['id'], $product['tags'] ?? []); ?>
                  <div class="col-12">
                    <form id="<?= e($formId) ?>" method="post" enctype="multipart/form-data" class="card">
                      <div class="card-body">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save_product">
                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                        <input type="hidden" name="category_id" value="<?= (int) $product['category_id'] ?>">
                        <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
                        <input type="hidden" name="sort_order" value="<?= (int) $product['sort_order'] ?>">
                        <input type="hidden" name="existing_image_path" value="<?= e($product['image_path']) ?>">

                        <div class="row g-3 mb-3">
                          <div class="col-md-6">
                            <label class="form-label">Название пиццы</label>
                            <input type="text" class="form-control" name="name" value="<?= e($product['name']) ?>" required />
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Изображение</label>
                            <input type="file" class="form-control" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif" />
                            <div class="form-text">Текущее изображение: <?= e($product['image_path']) ?></div>
                          </div>
                        </div>

                    <div class="mb-4">
                      <label class="form-label">Описание</label>
                      <textarea class="form-control" name="short_description" rows="3" required><?= e($product['short_description']) ?></textarea>
                    </div>

                    <?php if ($tags !== []): ?>
                      <div class="mb-4">
                        <label class="form-label d-block">Теги</label>
                        <div class="d-flex flex-wrap gap-3">
                          <?php foreach ($tags as $tag): ?>
                            <?php $tagId = (int) $tag['id']; ?>
                            <div class="form-check">
                              <input
                                class="form-check-input"
                                type="checkbox"
                                name="tag_ids[]"
                                value="<?= $tagId ?>"
                                id="product<?= (int) $product['id'] ?>Tag<?= $tagId ?>"
                                <?= in_array($tagId, $productTagIds, true) ? 'checked' : '' ?>
                              >
                              <label class="form-check-label" for="product<?= (int) $product['id'] ?>Tag<?= $tagId ?>"><?= e($tag['name']) ?></label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>

                    <h5 class="mb-3">Варианты товара</h5>
                    <div class="table-responsive">
                      <table class="table table-striped align-middle">
                        <thead>
                          <tr>
                            <th>Название пиццы</th>
                            <th>Цена</th>
                            <th>Размер</th>
                            <th>Активен</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($variantRows as $index => $variantRow): ?>
                            <tr>
                              <td><?= e($product['name']) ?></td>
                              <td>
                                <input type="number" class="form-control form-control-sm" name="variant_price[<?= $index ?>]" value="<?= e($variantRow['price']) ?>" min="0" />
                              </td>
                              <td>
                                <select class="form-select form-select-sm" name="variant_size[<?= $index ?>]">
                                  <option value="30"<?= $variantRow['size'] === '30' ? ' selected' : '' ?>>30 см</option>
                                  <option value="35"<?= $variantRow['size'] === '35' ? ' selected' : '' ?>>35 см</option>
                                  <option value="40"<?= $variantRow['size'] === '40' ? ' selected' : '' ?>>40 см</option>
                                </select>
                              </td>
                              <td>
                                <input type="hidden" name="variant_active[<?= $index ?>]" value="0" />
                                <div class="form-check">
                                  <input class="form-check-input" type="checkbox" name="variant_active[<?= $index ?>]" value="1"<?= $variantRow['is_active'] ? ' checked' : '' ?>>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                      <button
                        type="submit"
                        class="btn btn-outline-danger"
                        name="delete_product_id"
                        value="<?= (int) $product['id'] ?>"
                        onclick="return confirm('Удалить эту пиццу?');"
                      >
                        Удалить
                      </button>
                      <button type="submit" class="btn btn-dark">Сохранить</button>
                    </div>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Заказы</h5>
          </div>
          <div class="card-body">
            <?php if ($orders === []): ?>
              <p class="text-muted mb-0">Заказов пока нет.</p>
            <?php else: ?>
              <?php foreach ($orders as $order): ?>
                <form method="post" class="border rounded p-3 mb-3">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                  <div class="d-flex justify-content-between">
                    <strong><?= e($order['order_number']) ?></strong>
                    <span><?= e(format_price($order['total_amount'])) ?></span>
                  </div>
                  <div class="small text-muted mb-2"><?= e($order['recipient_name']) ?> · <?= e($order['recipient_phone']) ?></div>
                  <div class="small mb-1">
                    <strong>Способ получения:</strong>
                    <?= e($order['delivery_method'] === 'pickup' ? 'Самовывоз' : 'Доставка') ?>
                  </div>
                  <?php if (!empty($order['delivery_address'])): ?>
                    <div class="small mb-1">
                      <strong>Адрес:</strong>
                      <?= e($order['delivery_address']) ?>
                    </div>
                  <?php endif; ?>
                  <div class="small mb-2">
                    <strong>Промокод:</strong>
                    <?= e($order['promo_code'] ?: 'не использовался') ?>
                  </div>
                  <?php if (!empty($order['items'])): ?>
                    <div class="small mb-2">
                      <strong>Состав заказа:</strong>
                    </div>
                    <div class="small mb-3">
                      <?php foreach ($order['items'] as $item): ?>
                        <div>
                          <?= e($item['product_name_snapshot']) ?>
                          · <?= e((string) (float) $item['size_cm_snapshot']) ?> см
                          · <?= (int) $item['quantity'] ?> шт.
                          · <?= e(format_price($item['line_total'])) ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <select class="form-select mb-2" name="status">
                    <?php foreach (['new', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'] as $status): ?>
                      <option value="<?= e($status) ?>"<?= $order['status'] === $status ? ' selected' : '' ?>><?= e(order_status_label($status)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-outline-dark btn-sm">Обновить статус</button>
                </form>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="card mt-5">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Промокоды</h5>
          </div>
          <div class="card-body">
            <form method="post" class="card mb-4">
              <div class="card-body">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_promo_code">
              <input type="hidden" name="discount_type" value="percent">
              <input type="hidden" name="min_order_amount" value="0">
              <input type="hidden" name="is_active" value="1">

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label for="promoCode" class="form-label">Код</label>
                  <input type="text" class="form-control" id="promoCode" name="code" placeholder="Например, WELCOME10" required />
                </div>
                <div class="col-md-4">
                  <label for="promoDescription" class="form-label">Описание</label>
                  <input type="text" class="form-control" id="promoDescription" name="description" placeholder="Краткое описание промокода" />
                </div>
                <div class="col-md-4">
                  <label for="promoDiscountValue" class="form-label">Скидка, %</label>
                  <input type="number" class="form-control" id="promoDiscountValue" name="discount_value" min="1" max="100" step="1" placeholder="Например, 10" required />
                </div>
              </div>

              <div class="text-end">
                <button type="submit" class="btn btn-dark">Создать промокод</button>
              </div>
              </div>
            </form>

            <?php if ($promoCodes === []): ?>
              <div class="card">
                <div class="card-body text-muted">Промокодов пока нет.</div>
              </div>
            <?php else: ?>
              <div class="row g-4">
              <?php foreach ($promoCodes as $promoCode): ?>
                <div class="col-12">
                  <form method="post" class="card">
                    <div class="card-body">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="save_promo_code">
                      <input type="hidden" name="promo_code_id" value="<?= (int) $promoCode['id'] ?>">
                      <input type="hidden" name="discount_type" value="percent">
                      <input type="hidden" name="min_order_amount" value="0">
                      <input type="hidden" name="is_active" value="<?= !empty($promoCode['is_active']) ? '1' : '0' ?>">

                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                          <strong><?= e($promoCode['code']) ?></strong>
                          <span class="small text-muted ms-2">Использований: <?= (int) $promoCode['usage_count'] ?></span>
                        </div>
                        <div class="d-flex gap-2">
                          <span class="badge d-flex align-items-center <?= !empty($promoCode['is_active']) ? 'bg-success' : 'bg-secondary' ?>">
                            <?= !empty($promoCode['is_active']) ? 'Активен' : 'Отключён' ?>
                          </span>
                          <button
                            type="submit"
                            form="delete-promo-<?= (int) $promoCode['id'] ?>"
                            class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Удалить промокод <?= e($promoCode['code']) ?>?');"
                          >
                            Удалить
                          </button>
                        </div>
                      </div>

                      <div class="row g-3 mb-3">
                        <div class="col-md-4">
                          <label class="form-label">Код</label>
                          <input type="text" class="form-control" name="code" value="<?= e($promoCode['code']) ?>" required />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Описание</label>
                          <input type="text" class="form-control" name="description" value="<?= e($promoCode['description'] ?? '') ?>" placeholder="Краткое описание промокода" />
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Скидка, %</label>
                          <input type="number" class="form-control" name="discount_value" min="1" max="100" step="1" value="<?= e((string) (int) round((float) $promoCode['discount_value'])) ?>" required />
                        </div>
                      </div>

                      <div class="text-end">
                        <button type="submit" class="btn btn-dark">Сохранить промокод</button>
                      </div>
                    </div>
                  </form>

                  <form id="delete-promo-<?= (int) $promoCode['id'] ?>" method="post" class="d-none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete_promo_code">
                    <input type="hidden" name="promo_code_id" value="<?= (int) $promoCode['id'] ?>">
                  </form>
                </div>
              <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require view_path('footer.php'); ?>
