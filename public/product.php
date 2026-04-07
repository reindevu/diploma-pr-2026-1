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
$pageDescription = (string) ($product['short_description'] ?? 'Описание пиццы Flour and Fire.');
$pageKeywords = 'пицца, меню пиццерии, заказать пиццу, итальянская пицца';
$pageRobots = $product ? 'index, follow' : 'noindex, follow';
$pageOgTitle = $product ? 'Пицца ' . (string) $product['name'] . ' | Flour and Fire' : 'Товар | Flour and Fire';
$pageOgDescription = (string) ($product['short_description'] ?? 'Выберите пиццу Flour and Fire.');
$pageOgImage = (string) ($product['image_path'] ?? 'images/logo.png');
$activePage = 'menu';
$showTopInfoBar = false;

if (!$product && !$dbError) {
    $pageTitle = '404 — Страница не найдена | Flour and Fire';
    $pageDescription = 'Страница не найдена. Вернитесь на главную или в меню пиццерии Flour and Fire.';
    $pageOgTitle = '404 — Страница не найдена | Flour and Fire';
    $pageOgDescription = 'Кажется, такая пицца не в нашем меню. Вернитесь на главную и выберите что-то вкусное!';
    $pageOgImage = 'images/hero.webp';
}

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
<?php if ($dbError): ?>
  <section class="container py-5">
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  </section>
<?php elseif (!$product): ?>
  <section class="error-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="error-code">404</div>
          <h1 class="error-title">Страница не найдена</h1>
          <p class="error-text">
            Кажется, такой пиццы нет в нашем меню. Но не расстраивайтесь — у нас много других вкусных вариантов!
          </p>
          <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= e(route('home')) ?>" class="btn btn-error">На главную</a>
            <a href="<?= e(route('menu')) ?>" class="btn btn-error">В меню</a>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="container py-5">
    <div class="row">
      <div class="d-flex justify-content-center col-md-6">
        <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" alt="Пицца <?= e($product['name']) ?>" class="w-75">
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
              <input type="text" name="quantity" id="quantity-input" class="form-control text-center mx-2 quantity-input" value="1" style="width: 50px;">
              <button type="button" class="btn btn-outline-secondary quantity-btn plus-btn">+</button>
            </div>
            <div class="d-flex gap-2 mt-2">
              <?php foreach ($product['variants'] as $variant): ?>
                <?php $isActive = $selectedVariant && (int) $selectedVariant['id'] === (int) $variant['id']; ?>
                <button
                  type="button"
                  class="btn btn-outline-dark quantity-btn size-btn <?= $isActive ? 'active' : '' ?>"
                  data-size-choice
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
  </section>
<?php endif; ?>
<?php if ($product): ?>
  <script src="<?= e(asset('script/choice.js')) ?>"></script>
  <script src="<?= e(asset('script/calculation.js')) ?>"></script>
  <script>
    (function () {
      const variantInput = document.getElementById('variant-id');
      const priceNode = document.getElementById('product-price');
      const sizeButtons = document.querySelectorAll('.size-btn');

      sizeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
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
