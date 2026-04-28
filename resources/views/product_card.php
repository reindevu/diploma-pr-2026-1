<?php

$badgeClasses = [
    'new' => 'tag-badge-new',
    'hit' => 'tag-badge-hit',
    'spicy' => 'tag-badge-spicy',
    'vegetarian' => 'tag-badge-vegetarian',
    'cheesy' => 'tag-badge-cheesy',
    'meat' => 'tag-badge-meat',
    'recommended' => 'tag-badge-recommended',
    'sale' => 'tag-badge-sale',
    'signature' => 'tag-badge-signature',
];
?>
<div class="col-md-6 col-lg-3">
  <div class="card pizza-card h-100" onclick="location.href='<?= e(route('product', ['slug' => $product['slug']])) ?>'">
    <div class="position-relative">
      <img src="<?= e(asset($product['image_path'] ?: 'images/logo.png')) ?>" class="card-img-top pizza-img" alt="Пицца <?= e($product['name']) ?>" />
      <?php if (!empty($product['tags'])): ?>
        <div class="position-absolute top-0 end-0 d-flex flex-column align-items-end gap-1 m-2">
          <?php foreach ($product['tags'] as $tag): ?>
            <?php $badgeClass = $badgeClasses[$tag['code']] ?? 'bg-secondary'; ?>
            <span class="badge <?= e($badgeClass) ?>"><?= e($tag['name']) ?></span>
          <?php endforeach; ?>
        </div>
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
