<?php

use App\Repositories\CartRepository;

$cartCount = 0;

try {
    $cartCount = CartRepository::cartCount();
} catch (Throwable) {
    $cartCount = 0;
}

$flashMessages = consume_flash_messages();
$activePage = $activePage ?? '';
$pageTitle = $pageTitle ?? app_name();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="<?= e(asset('images/logo.png')) ?>" type="image/x-icon" />
  <link rel="stylesheet" href="<?= e(asset('bootstrap/bootstrap.min.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('style/style.css')) ?>" />
</head>
<body class="position-relative">
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center g-4" href="<?= e(route('home')) ?>">
        <img src="<?= e(asset('images/logo.png')) ?>" alt="Logo" class="d-inline-block align-text-top logo" />
        <?= e(app_name()) ?>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= e(route('home')) ?>">Главная</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'menu' ? 'active' : '' ?>" href="<?= e(route('menu')) ?>">Меню</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'photos' ? 'active' : '' ?>" href="<?= e(route('photos')) ?>">Фото</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'contact' ? 'active' : '' ?>" href="<?= e(route('contact')) ?>">Контакты</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'cart' ? 'active' : '' ?>" href="<?= e(route('cart')) ?>">Корзина (<?= $cartCount ?>)</a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= in_array($activePage, ['login', 'register', 'account', 'admin'], true) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
              Аккаунт
            </a>
            <ul class="dropdown-menu">
              <?php if ($user): ?>
                <li><a class="dropdown-item" href="<?= e(route('account')) ?>">Личный кабинет</a></li>
                <?php if (is_admin()): ?>
                  <li><a class="dropdown-item" href="<?= e(route('admin')) ?>">Админка</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="<?= e(route('logout')) ?>">Выйти</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= e(route('login')) ?>">Войти</a></li>
                <li><a class="dropdown-item" href="<?= e(route('register')) ?>">Зарегистрироваться</a></li>
              <?php endif; ?>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="bg-warning py-1">
    <div class="container">
      <div class="row align-items-center text-center">
        <div class="col-md-4">
          <strong>+7 (999) 123-45-67</strong>
        </div>
        <div class="col-md-4">
          <strong>бульвар Купца Ефремова, 3, Чебоксары</strong>
        </div>
        <div class="col-md-4">
          <strong>Ежедневно 10:00-23:00</strong>
        </div>
      </div>
    </div>
  </div>

  <main class="flex-grow-1">
    <div class="container mt-4">
      <?php foreach ($flashMessages as $flashMessage): ?>
        <div class="alert alert-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
      <?php endforeach; ?>
    </div>
