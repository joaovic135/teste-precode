DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'orders' AND column_name = 'marketplace_partner_id'
    ) THEN
        ALTER TABLE orders RENAME COLUMN marketplace_partner_id TO partner_order_id;
    END IF;
END $$;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS marketplace_codigo_pedido INTEGER        DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS origin                   VARCHAR(20)    DEFAULT 'incoming',
    ADD COLUMN IF NOT EXISTS marketplace_status       VARCHAR(20)    DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS marketplace_error        TEXT           DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS approved_at              TIMESTAMPTZ    DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cancelled_at             TIMESTAMPTZ    DEFAULT NULL;

ALTER TABLE orders
    ALTER COLUMN status SET DEFAULT 'novo';
