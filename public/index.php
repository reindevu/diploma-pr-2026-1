<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Главная');
$activePage = 'home';
$products = [];
$dbError = null;

try {
    $products = ProductRepository::featured(4);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

require view_path('header.php');
?>
<div class="col-md-12 hero-background">
  <header class="text-center py-5 h-100">
    <h1 class="text-light">Настоящая итальянская пицца с настоящими ингредиентами</h1>
    <p class="lead text-light">Лучшая итальянская пицца в одном месте</p>
  </header>

  <section class="container py-5">
    <div class="row">
      <div class="col-md-6" style="min-height: 38vh"></div>
      <div class="col-md-6">
        <h2 class="text-light">Мы используем проверенные рецепты и свежие ингредиенты</h2>
        <p class="text-light">
          В нашей пиццерии каждая пицца — это вкус традиций и качество в каждой детали.
          Меню, корзина и заказы теперь работают через PHP и Postgres.
        </p>
        <a href="<?= e(route('menu')) ?>" class="btn btn-light">Посмотреть меню</a>
      </div>
    </div>
  </section>
</div>

<section class="container py-5">
  <h2 class="mb-5">Наши популярные позиции</h2>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row g-4 mt-4">
    <?php foreach ($products as $product): ?>
      <?php require view_path('product_card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php require view_path('footer.php'); ?>
