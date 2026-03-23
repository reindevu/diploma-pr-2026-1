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
    $result = Auth::register($_POST);

    if ($result['success']) {
        clear_old_input();
        flash('success', 'Аккаунт создан.');
        redirect(route('account'));
    }

    foreach ($result['errors'] as $error) {
        flash('danger', $error);
    }

    redirect(route('register'));
}

$pageTitle = page_title('Регистрация');
$activePage = 'register';

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-center">
  <div class="col-md-5 px-3 py-4 rounded-4 border">
    <div class="text-center mb-4">
      <h2>Создать аккаунт</h2>
      <p class="text-muted">Регистрация работает через таблицу `users`</p>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label for="firstName" class="form-label">Имя</label>
          <input type="text" class="form-control" id="firstName" name="first_name" value="<?= e(old('first_name')) ?>" required />
        </div>
        <div class="col-md-6">
          <label for="lastName" class="form-label">Фамилия</label>
          <input type="text" class="form-control" id="lastName" name="last_name" value="<?= e(old('last_name')) ?>" required />
        </div>

        <div class="col-12">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= e(old('email')) ?>" required />
        </div>

        <div class="col-12">
          <label for="phone" class="form-label">Телефон</label>
          <input type="text" class="form-control" id="phone" name="phone" value="<?= e(old('phone')) ?>" />
        </div>

        <div class="col-12">
          <label for="password" class="form-label">Пароль</label>
          <input type="password" class="form-control" id="password" name="password" required />
          <div class="form-text">Пароль должен содержать минимум 8 символов</div>
        </div>

        <div class="col-12">
          <label for="confirmPassword" class="form-label">Подтвердите пароль</label>
          <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required />
        </div>

        <div class="col-12 mt-4">
          <button class="btn btn-dark w-100 py-2" type="submit">Зарегистрироваться</button>
        </div>

        <div class="col-12 text-center mt-3">
          <p>Уже есть аккаунт? <a href="<?= e(route('login')) ?>">Войти</a></p>
        </div>
      </div>
    </form>
  </div>
</section>
<?php require view_path('footer.php'); ?>
