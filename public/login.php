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
$activePage = 'login';

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-center">
  <div class="col-md-5 px-3 py-4 rounded-4 border">
    <div class="text-center">
      <h3>Вход в систему</h3>
      <p class="text-muted">Введите email и пароль</p>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required />
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <input type="password" class="form-control" id="password" name="password" required />
      </div>

      <button type="submit" class="btn btn-dark btn-login my-3 w-100">Войти</button>

      <div class="d-flex justify-content-center">
        <a href="<?= e(route('register')) ?>" class="text-decoration-none text-primary">Зарегистрироваться</a>
      </div>
    </form>
  </div>
</section>
<?php require view_path('footer.php'); ?>
