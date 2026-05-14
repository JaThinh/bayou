-- ============================================================
-- BAYOU TRAVEL — PostgreSQL Database Schema
-- Chạy file này trong pgAdmin hoặc psql để tạo toàn bộ DB
-- ============================================================

-- Tạo database (chạy với superuser nếu chưa tạo)
-- CREATE DATABASE bayou_db ENCODING 'UTF8' LC_COLLATE='vi_VN.UTF-8' TEMPLATE=template0;

-- Extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pg_trgm"; -- cho tìm kiếm fuzzy

-- ─────────────────────────────────────────────────────────────
-- 1. BẢNG AIRPORTS — Danh sách sân bay
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS airports (
    iata_code    CHAR(3)      PRIMARY KEY,
    name_vi      VARCHAR(120) NOT NULL,
    name_en      VARCHAR(120) NOT NULL,
    city_vi      VARCHAR(80)  NOT NULL,
    city_en      VARCHAR(80)  NOT NULL,
    country_code CHAR(2)      NOT NULL DEFAULT 'VN',
    country_vi   VARCHAR(60)  NOT NULL DEFAULT 'Việt Nam',
    entity_id    VARCHAR(20)  NULL,       -- Skyscanner entityId
    latitude     NUMERIC(9,6) NULL,
    longitude    NUMERIC(9,6) NULL,
    is_active    BOOLEAN      NOT NULL DEFAULT true,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_airports_country ON airports (country_code);
CREATE INDEX IF NOT EXISTS idx_airports_city    ON airports USING gin (city_vi gin_trgm_ops);

-- ─────────────────────────────────────────────────────────────
-- 2. BẢNG USERS — Người dùng & Admin
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            SERIAL       PRIMARY KEY,
    email         VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(120) NOT NULL,
    phone         VARCHAR(20)  NULL,
    role          VARCHAR(20)  NOT NULL DEFAULT 'customer',  -- customer | staff | admin
    is_active     BOOLEAN      NOT NULL DEFAULT true,
    last_login_at TIMESTAMPTZ  NULL,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_role  ON users (role);

-- ─────────────────────────────────────────────────────────────
-- 3. BẢNG SEARCH_LOGS — Log tìm kiếm vé
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS search_logs (
    id           BIGSERIAL    PRIMARY KEY,
    origin       CHAR(3)      NOT NULL,
    destination  CHAR(3)      NOT NULL,
    date_depart  DATE         NOT NULL,
    date_return  DATE         NULL,
    adults       SMALLINT     NOT NULL DEFAULT 1,
    children     SMALLINT     NOT NULL DEFAULT 0,
    infants      SMALLINT     NOT NULL DEFAULT 0,
    cabin_class  VARCHAR(30)  NOT NULL DEFAULT 'economy',
    result_count SMALLINT     NOT NULL DEFAULT 0,
    source       VARCHAR(50)  NULL,           -- google | skyscanner | both
    elapsed_ms   INT          NULL,
    user_ip      INET         NULL,
    user_agent   TEXT         NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_search_logs_route ON search_logs (origin, destination, date_depart);
CREATE INDEX IF NOT EXISTS idx_search_logs_date  ON search_logs (created_at DESC);

-- ─────────────────────────────────────────────────────────────
-- 4. BẢNG PRICE_CACHE — Cache kết quả tìm kiếm (10 phút)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS price_cache (
    id          SERIAL       PRIMARY KEY,
    route_key   VARCHAR(30)  NOT NULL UNIQUE, -- SGN-HAN-2026-06-15
    data_json   JSONB        NOT NULL,
    result_count SMALLINT    NOT NULL DEFAULT 0,
    source      VARCHAR(50)  NULL,
    expires_at  TIMESTAMPTZ  NOT NULL,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_price_cache_key     ON price_cache (route_key);
CREATE INDEX IF NOT EXISTS idx_price_cache_expires ON price_cache (expires_at);

-- ─────────────────────────────────────────────────────────────
-- 5. BẢNG BOOKINGS — Đặt chỗ
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id               SERIAL        PRIMARY KEY,
    user_id          INT           NULL REFERENCES users(id) ON DELETE SET NULL,
    booking_ref      VARCHAR(20)   NOT NULL UNIQUE DEFAULT upper(substring(md5(random()::text) from 1 for 8)),
    flight_number    VARCHAR(10)   NOT NULL,
    airline          VARCHAR(80)   NOT NULL,
    airline_code     CHAR(2)       NOT NULL,
    origin           CHAR(3)       NOT NULL,
    destination      CHAR(3)       NOT NULL,
    depart_at        TIMESTAMPTZ   NOT NULL,
    arrive_at        TIMESTAMPTZ   NULL,
    cabin_class      VARCHAR(30)   NOT NULL DEFAULT 'economy',
    adults           SMALLINT      NOT NULL DEFAULT 1,
    price_amount     NUMERIC(12,0) NOT NULL,
    currency         CHAR(3)       NOT NULL DEFAULT 'VND',
    -- Thông tin hành khách chính
    passenger_name   VARCHAR(120)  NOT NULL,
    passenger_email  VARCHAR(180)  NULL,
    contact_phone    VARCHAR(20)   NOT NULL,
    -- Phí phụ trội
    baggage_fee      NUMERIC(12,0) NOT NULL DEFAULT 0,
    seat_fee         NUMERIC(12,0) NOT NULL DEFAULT 0,
    payment_fee      NUMERIC(12,0) NOT NULL DEFAULT 0,
    total_amount     NUMERIC(12,0) GENERATED ALWAYS AS
                     (price_amount + baggage_fee + seat_fee + payment_fee) STORED,
    -- Trạng thái
    status           VARCHAR(20)   NOT NULL DEFAULT 'pending',  -- pending | confirmed | cancelled | refunded
    pnr              VARCHAR(20)   NULL,        -- Mã đặt chỗ từ hãng
    source           VARCHAR(30)   NOT NULL DEFAULT 'bayou',    -- bayou | google | skyscanner
    notes            TEXT          NULL,
    created_at       TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ   NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bookings_user    ON bookings (user_id);
CREATE INDEX IF NOT EXISTS idx_bookings_ref     ON bookings (booking_ref);
CREATE INDEX IF NOT EXISTS idx_bookings_route   ON bookings (origin, destination, depart_at);
CREATE INDEX IF NOT EXISTS idx_bookings_status  ON bookings (status);
CREATE INDEX IF NOT EXISTS idx_bookings_phone   ON bookings (contact_phone);

-- ─────────────────────────────────────────────────────────────
-- 6. FUNCTION: Tự động cập nhật updated_at
-- ─────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

CREATE OR REPLACE TRIGGER trg_bookings_updated_at
    BEFORE UPDATE ON bookings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- ─────────────────────────────────────────────────────────────
-- 7. FUNCTION: Tự động xóa cache hết hạn (cleanup)
-- ─────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION cleanup_expired_cache()
RETURNS void AS $$
BEGIN
    DELETE FROM price_cache WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;
