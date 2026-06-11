import pandas as pd
import os

# Membaca data
file_path = 'DATA_PLN.xlsx'
df = pd.read_excel(file_path, sheet_name='Sheet1')

# 1. Membersihkan spasi pada nama penyulang agar konsisten (misal: 'AR 01' menjadi 'AR01')
# Gunakan .astype(str) untuk memastikan fungsi string bekerja
df['PENYULANG'] = df['PENYULANG'].astype(str).str.replace(' ', '')  

# 2. Mengisi nilai NaN dengan 0 (asumsi tidak ada aktivitas/temuan pada sel kosong)
df = df.fillna(0)

# 3. Menghapus kolom 'No' yang tidak diperlukan untuk perhitungan jarak di K-NN
df_clean = df.drop(columns=['No'])

# Menentukan nama file output
output_file = 'PEMELIHARAAN_PLN_2024_CLEANED.xlsx'

# Menyimpan hasil ke file Excel baru
df_clean.to_excel(output_file, index=False)

print(f"Total baris data: {len(df_clean)}")
print(f"Data yang sudah dibersihkan telah disimpan dengan nama: {output_file}")
print("\nPreview 5 data teratas:")
print(df_clean.head())