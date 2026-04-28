<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

if (str_contains($contentType, 'application/json')) {
    $jsonPayload = json_decode((string) file_get_contents('php://input'), true);

    if (is_array($jsonPayload)) {
        $_POST = $jsonPayload;
    }
}

$respondJson = static function (array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$expectsJson = (string) ($_POST['ajax'] ?? '') === '1'
    || str_contains($contentType, 'application/json')
    || str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
    || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'change_quantity') {
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
            $change = (string) ($_POST['change'] ?? '');

            if ($change === 'increase') {
                $quantity++;
            }

            if ($change === 'decrease') {
                $quantity = max(1, $quantity - 1);
            }

            CartRepository::updateItem((int) $_POST['item_id'], $quantity);

            if ($expectsJson) {
                $cartData = CartRepository::current();
                $itemId = (int) $_POST['item_id'];
                $updatedItem = null;

                foreach ($cartData['items'] as $item) {
                    if ((int) $item['id'] === $itemId) {
                        $updatedItem = $item;
                        break;
                    }
                }

                $deliveryFee = 200;
                $total = max(0, $cartData['subtotal'] - $cartData['discount'] + $deliveryFee);

                $respondJson([
                    'success' => true,
                    'item' => $updatedItem ? [
                        'id' => (int) $updatedItem['id'],
                        'quantity' => (int) $updatedItem['quantity'],
                        'line_total' => format_price($updatedItem['line_total']),
                    ] : null,
                    'summary' => [
                        'items_count' => (int) $cartData['items_count'],
                        'subtotal' => format_price($cartData['subtotal']),
                        'discount' => format_price($cartData['discount']),
                        'delivery_fee' => format_price($deliveryFee),
                        'total' => format_price($total),
                    ],
                ]);
            }
        }

        if ($action === 'remove_item') {
            CartRepository::removeItem((int) $_POST['item_id']);
            flash('success', 'Позиция удалена.');

            if ($expectsJson) {
                $cartData = CartRepository::current();
                $deliveryFee = 200;
                $total = max(0, $cartData['subtotal'] - $cartData['discount'] + $deliveryFee);

                $respondJson([
                    'success' => true,
                    'removed_item_id' => (int) $_POST['item_id'],
                    'summary' => [
                        'items_count' => (int) $cartData['items_count'],
                        'subtotal' => format_price($cartData['subtotal']),
                        'discount' => format_price($cartData['discount']),
                        'delivery_fee' => format_price($deliveryFee),
                        'total' => format_price($total),
                    ],
                    'is_empty' => $cartData['items'] === [],
                ]);
            }
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
        if ($expectsJson) {
            $respondJson([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

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

$defaultAddress = '';
foreach ($addresses as $address) {
    if (!empty($address['is_default'])) {
        $defaultAddress = (string) $address['full_address'];
        break;
    }
}

if ($defaultAddress === '') {
    $defaultAddress = $addresses[0]['full_address'] ?? '';
}

$deliveryFee = 200;
$total = max(0, $cartData['subtotal'] - $cartData['discount'] + $deliveryFee);

$pageTitle = page_title('Корзина заказа');
$pageDescription = 'Корзина заказа пиццерии Flour and Fire. Оформление доставки или самовывоза, ввод промокода, выбор способа оплаты.';
$pageKeywords = 'корзина, оформление заказа, промокод, доставка пиццы';
$pageRobots = 'noindex, follow';
$activePage = 'cart';
$showTopInfoBar = false;

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
        <div class="cart-card">
          <div class="cart-header"></div>
          <div class="card-body">
            <?php if ($cartData['items'] === []): ?>
              <p class="text-muted mb-0">Корзина пуста.</p>
            <?php endif; ?>

            <?php foreach ($cartData['items'] as $item): ?>
              <div class="row align-items-center mb-4 pb-3 border-bottom" data-cart-item-row="<?= (int) $item['id'] ?>">
                <div class="col-md-2 mw-150px">
                  <img src="<?= e(asset($item['image_path'] ?: 'images/logo.png')) ?>" class="w-100" alt="<?= e($item['product_name']) ?>" />
                </div>
                <div class="col-md-4">
                  <h5 class="mb-1"><?= e($item['product_name']) ?></h5>
                  <p class="text-muted mb-0"><?= e($item['short_description']) ?></p>
                  <small class="text-danger"><?= e((string) (float) $item['size_cm']) ?> см</small>
                </div>
                <div class="col-md-3">
                  <form method="post" class="d-flex mt-2 cart-quantity-form" data-cart-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_quantity">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="quantity" value="<?= (int) $item['quantity'] ?>" data-cart-quantity-hidden>
                    <button type="button" class="btn btn-outline-secondary quantity-btn minus-btn" name="change" value="decrease" data-cart-change="decrease">-</button>
                    <input type="text" class="form-control text-center mx-2 quantity-input" value="<?= (int) $item['quantity'] ?>" style="width: 50px" readonly data-cart-quantity-display />
                    <button type="button" class="btn btn-outline-secondary quantity-btn plus-btn" name="change" value="increase" data-cart-change="increase">+</button>
                  </form>
                </div>
                <div class="col-md-2 text-end" data-cart-line-total>
                  <h5 class="mb-0"><?= e(format_price($item['line_total'])) ?></h5>
                </div>
                <div class="col-md-1 text-end">
                  <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove_item">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <button class="btn btn-link text-danger">&times;</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="summary-card sticky-top" style="top: 20px">
          <h4 class="mb-4">Итого</h4>

          <div class="mb-3">
            <div class="d-flex justify-content-between mb-2">
              <span id="cart-summary-items-label">Товары (<?= (int) $cartData['items_count'] ?>)</span>
              <span id="cart-summary-subtotal"><?= e(format_price($cartData['subtotal'])) ?></span>
            </div>
            <?php if ((float) $cartData['discount'] > 0): ?>
              <div class="d-flex justify-content-between mb-2" id="cart-summary-discount-row">
                <span>Скидка</span>
                <span id="cart-summary-discount">-<?= e(format_price($cartData['discount'])) ?></span>
              </div>
            <?php else: ?>
              <div class="d-flex justify-content-between mb-2 d-none" id="cart-summary-discount-row">
                <span>Скидка</span>
                <span id="cart-summary-discount">-<?= e(format_price($cartData['discount'])) ?></span>
              </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between mb-2">
              <span>Доставка</span>
              <span id="cart-summary-delivery"><?= e(format_price($deliveryFee)) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
              <span class="fw-bold">Итого</span>
              <span class="fw-bold" id="cart-summary-total"><?= e(format_price($total)) ?></span>
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

            <?php if ($user): ?>
              <input type="hidden" name="recipient_name" value="<?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>">
              <input type="hidden" name="recipient_phone" value="<?= e($user['phone'] ?? '') ?>">
              <input type="hidden" name="recipient_email" value="<?= e($user['email'] ?? '') ?>">
            <?php else: ?>
              <div class="mb-4">
                <label class="form-label">Имя получателя</label>
                <input type="text" class="form-control" name="recipient_name" placeholder="Введите имя получателя" required />
              </div>

              <div class="mb-4">
                <label class="form-label">Телефон</label>
                <input type="tel" class="form-control" name="recipient_phone" placeholder="+79991234567" required />
              </div>

              <div class="mb-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="recipient_email" placeholder="example@mail.com" />
              </div>
            <?php endif; ?>

            <div class="mb-4">
              <label class="form-label">Способ получения</label>
              <select class="form-select" id="delivery-method-select" name="delivery_method">
                <option value="delivery" selected>Доставка курьером</option>
                <option value="pickup">Самовывоз из пиццерии</option>
              </select>
            </div>

            <div class="mb-4" id="delivery-address-group">
              <label for="delivery-address-choice" class="form-label">Адрес</label>
              <select class="form-select" id="delivery-address-choice">
                <option value=""<?= $defaultAddress === '' ? ' selected' : '' ?>>Не указан</option>
                <?php foreach ($addresses as $address): ?>
                  <option value="<?= e($address['full_address']) ?>"<?= $address['full_address'] === $defaultAddress ? ' selected' : '' ?>>
                    <?= e(($address['label'] ?: 'Адрес') . ' — ' . $address['full_address']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input
                type="text"
                class="form-control mt-2<?= $defaultAddress !== '' ? ' d-none' : '' ?>"
                id="delivery-address-input"
                name="delivery_address"
                value="<?= e($defaultAddress) ?>"
                data-manual-address=""
                placeholder="Укажите адрес"
              />
            </div>

            <button class="btn btn-dark w-100 py-3 fw-bold"<?= $cartData['items'] === [] ? ' disabled' : '' ?>>Оформить заказ</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>
<script>
  (function () {
    const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const forms = document.querySelectorAll('[data-cart-form]');
    const itemsLabelNode = document.getElementById('cart-summary-items-label');
    const subtotalNode = document.getElementById('cart-summary-subtotal');
    const discountRow = document.getElementById('cart-summary-discount-row');
    const discountNode = document.getElementById('cart-summary-discount');
    const deliveryNode = document.getElementById('cart-summary-delivery');
    const totalNode = document.getElementById('cart-summary-total');
    const deliveryMethodSelect = document.getElementById('delivery-method-select');
    const deliveryAddressGroup = document.getElementById('delivery-address-group');
    const deliveryAddressChoice = document.getElementById('delivery-address-choice');
    const deliveryAddressInput = document.getElementById('delivery-address-input');

    const updateSummary = function (summary) {
      if (!summary) {
        return;
      }

      if (itemsLabelNode) {
        itemsLabelNode.textContent = 'Товары (' + summary.items_count + ')';
      }

      if (subtotalNode) {
        subtotalNode.textContent = summary.subtotal;
      }

      if (deliveryNode) {
        deliveryNode.textContent = summary.delivery_fee;
      }

      if (totalNode) {
        totalNode.textContent = summary.total;
      }

      if (discountRow && discountNode) {
        const hasDiscount = summary.discount !== '0 ₽';
        discountNode.textContent = '-' + summary.discount;
        discountRow.classList.toggle('d-none', !hasDiscount);
      }
    };

    forms.forEach(function (form) {
      form.querySelectorAll('[data-cart-change]').forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();

          const row = form.closest('[data-cart-item-row]');
          const hiddenQuantity = form.querySelector('[data-cart-quantity-hidden]');
          const displayQuantity = form.querySelector('[data-cart-quantity-display]');
          const itemIdField = form.querySelector('input[name="item_id"]');
          const quantity = Number(hiddenQuantity ? hiddenQuantity.value : 1);
          const requestBody = {
            _csrf: csrfToken,
            ajax: '1',
            action: 'change_quantity',
            item_id: itemIdField ? itemIdField.value : '',
            quantity: quantity,
            change: button.value,
          };
          const requestUrl = window.location.pathname + window.location.search;

          fetch(requestUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(requestBody),
          })
            .then(function (response) {
              return response.text().then(function (text) {
                let payload = null;

                try {
                  payload = JSON.parse(text);
                } catch (error) {
                  throw new Error('Сервер вернул неожиданный ответ вместо JSON.');
                }

                if (!response.ok) {
                  throw new Error(payload.message || 'Не удалось обновить корзину.');
                }

                return payload;
              });
            })
            .then(function (payload) {
              if (!payload.success) {
                throw new Error(payload.message || 'Не удалось обновить корзину.');
              }

              if (payload.item && hiddenQuantity && displayQuantity) {
                hiddenQuantity.value = String(payload.item.quantity);
                displayQuantity.value = String(payload.item.quantity);

                const lineTotalNode = row ? row.querySelector('[data-cart-line-total] h5') : null;
                if (lineTotalNode) {
                  lineTotalNode.textContent = payload.item.line_total;
                }
              }

              updateSummary(payload.summary);
            })
            .catch(function (error) {
              window.alert(error.message);
            });
        });
      });
    });

    if (deliveryAddressChoice && deliveryAddressInput) {
      let manualAddressValue = deliveryAddressInput.dataset.manualAddress || '';

      if (!deliveryAddressInput.classList.contains('d-none')) {
        manualAddressValue = deliveryAddressInput.value;
      }

      const syncDeliveryAddress = function () {
        if (deliveryAddressChoice.value === '') {
          deliveryAddressInput.classList.remove('d-none');
          deliveryAddressInput.value = manualAddressValue;
          return;
        }

        if (!deliveryAddressInput.classList.contains('d-none')) {
          manualAddressValue = deliveryAddressInput.value;
        }

        deliveryAddressInput.value = deliveryAddressChoice.value;
        deliveryAddressInput.classList.add('d-none');
      };

      deliveryAddressChoice.addEventListener('change', syncDeliveryAddress);
      deliveryAddressInput.addEventListener('input', function () {
        if (deliveryAddressChoice.value === '') {
          manualAddressValue = deliveryAddressInput.value;
        }
      });

      syncDeliveryAddress();
    }

    if (deliveryMethodSelect && deliveryAddressGroup) {
      const syncDeliveryMethod = function () {
        deliveryAddressGroup.classList.toggle('d-none', deliveryMethodSelect.value === 'pickup');
      };

      deliveryMethodSelect.addEventListener('change', syncDeliveryMethod);
      syncDeliveryMethod();
    }
  }());
</script>
<?php require view_path('footer.php'); ?>
