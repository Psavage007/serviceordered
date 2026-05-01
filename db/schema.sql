-- ServiceOrdered Database Schema

CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255)  NOT NULL,
    slug        VARCHAR(255)  NOT NULL UNIQUE,
    description TEXT,
    icon        VARCHAR(10),
    group_name  VARCHAR(100),
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS states (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    abbreviation CHAR(2)      NOT NULL UNIQUE,
    slug         VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS cities (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    state_id   INT          NOT NULL,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(255) NOT NULL,
    population INT,
    lat        DECIMAL(10,7),
    lng        DECIMAL(10,7),
    FOREIGN KEY (state_id) REFERENCES states(id),
    UNIQUE KEY uq_city_state (state_id, slug)
);

CREATE TABLE IF NOT EXISTS businesses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    slug          VARCHAR(255) NOT NULL UNIQUE,
    address       TEXT,
    city_id       INT,
    phone         VARCHAR(50),
    website       VARCHAR(500),
    email         VARCHAR(255),
    rating        DECIMAL(3,2),
    review_count  INT DEFAULT 0,
    description   TEXT,
    hours         JSON,
    gmb_place_id  VARCHAR(255) UNIQUE,
    gmb_url       VARCHAR(500),
    photos        JSON,
    verified      TINYINT(1) DEFAULT 0,
    featured      TINYINT(1) DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);

CREATE TABLE IF NOT EXISTS business_categories (
    business_id  INT NOT NULL,
    category_id  INT NOT NULL,
    PRIMARY KEY (business_id, category_id),
    FOREIGN KEY (business_id)  REFERENCES businesses(id)  ON DELETE CASCADE,
    FOREIGN KEY (category_id)  REFERENCES categories(id)  ON DELETE CASCADE
);

-- Indexes for fast lookups
CREATE INDEX idx_businesses_city     ON businesses(city_id);
CREATE INDEX idx_businesses_rating   ON businesses(rating DESC);
CREATE INDEX idx_cities_state        ON cities(state_id);
