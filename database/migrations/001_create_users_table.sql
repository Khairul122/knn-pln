-- Migration: 001_create_users_table
-- Database: knn-pln

CREATE DATABASE IF NOT EXISTS `knn-pln`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `knn-pln`;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)     NOT NULL,
    email      VARCHAR(150)     NOT NULL,
    password   VARCHAR(255)     NOT NULL,
    role       ENUM('admin','operator','viewer') NOT NULL DEFAULT 'viewer',
    is_active  TINYINT(1)       NOT NULL DEFAULT 1,
    last_login DATETIME         NULL,
    created_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
