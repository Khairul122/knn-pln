import pandas as pd
from sklearn.preprocessing import MinMaxScaler
import os

# 1. Pastikan nama file sesuai dengan yang ada di folder Anda
# Jika nama filenya berbeda, silakan ganti teks di bawah ini
file_path = 'PEMELIHARAAN_PLN_2024_CLEANED.xlsx' 

# Cek apakah file ada sebelum membaca
if not os.path.exists(file_path):
    print(f"Error: File '{file_path}' tidak ditemukan di folder ini.")
    print("Pastikan file Excel dan script .py berada di folder yang sama.")
else:
    # 2. Memuat data dari file Excel (Memerlukan library: pip install openpyxl)
    df = pd.read_excel(file_path)

    # 3. Menentukan kolom yang akan dinormalisasi (Numerik saja, kecuali 'No')
    cols_to_scale = df.select_dtypes(include=['float64', 'int64']).columns.tolist()
    if 'No' in cols_to_scale:
        cols_to_scale.remove('No')

    # 4. Inisialisasi MinMaxScaler
    scaler = MinMaxScaler()

    # 5. Melakukan Proses Normalisasi
    df_normalized = df.copy()
    df_normalized[cols_to_scale] = scaler.fit_transform(df[cols_to_scale])

    # 6. Menampilkan hasil di terminal
    print("\n--- Hasil Normalisasi Data (10 Baris Pertama) ---")
    print(df_normalized.head(10).to_string())

    # 7. Menyimpan hasil ke file Excel baru
    output_file = 'hasil_normalisasi_gardu.xlsx'
    df_normalized.to_excel(output_file, index=False)
    print(f"\nSelesai! Hasil disimpan ke: {os.path.abspath(output_file)}")