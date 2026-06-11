# Transfer Knowledge — PLN GridRisk

Dokumen serah terima pengetahuan untuk pengembang yang melanjutkan proyek ini.

## 1. Gambaran Umum

PLN GridRisk adalah aplikasi web untuk memprediksi tingkat risiko pemeliharaan
jaringan distribusi listrik (penyulang/feeder) menggunakan metode **FMEA**
(Failure Mode and Effects Analysis) untuk pelabelan dan **KNN** (K-Nearest
Neighbor) untuk klasifikasi. Paradigma yang dituju: pemeliharaan prediktif,
bukan reaktif.

**Tech stack:** PHP 8.1 (MVC native tanpa framework), MySQL (PDO), Tailwind CSS,
Chart.js, PhpSpreadsheet (Composer).

## 2. Alur Sistem

```
Data Pemeliharaan (414 baris, 64 penyulang, 12 bulan)
        ↓
FMEA Labeling  →  S × O × D = RPN  →  Label (Rendah/Sedang/Tinggi)
        ↓
Split Data (stratified 80/20, seed 42)
        ↓
Training KNN (K=3, euclidean, normalisasi min-max)
        ↓
Evaluasi (confusion matrix, akurasi ~97.5%)  →  Laporan Risiko
```

## 3. Struktur Kode

| Path | Fungsi |
|---|---|
| `core/` | Router, base Controller/Model (PDO), Flash message |
| `app/Controllers/` | Auth, Dashboard, Pemeliharaan (CRUD), Labeling (FMEA), Knn (train/predict/evaluate), Laporan, Landing |
| `app/Models/` | Pemeliharaan, Labeling (termasuk stratified split), KnnModel, Laporan, User |
| `app/Services/FmeaScorer.php` | **Satu-satunya sumber logika skor FMEA** — dipakai controller & CLI |
| `app/Services/KNNClassifier.php` | KNN native PHP: fit, predict, evaluate, save/load (serialize ke `.bin`) |
| `app/Views/` | Tampilan per modul (Tailwind) |
| `database/migrations/` | Skema bertahap 001–006 (jalankan berurutan untuk DB baru) |
| `database/seeders/` | 001 user awal, 002 sinkronisasi nilai desimal data pemeliharaan |
| `database/knn-pln.sql` | Dump lengkap (skema + data) — cara tercepat setup DB |
| `scripts/relabel_fmea.php` | CLI: regenerasi label FMEA semua baris (`php scripts/relabel_fmea.php 2025`) |
| `storage/models/` | File model KNN hasil training (`knn_{tahun}_{timestamp}.bin`) |

## 4. Aturan FMEA (identik dengan referensi Python `fmea.py`)

**Severity (S)** — aksi pemeliharaan dominan:
| Kondisi | S |
|---|---|
| `pergantian_fco > 0` ATAU `perbaikan_grounding_trafo > 0` | 9 |
| `penyeimbangan_beban_gardu > 0` | 6 |
| `pengukuran > 0` | 4 |
| `penghalang_panjat > 0` | 2 |
| Tidak ada | 1 |

**Occurrence (O)** — total temuan (`tier1_temuan + tier2_temuan`):
| Total | O |
|---|---|
| = 0 | 1 |
| 1 – 10 | 4 |
| 11 – 20 | 7 |
| Selain itu (termasuk desimal 0.6 atau 10.3!) | 9 |

> PENTING: batas rentang memakai semantik Python `1 <= t <= 10`. Nilai desimal
> di luar rentang (mis. 0.6, 10.3, 20.6) jatuh ke O=9. Jangan "menyederhanakan"
> jadi `t <= 10` — hasilnya akan beda dari referensi penelitian.

**Detection (D)** — realisasi inspeksi:
| Kondisi | D |
|---|---|
| Tier 1 DAN Tier 2 > 0 | 2 |
| Salah satu > 0 | 5 |
| Keduanya 0 | 9 |

**RPN = S × O × D**, label: RPN ≤ 9 → **Rendah**, 10–99 → **Sedang**, ≥ 100 → **Tinggi**.

Distribusi label dataset saat ini: **Rendah 182, Sedang 172, Tinggi 60** (total 414).

## 5. Konfigurasi KNN

- **K = 3** (default; hasil evaluasi terbaik, dibanding K=5/7/9)
- **Metrik:** Euclidean (Manhattan tersedia)
- **Fitur:** 9 kolom mentah pemeliharaan (BUKAN skor S/O/D):
  `tier1_inpeksi, tier1_temuan, tier2_inpeksi, tier2_temuan, pengukuran,
  pergantian_fco, penyeimbangan_beban_gardu, perbaikan_grounding_trafo, penghalang_panjat`
- **Normalisasi:** min-max per fitur ke [0,1]
- **Split:** stratified per label, rasio 0.8, seed `mt_srand(42)` (lihat `Labeling::applyStratifiedSplit`)
- **Hasil:** akurasi test ~97.56% (referensi Python sklearn: 97.59%; selisih kecil
  karena algoritma shuffle PHP ≠ sklearn meski seed sama-sama 42)

## 6. Database

DB name: `knn-pln` (lihat `config/app.php`). Tabel inti:

- `pemeliharaan` — data mentah; **9 kolom fitur bertipe `DECIMAL(8,2)`** (migration 006).
  Data asli mengandung nilai desimal (10.3, 0.3, 14.2) — jangan diubah kembali ke INT.
- `labeling` — 1:1 ke pemeliharaan; S/O/D/RPN/risk_label + `split_type` (train/test/NULL).
- `knn_models` — metadata model + path file `.bin`.
- `knn_predictions` — hasil prediksi per baris (unique per model+pemeliharaan).

## 7. Setup dari Nol

1. Laragon/XAMPP dengan PHP 8.1 + MySQL 8, `composer install`.
2. Buat DB `knn-pln`, import `database/knn-pln.sql` (atau jalankan migrations 001–006 + seeders).
3. Akses `http://localhost/knn-pln/public`. Login memakai user di seeder
   (`database/seeders/001_seed_users.sql`).
4. Jika label perlu digenerate ulang: `php scripts/relabel_fmea.php 2025`
   (atau menu Labeling → Auto Label dengan overwrite).

## 8. Referensi Python

Validasi perhitungan dilakukan terhadap script Python (pandas + scikit-learn)
di folder `perhitungan_data/` — folder ini sudah dihapus dari working tree,
tetapi tersedia di riwayat git (commit `9ccfbcd`). File kunci:
- `fmea.py` — aturan S/O/D/RPN (sumber kebenaran aturan FMEA)
- `normalisasi.py`, `train knn.py`, `testing knn.py` — pipeline KNN
- `PEMELIHARAAN_PLN_2024_FINAL_LABELED.xlsx` — dataset berlabel acuan

Verifikasi terakhir (Juni 2026): 414/414 baris DB identik dengan Excel acuan
(9 fitur + S/O/D/RPN/label, 0 selisih).

## 9. Jebakan yang Pernah Terjadi (jangan diulang)

1. **Truncation desimal** — kolom fitur dulu `INT UNSIGNED`; nilai 10.3 terpotong
   jadi 10 sehingga 8 baris salah label. Sudah diperbaiki ke `DECIMAL(8,2)`.
2. **Strict comparison pada float** — `$totalTemuan === 0` gagal untuk `0.0`.
   Gunakan `==` untuk perbandingan nilai numerik campuran int/float.
3. **Tahun data** — data fisik tahun 2024, tapi di DB disimpan sebagai `tahun = 2025`
   (migration 005). Konsisten gunakan 2025 di query/filter.
4. **Logika FMEA terduplikasi** — semua perubahan aturan FMEA hanya di
   `app/Services/FmeaScorer.php`; controller dan CLI sama-sama memanggilnya.
