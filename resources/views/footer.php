  </main>

  <footer class="bg-dark text-light pt-5 pb-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="text-warning mb-3"><?= e(app_name()) ?></h5>
          <p class="mb-3">
            Готовим с любовью из свежих ингредиентов по традиционным
            итальянским рецептам.
          </p>
          <div>Ежедневно 10:00-23:00</div>
        </div>

        <div class="col-lg-2 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Меню</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="<?= e(route('menu')) ?>" class="text-light text-decoration-none">Каталог</a></li>
            <li class="mb-2"><a href="<?= e(route('photos')) ?>" class="text-light text-decoration-none">Фото</a></li>
            <li class="mb-2"><a href="<?= e(route('contact')) ?>" class="text-light text-decoration-none">Контакты</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Аккаунт</h5>
          <ul class="list-unstyled">
            <?php if (is_logged_in()): ?>
              <li class="mb-2"><a href="<?= e(route('account')) ?>" class="text-light text-decoration-none">Личный кабинет</a></li>
              <li class="mb-2"><a href="<?= e(route('cart')) ?>" class="text-light text-decoration-none">Корзина</a></li>
            <?php else: ?>
              <li class="mb-2"><a href="<?= e(route('login')) ?>" class="text-light text-decoration-none">Войти</a></li>
              <li class="mb-2"><a href="<?= e(route('register')) ?>" class="text-light text-decoration-none">Регистрация</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Контакты</h5>
          <div class="mb-3">бульвар Купца Ефремова, 3, Чебоксары</div>
          <div class="mb-3"><a href="tel:+79991234567" class="text-light text-decoration-none">+7 (999) 123-45-67</a></div>
          <div class="mb-3"><a href="mailto:info@flourandfire.ru" class="text-light text-decoration-none">info@flourandfire.ru</a></div>
        </div>
      </div>

      <div class="row mt-4 pt-3 border-top border-secondary">
        <div class="col-md-12 d-flex justify-content-center text-center text-md-start">
          <p class="mb-0">&copy; <?= date('Y') ?> <?= e(app_name()) ?>. Все права защищены.</p>
        </div>
      </div>
    </div>
  </footer>

  <script src="<?= e(asset('bootstrap/bootstrap.bundle.min.js')) ?>"></script>
</body>
</html>
