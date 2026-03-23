<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'update_quantity') {
            CartRepository::updateItem((int) $_POST['item_id'], (int) $_POST['quantity']);
            flash('success', 'Количество обновлено.');
        }

        if ($action === 'remove_item') {
            CartRepository::removeItem((int) $_POST['item_id']);
            flash('success', 'Позиция удалена.');
        }

        if ($action === 'promo_code') {
            $result = CartRepository::applyPromoCode((string) ($_POST['promo_code'] ?? ''));
            flash($result['success'] ? 'success' : 'danger', $result['message']);
        }

        if ($action === 'clear_promo_code') {
            CartRepository::clearPromoCode();
            flash('success', 'Промокод убран.');
        }

        if ($action === 'checkout') {
            $orderNumber = OrderRepository::createFromCart($_POST);
            flash('success', 'Заказ оформлен: ' . $orderNumber);
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect(route('cart'));
}

$cartData = ['items' => [], 'subtotal' => 0, 'discount' => 0, 'items_count' => 0, 'promo_code' => null];
$addresses = [];
$dbError = null;
$user = current_user();

try {
    $cartData = CartRepository::current();
    if ($user) {
        $addresses = UserRepository::addresses((int) $user['id']);
    }
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$defaultAddress = $addresses[0]['full_address'] ?? '';
$deliveryMethod = 'delivery';
$deliveryFee = $deliveryMethod === 'delivery' ? 200 : 0;
$total = max(0, $cartData['subtotal'] - $cartData['discount'] + $deliveryFee);

$pageTitle = page_title('Корзина');
$activePage = 'cart';

require view_path('header.php');
?>
<section class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Корзина</h1>
    <a href="<?= e(route('menu')) ?>" class="btn btn-outline-dark">Вернуться в меню</a>
  </div>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php else: ?>
    <div class="row">
      <div class="col-lg-8 mb-4">
        <div class="cart-card border rounded p-4">
          <?php if ($cartData['items'] === []): ?>
            <p class="mb-0 text-muted">Корзина пуста.</p>
          <?php endif; ?>

          <?php foreach ($cartData['items'] as $item): ?>
            <div class="row align-items-center mb-4 pb-3 border-bottom">
              <div class="col-md-2 mw-150px">
                <img src="<?= e(asset($item['image_path'] ?: 'images/logo.png')) ?>" class="w-100" alt="<?= e($item['product_name']) ?>" />
              </div>
              <div class="col-md-4">
                <h5 class="mb-1"><?= e($item['product_name']) ?></h5>
                <p class="text-muted mb-0"><?= e($item['short_description']) ?></p>
                <small class="text-danger"><?= e((string) (float) $item['size_cm']) ?> см</small>
              </div>
              <div class="col-md-3">
                <form method="post" class="d-flex mt-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_quantity">
                  <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                  <input type="number" min="1" class="form-control text-center mx-2 quantity-input" name="quantity" value="<?= (int) $item['quantity'] ?>" style="width: 90px">
                  <button class="btn btn-outline-secondary">OK</button>
                </form>
              </div>
              <div class="col-md-2 text-end">
                <h5 class="mb-0"><?= e(format_price($item['line_total'])) ?></h5>
              </div>
              <div class="col-md-1 text-end">
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove_item">
                  <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                  <button class="btn btn-link text-danger">Удалить</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="summary-card sticky-top border rounded p-4" style="top: 20px">
          <h4 class="mb-4">Итого</h4>

          <div class="mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span>Товары (<?= (int) $cartData['items_count'] ?>)</span>
              <span><?= e(format_price($cartData['subtotal'])) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Скидка</span>
              <span><?= e(format_price($cartData['discount'])) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Доставка</span>
              <span><?= e(format_price($deliveryFee)) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span class="fw-bold">Итого</span>
              <span class="fw-bold"><?= e(format_price($total)) ?></span>
            </div>
          </div>

          <form method="post" class="mb-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="promo_code">
            <label class="form-label">Промокод</label>
            <div class="input-group">
              <input type="text" class="form-control promo-input" name="promo_code" placeholder="Введите промокод" />
              <button class="btn btn-dark promo-btn">Применить</button>
            </div>
          </form>

          <?php if ($cartData['promo_code']): ?>
            <form method="post" class="mb-4">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear_promo_code">
              <div class="small text-muted mb-2">Активный промокод: <?= e($cartData['promo_code']['code']) ?></div>
              <button class="btn btn-outline-secondary btn-sm">Убрать промокод</button>
            </form>
          <?php endif; ?>

          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="checkout">

            <div class="mb-3">
              <label class="form-label">Имя получателя</label>
              <input type="text" class="form-control" name="recipient_name" value="<?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Телефон</label>
              <input type="text" class="form-control" name="recipient_phone" value="<?= e($user['phone'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="recipient_email" value="<?= e($user['email'] ?? '') ?>">
            </div>

            <div class="mb-4">
              <label class="form-label">Способ получения</label>
              <select class="form-select" name="delivery_method">
                <option value="delivery" selected>Доставка курьером</option>
                <option value="pickup">Самовывоз из пиццерии</option>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label">Адрес доставки</label>
              <input type="text" class="form-control" name="delivery_address" value="<?= e($defaultAddress) ?>" placeholder="Укажите адрес">
            </div>

            <div class="mb-4">
              <label class="form-label">Комментарий</label>
              <textarea class="form-control" name="order_comment" rows="3" placeholder="Комментарий к заказу"></textarea>
            </div>

            <button class="btn btn-dark w-100 py-3 fw-bold" <?= $cartData['items'] === [] ? 'disabled' : '' ?>>
              Оформить заказ
            </button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php require view_path('footer.php'); ?>
