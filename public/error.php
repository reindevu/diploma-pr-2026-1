<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

http_response_code(404);

$pageTitle = '404 — Страница не найдена | Flour and Fire';
$pageDescription = 'Страница не найдена. Вернитесь на главную или в меню пиццерии Flour and Fire.';
$pageRobots = 'noindex, follow';
$pageOgTitle = '404 — Страница не найдена | Flour and Fire';
$pageOgDescription = 'Кажется, такая пицца не в нашем меню. Вернитесь на главную и выберите что-то вкусное!';
$pageOgImage = 'images/hero.webp';
$activePage = '';
$showTopInfoBar = false;

require view_path('header.php');
?>
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
<?php require view_path('footer.php'); ?>
