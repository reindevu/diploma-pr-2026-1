<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Auth;

if (is_logged_in()) {
    redirect(route('account'));
}

if (is_post()) {
    verify_csrf();
    set_old_input($_POST);

    if (Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
        clear_old_input();
        flash('success', 'Вы вошли в систему.');
        redirect(route('account'));
    }

    flash('danger', 'Неверный email или пароль.');
    redirect(route('login'));
}

$pageTitle = page_title('Вход');
$pageDescription = 'Вход в личный кабинет пиццерии Flour and Fire. Авторизуйтесь, чтобы отслеживать заказы и управлять профилем.';
$pageKeywords = 'вход, авторизация, личный кабинет пиццерия';
$pageRobots = 'noindex, follow';
$activePage = 'login';
$showTopInfoBar = false;

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-center">
  <div class="col-md-4 px-3 py-4 rounded-4 border">
    <div class="text-center">
      <h3>Вход в систему</h3>
      <p class="text-muted">Пожалуйста, введите свои учетные данные</p>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Введите email" value="<?= e(old('email')) ?>" required />
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="Введите пароль" required />
      </div>

      <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="rememberMe" />
        <label class="form-check-label" for="rememberMe">Запомнить меня</label>
      </div>

      <button type="submit" class="btn btn-dark btn-login my-3 w-100">Войти</button>

      <div class="d-flex justify-content-center">
        <a href="#" class="text-decoration-none text-dark">Еще нет аккаунта?</a>
        <span class="mx-2">|</span>
        <a href="<?= e(route('register')) ?>" class="text-decoration-none text-primary">Зарегистрироваться</a>
        <span class="mx-2"></span>
        <a href="<?= e(route('admin')) ?>" class="text-decoration-none text-primary">Админка</a>
      </div>
    </form>
  </div>
</section>
<?php require view_path('footer.php'); ?>
