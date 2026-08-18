

CREATE DATABASE IF NOT EXISTS foundly_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE foundly_db;

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    phone         VARCHAR(10)         NOT NULL,
    password_hash VARCHAR(255)        NOT NULL,
    user_type     ENUM('student','staff') NOT NULL DEFAULT 'student',
    role          ENUM('user','admin')    NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (category_name) VALUES
    ('Electronics'),
    ('Documents / ID Cards'),
    ('Bags'),
    ('Clothing'),
    ('Accessories'),
    ('Keys'),
    ('Books & Stationery'),
    ('Other');

CREATE TABLE reports (
    report_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    type          ENUM('lost','found') NOT NULL,
    item_name     VARCHAR(100) NOT NULL,
    category_id   INT NULL,
    description   TEXT NOT NULL,
    location      VARCHAR(150) NOT NULL,
    date_reported DATE NOT NULL,
    status        ENUM('open','matched','closed') NOT NULL DEFAULT 'open',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reports_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_reports_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;


CREATE TABLE matches (
    match_id        INT AUTO_INCREMENT PRIMARY KEY,
    lost_report_id  INT NOT NULL,
    found_report_id INT NOT NULL,
    status          ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    verified_by     INT NULL,
    verified_at     TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_matches_lost
        FOREIGN KEY (lost_report_id) REFERENCES reports(report_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_matches_found
        FOREIGN KEY (found_report_id) REFERENCES reports(report_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_matches_admin
        FOREIGN KEY (verified_by) REFERENCES users(user_id)
        ON DELETE SET NULL
) ENGINE=InnoDB;