import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler
from sklearn.neighbors import KNeighborsClassifier

# Load data
df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')

# Hitung RPN
def severity(row):
    for col in ['PERGANTIAN_FCO', 'PERGANTIAN FCO', 'PERBAIKAN GROUNDING TRAFO']:
        if col in row.index and row.get(col, 0) > 0: return 9
    if row.get('PENYEIMBANGAN BEBAN GARDU', 0) > 0: return 6
    if row.get('PENGUKURAN', 0) > 0: return 4
    return 1

def occurrence(row):
    total = row.get('TIER1_TEMUAN', 0) + row.get('TIER1 TEMUAN', 0) + row.get('TIER2_TEMUAN', 0) + row.get('TIER2 TEMUAN', 0)
    return 1 if total == 0 else (4 if total <= 10 else (7 if total <= 20 else 9))

def detection(row):
    tier1 = row.get('TIER1_INPEKSI', 0) + row.get('TIER1 INPEKSI', 0)
    tier2 = row.get('TIER2_INPEKSI', 0) + row.get('TIER2 INPEKSI', 0)
    return 2 if tier1 > 0 and tier2 > 0 else (5 if tier1 > 0 or tier2 > 0 else 9)

df['S'] = df.apply(severity, axis=1)
df['O'] = df.apply(occurrence, axis=1)
df['D'] = df.apply(detection, axis=1)
df['RPN'] = df['S'] * df['O'] * df['D']
df['LABEL'] = df['RPN'].apply(lambda x: 'Rendah' if x <= 9 else ('Sedang' if x <= 99 else 'Tinggi'))

# Normalisasi
cols = [c for c in df.select_dtypes(include=['float64', 'int64']).columns if c not in ['RPN', 'S', 'O', 'D']]
scaler = MinMaxScaler()
df[cols] = scaler.fit_transform(df[cols])

# Split data
X, y = df[cols].values, df['LABEL'].values
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

# Train model
knn = KNeighborsClassifier(n_neighbors=3, metric='euclidean')
knn.fit(X_train, y_train)

# Prediksi data latih
y_train_pred = knn.predict(X_train)

# Buat DataFrame untuk output data latih
X_train_df = pd.DataFrame(X_train, columns=cols)
train_data_df = X_train_df.copy()
train_data_df['Actual_Label'] = y_train
train_data_df['Predicted_Label'] = y_train_pred
train_data_df['Prediction_Match'] = train_data_df['Actual_Label'] == train_data_df['Predicted_Label']

print("Data Latih:")
print(train_data_df.to_string(index=True, max_rows=11))

# Print hasil
print(f"Data: {len(df)} samples")
print(f"Train: {len(X_train)}, Test: {len(X_test)}")
print(f"Train accuracy: {knn.score(X_train, y_train):.4f}")

# Simpan hasil ke Excel
df.to_excel('hasil_training.xlsx', index=False)
train_data_df.to_excel('train_data_latih.xlsx', index=False)
print("Training selesai!")
print("Hasil disimpan ke file: hasil_training.xlsx dan train_data_latih.xlsx")
