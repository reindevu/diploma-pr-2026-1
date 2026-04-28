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
$pageDescription = 'Регистрация на сайте пиццерии Flour and Fire. Создайте аккаунт для быстрого оформления заказов и получения скидок.';
$pageKeywords = 'регистрация, создать аккаунт, новый пользователь';
$pageRobots = 'noindex, follow';
$activePage = 'register';
$showTopInfoBar = false;

require view_path('header.php');
?>
<section class="container py-5 d-flex justify-content-center">
  <div class="col-md-4 px-3 py-4 rounded-4 border">
    <div class="text-center mb-4">
      <h2>Создать аккаунт</h2>
      <p class="text-muted">Заполните форму для регистрации</p>
    </div>

    <form method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label for="firstName" class="form-label">Имя</label>
          <input type="text" class="form-control" id="firstName" name="first_name" placeholder="Введите имя" value="<?= e(old('first_name')) ?>" required />
        </div>
        <div class="col-md-6">
          <label for="lastName" class="form-label">Фамилия</label>
          <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Введите фамилию" value="<?= e(old('last_name')) ?>" required />
        </div>

        <div class="col-12">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="example@mail.com" value="<?= e(old('email')) ?>" required />
        </div>

        <div class="col-12">
          <label for="password" class="form-label">Пароль</label>
          <input
            type="password"
            class="form-control"
            id="password"
            name="password"
            minlength="8"
            autocomplete="new-password"
            placeholder="Придумайте пароль"
            required
          />
          <div class="password-strength mt-2">
            <div class="strength-bar" id="strengthBar"></div>
          </div>
          <div class="form-text" id="passwordHelp">Пароль должен содержать минимум 8 символов, буквы разного регистра, цифру и спецсимвол</div>
        </div>

        <div class="col-12">
          <label for="confirmPassword" class="form-label">Подтвердите пароль</label>
          <input
            type="password"
            class="form-control"
            id="confirmPassword"
            name="password_confirmation"
            minlength="8"
            autocomplete="new-password"
            placeholder="Повторите пароль"
            required
          />
          <div class="form-text" id="confirmPasswordHelp"></div>
        </div>

        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="terms" required />
            <label class="form-check-label" for="terms">
              Я согласен с <a href="#">условиями использования</a> и
              <a href="#">политикой конфиденциальности</a>
            </label>
          </div>
        </div>

        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" value="1" />
            <label class="form-check-label" for="rememberMe">Запомнить меня</label>
          </div>
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
<script>
  (function () {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('strengthBar');
    const passwordHelp = document.getElementById('passwordHelp');
    const confirmPasswordHelp = document.getElementById('confirmPasswordHelp');

    if (!passwordInput || !confirmPasswordInput || !strengthBar || !passwordHelp || !confirmPasswordHelp) {
      return;
    }

    const evaluatePassword = function (value) {
      const hasLower = /[a-zа-яё]/.test(value);
      const hasUpper = /[A-ZА-ЯЁ]/.test(value);
      const hasDigit = /\d/.test(value);
      const hasSpecial = /[^a-zа-яё0-9]/i.test(value);
      const uniqueCharacters = new Set(value).size;
      const typesCount = [hasLower, hasUpper, hasDigit, hasSpecial].filter(Boolean).length;
      const missingParts = [];

      if (value.length === 0) {
        return {
          width: '0%',
          color: '#e9ecef',
          text: 'Пароль должен содержать минимум 8 символов',
          valid: false,
        };
      }

      if (value.length < 8) {
        return {
          width: '20%',
          color: '#dc3545',
          text: 'Слишком короткий пароль: минимум 8 символов',
          valid: false,
        };
      }

      if (!hasLower) {
        missingParts.push('строчные буквы');
      }

      if (!hasUpper) {
        missingParts.push('заглавные буквы');
      }

      if (!hasDigit) {
        missingParts.push('цифры');
      }

      if (!hasSpecial) {
        missingParts.push('спецсимволы');
      }

      if (uniqueCharacters <= 3) {
        return {
          width: '25%',
          color: '#dc3545',
          text: 'Слабый пароль: слишком много повторяющихся символов',
          valid: false,
        };
      }

      if (typesCount <= 1) {
        return {
          width: '30%',
          color: '#dc3545',
          text: 'Слабый пароль: добавьте ' + missingParts.slice(0, 2).join(' и '),
          valid: false,
        };
      }

      if (typesCount === 2) {
        return {
          width: '55%',
          color: '#fd7e14',
          text: 'Средний пароль: для надёжности добавьте ' + missingParts.slice(0, 2).join(' и '),
          valid: false,
        };
      }

      if (typesCount === 3) {
        return {
          width: '75%',
          color: '#ffc107',
          text: 'Почти готово: добавьте ' + missingParts[0],
          valid: false,
        };
      }

      return {
        width: '100%',
        color: '#198754',
        text: value.length >= 12 ? 'Надёжный пароль' : 'Очень хороший пароль',
        valid: true,
      };
    };

    const updatePasswordStrength = function () {
      const result = evaluatePassword(passwordInput.value);
      strengthBar.style.width = result.width;
      strengthBar.style.backgroundColor = result.color;
      passwordHelp.textContent = result.text;
      passwordHelp.style.color = result.color === '#e9ecef' ? '' : result.color;
      passwordInput.setCustomValidity(result.valid ? '' : result.text);
    };

    const updatePasswordConfirmation = function () {
      if (confirmPasswordInput.value === '') {
        confirmPasswordHelp.textContent = '';
        confirmPasswordInput.setCustomValidity('');
        return;
      }

      if (passwordInput.value === confirmPasswordInput.value) {
        confirmPasswordHelp.textContent = 'Пароли совпадают';
        confirmPasswordHelp.style.color = '#198754';
        confirmPasswordInput.setCustomValidity('');
        return;
      }

      confirmPasswordHelp.textContent = 'Пароли не совпадают';
      confirmPasswordHelp.style.color = '#dc3545';
      confirmPasswordInput.setCustomValidity('Пароли не совпадают');
    };

    passwordInput.addEventListener('input', function () {
      updatePasswordStrength();
      updatePasswordConfirmation();
    });

    confirmPasswordInput.addEventListener('input', updatePasswordConfirmation);

    updatePasswordStrength();
    updatePasswordConfirmation();
  }());
</script>
<?php require view_path('footer.php'); ?>
