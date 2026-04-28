INSERT INTO product_tags (code, name)
VALUES
  ('new', 'Новинка'),
  ('hit', 'Хит'),
  ('spicy', 'Острое'),
  ('vegetarian', 'Вегетарианская'),
  ('cheesy', 'Сырная'),
  ('meat', 'Мясная'),
  ('recommended', 'Рекомендуем'),
  ('sale', 'Акция'),
  ('signature', 'Фирменная')
ON CONFLICT (code) DO UPDATE
SET name = EXCLUDED.name;
