CREATE TABLE IF NOT EXISTS labeling (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    pemeliharaan_id  INT UNSIGNED NOT NULL,
    failure_mode     VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Mode kegagalan yang teridentifikasi',
    severity         TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Keparahan dampak 1-10',
    occurrence       TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Frekuensi kejadian 1-10',
    detection        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Kemudahan deteksi 1-10 (10=sangat sulit)',
    rpn              SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Risk Priority Number = S x O x D',
    risk_label       ENUM('Low','Medium','High') NOT NULL DEFAULT 'Low',
    catatan          TEXT NULL,
    labeled_by       INT UNSIGNED NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pemeliharaan (pemeliharaan_id),
    INDEX idx_risk_label (risk_label),
    INDEX idx_rpn (rpn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
