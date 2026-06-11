import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split

# 1. Load Data
df_norm = pd.read_csv("hasil_normalisasi_gardu.xlsx - Sheet1.csv")
df_orig = pd.read_csv("PEMELIHARAAN_PLN_2024_FINAL_LABELED.xlsx - Sheet1.csv")

# Fitur teknis
features = df_norm.columns[2:]
X = df_norm[features].values
y = df_orig['LABEL_RISIKO'].values
labels = df_norm['PENYULANG'] + " (" + df_norm['BULAN'] + ")"

# 2. Split 80:20 (Konsisten dengan sebelumnya)
X_train, X_test, y_train, y_test, labels_train, labels_test = train_test_split(
    X, y, labels, test_size=0.20, random_state=42
)

# 3. Pilih SATU data uji sebagai contoh (Ambil index pertama dari data uji)
idx_uji = 0
data_uji_single = X_test[idx_uji]
label_uji_single = labels_test.iloc[idx_uji]
target_asli = y_test[idx_uji]

# 4. Hitung Euclidean Distance dari SATU data uji ke SEMUA data latih
distances = np.sqrt(np.sum((X_train - data_uji_single)**2, axis=1))

# 5. Gabungkan hasil ke dalam tabel untuk diurutkan
hasil_jarak = pd.DataFrame({
    'Penyulang_Latih': labels_train,
    'Jarak_Euclidean': distances,
    'Label_Risiko_Latih': y_train
})

# Urutkan berdasarkan jarak terkecil
hasil_urut = hasil_jarak.sort_values(by='Jarak_Euclidean').reset_index(drop=True)

print(f"SIMULASI KNN UNTUK SATU DATA UJI")
print(f"---------------------------------")
print(f"Data Uji yang dipilih: {label_uji_single}")
print(f"Label Asli: {target_asli}")
print(f"\n5 Tetangga Terdekat (Jarak Terkecil):")
print(hasil_urut.head(5))

# Tentukan K=3 untuk contoh prediksi
k = 3
tetangga_terdekat = hasil_urut.head(k)
prediksi = tetangga_terdekat['Label_Risiko_Latih'].mode()[0]

print(f"\nPrediksi dengan K={k}: {prediksi}")