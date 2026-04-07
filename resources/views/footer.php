<?php

use App\Repositories\ProductRepository;

$footerProducts = [];

try {
    $footerProducts = ProductRepository::featured(4);
} catch (Throwable) {
    $footerProducts = [];
}
?>
  </main>

  <footer class="bg-dark text-light pt-5 pb-4">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="text-warning mb-3"><?= e(app_name()) ?></h5>
          <p class="mb-3">
            Готовим с любовью из свежих ингредиентов по традиционным
            итальянским рецептам.
          </p>
          <div class="d-flex">
            <div class="me-3">
              <i class="fas fa-clock text-warning me-2"></i>
              <span>Ежедневно 10:00-23:00</span>
            </div>
          </div>
        </div>

        <div class="col-lg-2 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Меню</h5>
          <ul class="list-unstyled">
            <?php foreach ($footerProducts as $footerProduct): ?>
              <li class="mb-2">
                <a href="<?= e(route('product', ['slug' => $footerProduct['slug']])) ?>" class="text-light text-decoration-none"><?= e($footerProduct['name']) ?></a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="col-lg-2 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Информация</h5>
          <ul class="list-unstyled">
            <li class="mb-2"><a href="<?= e(route('menu')) ?>" class="text-light text-decoration-none">Меню</a></li>
            <li class="mb-2"><a href="<?= e(route('contact')) ?>" class="text-light text-decoration-none">Контакты</a></li>
            <li class="mb-2"><a href="<?= e(route('photos')) ?>" class="text-light text-decoration-none">Галерея</a></li>
          </ul>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="text-warning mb-3">Контакты</h5>
          <div class="mb-3">
            <i class="fas fa-map-marker-alt text-warning me-2"></i>
            <span>бульвар Купца Ефремова, 3, Чебоксары</span>
          </div>
          <div class="mb-3">
            <i class="fas fa-phone text-warning me-2"></i>
            <a href="tel:+79991234567" class="text-light text-decoration-none">+7 (999) 123-45-67</a>
          </div>
          <div class="mb-3">
            <i class="fas fa-envelope text-warning me-2"></i>
            <a href="mailto:info@flourandfire.ru" class="text-light text-decoration-none">info@flourandfire.ru</a>
          </div>
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
