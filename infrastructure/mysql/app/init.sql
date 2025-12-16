CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin','client','proprietor') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL
);

CREATE TABLE parkings (
    id CHAR(36) PRIMARY KEY,
    owner_id CHAR(36) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE opening_hours (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    parking_id CHAR(36) NOT NULL,
    day_of_week TINYINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    FOREIGN KEY (parking_id) REFERENCES parkings(id)
);

CREATE TABLE parking_tariffs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    parking_id CHAR(36) NOT NULL,
    label VARCHAR(100) NOT NULL,
    base_price DECIMAL(10,2) NOT NULL,
    increment_minutes INT NOT NULL,
    increment_price DECIMAL(10,2) NOT NULL,
    effective_from DATETIME NOT NULL,
    effective_to DATETIME NULL,
    FOREIGN KEY (parking_id) REFERENCES parkings(id)
);

CREATE TABLE reservations (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    parking_id CHAR(36) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('pending','confirmed','cancelled','completed') NOT NULL,
    price DECIMAL(10,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parking_id) REFERENCES parkings(id)
);

CREATE TABLE subscriptions (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    parking_id CHAR(36) NOT NULL,
    type ENUM('full','weekend','evening','custom') NOT NULL,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    status ENUM('active','paused','cancelled','expired') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parking_id) REFERENCES parkings(id)
);

CREATE TABLE subscription_slots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    subscription_id CHAR(36) NOT NULL,
    day_of_week TINYINT NOT NULL CHECK (day_of_week BETWEEN 0 AND 6),
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE TABLE stationings (
    id CHAR(36) PRIMARY KEY,
    parking_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    reservation_id CHAR(36) NULL,
    subscription_id CHAR(36) NULL,
    entered_at DATETIME NOT NULL,
    exited_at DATETIME NULL,
    amount DECIMAL(10,2) NULL,
    CHECK (
        (reservation_id IS NOT NULL AND subscription_id IS NULL)
        OR (reservation_id IS NULL AND subscription_id IS NOT NULL)
    ),
    FOREIGN KEY (parking_id) REFERENCES parkings(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

CREATE TABLE payments (
    id CHAR(36) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    reservation_id CHAR(36) NULL,
    subscription_id CHAR(36) NULL,
    stationing_id CHAR(36) NULL,
    status ENUM('pending','approved','refused') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    provider_reference VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
    FOREIGN KEY (stationing_id) REFERENCES stationings(id)
);
