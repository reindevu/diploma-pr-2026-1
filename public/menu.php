<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Меню');
$activePage = 'menu';
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
  <h2>Познакомьтесь с нашими самыми вкусными предложениями</h2>
  <p class="text-muted">Каталог подгружается из PostgreSQL</p>

  <?php if ($dbError): ?>
    <div class="alert alert-danger mt-4"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row g-4 mt-4">
    <?php foreach ($products as $product): ?>
      <?php require view_path('product_card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php require view_path('footer.php'); ?>
