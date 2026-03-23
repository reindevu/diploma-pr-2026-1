<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Фото');
$activePage = 'photos';
$products = [];
$dbError = null;

try {
    $products = ProductRepository::allActive();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

require view_path('header.php');
?>
<section class="container py-5 text-center">
  <h2>Наша галерея пиццы</h2>
  <p class="text-muted">Фотографии берутся из каталога товаров</p>

  <?php if ($dbError): ?>
    <div class="alert alert-danger mt-4"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row g-4 pt-5">
    <?php foreach ($products as $product): ?>
      <div class="col-md-4">
        <a href="<?= e(route('product', ['slug' => $product['slug']])) ?>">
          <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" class="img-fluid rounded" alt="<?= e($product['name']) ?>" />
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php require view_path('footer.php'); ?>
