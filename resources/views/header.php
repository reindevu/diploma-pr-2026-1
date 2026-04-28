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
$pageDescription = $pageDescription ?? '';
$pageKeywords = $pageKeywords ?? '';
$pageRobots = $pageRobots ?? 'index, follow';
$pageOgTitle = $pageOgTitle ?? $pageTitle;
$pageOgDescription = $pageOgDescription ?? $pageDescription;
$pageOgImage = $pageOgImage ?? '';
$pageOgType = $pageOgType ?? 'website';
$pageCanonical = $pageCanonical ?? current_url();
$pageSchema = $pageSchema ?? null;
$bodyClass = $bodyClass ?? '';
$showTopInfoBar = $showTopInfoBar ?? false;
$user = current_user();
$ogImage = '';

if ($pageOgImage !== '') {
    $ogImage = str_starts_with($pageOgImage, 'http')
        ? $pageOgImage
        : absolute_url(ltrim($pageOgImage, '/'));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="<?= e($pageRobots) ?>" />
  <?php if ($pageDescription !== ''): ?>
    <meta name="description" content="<?= e($pageDescription) ?>" />
  <?php endif; ?>
  <?php if ($pageKeywords !== ''): ?>
    <meta name="keywords" content="<?= e($pageKeywords) ?>" />
  <?php endif; ?>
  <link rel="canonical" href="<?= e($pageCanonical) ?>" />
  <?php if ($pageOgTitle !== ''): ?>
    <meta property="og:title" content="<?= e($pageOgTitle) ?>" />
  <?php endif; ?>
  <?php if ($pageOgDescription !== ''): ?>
    <meta property="og:description" content="<?= e($pageOgDescription) ?>" />
  <?php endif; ?>
  <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>" />
  <?php endif; ?>
  <meta property="og:type" content="<?= e($pageOgType) ?>" />
  <meta property="og:url" content="<?= e($pageCanonical) ?>" />
  <meta property="og:site_name" content="<?= e(app_name()) ?>" />
  <meta property="og:locale" content="ru_RU" />
  <meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>" />
  <meta name="twitter:title" content="<?= e($pageOgTitle) ?>" />
  <?php if ($pageOgDescription !== ''): ?>
    <meta name="twitter:description" content="<?= e($pageOgDescription) ?>" />
  <?php endif; ?>
  <?php if ($ogImage !== ''): ?>
    <meta name="twitter:image" content="<?= e($ogImage) ?>" />
  <?php endif; ?>
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="<?= e(asset('images/logo.png')) ?>" type="image/x-icon" />
  <link rel="stylesheet" href="<?= e(asset('bootstrap/bootstrap.min.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('style/style.css')) ?>" />
  <?php if (is_array($pageSchema)): ?>
    <script type="application/ld+json"><?= json_encode($pageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <?php endif; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . e($bodyClass) . '"' : '' ?>>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top" style="z-index: 1021">
    <div class="container">
      <a class="navbar-brand fw-bold d-flex align-items-center g-4" href="<?= e(route('home')) ?>">
        <img src="<?= e(asset('images/logo.png')) ?>" alt="Логотип" class="d-inline-block align-text-top logo" />
        <?= e(app_name()) ?>
      </a>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?> href="<?= e(route('home')) ?>">Главная</a>
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
                <li><a class="dropdown-item" href="<?= e(route('cart')) ?>">Корзина<?php if ($cartCount > 0): ?> (<?= $cartCount ?>)<?php endif; ?></a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="<?= e(route('logout')) ?>">Выйти</a></li>
              <?php else: ?>
                <li><a class="dropdown-item" href="<?= e(route('account')) ?>">Личный кабинет</a></li>
                <li><a class="dropdown-item" href="<?= e(route('login')) ?>">Войти</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="<?= e(route('cart')) ?>">Корзина<?php if ($cartCount > 0): ?> (<?= $cartCount ?>)<?php endif; ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <?php if ($showTopInfoBar): ?>
    <div class="bg-warning py-1">
      <div class="container">
        <div class="row align-items-center text-center">
          <div class="col-md-4">
            <strong><i class="fas fa-phone me-2"></i>+7 (999) 123-45-67</strong>
          </div>
          <div class="col-md-4">
            <strong><i class="fas fa-map-marker-alt me-2"></i>бульвар Купца Ефремова, 3</strong>
          </div>
          <div class="col-md-4">
            <strong><i class="fas fa-clock me-2"></i>Ежедневно 10:00-23:00</strong>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <main class="flex-grow-1">
    <?php if ($flashMessages !== []): ?>
      <div class="container mt-4">
        <?php foreach ($flashMessages as $flashMessage): ?>
          <div class="alert alert-<?= e($flashMessage['type']) ?>"><?= e($flashMessage['message']) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
