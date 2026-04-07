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

        if ($action === 'address_update') {
            UserRepository::updateAddress((int) $user['id'], (int) ($_POST['address_id'] ?? 0), $_POST);
            flash('success', 'Адрес обновлён.');
        }

        if ($action === 'address_delete') {
            UserRepository::deleteAddress((int) $user['id'], (int) ($_POST['address_id'] ?? 0));
            flash('success', 'Адрес удалён.');
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
$pageDescription = 'Личный кабинет пиццерии Flour and Fire. Личные данные, адреса доставки.';
$pageKeywords = 'личный кабинет, мои заказы, история заказов, профиль';
$pageRobots = 'noindex, follow';
$activePage = 'account';
$showTopInfoBar = false;

require view_path('header.php');
?>
<section class="container py-5">
  <header class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="m-0">Личный кабинет</h1>
  </header>

  <?php if ($dbError): ?>
    <div class="alert alert-danger"><?= e($dbError) ?></div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="form-section">
        <h4 class="form-title">Личные данные</h4>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="profile">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="firstName" class="form-label">Имя</label>
              <input type="text" class="form-control" id="firstName" name="first_name" placeholder="Введите имя" value="<?= e($user['first_name']) ?>">
            </div>
            <div class="col-md-6">
              <label for="lastName" class="form-label">Фамилия</label>
              <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Введите фамилию" value="<?= e($user['last_name']) ?>">
            </div>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="example@mail.com" value="<?= e($user['email']) ?>">
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="text" class="form-control" id="phone" name="phone" placeholder="+7 (999) 123-45-67" value="<?= e($user['phone'] ?? '') ?>">
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-dark">Сохранить изменения</button>
          </div>
        </form>
      </div>

      <div class="form-section">
        <h4 class="form-title">Смена пароля</h4>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <div class="mb-3">
            <label for="currentPassword" class="form-label">Текущий пароль</label>
            <input type="password" class="form-control" id="currentPassword" name="current_password" placeholder="Введите текущий пароль" autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label for="newPassword" class="form-label">Новый пароль</label>
            <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="Придумайте новый пароль" minlength="8" autocomplete="new-password">
            <div class="form-text" id="accountPasswordHelp">Пароль должен содержать минимум 8 символов</div>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Подтвердите новый пароль</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" placeholder="Повторите новый пароль" minlength="8" autocomplete="new-password">
            <div class="form-text" id="accountConfirmPasswordHelp"></div>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-dark">Изменить пароль</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="row justify-content-center mt-4">
    <div class="col-lg-6">
      <div class="form-section mb-4">
        <h4 class="form-title">Адреса доставки</h4>
        <form method="post" class="mb-4">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="address">
          <div class="mb-3">
            <label for="addressLabel" class="form-label">Название адреса</label>
            <input type="text" class="form-control" id="addressLabel" name="label" placeholder="Например, Дом" />
          </div>
          <div class="mb-3">
            <label for="fullAddress" class="form-label">Полный адрес</label>
            <input type="text" class="form-control" id="fullAddress" name="full_address" placeholder="Улица, дом, квартира, подъезд" required />
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="recipientName" class="form-label">Получатель</label>
              <input type="text" class="form-control" id="recipientName" name="recipient_name" placeholder="Имя получателя" />
            </div>
            <div class="col-md-6">
              <label for="recipientPhone" class="form-label">Телефон</label>
              <input type="text" class="form-control" id="recipientPhone" name="recipient_phone" placeholder="+7 (999) 123-45-67" />
            </div>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
            <label class="form-check-label" for="is_default">Сделать адресом по умолчанию</label>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-dark">Сохранить адрес</button>
          </div>
        </form>

        <?php if ($addresses === []): ?>
          <p class="text-muted mb-0">Сохранённых адресов пока нет.</p>
        <?php else: ?>
          <?php foreach ($addresses as $address): ?>
            <div class="border rounded p-3 mb-3">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fw-bold">
                  <?= e($address['label'] ?: 'Адрес') ?>
                  <?php if ($address['is_default']): ?>
                    <span class="badge bg-dark">Основной</span>
                  <?php endif; ?>
                </div>
                <form method="post" onsubmit="return confirm('Удалить этот адрес?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="address_delete">
                  <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm">Удалить</button>
                </form>
              </div>

              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="address_update">
                <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">

                <div class="mb-3">
                  <label class="form-label" for="addressLabel<?= (int) $address['id'] ?>">Название адреса</label>
                  <input
                    type="text"
                    class="form-control"
                    id="addressLabel<?= (int) $address['id'] ?>"
                    name="label"
                    placeholder="Например, Дом"
                    value="<?= e($address['label'] ?? '') ?>"
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label" for="fullAddress<?= (int) $address['id'] ?>">Полный адрес</label>
                  <input
                    type="text"
                    class="form-control"
                    id="fullAddress<?= (int) $address['id'] ?>"
                    name="full_address"
                    placeholder="Улица, дом, квартира, подъезд"
                    value="<?= e($address['full_address']) ?>"
                    required
                  />
                </div>

                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label" for="recipientName<?= (int) $address['id'] ?>">Получатель</label>
                    <input
                      type="text"
                      class="form-control"
                      id="recipientName<?= (int) $address['id'] ?>"
                      name="recipient_name"
                      placeholder="Имя получателя"
                      value="<?= e($address['recipient_name'] ?? '') ?>"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="recipientPhone<?= (int) $address['id'] ?>">Телефон</label>
                    <input
                      type="text"
                      class="form-control"
                      id="recipientPhone<?= (int) $address['id'] ?>"
                      name="recipient_phone"
                      placeholder="+7 (999) 123-45-67"
                      value="<?= e($address['recipient_phone'] ?? '') ?>"
                    />
                  </div>
                </div>

                <div class="form-check mb-3">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    name="is_default"
                    id="is_default_<?= (int) $address['id'] ?>"
                    <?= !empty($address['is_default']) ? 'checked' : '' ?>
                  >
                  <label class="form-check-label" for="is_default_<?= (int) $address['id'] ?>">Сделать адресом по умолчанию</label>
                </div>

                <div class="text-end">
                  <button type="submit" class="btn btn-dark">Сохранить изменения</button>
                </div>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="form-section">
        <h4 class="form-title">История заказов</h4>
        <?php if ($orders === []): ?>
          <p class="text-muted mb-0">Пока нет заказов.</p>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between">
                <strong><?= e($order['order_number']) ?></strong>
                <span><?= e(format_price($order['total_amount'])) ?></span>
              </div>
              <div class="small text-muted mb-2">Статус: <?= e($order['status']) ?></div>
              <div class="small mb-1">
                <strong>Способ получения:</strong>
                <?= e($order['delivery_method'] === 'pickup' ? 'Самовывоз' : 'Доставка') ?>
              </div>
              <?php if (!empty($order['delivery_address'])): ?>
                <div class="small mb-1">
                  <strong>Адрес:</strong>
                  <?= e($order['delivery_address']) ?>
                </div>
              <?php endif; ?>
              <div class="small mb-2">
                <strong>Промокод:</strong>
                <?= e($order['promo_code'] ?: 'не использовался') ?>
              </div>
              <?php if (!empty($order['items'])): ?>
                <div class="small mb-2">
                  <strong>Состав заказа:</strong>
                </div>
                <div class="small">
                  <?php foreach ($order['items'] as $item): ?>
                    <div>
                      <?= e($item['product_name_snapshot']) ?>
                      · <?= e((string) (float) $item['size_cm_snapshot']) ?> см
                      · <?= (int) $item['quantity'] ?> шт.
                      · <?= e(format_price($item['line_total'])) ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<script>
  (function () {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const passwordHelp = document.getElementById('accountPasswordHelp');
    const confirmHelp = document.getElementById('accountConfirmPasswordHelp');

    if (!newPasswordInput || !confirmPasswordInput || !passwordHelp || !confirmHelp) {
      return;
    }

    const updatePasswordState = function () {
      if (newPasswordInput.value === '') {
        passwordHelp.textContent = 'Пароль должен содержать минимум 8 символов';
        passwordHelp.style.color = '';
        newPasswordInput.setCustomValidity('');
        return;
      }

      if (newPasswordInput.value.length < 8) {
        passwordHelp.textContent = 'Новый пароль слишком короткий';
        passwordHelp.style.color = '#dc3545';
        newPasswordInput.setCustomValidity('Новый пароль слишком короткий');
        return;
      }

      passwordHelp.textContent = 'Пароль подходит по длине';
      passwordHelp.style.color = '#198754';
      newPasswordInput.setCustomValidity('');
    };

    const updateConfirmationState = function () {
      if (confirmPasswordInput.value === '') {
        confirmHelp.textContent = '';
        confirmPasswordInput.setCustomValidity('');
        return;
      }

      if (newPasswordInput.value === confirmPasswordInput.value) {
        confirmHelp.textContent = 'Пароли совпадают';
        confirmHelp.style.color = '#198754';
        confirmPasswordInput.setCustomValidity('');
        return;
      }

      confirmHelp.textContent = 'Пароли не совпадают';
      confirmHelp.style.color = '#dc3545';
      confirmPasswordInput.setCustomValidity('Пароли не совпадают');
    };

    newPasswordInput.addEventListener('input', function () {
      updatePasswordState();
      updateConfirmationState();
    });

    confirmPasswordInput.addEventListener('input', updateConfirmationState);

    updatePasswordState();
    updateConfirmationState();
  }());
</script>
<?php require view_path('footer.php'); ?>
