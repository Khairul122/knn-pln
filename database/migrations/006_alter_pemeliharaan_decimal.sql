-- 006: Ubah kolom fitur pemeliharaan dari INT ke DECIMAL(8,2)
-- Data referensi Python (PEMELIHARAAN_PLN_2024_FINAL_LABELED.xlsx) berisi nilai
-- desimal (mis. 10.3, 0.3, 14.2) yang sebelumnya terpotong oleh tipe INT.

ALTER TABLE pemeliharaan
    MODIFY tier1_inpeksi             DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY tier1_temuan              DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY tier2_inpeksi             DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY tier2_temuan              DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY pengukuran                DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY pergantian_fco            DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY penyeimbangan_beban_gardu DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY perbaikan_grounding_trafo DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
    MODIFY penghalang_panjat         DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0;
