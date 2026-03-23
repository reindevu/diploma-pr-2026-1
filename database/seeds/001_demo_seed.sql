BEGIN;

-- Seed data for the current frontend catalog.
-- Assumption: the visible price on the product card is the 35 cm variant price.
-- 30 cm and 40 cm prices are demo values derived symmetrically around the shown price.
-- Users are intentionally not seeded here because the project does not yet have
-- a PHP layer to generate real password hashes via password_hash().

INSERT INTO product_categories (name, slug, sort_order, is_active)
VALUES ('Пицца', 'pizza', 1, TRUE)
ON CONFLICT (slug) DO UPDATE
SET
  name = EXCLUDED.name,
  sort_order = EXCLUDED.sort_order,
  is_active = EXCLUDED.is_active,
  updated_at = NOW();

INSERT INTO product_tags (code, name)
VALUES
  ('hit', 'Хит'),
  ('vegetarian', 'Вегетарианская'),
  ('new', 'Новинка'),
  ('spicy', 'Острое')
ON CONFLICT (code) DO UPDATE
SET name = EXCLUDED.name;

INSERT INTO products (
  category_id,
  name,
  slug,
  short_description,
  full_description,
  image_path,
  is_active,
  sort_order
)
VALUES
  (
    (SELECT id FROM product_categories WHERE slug = 'pizza'),
    'Маргарита',
    'margarita',
    'Классическая итальянская пицца с томатным соусом, моцареллой и свежим базиликом',
    'Пицца Margarita — типичная неаполитанская пицца, c измельчёнными и очищенными помидорами, моцареллой, свежими листьями базилика и оливковым маслом.',
    'images/margherita.png',
    TRUE,
    10
  ),
  (
    (SELECT id FROM product_categories WHERE slug = 'pizza'),
    'Спиначи',
    'spinachi',
    'Пицца со шпинатом, сыром рикотта, моцареллой и чесночным соусом',
    'Пицца Spinaci представляет собой сочетание шпината, моцареллы, пармезана и оливок на основе томатного соуса.',
    'images/spinachi.png',
    TRUE,
    20
  ),
  (
    (SELECT id FROM product_categories WHERE slug = 'pizza'),
    'Чикен Пини',
    'chiken-pini',
    'Пицца с курицей, пепперони, сладким перцем, луком и томатным соусом',
    'Пицца Chicken Pini — это сочетание сочной курицы, пикантного бекона и расплавленного сыра на хрустящей основе.',
    'images/chiken_pini.png',
    TRUE,
    30
  ),
  (
    (SELECT id FROM product_categories WHERE slug = 'pizza'),
    'Паприка Вида',
    'paprica-vida',
    'Острая пицца с пепперони, халапеньо, перцем чили и специальным острым соусом',
    'Пицца Paprica Vida сочетает томатный соус, лёгкий чесночный айоли и выраженный вкус копчёной паприки.',
    'images/paprica_vida.png',
    TRUE,
    40
  )
ON CONFLICT (slug) DO UPDATE
SET
  category_id = EXCLUDED.category_id,
  name = EXCLUDED.name,
  short_description = EXCLUDED.short_description,
  full_description = EXCLUDED.full_description,
  image_path = EXCLUDED.image_path,
  is_active = EXCLUDED.is_active,
  sort_order = EXCLUDED.sort_order,
  updated_at = NOW();

INSERT INTO product_tag_links (product_id, tag_id)
VALUES
  (
    (SELECT id FROM products WHERE slug = 'margarita'),
    (SELECT id FROM product_tags WHERE code = 'hit')
  ),
  (
    (SELECT id FROM products WHERE slug = 'spinachi'),
    (SELECT id FROM product_tags WHERE code = 'vegetarian')
  ),
  (
    (SELECT id FROM products WHERE slug = 'chiken-pini'),
    (SELECT id FROM product_tags WHERE code = 'new')
  ),
  (
    (SELECT id FROM products WHERE slug = 'paprica-vida'),
    (SELECT id FROM product_tags WHERE code = 'spicy')
  )
ON CONFLICT DO NOTHING;

INSERT INTO product_variants (product_id, sku, size_cm, price, is_active)
VALUES
  ((SELECT id FROM products WHERE slug = 'margarita'), 'MARGARITA-30', 30, 800, TRUE),
  ((SELECT id FROM products WHERE slug = 'margarita'), 'MARGARITA-35', 35, 1000, TRUE),
  ((SELECT id FROM products WHERE slug = 'margarita'), 'MARGARITA-40', 40, 1200, TRUE),
  ((SELECT id FROM products WHERE slug = 'spinachi'), 'SPINACHI-30', 30, 1000, TRUE),
  ((SELECT id FROM products WHERE slug = 'spinachi'), 'SPINACHI-35', 35, 1200, TRUE),
  ((SELECT id FROM products WHERE slug = 'spinachi'), 'SPINACHI-40', 40, 1400, TRUE),
  ((SELECT id FROM products WHERE slug = 'chiken-pini'), 'CHIKEN-PINI-30', 30, 1200, TRUE),
  ((SELECT id FROM products WHERE slug = 'chiken-pini'), 'CHIKEN-PINI-35', 35, 1400, TRUE),
  ((SELECT id FROM products WHERE slug = 'chiken-pini'), 'CHIKEN-PINI-40', 40, 1600, TRUE),
  ((SELECT id FROM products WHERE slug = 'paprica-vida'), 'PAPRICA-VIDA-30', 30, 1400, TRUE),
  ((SELECT id FROM products WHERE slug = 'paprica-vida'), 'PAPRICA-VIDA-35', 35, 1600, TRUE),
  ((SELECT id FROM products WHERE slug = 'paprica-vida'), 'PAPRICA-VIDA-40', 40, 1800, TRUE)
ON CONFLICT (product_id, size_cm) DO UPDATE
SET
  sku = EXCLUDED.sku,
  price = EXCLUDED.price,
  is_active = EXCLUDED.is_active,
  updated_at = NOW();

INSERT INTO promo_codes (
  code,
  description,
  discount_type,
  discount_value,
  min_order_amount,
  usage_limit,
  per_user_limit,
  is_active
)
VALUES
  ('WELCOME10', 'Скидка 10% на первый заказ', 'percent', 10, 0, NULL, 1, TRUE),
  ('PIZZA200', 'Скидка 200 рублей при заказе от 2000 рублей', 'fixed', 200, 2000, NULL, NULL, TRUE)
ON CONFLICT (code) DO UPDATE
SET
  description = EXCLUDED.description,
  discount_type = EXCLUDED.discount_type,
  discount_value = EXCLUDED.discount_value,
  min_order_amount = EXCLUDED.min_order_amount,
  usage_limit = EXCLUDED.usage_limit,
  per_user_limit = EXCLUDED.per_user_limit,
  is_active = EXCLUDED.is_active,
  updated_at = NOW();

COMMIT;
