<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\CartRepository;
use App\Repositories\ProductRepository;

$slug = (string) ($_GET['slug'] ?? '');
$product = null;
$dbError = null;
$selectedVariant = null;

try {
    $product = ProductRepository::findBySlug($slug);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

if (!$product && !$dbError) {
    http_response_code(404);
}

if (is_post()) {
    verify_csrf();

    try {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        $quantity = (int) ($_POST['quantity'] ?? 1);
        CartRepository::addItem($variantId, $quantity);
        flash('success', 'Товар добавлен в корзину.');
        redirect(route('cart'));
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
        redirect(route('product', ['slug' => $slug]));
    }
}

$pageTitle = page_title($product['name'] ?? 'Товар');
$activePage = 'menu';

if ($product) {
    $selectedVariant = $product['variants'][0] ?? null;

    foreach ($product['variants'] as $variant) {
        if ((float) $variant['size_cm'] === 35.0) {
            $selectedVariant = $variant;
            break;
        }
    }
}

require view_path('header.php');
?>
<section class="container py-5">
  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php elseif (!$product): ?>
    <div class="alert alert-warning">Товар не найден.</div>
  <?php else: ?>
    <div class="row">
      <div class="d-flex justify-content-center col-md-6">
        <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" alt="<?= e($product['name']) ?>" class="w-75">
      </div>
      <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center w-100 py-2">
          <h1 class="mt-2"><?= e($product['name']) ?></h1>
          <h4 class="text-muted" id="product-price"><?= e(format_price($selectedVariant['price'] ?? $product['min_price'])) ?></h4>
        </div>
        <p><?= e($product['full_description'] ?: $product['short_description']) ?></p>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="variant_id" id="variant-id" value="<?= (int) ($selectedVariant['id'] ?? 0) ?>">
          <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="d-flex mt-2">
              <button type="button" class="btn btn-outline-secondary quantity-btn minus-btn">-</button>
              <input
                type="text"
                name="quantity"
                id="quantity-input"
                class="form-control text-center mx-2 quantity-input"
                value="1"
                style="width: 50px;"
              >
              <button type="button" class="btn btn-outline-secondary quantity-btn plus-btn">+</button>
            </div>
            <div class="d-flex gap-2 mt-2">
              <?php foreach ($product['variants'] as $variant): ?>
                <?php $isActive = $selectedVariant && (int) $selectedVariant['id'] === (int) $variant['id']; ?>
                <button
                  type="button"
                  class="btn btn-outline-dark quantity-btn size-btn <?= $isActive ? 'active' : '' ?>"
                  data-variant-id="<?= (int) $variant['id'] ?>"
                  data-price="<?= e((string) $variant['price']) ?>"
                >
                  <?= e((string) (float) $variant['size_cm']) ?> см
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="submit" class="btn btn-dark float-end mt-4">В корзину</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</section>
<?php if ($product): ?>
  <script>
    (function () {
      const minusBtn = document.querySelector('.minus-btn');
      const plusBtn = document.querySelector('.plus-btn');
      const quantityInput = document.getElementById('quantity-input');
      const variantInput = document.getElementById('variant-id');
      const priceNode = document.getElementById('product-price');
      const sizeButtons = document.querySelectorAll('.size-btn');

      if (minusBtn && plusBtn && quantityInput) {
        minusBtn.addEventListener('click', function () {
          const value = parseInt(quantityInput.value, 10);
          quantityInput.value = !value || value <= 1 ? '1' : String(value - 1);
        });

        plusBtn.addEventListener('click', function () {
          const value = parseInt(quantityInput.value, 10);
          quantityInput.value = !value || value < 1 ? '1' : String(value + 1);
        });

        quantityInput.addEventListener('input', function () {
          const value = parseInt(quantityInput.value, 10);
          quantityInput.value = !value || value < 1 ? '1' : String(value);
        });
      }

      sizeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          sizeButtons.forEach(function (item) {
            item.classList.remove('active');
          });

          button.classList.add('active');
          variantInput.value = button.dataset.variantId || '';

          if (priceNode && button.dataset.price) {
            const amount = Number(button.dataset.price);
            priceNode.textContent = new Intl.NumberFormat('ru-RU').format(amount) + ' ₽';
          }
        });
      });
    }());
  </script>
<?php endif; ?>
<?php require view_path('footer.php'); ?>
