-- 005: Update all year references from 2024 to 2025
-- Run this once to migrate existing data

UPDATE pemeliharaan SET tahun = 2025 WHERE tahun = 2024;

UPDATE knn_models SET tahun = 2025 WHERE tahun = 2024;

-- Verify row counts after update
SELECT 'pemeliharaan' AS tabel, COUNT(*) AS total, SUM(tahun = 2025) AS tahun_2025, SUM(tahun = 2024) AS tahun_2024_sisa FROM pemeliharaan
UNION ALL
SELECT 'knn_models',   COUNT(*), SUM(tahun = 2025), SUM(tahun = 2024) FROM knn_models;
