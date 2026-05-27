CREATE TABLE IF NOT EXISTS pemeliharaan (
    id                        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    penyulang                 VARCHAR(100) NOT NULL,
    bulan                     TINYINT UNSIGNED NOT NULL COMMENT '1=Jan ... 12=Des',
    tahun                     YEAR NOT NULL DEFAULT 2025,
    tier1_inpeksi             INT UNSIGNED DEFAULT 0,
    tier1_temuan              INT UNSIGNED DEFAULT 0,
    tier2_inpeksi             INT UNSIGNED DEFAULT 0,
    tier2_temuan              INT UNSIGNED DEFAULT 0,
    pengukuran                INT UNSIGNED DEFAULT 0,
    pergantian_fco            INT UNSIGNED DEFAULT 0,
    penyeimbangan_beban_gardu INT UNSIGNED DEFAULT 0,
    perbaikan_grounding_trafo INT UNSIGNED DEFAULT 0,
    penghalang_panjat         INT UNSIGNED DEFAULT 0,
    created_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_penyulang (penyulang),
    INDEX idx_bulan_tahun (bulan, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
