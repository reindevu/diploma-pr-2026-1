<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$pageTitle = page_title('Контакты');
$activePage = 'contact';

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-between flex-wrap">
  <section>
    <h2>Адрес</h2>
    <p>бульвар Купца Ефремова, 3, Чебоксары, Россия</p>
    <h2>Телефон для заказа</h2>
    <p class="phone-number">+7 (999) 123-45-67</p>
    <h2>Часы работы</h2>
    <p>Пн-Вс: 10:00 – 23:00</p>
    <p class="text-muted">Страница уже на PHP, но контактные данные пока остаются статическими.</p>
  </section>
  <section class="w-100">
    <iframe
      src="https://yandex.ru/map-widget/v1/?um=constructor%3Afa980cc501d6d6d09fa4c8838692e4387ff6dfcf1afd1a50679830241569ed40&amp;source=constructor"
      width="100%"
      height="400"
      frameborder="0"
    ></iframe>
  </section>
</section>
<?php require view_path('footer.php'); ?>
