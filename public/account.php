<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;

require_auth();

$user = current_user();
$addresses = [];
$orders = [];
$dbError = null;

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'profile') {
            UserRepository::updateProfile((int) $user['id'], $_POST);
            flash('success', 'Профиль обновлён.');
        }

        if ($action === 'password') {
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($newPassword !== $confirmPassword) {
                throw new RuntimeException('Подтверждение нового пароля не совпадает.');
            }

            if (mb_strlen($newPassword) < 8) {
                throw new RuntimeException('Новый пароль слишком короткий.');
            }

            if (!UserRepository::changePassword((int) $user['id'], (string) ($_POST['current_password'] ?? ''), $newPassword)) {
                throw new RuntimeException('Текущий пароль неверен.');
            }

            flash('success', 'Пароль обновлён.');
        }

        if ($action === 'address') {
            UserRepository::saveAddress((int) $user['id'], $_POST);
            flash('success', 'Адрес сохранён.');
        }
    } catch (Throwable $exception) {
        flash('danger', $exception->getMessage());
    }

    redirect(route('account'));
}

try {
    $addresses = UserRepository::addresses((int) $user['id']);
    $orders = OrderRepository::byUser((int) $user['id']);
} catch (Throwable $exception) {
    $dbError = $exception->getMessage();
}

$pageTitle = page_title('Личный кабинет');
$activePage = 'account';

require view_path('header.php');
?>
<section class="container py-5">
  <header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0">Личный кабинет</h1>
  </header>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="form-section border rounded p-4 mb-4">
        <h4 class="form-title">Личные данные</h4>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="firstName" class="form-label">Имя</label>
              <input type="text" class="form-control" id="firstName" name="first_name" value="<?= e($user['first_name']) ?>">
            </div>
            <div class="col-md-6">
              <label for="lastName" class="form-label">Фамилия</label>
              <input type="text" class="form-control" id="lastName" name="last_name" value="<?= e($user['last_name']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($user['email']) ?>">
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?= e($user['phone']) ?>">
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-dark">Сохранить изменения</button>
          </div>
        </form>
      </div>

      <div class="form-section border rounded p-4 mb-4">
        <h4 class="form-title">Смена пароля</h4>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Текущий пароль</label>
            <input type="password" class="form-control" id="currentPassword" name="current_password">
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">Новый пароль</label>
            <input type="password" class="form-control" id="newPassword" name="new_password">
            <div class="form-text">Пароль должен содержать минимум 8 символов</div>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Подтвердите новый пароль</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirm_password">
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-dark">Изменить пароль</button>
          </div>
        </form>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="border rounded p-4 mb-4">
        <h4 class="mb-3">Сохранённые адреса</h4>
        <form method="post" class="mb-4">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="address">
          <div class="mb-2">
            <input type="text" class="form-control" name="label" placeholder="Название адреса, например Дом" />
          </div>
          <div class="mb-2">
            <input type="text" class="form-control" name="full_address" placeholder="Полный адрес" required />
          </div>
          <div class="row g-2 mb-2">
            <div class="col">
              <input type="text" class="form-control" name="recipient_name" placeholder="Получатель" />
            </div>
            <div class="col">
              <input type="text" class="form-control" name="recipient_phone" placeholder="Телефон" />
            </div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
            <label class="form-check-label" for="is_default">Сделать адресом по умолчанию</label>
          </div>
          <button type="submit" class="btn btn-outline-dark">Сохранить адрес</button>
        </form>

        <?php foreach ($addresses as $address): ?>
          <div class="border rounded p-3 mb-2">
            <div class="fw-bold"><?= e($address['label'] ?: 'Адрес') ?> <?= $address['is_default'] ? '<span class="badge bg-dark">Основной</span>' : '' ?></div>
            <div><?= e($address['full_address']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="border rounded p-4">
        <h4 class="mb-3">История заказов</h4>
        <?php if ($orders === []): ?>
          <p class="text-muted mb-0">Пока нет заказов.</p>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between">
                <strong><?= e($order['order_number']) ?></strong>
                <span class="text-muted"><?= e(format_price($order['total_amount'])) ?></span>
              </div>
              <div class="text-muted small">Статус: <?= e($order['status']) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require view_path('footer.php'); ?>
