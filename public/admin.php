<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Auth;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

require_admin();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'save_product') {
            $productId = isset($_POST['product_id']) && $_POST['product_id'] !== '' ? (int) $_POST['product_id'] : null;
            ProductRepository::save($_POST, $productId);
            flash('success', 'Товар сохранён.');
        }

        if ($action === 'update_order_status') {
            OrderRepository::updateStatus((int) $_POST['order_id'], (string) $_POST['status'], Auth::id());
            flash('success', 'Статус заказа обновлён.');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect(route('admin'));
}

$products = [];
$orders = [];
$categories = [];
$tags = [];
$dbError = null;

try {
    $products = ProductRepository::allForAdmin();
    $orders = OrderRepository::all();
    $categories = ProductRepository::categories();
    $tags = ProductRepository::tags();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$pageTitle = page_title('Админка');
$activePage = 'admin';

require view_path('header.php');
?>
<section class="container py-5">
  <header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0">Админ-панель</h1>
  </header>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card mb-4">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Добавить новую пиццу</h5>
          </div>
          <div class="card-body">
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_product">
              <div class="mb-3">
                <label class="form-label">Категория</label>
                <select class="form-select" name="category_id" required>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Название</label>
                  <input type="text" class="form-control" name="name" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Slug</label>
                  <input type="text" class="form-control" name="slug" />
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Краткое описание</label>
                <textarea class="form-control" name="short_description" rows="2" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Полное описание</label>
                <textarea class="form-control" name="full_description" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Путь к изображению</label>
                <input type="text" class="form-control" name="image_path" value="images/logo.png" />
              </div>
              <div class="mb-3">
                <label class="form-label">Порядок сортировки</label>
                <input type="number" class="form-control" name="sort_order" value="0" />
              </div>
              <div class="row g-2 mb-3">
                <div class="col"><input type="number" step="0.1" class="form-control" name="variant_size[]" value="30" placeholder="Размер"></div>
                <div class="col"><input type="number" step="0.01" class="form-control" name="variant_price[]" placeholder="Цена"></div>
              </div>
              <div class="row g-2 mb-3">
                <div class="col"><input type="number" step="0.1" class="form-control" name="variant_size[]" value="35" placeholder="Размер"></div>
                <div class="col"><input type="number" step="0.01" class="form-control" name="variant_price[]" placeholder="Цена"></div>
              </div>
              <div class="row g-2 mb-3">
                <div class="col"><input type="number" step="0.1" class="form-control" name="variant_size[]" value="40" placeholder="Размер"></div>
                <div class="col"><input type="number" step="0.01" class="form-control" name="variant_price[]" placeholder="Цена"></div>
              </div>
              <div class="mb-3">
                <label class="form-label">Теги</label>
                <?php foreach ($tags as $tag): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tag_ids[]" value="<?= (int) $tag['id'] ?>" id="tag-new-<?= (int) $tag['id'] ?>">
                    <label class="form-check-label" for="tag-new-<?= (int) $tag['id'] ?>"><?= e($tag['name']) ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="new-is-active" checked>
                <label class="form-check-label" for="new-is-active">Активен</label>
              </div>
              <button type="submit" class="btn btn-dark">Добавить пиццу</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card">
          <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Заказы</h5>
          </div>
          <div class="card-body">
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
                <select class="form-select mb-2" name="status">
                  <?php foreach (['new', 'confirmed', 'preparing', 'out_for_delivery', 'completed', 'cancelled'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-dark btn-sm">Обновить статус</button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Текущие товары</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($products as $product): ?>
            <div class="col-lg-6">
              <form method="post" class="border rounded p-3 h-100">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <div class="mb-2">
                  <label class="form-label">Название</label>
                  <input type="text" class="form-control" name="name" value="<?= e($product['name']) ?>" required>
                </div>
                <div class="mb-2">
                  <label class="form-label">Slug</label>
                  <input type="text" class="form-control" name="slug" value="<?= e($product['slug']) ?>">
                </div>
                <input type="hidden" name="category_id" value="<?= (int) $product['category_id'] ?>">
                <div class="mb-2">
                  <label class="form-label">Порядок сортировки</label>
                  <input type="number" class="form-control" name="sort_order" value="<?= (int) $product['sort_order'] ?>">
                </div>
                <div class="mb-2">
                  <label class="form-label">Краткое описание</label>
                  <textarea class="form-control" name="short_description" rows="2"><?= e($product['short_description']) ?></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Полное описание</label>
                  <textarea class="form-control" name="full_description" rows="3"><?= e($product['full_description']) ?></textarea>
                </div>
                <div class="mb-2">
                  <label class="form-label">Путь к изображению</label>
                  <input type="text" class="form-control" name="image_path" value="<?= e($product['image_path']) ?>">
                </div>
                <?php foreach ($product['variants'] as $variant): ?>
                  <div class="row g-2 mb-2">
                    <div class="col">
                      <input type="number" step="0.1" class="form-control" name="variant_size[]" value="<?= e((string) (float) $variant['size_cm']) ?>">
                    </div>
                    <div class="col">
                      <input type="number" step="0.01" class="form-control" name="variant_price[]" value="<?= e((string) $variant['price']) ?>">
                    </div>
                  </div>
                <?php endforeach; ?>
                <div class="mb-3">
                  <?php
                  $productTagIds = array_map(static fn(array $tag): int => (int) $tag['id'], $product['tags']);
                  foreach ($tags as $tag):
                  ?>
                    <div class="form-check">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        name="tag_ids[]"
                        value="<?= (int) $tag['id'] ?>"
                        id="tag-<?= (int) $product['id'] ?>-<?= (int) $tag['id'] ?>"
                        <?= in_array((int) $tag['id'], $productTagIds, true) ? 'checked' : '' ?>
                      >
                      <label class="form-check-label" for="tag-<?= (int) $product['id'] ?>-<?= (int) $tag['id'] ?>"><?= e($tag['name']) ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" name="is_active" id="active-<?= (int) $product['id'] ?>" <?= $product['is_active'] ? 'checked' : '' ?>>
                  <label class="form-check-label" for="active-<?= (int) $product['id'] ?>">Активен</label>
                </div>
                <button class="btn btn-dark btn-sm">Сохранить</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require view_path('footer.php'); ?>
