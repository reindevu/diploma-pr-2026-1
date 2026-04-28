<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Фото');
$pageDescription = 'Фотографии пиццы из дровяной печи Flour and Fire. Аппетитные снимки блюд, интерьер уютной пиццерии, процесс приготовления.';
$pageKeywords = 'фото пиццы, фотографии пиццерии, интерьер пиццерии, как выглядит пицца, аппетитная пицца фото';
$pageOgTitle = 'Фотогалерея пиццерии Flour and Fire';
$pageOgDescription = 'Смотрите фотографии нашей пиццы, интерьера и процесса приготовления. Выбирайте самое аппетитное!';
$pageOgImage = 'images/chiken_pini.png';
$pageCanonical = absolute_url(route('photos'));
$activePage = 'photos';
$showTopInfoBar = true;
$products = [];
$dbError = null;

try {
    $products = ProductRepository::allActive();
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

if ($products !== []) {
    $pageOgImage = (string) ($products[0]['image_path'] ?: $pageOgImage);
}

$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ImageGallery',
    'name' => 'Фото пиццы ' . app_name(),
    'description' => $pageDescription,
    'url' => $pageCanonical,
    'image' => array_map(
        static fn (array $product): string => absolute_url($product['image_path'] ?: 'images/logo.png'),
        $products
    ),
];

require view_path('header.php');
?>
<section class="container py-5 text-center">
  <h2>Наша галерея пиццы</h2>
  <p class="text-muted">Взгляните на нашу вкусную пиццу только что из духовки</p>

  <?php if ($dbError): ?>
    <div class="alert alert-danger text-start"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row g-4 pt-5">
    <?php foreach ($products as $product): ?>
      <div class="col-md-4">
        <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" class="img-fluid rounded" alt="Пицца <?= e($product['name']) ?>" />
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php require view_path('footer.php'); ?>
