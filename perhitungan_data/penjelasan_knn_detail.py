"""
KNN (K-Nearest Neighbors) - Penjelasan Detail Algoritma
========================================================
File ini menjelaskan proses KNN step-by-step dengan contoh nyata
"""

import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler

print("="*70)
print("KNN ALGORITHM - PENJELASAN DETAIL")
print("="*70)

# ==================== 1. LOAD & PREPARE DATA ====================
print("\n[STEP 1] LOAD & PREPARE DATA")
print("-"*70)

df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')

# Hitung RPN & Label
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

print(f"Total data: {len(df)} samples")
print(f"Distribusi label: {df['LABEL'].value_counts().to_dict()}")

# ==================== 2. NORMALISASI ====================
print("\n[STEP 2] NORMALISASI (MinMax Scaling 0-1)")
print("-"*70)

features = [c for c in df.select_dtypes(include=['float64', 'int64']).columns if c not in ['RPN', 'S', 'O', 'D']]
scaler = MinMaxScaler()
X = scaler.fit_transform(df[features])
y_str = df['LABEL'].values

print(f"Fitur yang digunakan: {len(features)}")
print(f"Contoh fitur: {features[:3]}")

# ==================== 3. SPLIT DATA ====================
print("\n[STEP 3] SPLIT TRAIN/TEST (80:20)")
print("-"*70)

X_train, X_test, y_train, y_test = train_test_split(X, y_str, test_size=0.2, random_state=42, stratify=y_str)
print(f"Training: {len(X_train)} samples")
print(f"Testing: {len(X_test)} samples")

# ==================== 4. FUNGSI EUCLIDEAN DISTANCE ====================
print("\n[STEP 4] EUCLIDEAN DISTANCE - RUMUS")
print("-"*70)
print("Jarak Euclidean = √[(x₁-x₂)² + (y₁-y₂)² + ... + (n₁-n₂)²]")

def euclidean_distance(point1, point2):
    """Hitung jarak Euclidean antara 2 titik"""
    return np.sqrt(np.sum((point1 - point2) ** 2))

# Test dengan 2 sample
sample1 = X_train[0]
sample2 = X_train[1]
jarak = euclidean_distance(sample1, sample2)
print(f"\nContoh perhitungan jarak:")
print(f"  Fitur sample 1: {sample1[:3]}...")
print(f"  Fitur sample 2: {sample2[:3]}...")
print(f"  Jarak = {jarak:.6f}")

# ==================== 5. K-NN PREDICTION ====================
print("\n[STEP 5] KNN PREDICTION - ALGORITMA")
print("-"*70)
print("1. Hitung jarak ke SEMUA training data")
print("2. Urutkan dari jarak terkecil")
print("3. Ambil K tetangga terdekat")
print("4. Vote: kelas mana yang paling banyak")
print("5. Output: kelas pemenang\n")

def knn_predict_detail(test_sample, X_train, y_train, k=3):
    """KNN prediksi dengan detail output"""
    # 1. Hitung jarak ke semua training data
    distances = np.array([euclidean_distance(test_sample, x) for x in X_train])
    
    # 2. Urutkan & ambil k tetangga terdekat
    nearest_idx = np.argsort(distances)[:k]
    nearest_distances = distances[nearest_idx]
    nearest_labels = y_train[nearest_idx]
    
    # 3. Voting
    from collections import Counter
    votes = Counter(nearest_labels)
    prediction = votes.most_common(1)[0][0]
    
    return prediction, nearest_idx, nearest_distances, nearest_labels

# ==================== 6. DEMO PREDIKSI DETAIL ====================
print("[STEP 6] DEMO - PREDIKSI DETAIL UNTUK 3 SAMPLE TEST")
print("-"*70)

k = 3
for i in range(3):
    test_sample = X_test[i]
    actual = y_test[i]
    
    pred, idx, dist, labels = knn_predict_detail(test_sample, X_train, y_train, k)
    
    print(f"\n┌─ Sample Test #{i+1}")
    print(f"│ Label aktual: {actual}")
    print(f"│")
    print(f"│ K-Tetangga Terdekat (k=3):")
    for j, (neighbor_idx, distance, label) in enumerate(zip(idx, dist, labels)):
        print(f"│   {j+1}. Distance={distance:.6f} → Label={label}")
    
    from collections import Counter
    votes = Counter(labels)
    print(f"│")
    print(f"│ Voting: {dict(votes)}")
    print(f"│ Prediksi: {pred}")
    print(f"│ Status: {'✓ BENAR' if pred == actual else '✗ SALAH'}")
    print(f"└─")

# ==================== 7. EVALUASI KESELURUHAN ====================
print("\n[STEP 7] EVALUASI KESELURUHAN MODEL")
print("-"*70)

k = 3
correct = 0
total = len(X_test)

for i in range(total):
    pred, _, _, _ = knn_predict_detail(X_test[i], X_train, y_train, k)
    if pred == y_test[i]:
        correct += 1

accuracy = correct / total
print(f"K = {k}")
print(f"Test Accuracy: {accuracy:.4f} ({accuracy*100:.2f}%)")
print(f"Benar: {correct}/{total}")

# ==================== 8. SIMPAN MODEL (TRAINING) ====================
print("\n[STEP 8] SIMPAN MODEL - UNTUK DEPLOYMENT")
print("-"*70)

import pickle
import os

# Buat folder jika belum ada
os.makedirs('model_deployment', exist_ok=True)

# Simpan scaler (penting untuk normalize data baru)
with open('model_deployment/scaler.pkl', 'wb') as f:
    pickle.dump(scaler, f)
print("✓ Scaler disimpan: model_deployment/scaler.pkl")

# Simpan training data (untuk KNN perlu data training asli)
train_data = {
    'X_train': X_train,
    'y_train': y_train,
    'features': features,
    'k': k
}
with open('model_deployment/knn_model.pkl', 'wb') as f:
    pickle.dump(train_data, f)
print("✓ Model disimpan: model_deployment/knn_model.pkl")

# ==================== 9. LOAD MODEL (DEPLOYMENT) ====================
print("\n[STEP 9] LOAD MODEL - UNTUK PENGGUNAAN")
print("-"*70)

# Load scaler
with open('model_deployment/scaler.pkl', 'rb') as f:
    scaler_loaded = pickle.load(f)
print("✓ Scaler dimuat")

# Load model
with open('model_deployment/knn_model.pkl', 'rb') as f:
    model_data = pickle.load(f)
    X_train_loaded = model_data['X_train']
    y_train_loaded = model_data['y_train']
    features_loaded = model_data['features']
    k_loaded = model_data['k']
print(f"✓ Model dimuat (k={k_loaded}, training size={len(X_train_loaded)})")

# ==================== 10. PREDIKSI DATA BARU ====================
print("\n[STEP 10] PREDIKSI DATA BARU (DEPLOYMENT)")
print("-"*70)

# Simulasi: Ambil 5 sample dari test data sebagai "data baru"
print("\nSkenario: Ada 5 data baru yang perlu diprediksi\n")

data_baru_raw = X_test[:5]  # Simulasi data baru (sudah normalized)
label_asli = y_test[:5]     # Label asli untuk validasi

for idx, (data, label_true) in enumerate(zip(data_baru_raw, label_asli)):
    # Prediksi menggunakan model yang sudah disimpan
    pred, neighbors_idx, distances, neighbors_labels = knn_predict_detail(
        data, X_train_loaded, y_train_loaded, k_loaded
    )
    
    print(f"Data Baru #{idx+1}:")
    print(f"  Prediksi: {pred}")
    print(f"  Label Aktual: {label_true}")
    print(f"  Status: {'✓ BENAR' if pred == label_true else '✗ SALAH'}")
    print()

# ==================== 11. FLOW DIAGRAM ====================
print("[STEP 11] RINGKASAN FLOW KNN")
print("-"*70)

flow = """
┌─── TRAINING (Offline - Dilakukan sekali) ───┐
│                                              │
│ 1. Load data + Preprocessing                │
│ 2. Normalisasi data                         │
│ 3. Split train/test                         │
│ 4. Simpan:                                  │
│    - scaler.pkl                             │
│    - X_train, y_train, features, k          │
│                                              │
└──────────────────────────────────────────────┘
                        ↓
        ┌─── DEPLOYMENT (Online - Runtime) ───┐
        │                                      │
        │ 1. Load scaler.pkl                  │
        │ 2. Load X_train, y_train, k         │
        │ 3. Normalize data baru              │
        │ 4. Hitung jarak ke X_train          │
        │ 5. Ambil k tetangga terdekat        │
        │ 6. Voting → Prediksi                │
        │                                      │
        └──────────────────────────────────────┘
"""
print(flow)

print("="*70)
print("✓ PENJELASAN KNN LENGKAP (TRAINING + DEPLOYMENT)")
print("="*70)
