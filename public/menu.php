<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Меню');
$pageDescription = 'Меню пиццерии Flour and Fire: классическая Маргарита, пикантная Пепперони, сырная Четыре сыра, мясная и вегетарианская пицца. Все размеры и цены.';
$pageKeywords = 'меню пиццерии, пицца меню, цены на пиццу, маргарита, пепперони, четыре сыра, гавайская пицца, мясная пицца';
$pageOgTitle = 'Меню пиццерии Flour and Fire — цены и состав';
$pageOgDescription = 'Полное меню пиццерии Flour and Fire. Классические и авторские пиццы, выбор размера, подробный состав каждой позиции.';
$pageOgImage = 'images/original.png';
$activePage = 'menu';
$showTopInfoBar = true;
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
  <p class="text-muted">Наши популярные позиции</p>

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
