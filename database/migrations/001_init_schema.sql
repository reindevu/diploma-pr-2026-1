-- Initial PostgreSQL schema for the "Flour and Fire" pizza ordering app.

CREATE TYPE user_role AS ENUM ('customer', 'admin');
CREATE TYPE cart_status AS ENUM ('active', 'converted', 'abandoned');
CREATE TYPE discount_type AS ENUM ('percent', 'fixed');
CREATE TYPE delivery_method AS ENUM ('delivery', 'pickup');
CREATE TYPE order_status AS ENUM (
  'new',
  'confirmed',
  'preparing',
  'out_for_delivery',
  'completed',
  'cancelled'
);
CREATE TYPE payment_method AS ENUM ('cash', 'card_online', 'card_on_delivery');
CREATE TYPE payment_status AS ENUM ('pending', 'paid', 'failed', 'refunded');

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE users (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(30),
  password_hash TEXT NOT NULL,
  role user_role NOT NULL DEFAULT 'customer',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX users_email_lower_uidx ON users (LOWER(email));

CREATE TABLE user_addresses (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  label VARCHAR(50),
  recipient_name VARCHAR(150),
  recipient_phone VARCHAR(30),
  full_address TEXT NOT NULL,
  entrance VARCHAR(20),
  floor VARCHAR(20),
  apartment VARCHAR(20),
  comment TEXT,
  is_default BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX user_addresses_one_default_uidx
  ON user_addresses (user_id)
  WHERE is_default = TRUE;

CREATE TABLE product_categories (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT product_categories_slug_key UNIQUE (slug)
);

CREATE TABLE products (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  category_id BIGINT NOT NULL REFERENCES product_categories(id) ON DELETE RESTRICT,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(150) NOT NULL,
  short_description TEXT NOT NULL,
  full_description TEXT,
  image_path TEXT,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT products_slug_key UNIQUE (slug)
);

CREATE TABLE product_tags (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT product_tags_code_key UNIQUE (code)
);

CREATE TABLE product_tag_links (
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  tag_id BIGINT NOT NULL REFERENCES product_tags(id) ON DELETE CASCADE,
  PRIMARY KEY (product_id, tag_id)
);

CREATE TABLE product_variants (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  product_id BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  sku VARCHAR(64),
  size_cm NUMERIC(4,1) NOT NULL CHECK (size_cm > 0),
  price NUMERIC(10,2) NOT NULL CHECK (price >= 0),
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT product_variants_product_size_key UNIQUE (product_id, size_cm),
  CONSTRAINT product_variants_sku_key UNIQUE (sku)
);

CREATE TABLE promo_codes (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  code VARCHAR(50) NOT NULL,
  description TEXT,
  discount_type discount_type NOT NULL,
  discount_value NUMERIC(10,2) NOT NULL CHECK (discount_value > 0),
  min_order_amount NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (min_order_amount >= 0),
  usage_limit INT CHECK (usage_limit IS NULL OR usage_limit > 0),
  per_user_limit INT CHECK (per_user_limit IS NULL OR per_user_limit > 0),
  starts_at TIMESTAMPTZ,
  ends_at TIMESTAMPTZ,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT promo_codes_code_key UNIQUE (code),
  CONSTRAINT promo_codes_period_check CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at >= starts_at)
);

CREATE TABLE carts (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  promo_code_id BIGINT REFERENCES promo_codes(id) ON DELETE SET NULL,
  guest_token VARCHAR(128),
  status cart_status NOT NULL DEFAULT 'active',
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  expires_at TIMESTAMPTZ,
  CONSTRAINT carts_owner_check CHECK (user_id IS NOT NULL OR guest_token IS NOT NULL)
);

CREATE UNIQUE INDEX carts_active_user_uidx
  ON carts (user_id)
  WHERE user_id IS NOT NULL AND status = 'active';

CREATE UNIQUE INDEX carts_active_guest_token_uidx
  ON carts (guest_token)
  WHERE guest_token IS NOT NULL AND status = 'active';

CREATE INDEX carts_guest_token_idx
  ON carts (guest_token)
  WHERE guest_token IS NOT NULL;

CREATE TABLE cart_items (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  cart_id BIGINT NOT NULL REFERENCES carts(id) ON DELETE CASCADE,
  product_variant_id BIGINT NOT NULL REFERENCES product_variants(id) ON DELETE RESTRICT,
  quantity INT NOT NULL CHECK (quantity > 0),
  unit_price NUMERIC(10,2) NOT NULL CHECK (unit_price >= 0),
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT cart_items_cart_variant_key UNIQUE (cart_id, product_variant_id)
);

CREATE TABLE orders (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  order_number VARCHAR(30) NOT NULL,
  user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  cart_id BIGINT REFERENCES carts(id) ON DELETE SET NULL,
  promo_code_id BIGINT REFERENCES promo_codes(id) ON DELETE SET NULL,
  status order_status NOT NULL DEFAULT 'new',
  payment_method payment_method NOT NULL DEFAULT 'cash',
  payment_status payment_status NOT NULL DEFAULT 'pending',
  delivery_method delivery_method NOT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  recipient_phone VARCHAR(30) NOT NULL,
  recipient_email VARCHAR(255),
  delivery_address TEXT,
  order_comment TEXT,
  subtotal_amount NUMERIC(10,2) NOT NULL CHECK (subtotal_amount >= 0),
  discount_amount NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
  delivery_fee NUMERIC(10,2) NOT NULL DEFAULT 0 CHECK (delivery_fee >= 0),
  total_amount NUMERIC(10,2) NOT NULL CHECK (total_amount >= 0),
  placed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  confirmed_at TIMESTAMPTZ,
  completed_at TIMESTAMPTZ,
  cancelled_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CONSTRAINT orders_order_number_key UNIQUE (order_number),
  CONSTRAINT orders_delivery_address_check CHECK (
    delivery_method = 'pickup' OR delivery_address IS NOT NULL
  )
);

CREATE TABLE order_items (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  product_id BIGINT REFERENCES products(id) ON DELETE SET NULL,
  product_variant_id BIGINT REFERENCES product_variants(id) ON DELETE SET NULL,
  product_name_snapshot VARCHAR(150) NOT NULL,
  size_cm_snapshot NUMERIC(4,1) NOT NULL CHECK (size_cm_snapshot > 0),
  quantity INT NOT NULL CHECK (quantity > 0),
  unit_price NUMERIC(10,2) NOT NULL CHECK (unit_price >= 0),
  line_total NUMERIC(10,2) NOT NULL CHECK (line_total >= 0)
);

CREATE TABLE order_status_history (
  id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  order_id BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  status order_status NOT NULL,
  comment TEXT,
  changed_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  changed_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX user_addresses_user_id_idx ON user_addresses (user_id);
CREATE INDEX products_category_id_idx ON products (category_id);
CREATE INDEX product_variants_product_id_idx ON product_variants (product_id);
CREATE INDEX carts_user_id_idx ON carts (user_id);
CREATE INDEX cart_items_cart_id_idx ON cart_items (cart_id);
CREATE INDEX orders_user_id_idx ON orders (user_id);
CREATE INDEX orders_status_idx ON orders (status);
CREATE INDEX orders_placed_at_idx ON orders (placed_at DESC);
CREATE INDEX order_items_order_id_idx ON order_items (order_id);
CREATE INDEX order_status_history_order_id_idx ON order_status_history (order_id);

CREATE TRIGGER users_set_updated_at
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER user_addresses_set_updated_at
BEFORE UPDATE ON user_addresses
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER product_categories_set_updated_at
BEFORE UPDATE ON product_categories
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER products_set_updated_at
BEFORE UPDATE ON products
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER product_variants_set_updated_at
BEFORE UPDATE ON product_variants
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER promo_codes_set_updated_at
BEFORE UPDATE ON promo_codes
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER carts_set_updated_at
BEFORE UPDATE ON carts
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER cart_items_set_updated_at
BEFORE UPDATE ON cart_items
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER orders_set_updated_at
BEFORE UPDATE ON orders
FOR EACH ROW
EXECUTE FUNCTION set_updated_at();
