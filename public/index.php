<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\ProductRepository;

$pageTitle = page_title('Главная');
$pageDescription = 'Пиццерия Flour and Fire — настоящая итальянская пицца из дровяной печи. Свежайшие ингредиенты, тонкое тесто и атмосфера уюта. Доставка пиццы и самовывоз.';
$pageKeywords = 'пиццерия, итальянская пицца, пицца из печи, дровяная печь, доставка пиццы, заказать пиццу, пицца на заказ';
$pageOgTitle = 'Пиццерия Flour and Fire — итальянская пицца из дровяной печи';
$pageOgDescription = 'Настоящая пицца из дровяной печи. Свежие ингредиенты, тонкое тесто, итальянские рецепты. Доставка и самовывоз.';
$pageOgImage = 'images/hero.webp';
$pageCanonical = absolute_url(route('home'));
$pageSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => app_name(),
    'description' => $pageDescription,
    'servesCuisine' => ['Итальянская кухня', 'Пицца'],
    'image' => absolute_url('images/hero.webp'),
    'url' => $pageCanonical,
    'telephone' => '+79371234567',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'бульвар Купца Ефремова, 3',
        'addressLocality' => 'Чебоксары',
        'addressCountry' => 'RU',
    ],
    'openingHoursSpecification' => [[
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        'opens' => '10:00',
        'closes' => '23:00',
    ]],
];
$activePage = 'home';
$bodyClass = 'position-relative';
$showTopInfoBar = true;
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
    <p class="lead text-light">Лучшая итальянская пицца</p>
  </header>

  <section class="container py-5">
    <div class="row">
      <div class="col-md-6" style="min-height: 38vh"></div>
      <div class="col-md-6">
        <h2 class="text-light">Мы используем проверенные рецепты и свежие ингредиенты</h2>
        <p class="text-light">
          В нашей пиццерии каждая пицца — это вкус традиций и качество в
          каждой детали. Мы готовим по проверенным рецептам, которые
          передаются из поколения в поколение, и используем только свежие
          ингредиенты от местных фермеров.
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
