<?php

$badgeClasses = [
    'hit' => 'bg-warning',
    'vegetarian' => 'bg-success',
    'new' => 'bg-info',
    'spicy' => 'bg-danger',
];

$primaryTag = $product['tags'][0] ?? null;
$badgeClass = $primaryTag ? ($badgeClasses[$primaryTag['code']] ?? 'bg-secondary') : null;
?>
<div class="col-md-6 col-lg-3">
  <div class="card pizza-card h-100" onclick="location.href='<?= e(route('product', ['slug' => $product['slug']])) ?>'">
    <div class="position-relative">
      <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" class="card-img-top pizza-img" alt="Пицца <?= e($product['name']) ?>" />
      <?php if ($primaryTag): ?>
        <span class="position-absolute top-0 end-0 badge <?= e($badgeClass) ?> m-2"><?= e($primaryTag['name']) ?></span>
      <?php endif; ?>
    </div>
    <div class="card-body d-flex flex-column">
      <h5 class="card-title"><?= e($product['name']) ?></h5>
      <p class="card-text text-muted flex-grow-1"><?= e($product['short_description']) ?></p>
      <div class="d-flex justify-content-between align-items-center mt-auto">
        <span class="h5 text-dark mb-0"><?= e(format_price($product['min_price'])) ?></span>
        <a href="<?= e(route('product', ['slug' => $product['slug']])) ?>" class="btn btn-dark btn-sm">Подробнее</a>
      </div>
    </div>
  </div>
</div>
