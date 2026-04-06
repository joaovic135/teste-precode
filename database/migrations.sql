CREATE TABLE IF NOT EXISTS products (
    id                 SERIAL PRIMARY KEY,
    sku                VARCHAR(100)     UNIQUE NOT NULL,
    name               VARCHAR(255)     NOT NULL,
    description        TEXT             DEFAULT '',
    category           VARCHAR(100)     DEFAULT '',
    brand              VARCHAR(100)     DEFAULT '',
    weight             NUMERIC(10, 3)   DEFAULT 0,
    width              NUMERIC(10, 3)   DEFAULT 0,
    height             NUMERIC(10, 3)   DEFAULT 0,
    length             NUMERIC(10, 3)   DEFAULT 0,
    cost               NUMERIC(10, 2)   DEFAULT 0,
    promotional_price  NUMERIC(10, 2)   DEFAULT 0,
    ean                VARCHAR(50)      DEFAULT '',
    price              NUMERIC(10, 2)   NOT NULL,
    stock              INTEGER          NOT NULL DEFAULT 0,
    images             JSONB            DEFAULT '[]',
    marketplace_status VARCHAR(20)      DEFAULT 'pending',
    marketplace_error  TEXT             DEFAULT NULL,
    created_at         TIMESTAMPTZ      DEFAULT NOW(),
    updated_at         TIMESTAMPTZ      DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS price_stock_updates (
    id             SERIAL PRIMARY KEY,
    product_sku    VARCHAR(100)   NOT NULL,
    update_type    VARCHAR(10)    NOT NULL CHECK (update_type IN ('price', 'stock')),
    old_value      NUMERIC(10, 2),
    new_value      NUMERIC(10, 2) NOT NULL,
    status         VARCHAR(20)    DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'error')),
    error_message  TEXT           DEFAULT NULL,
    created_at     TIMESTAMPTZ    DEFAULT NOW(),
    updated_at     TIMESTAMPTZ    DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS orders (
    id                   SERIAL PRIMARY KEY,
    marketplace_order_id VARCHAR(100)   UNIQUE NOT NULL,
    partner_order_id     VARCHAR(100)   DEFAULT '',
    status               VARCHAR(50)    NOT NULL,
    customer_name        VARCHAR(255)   DEFAULT '',
    total                NUMERIC(10, 2) DEFAULT 0,
    items                JSONB          DEFAULT '[]',
    raw_data             JSONB,
    processed_at         TIMESTAMPTZ    DEFAULT NULL,
    created_at           TIMESTAMPTZ    DEFAULT NOW(),
    updated_at           TIMESTAMPTZ    DEFAULT NOW()
);

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER products_set_updated_at
    BEFORE UPDATE ON products
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER price_stock_updates_set_updated_at
    BEFORE UPDATE ON price_stock_updates
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER orders_set_updated_at
    BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX IF NOT EXISTS idx_price_stock_updates_product_sku
    ON price_stock_updates (product_sku);
