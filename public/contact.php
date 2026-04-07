<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$pageTitle = page_title('Контакты');
$pageDescription = 'Контакты пиццерии Flour and Fire: адрес, телефон, часы работы, схема проезда. Как добраться и связаться с нами.';
$pageKeywords = 'адрес пиццерии, телефон пиццерии, часы работы, контакты пиццерии, как проехать, пиццерия на карте';
$pageOgTitle = 'Контакты пиццерии Flour and Fire — адрес и телефон';
$pageOgDescription = 'Адрес, телефон, часы работы и схема проезда. Ждем вас в гости или доставим пиццу домой!';
$activePage = 'contact';
$showTopInfoBar = true;

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-between flex-wrap">
  <section class="">
    <h2>Адрес</h2>
    <p>бульвар Купца Ефремова, 3, Чебоксары, Россия</p>
    <h2>Телефон для заказа</h2>
    <p class="phone-number">+7 (937) 123-45-67</p>
    <h2>Часы работы</h2>
    <p>Пн-Вс: 10:00 – 23:00</p>
  </section>
  <section class="w-100">
    <iframe
      class=""
      src="https://yandex.ru/map-widget/v1/?um=constructor%3Afa980cc501d6d6d09fa4c8838692e4387ff6dfcf1afd1a50679830241569ed40&amp;source=constructor"
      width="100%"
      height="400"
      frameborder="0"
    ></iframe>
  </section>
</section>
<?php require view_path('footer.php'); ?>
