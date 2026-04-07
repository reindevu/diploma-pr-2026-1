<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$pageTitle = page_title('Фото');
$pageDescription = 'Фотографии пиццы из дровяной печи Flour and Fire. Аппетитные снимки блюд, интерьер уютной пиццерии, процесс приготовления.';
$pageKeywords = 'фото пиццы, фотографии пиццерии, интерьер пиццерии, как выглядит пицца, аппетитная пицца фото';
$pageOgTitle = 'Фотогалерея пиццерии Flour and Fire';
$pageOgDescription = 'Смотрите фотографии нашей пиццы, интерьера и процесса приготовления. Выбирайте самое аппетитное!';
$pageOgImage = 'images/chiken_pini.png';
$activePage = 'photos';
$showTopInfoBar = true;
$galleryImages = [
    ['src' => 'images/spinachi.png', 'alt' => 'Пицца Спиначи'],
    ['src' => 'images/chiken_pini.png', 'alt' => 'Пицца Чикен Пини'],
    ['src' => 'images/paprica_vida.png', 'alt' => 'Пицца Паприка Вида'],
    ['src' => 'images/margherita.png', 'alt' => 'Пицца Маргарита'],
    ['src' => 'images/gribnaya.png', 'alt' => 'Пицца Грибная'],
    ['src' => 'images/original.png', 'alt' => 'Пицца Оригинал'],
];

require view_path('header.php');
?>
<section class="container py-5 text-center">
  <h2>Наша галерея пиццы</h2>
  <p class="text-muted">Взгляните на нашу вкусную пиццу только что из духовки</p>

  <div class="row g-4 pt-5">
    <?php foreach ($galleryImages as $image): ?>
      <div class="col-md-4">
        <img src="<?= e(asset($image['src'])) ?>" class="img-fluid rounded" alt="<?= e($image['alt']) ?>" />
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php require view_path('footer.php'); ?>
