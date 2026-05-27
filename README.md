# PLN GridRisk — Sistem Prediksi Risiko Jaringan Distribusi

Aplikasi berbasis web untuk analisis dan prediksi risiko pemeliharaan jaringan distribusi listrik menggunakan algoritma **K-Nearest Neighbor (KNN)** dan metodologi **FMEA (Failure Mode and Effects Analysis)**.

---

## Daftar Isi

1. [Tech Stack](#tech-stack)
2. [Arsitektur Aplikasi](#arsitektur-aplikasi)
3. [Struktur Direktori](#struktur-direktori)
4. [Skema Database](#skema-database)
5. [Instalasi & Setup](#instalasi--setup)
6. [Tabel Route](#tabel-route)
7. [Panduan Fitur](#panduan-fitur)
8. [Algoritma KNN](#algoritma-knn)
9. [Keputusan Teknis & Catatan Penting](#keputusan-teknis--catatan-penting)
10. [Troubleshooting](#troubleshooting)

---

## Tech Stack

| Layer        | Teknologi                                               |
|--------------|---------------------------------------------------------|
| Backend      | PHP 8.1, custom MVC (tanpa framework)                  |
| Database     | MySQL 8 (via Laragon), PDO prepared statements          |
| Frontend     | Tailwind CSS CDN, Material Symbols Outlined (Google)   |
| Charts       | Chart.js 4.4.0 CDN                                     |
| Excel Import | PhpSpreadsheet (via Composer)                          |
| Server       | Laragon (Apache), `http://localhost/knn-pln/public`    |
| PHP Version  | 8.1.10                                                 |

---

## Arsitektur Aplikasi

```
Request → public/index.php → Router → Controller → Model → View
                                  ↓
                            Services (KNNClassifier)
```

### Core Layer (`core/`)

| File           | Peran                                                                 |
|----------------|-----------------------------------------------------------------------|
| `Router.php`   | Dispatch request ke controller. Mendukung exact match dan `{param}` regex |
| `Controller.php` | Base class: `view()`, `redirect()`, `json()`, `input()`           |
| `Model.php`    | Base class: inisialisasi PDO connection dari konstanta `DB_*`        |
| `Flash.php`    | Session flash messages → ditampilkan sebagai toast JS (top-right)   |

### Request Lifecycle

1. `public/index.php` — define konstanta `ROOT_PATH`, `APP_PATH`, `CORE_PATH`; require core files; require `config/app.php`; load `routes/web.php`; panggil `$router->dispatch()`
2. Router strip base path (`/knn-pln/public`), exact match dulu, baru regex match untuk route dengan `{id}`
3. Controller di-require secara lazy (`require_once`) saat route cocok
4. Controller memanggil Model, mengolah data, lalu memanggil `$this->view()`
5. `view()` menggunakan `extract($data)` → semua key array menjadi variabel di scope view

---

## Struktur Direktori

```
knn-pln/
├── app/
│   ├── Controllers/
│   │   ├── AuthController.php          Login / logout / session
│   │   ├── DashboardController.php     Halaman dashboard
│   │   ├── PemeliharaanController.php  CRUD data pemeliharaan + import Excel
│   │   ├── LabelingController.php      FMEA labeling + auto-label + split data
│   │   ├── KnnController.php           Training, evaluasi, prediksi KNN
│   │   ├── LaporanController.php       Laporan risiko
│   │   └── LandingController.php       Landing page (publik)
│   ├── Models/
│   │   ├── User.php                    Autentikasi pengguna
│   │   ├── Pemeliharaan.php            CRUD tabel pemeliharaan
│   │   ├── Labeling.php                FMEA labeling + split stratifikasi
│   │   ├── KnnModel.php                Penyimpanan model & prediksi KNN
│   │   └── Laporan.php                 Query agregat untuk laporan & landing
│   ├── Services/
│   │   └── KNNClassifier.php           Algoritma KNN murni PHP
│   └── Views/
│       ├── auth/login.php
│       ├── dashboard/index.php
│       ├── pemeliharaan/{index,form}.php
│       ├── labeling/{index,form,split}.php
│       ├── knn/{train,evaluate,predict}.php
│       ├── laporan/index.php
│       ├── landing/
│       │   ├── index.php               Orchestrator (head, navbar, scripts)
│       │   └── partials/
│       │       ├── hero.php
│       │       ├── evaluasi.php        Data real dari DB
│       │       ├── prediksi.php        Metrik KNN real dari DB
│       │       ├── about.php
│       │       └── footer.php
│       └── partials/                   Shared partials (app pages)
│           ├── head.php
│           ├── header.php
│           ├── sidebar.php
│           ├── toast.php
│           └── sidebar_script.php
├── core/
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   └── Flash.php
├── config/app.php                      Konstanta DB, BASE_URL, session_start()
├── routes/web.php                      Daftar semua route
├── database/
│   ├── migrations/
│   │   ├── 001_create_users_table.sql
│   │   ├── 002_create_pemeliharaan_table.sql
│   │   ├── 003_create_labeling_table.sql
│   │   └── 004_create_knn_tables.sql
│   └── seeders/
│       └── 001_seed_users.sql
├── storage/
│   ├── models/                         File biner model KNN terserialisasi
│   └── uploads/                        Upload sementara file Excel
├── vendor/                             Composer (PhpSpreadsheet)
├── composer.json
└── public/
    └── index.php                       Entry point tunggal
```

---

## Skema Database

### `users`
| Kolom      | Tipe                                   | Keterangan              |
|------------|----------------------------------------|-------------------------|
| id         | INT UNSIGNED PK                        |                         |
| name       | VARCHAR(100)                           |                         |
| email      | VARCHAR(150) UNIQUE                    | Digunakan sebagai login |
| password   | VARCHAR(255)                           | bcrypt hash             |
| role       | ENUM('admin','operator','viewer')      |                         |
| is_active  | TINYINT(1)                             |                         |
| last_login | DATETIME NULL                          |                         |

### `pemeliharaan`
| Kolom                      | Tipe              | Keterangan                         |
|----------------------------|-------------------|------------------------------------|
| id                         | INT UNSIGNED PK   |                                    |
| penyulang                  | VARCHAR(100)      | Nama penyulang (feeder)            |
| bulan                      | TINYINT(1-12)     |                                    |
| tahun                      | YEAR              | Default 2024                       |
| tier1_inpeksi              | INT UNSIGNED      | Jumlah inspeksi Tier 1             |
| tier1_temuan               | INT UNSIGNED      | Jumlah temuan Tier 1               |
| tier2_inpeksi              | INT UNSIGNED      |                                    |
| tier2_temuan               | INT UNSIGNED      |                                    |
| pengukuran                 | INT UNSIGNED      |                                    |
| pergantian_fco             | INT UNSIGNED      | Penggantian Fuse Cut-Out           |
| penyeimbangan_beban_gardu  | INT UNSIGNED      |                                    |
| perbaikan_grounding_trafo  | INT UNSIGNED      |                                    |
| penghalang_panjat          | INT UNSIGNED      |                                    |

### `labeling`
| Kolom           | Tipe                               | Keterangan                                          |
|-----------------|------------------------------------|-----------------------------------------------------|
| id              | INT UNSIGNED PK                    |                                                     |
| pemeliharaan_id | INT UNSIGNED FK UNIQUE             | 1 record pemeliharaan = 1 label                     |
| failure_mode    | VARCHAR(255)                       |                                                     |
| severity        | TINYINT(1-10)                      | Keparahan dampak                                    |
| occurrence      | TINYINT(1-10)                      | Frekuensi kejadian                                  |
| detection       | TINYINT(1-10)                      | Kemudahan deteksi (10 = sangat sulit dideteksi)    |
| rpn             | SMALLINT                           | Risk Priority Number = S × O × D                   |
| risk_label      | ENUM('Rendah','Sedang','Tinggi')   | Label risiko final                                  |
| split_type      | ENUM('train','test') NULL          | Hasil stratified split; NULL = belum di-split       |
| catatan         | TEXT NULL                          |                                                     |
| labeled_by      | INT UNSIGNED FK → users.id         |                                                     |

> **Catatan:** File SQL awal mendefinisikan `risk_label` sebagai `ENUM('Low','Medium','High')`. Nilai yang digunakan di seluruh aplikasi PHP adalah **Bahasa Indonesia** (`Rendah`, `Sedang`, `Tinggi`). Pastikan kolom sudah di-ALTER atau migration SQL sudah diupdate sebelum seed data.

### `knn_models`
| Kolom            | Tipe            | Keterangan                                      |
|------------------|-----------------|-------------------------------------------------|
| id               | INT UNSIGNED PK |                                                 |
| tahun            | YEAR            |                                                 |
| k_value          | TINYINT         | Nilai K yang digunakan saat training            |
| feature_columns  | VARCHAR(100)    | Fitur CSV, misal: `severity,occurrence,detection` |
| distance_metric  | VARCHAR(20)     | `euclidean` atau `manhattan`                    |
| train_count      | INT             | Jumlah data training                            |
| test_count       | INT             | Jumlah data test                                |
| accuracy         | DECIMAL(6,4)    | Akurasi pada test set                           |
| precision_score  | DECIMAL(6,4)    | Macro precision                                 |
| recall_score     | DECIMAL(6,4)    | Macro recall                                    |
| f1_score         | DECIMAL(6,4)    | Macro F1                                        |
| model_path       | VARCHAR(500)    | Path absolut ke file `.bin` terserialisasi      |
| trained_by       | INT FK          | `users.id`                                      |

### `knn_predictions`
| Kolom           | Tipe                               | Keterangan                          |
|-----------------|------------------------------------|-------------------------------------|
| id              | INT UNSIGNED PK                    |                                     |
| model_id        | INT UNSIGNED FK                    | → knn_models.id                     |
| pemeliharaan_id | INT UNSIGNED FK                    | → pemeliharaan.id (UNIQUE per model)|
| predicted_label | ENUM('Rendah','Sedang','Tinggi')   |                                     |
| actual_label    | ENUM('Rendah','Sedang','Tinggi')   | Salin dari labeling saat batch run  |
| confidence      | DECIMAL(5,4)                       | Proporsi vote mayoritas (0–1)       |
| neighbors_json  | TEXT                               | JSON array K tetangga terdekat      |

---

## Instalasi & Setup

### Prasyarat
- Laragon (Apache + MySQL 8 + PHP 8.1)
- Composer

### Langkah-langkah

```bash
# 1. Clone / letakkan di direktori web
# Pastikan folder ada di: C:\laragon\www\knn-pln\

# 2. Install dependensi PHP
cd C:\laragon\www\knn-pln
composer install

# 3. Jalankan migrasi — via phpMyAdmin atau CLI
mysql -u root knn-pln < database/migrations/001_create_users_table.sql
mysql -u root knn-pln < database/migrations/002_create_pemeliharaan_table.sql
mysql -u root knn-pln < database/migrations/003_create_labeling_table.sql
mysql -u root knn-pln < database/migrations/004_create_knn_tables.sql

# 4. Fix collation (WAJIB untuk MySQL 8 default utf8mb4_0900_ai_ci)
mysql -u root -e "
  USE \`knn-pln\`;
  ALTER TABLE knn_predictions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ALTER TABLE knn_models CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"

# 5. Generate password hash untuk seeder
php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
# Tempel hasilnya ke database/seeders/001_seed_users.sql, lalu jalankan

# 6. Pastikan direktori storage dapat ditulis
mkdir -p storage/models storage/uploads

# 7. Sesuaikan BASE_URL di config/app.php jika port/path berbeda
# Buka: config/app.php
# Ubah: define('BASE_URL', 'http://localhost/knn-pln/public');
```

### Akun Default (setelah seed)

| Email                   | Password       | Role     |
|-------------------------|----------------|----------|
| admin@pln.co.id         | admin123       | admin    |
| operator@pln.co.id      | operator123    | operator |
| viewer@pln.co.id        | viewer123      | viewer   |

---

## Tabel Route

| Method | Path                        | Controller@Method                    | Akses    |
|--------|-----------------------------|--------------------------------------|----------|
| GET    | `/`                         | `LandingController@index`            | Publik   |
| GET    | `/login`                    | `AuthController@showLogin`           | Publik   |
| POST   | `/login`                    | `AuthController@login`               | Publik   |
| GET    | `/logout`                   | `AuthController@logout`              | Auth     |
| GET    | `/dashboard`                | `DashboardController@index`          | Auth     |
| GET    | `/pemeliharaan`             | `PemeliharaanController@index`       | Auth     |
| GET    | `/pemeliharaan/create`      | `PemeliharaanController@create`      | Auth     |
| POST   | `/pemeliharaan/create`      | `PemeliharaanController@store`       | Auth     |
| GET    | `/pemeliharaan/edit/{id}`   | `PemeliharaanController@edit`        | Auth     |
| POST   | `/pemeliharaan/edit/{id}`   | `PemeliharaanController@update`      | Auth     |
| POST   | `/pemeliharaan/delete/{id}` | `PemeliharaanController@delete`      | Auth     |
| GET    | `/labeling`                 | `LabelingController@index`           | Auth     |
| GET    | `/labeling/create/{pemId}`  | `LabelingController@create`          | Auth     |
| POST   | `/labeling/store`           | `LabelingController@store`           | Auth     |
| GET    | `/labeling/edit/{id}`       | `LabelingController@edit`            | Auth     |
| POST   | `/labeling/edit/{id}`       | `LabelingController@update`          | Auth     |
| POST   | `/labeling/delete/{id}`     | `LabelingController@delete`          | Auth     |
| POST   | `/labeling/auto-label`      | `LabelingController@autoLabel`       | Auth     |
| GET    | `/labeling/split`           | `LabelingController@splitForm`       | Auth     |
| POST   | `/labeling/split`           | `LabelingController@executeSplit`    | Auth     |
| POST   | `/labeling/split/reset`     | `LabelingController@resetSplitData` | Auth     |
| GET    | `/knn/train`                | `KnnController@trainForm`            | Auth     |
| POST   | `/knn/train`                | `KnnController@train`                | Auth     |
| GET    | `/knn/evaluate`             | `KnnController@evaluate`             | Auth     |
| GET    | `/knn/predict`              | `KnnController@predictForm`          | Auth     |
| POST   | `/knn/predict`              | `KnnController@predictManual`        | Auth     |
| POST   | `/knn/predict/batch`        | `KnnController@predictBatch`         | Auth     |
| POST   | `/knn/delete/{id}`          | `KnnController@deleteModel`          | Auth     |
| GET    | `/laporan`                  | `LaporanController@index`            | Auth     |

---

## Panduan Fitur

### Alur Kerja Lengkap

```
Import Data Excel
      ↓
Data Pemeliharaan (CRUD)
      ↓
FMEA Labeling (manual / auto)
      ↓
Split Data (Stratified Train/Test)
      ↓
Training KNN
      ↓
Evaluasi Model (Confusion Matrix, Metrik)
      ↓
Prediksi (Manual S/O/D atau Batch)
      ↓
Laporan Risiko
```

---

### 1. Data Pemeliharaan (`/pemeliharaan`)

- CRUD data penyulang per bulan/tahun.
- Import massal via file `.xlsx` menggunakan **PhpSpreadsheet**.
- Kolom Excel: `PENYULANG | BULAN | TIER1_INPEKSI | TIER1_TEMUAN | TIER2_INPEKSI | TIER2_TEMUAN | PENGUKURAN | PERGANTIAN_FCO | PENYEIMBANGAN BEBAN GARDU | PERBAIKAN GROUNDING TRAFO | PENGHALANG_PANJAT`
- Opsi import: *timpa* (truncate + insert) atau *tambahkan* (append).

---

### 2. Labeling FMEA (`/labeling`)

Setiap record pemeliharaan diberi label risiko berdasarkan:

```
RPN = Severity × Occurrence × Detection
```

| RPN         | Label  |
|-------------|--------|
| 1 – 125     | Rendah |
| 126 – 512   | Sedang |
| 513 – 1000  | Tinggi |

- **Manual:** isi form Severity/Occurrence/Detection, RPN dihitung otomatis.
- **Auto-label:** tombol batch yang menghitung RPN dari data yang sudah ada dan mengisi label otomatis berdasarkan threshold di atas.
- Setiap pemeliharaan maksimal punya **1 label** (UNIQUE constraint pada `pemeliharaan_id`).

---

### 3. Split Data (`/labeling/split`)

Stratified split menjaga proporsi kelas (Rendah/Sedang/Tinggi) di kedua set:

```php
// Per kelas: acak ID → ambil n = round(total × ratio) untuk train
// Sisa → test
```

- Rasio yang tersedia: 70/30, 75/25, 80/20, 90/10.
- Hasil disimpan di kolom `split_type` pada tabel `labeling`.
- Split dapat di-reset dan dijalankan ulang.

---

### 4. Training KNN (`/knn/train`)

**Konfigurasi yang dapat diatur:**
- **K** (1–20): jumlah tetangga terdekat
- **Metrik jarak**: Euclidean (default) atau Manhattan
- **Fitur**: severity, occurrence, detection, rpn (pilih minimal 1)

**Proses training:**
1. Ambil data `split_type = 'train'` dari DB.
2. Normalisasi min-max per fitur.
3. `KNNClassifier::fit()` — simpan training data + normalization params.
4. `KNNClassifier::evaluate()` pada test set → hitung metrik.
5. Serialize model ke `storage/models/knn_{tahun}_{timestamp}.bin`.
6. Simpan metadata + metrik ke tabel `knn_models`.

**K-Accuracy Curve:** setelah training, controller menghitung akurasi untuk K=1 hingga min(15, train_count) untuk membantu pemilihan K optimal.

---

### 5. Evaluasi Model (`/knn/evaluate`)

Menampilkan:
- **4 metric cards**: Akurasi, Presisi (Macro), Recall (Macro), F1-Score (Macro)
- **Confusion Matrix 3×3** (Rendah/Sedang/Tinggi): diagonal biru (benar), off-diagonal merah (salah), warna proporsional terhadap jumlah
- **K-Accuracy Curve** (Chart.js line)
- **Classification Report** per kelas: Precision, Recall, F1, Support
- **Tabel prediksi detail**: baris merah = prediksi salah

> **Penting:** Akurasi di halaman evaluasi menggunakan **test set saja** (generalisasi nyata). Akurasi di halaman prediksi batch mencakup semua data termasuk train set — wajar lebih tinggi karena KNN "menghafal" data training (lazy learner).

---

### 6. Prediksi (`/knn/predict`)

**Tab Manual:**
- Input Severity, Occurrence, Detection via range slider
- RPN dihitung live di browser
- Tampil: label prediksi, confidence, tabel K tetangga terdekat

**Tab Batch:**
- Prediksi semua data berlabel sekaligus
- Hasil disimpan ke tabel `knn_predictions`
- Statistik dipisah: **Test Set** (acuan utama), **Train Set**, **Semua Data**
- Kolom "Split" di tabel menandai mana data train vs test

---

### 7. Laporan Risiko (`/laporan`)

- Filter tahun + pilih model KNN yang digunakan untuk perbandingan
- 6 summary cards: Total Data, Berlabel, Rendah, Sedang, Tinggi, Avg RPN
- Donut chart distribusi risiko
- Stacked bar distribusi bulanan
- Line chart tren RPN per bulan
- Grid alert penyulang risiko tinggi
- Tabel per penyulang (sortable: tinggi DESC → max_rpn DESC)
- **Tabel KNN vs FMEA Disagreements**: baris di mana prediksi KNN berbeda dengan label FMEA manual
- Tabel detail lengkap semua data
- **Print CSS**: sidebar/header disembunyikan saat cetak

---

### 8. Landing Page (`/`)

Halaman publik yang menampilkan **data nyata dari database**:

- Landing dipisah menjadi 5 partial: `hero.php`, `evaluasi.php`, `prediksi.php`, `about.php`, `footer.php`
- `LandingController` me-load: summary stats, distribusi bulanan, jumlah penyulang, model KNN terbaru
- **Evaluasi section**: stat cards (total/berlabel/penyulang/avgRPN), progress bar distribusi risiko, stacked bar chart bulanan (Chart.js)
- **Prediksi section**: metric cards KNN nyata (atau placeholder jika belum ada model), info strip detail model, bar chart tren bulanan
- Graceful empty state di semua komponen ketika database kosong
- Logged-in users di-redirect ke `/dashboard`

---

## Algoritma KNN

### `app/Services/KNNClassifier.php`

```
fit(data):
  simpan training data
  hitung normMin[f] dan normMax[f] per fitur

predict(point):
  normalize(point) → nilai 0–1 per fitur
  hitung jarak ke semua training data
  ambil K titik terdekat
  vote majority → label dengan suara terbanyak
  confidence = votes[label_menang] / K

evaluate(testData):
  jalankan predict() untuk setiap titik test
  bangun confusion matrix 3×3
  hitung per-class: TP, FP, FN → precision, recall, F1
  macro avg = rata-rata per kelas
```

### Normalisasi Min-Max

```
x_norm = (x - min) / (max - min)
```

Jika `max == min` (semua nilai sama), `max` di-set menjadi `min + 1` untuk menghindari pembagian dengan nol.

### Jarak

```
Euclidean: sqrt( Σ (a[f] - b[f])² )
Manhattan: Σ |a[f] - b[f]|
```

### Persistensi Model

Model di-serialize dengan `serialize()` PHP ke file `.bin`:
```php
// Simpan
file_put_contents($path, serialize([trainingData, k, metric, features, normMin, normMax]));

// Muat
$data = unserialize(file_get_contents($path));
```

---

## Keputusan Teknis & Catatan Penting

### Collation MySQL 8

MySQL 8 default collation adalah `utf8mb4_0900_ai_ci`, sedangkan tabel lama (`labeling`, `pemeliharaan`) menggunakan `utf8mb4_unicode_ci`. Perbandingan string lintas tabel (`l.risk_label <> kp.predicted_label`) akan gagal dengan error:

```
SQLSTATE[HY000]: Illegal mix of collations (utf8mb4_unicode_ci,IMPLICIT) 
and (utf8mb4_0900_ai_ci,IMPLICIT) for operation '<>'
```

**Fix wajib** setelah migration:
```sql
ALTER TABLE knn_predictions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE knn_models      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Lazy Loading Controller

Router tidak me-require semua controller di awal. Setiap controller di-require hanya saat route-nya dipanggil. Ini berarti **semua `require_once` model harus ada di dalam controller**, bukan di `index.php`.

### Array Input `$_POST['features']`

`$this->input('features')` di `Controller` hanya mengambil scalar `$_POST`. Untuk checkbox array (`features[]`), gunakan `$_POST['features']` langsung di controller. Ini sudah diimplementasikan di `KnnController::train()`.

### RPN Threshold Auto-Label

| RPN         | Label  |
|-------------|--------|
| 1 – 125     | Rendah |
| 126 – 512   | Sedang |
| 513 – 1000  | Tinggi |

Batas ini di-hardcode di `LabelingController::autoLabel()`. Jika perlu diubah, edit method tersebut.

### Flash Messages

`Flash::set('success'|'error'|'warning', 'pesan')` → disimpan ke `$_SESSION['flash']`. Di setiap view, `Flash::get()` mengambil sekaligus menghapus dari session. Toast JavaScript di `partials/toast.php` memproses variabel `$flash`.

### View Partials — Scope Inheritance

Semua `require` di dalam view PHP berbagi variable scope. Partial landing page (`hero.php`, `evaluasi.php`, dll.) mewarisi `$baseUrl`, `$summary`, `$monthly`, dll. dari `index.php` karena di-`require` (bukan `include` isolated). Variabel `$LANDING` di `landing/index.php` adalah shortcut path untuk partial:
```php
$LANDING = APP_PATH . '/Views/landing/partials/';
require $LANDING . 'hero.php';
```

---

## Troubleshooting

| Masalah | Penyebab | Solusi |
|---------|----------|--------|
| `Table 'knn-pln.knn_models' doesn't exist` | Migration 004 belum dijalankan | Jalankan `004_create_knn_tables.sql` via phpMyAdmin atau CLI |
| `Illegal mix of collations` di halaman Laporan | Collation mismatch MySQL 8 | Jalankan `ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` untuk `knn_predictions` dan `knn_models` |
| Akurasi di Evaluasi ≠ Prediksi Batch | Evaluasi = test set saja; Batch = semua data termasuk train | Ini **benar secara algoritmik**. Gunakan metrik test set sebagai acuan utama |
| Model binary tidak bisa dimuat | File `.bin` terhapus / path berubah | Re-train model. File disimpan di `storage/models/` |
| Chart tidak muncul | Chart.js belum di-load | `landing/index.php` memuat `chart.js` CDN di `<head>`. Pastikan akses internet tersedia |
| Import Excel gagal | `vendor/autoload.php` tidak ada | Jalankan `composer install` |
| Login gagal meski password benar | Hash di seeder adalah placeholder `$2y$10$...` | Generate ulang: `php -r "echo password_hash('password', PASSWORD_BCRYPT);"` |
| Halaman `/` redirect ke dashboard | User masih login | Logout dulu via `/logout` |
