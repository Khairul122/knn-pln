import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.preprocessing import MinMaxScaler
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, confusion_matrix, classification_report
import matplotlib.pyplot as plt
import seaborn as sns
import pickle
import os
from datetime import datetime

# Load data
df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')

# Hitung RPN score
def calculate_severity(row):
    for col in ['PERGANTIAN_FCO', 'PERGANTIAN FCO', 'PERBAIKAN GROUNDING TRAFO']:
        if col in row.index and row.get(col, 0) > 0: return 9
    if row.get('PENYEIMBANGAN BEBAN GARDU', 0) > 0: return 6
    if row.get('PENGUKURAN', 0) > 0: return 4
    return 1

def calculate_occurrence(row):
    total = row.get('TIER1_TEMUAN', 0) + row.get('TIER1 TEMUAN', 0) + row.get('TIER2_TEMUAN', 0) + row.get('TIER2 TEMUAN', 0)
    if total == 0: return 1
    elif total <= 10: return 4
    elif total <= 20: return 7
    else: return 9

def calculate_detection(row):
    tier1 = row.get('TIER1_INPEKSI', 0) + row.get('TIER1 INPEKSI', 0)
    tier2 = row.get('TIER2_INPEKSI', 0) + row.get('TIER2 INPEKSI', 0)
    if tier1 > 0 and tier2 > 0: return 2
    elif tier1 > 0 or tier2 > 0: return 5
    else: return 9

df['S'] = df.apply(calculate_severity, axis=1)
df['O'] = df.apply(calculate_occurrence, axis=1)
df['D'] = df.apply(calculate_detection, axis=1)
df['RPN'] = df['S'] * df['O'] * df['D']
df['LABEL'] = df['RPN'].apply(lambda x: 'Rendah' if x <= 9 else ('Sedang' if x <= 99 else 'Tinggi'))

print("Distribusi Label:\n", df['LABEL'].value_counts())

# Normalisasi
numeric_cols = df.select_dtypes(include=['float64', 'int64']).columns.tolist()
numeric_cols = [c for c in numeric_cols if c not in ['RPN', 'S', 'O', 'D']]

scaler = MinMaxScaler()
df_norm = df.copy()
df_norm[numeric_cols] = scaler.fit_transform(df[numeric_cols])

# Persiapan X, y dan split
X = df_norm[numeric_cols]
y = df_norm['LABEL'].map({'Rendah': 0, 'Sedang': 1, 'Tinggi': 2})

X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

print(f"\nData Split (80:20):")
print(f"Training: {len(X_train)} samples ({len(X_train)/len(X)*100:.1f}%)")
print(f"Testing: {len(X_test)} samples ({len(X_test)/len(X)*100:.1f}%)")

# Training dan Evaluasi dengan berbagai K
print("\n" + "="*50)
print("TRAINING KNN")
print("="*50)

results = {}
cv_scores_all = {}

for k in [3, 5, 7, 9]:
    knn = KNeighborsClassifier(n_neighbors=k)
    knn.fit(X_train, y_train)
    y_pred = knn.predict(X_test)
    
    # Cross-validation
    cv_scores = cross_val_score(knn, X_train, y_train, cv=5, scoring='accuracy')
    cv_scores_all[k] = cv_scores

    
    
    acc = accuracy_score(y_test, y_pred)
    prec = precision_score(y_test, y_pred, average='weighted', zero_division=   0)
    rec = recall_score(y_test, y_pred, average='weighted', zero_division=0)
    f1 = f1_score(y_test, y_pred, average='weighted', zero_division=0)
    cm = confusion_matrix(y_test, y_pred, labels=[0, 1, 2])
    
    results[k] = {'acc': acc, 'prec': prec, 'rec': rec, 'f1': f1, 'cm': cm, 
                  'model': knn, 'y_pred': y_pred, 'cv_mean': cv_scores.mean(), 'cv_std': cv_scores.std()}
    
    print(f"\nK={k}:")
    print(f"  Accuracy (Test): {acc:.4f} ({acc*100:.2f}%)")
    print(f"  Cross-Validation: {cv_scores.mean():.4f} (+/- {cv_scores.std():.4f})")
    print(f"  Precision: {prec:.4f}")
    print(f"  Recall: {rec:.4f}")
    print(f"  F1-Score: {f1:.4f}")
    print(f"  Confusion Matrix:\n{cm}")

best_k = max(results, key=lambda k: results[k]['acc'])
best_model = results[best_k]['model']
print(f"\nK TERBAIK: {best_k} dengan Accuracy {results[best_k]['acc']:.4f}")

# ==================== SIMPAN MODEL ====================
print("\n" + "="*50)
print("MENYIMPAN MODEL DAN HASIL")
print("="*50)

# Buat folder output jika belum ada
if not os.path.exists('output_knn'):
    os.makedirs('output_knn')

# Simpan model terbaik
model_filename = f'output_knn/knn_model_k{best_k}.pkl'
with open(model_filename, 'wb') as f:
    pickle.dump(best_model, f)
print(f"✓ Model disimpan: {model_filename}")

# Simpan scaler
scaler_filename = 'output_knn/scaler.pkl'
with open(scaler_filename, 'wb') as f:
    pickle.dump(scaler, f)
print(f"✓ Scaler disimpan: {scaler_filename}")

# Simpan hasil evaluasi ke CSV
results_df = pd.DataFrame({
    'K': list(results.keys()),
    'Accuracy': [results[k]['acc'] for k in results.keys()],
    'Precision': [results[k]['prec'] for k in results.keys()],
    'Recall': [results[k]['rec'] for k in results.keys()],
    'F1-Score': [results[k]['f1'] for k in results.keys()],
    'CV_Mean': [results[k]['cv_mean'] for k in results.keys()],
    'CV_Std': [results[k]['cv_std'] for k in results.keys()]
})
results_csv = 'output_knn/evaluasi_hasil.csv'
results_df.to_csv(results_csv, index=False)
print(f"✓ Hasil evaluasi disimpan: {results_csv}")

# ==================== VISUALISASI ====================
print("\n" + "="*50)
print("MEMBUAT VISUALISASI")
print("="*50)

# Plot 1: Perbandingan Metrics
fig, axes = plt.subplots(2, 2, figsize=(14, 10))
fig.suptitle('Evaluasi KNN dengan Berbagai K', fontsize=16, fontweight='bold')

k_vals = sorted(results.keys())

axes[0, 0].plot(k_vals, [results[k]['acc'] for k in k_vals], marker='o', linewidth=2)
axes[0, 0].set_title('Accuracy vs K')
axes[0, 0].set_xlabel('K')
axes[0, 0].set_ylabel('Accuracy')
axes[0, 0].grid(True, alpha=0.3)
axes[0, 0].set_xticks(k_vals)

axes[0, 1].plot(k_vals, [results[k]['prec'] for k in k_vals], marker='s', linewidth=2, color='orange')
axes[0, 1].set_title('Precision vs K')
axes[0, 1].set_xlabel('K')
axes[0, 1].set_ylabel('Precision')
axes[0, 1].grid(True, alpha=0.3)
axes[0, 1].set_xticks(k_vals)

axes[1, 0].plot(k_vals, [results[k]['rec'] for k in k_vals], marker='^', linewidth=2, color='green')
axes[1, 0].set_title('Recall vs K')
axes[1, 0].set_xlabel('K')
axes[1, 0].set_ylabel('Recall')
axes[1, 0].grid(True, alpha=0.3)
axes[1, 0].set_xticks(k_vals)

axes[1, 1].plot(k_vals, [results[k]['f1'] for k in k_vals], marker='D', linewidth=2, color='red')
axes[1, 1].set_title('F1-Score vs K')
axes[1, 1].set_xlabel('K')
axes[1, 1].set_ylabel('F1-Score')
axes[1, 1].grid(True, alpha=0.3)
axes[1, 1].set_xticks(k_vals)

plt.tight_layout()
plt.savefig('output_knn/metrics_comparison.png', dpi=300, bbox_inches='tight')
print("✓ Grafik metrics disimpan: output_knn/metrics_comparison.png")
plt.close()

# Plot 2: Confusion Matrix untuk K terbaik
plt.figure(figsize=(8, 6))
cm = results[best_k]['cm']
sns.heatmap(cm, annot=True, fmt='d', cmap='Blues', cbar=True,
            xticklabels=['Rendah', 'Sedang', 'Tinggi'],
            yticklabels=['Rendah', 'Sedang', 'Tinggi'])
plt.title(f'Confusion Matrix K={best_k} (Accuracy: {results[best_k]["acc"]:.2%})')
plt.ylabel('True Label')
plt.xlabel('Predicted Label')
plt.tight_layout()
plt.savefig(f'output_knn/confusion_matrix_k{best_k}.png', dpi=300, bbox_inches='tight')
print(f"✓ Confusion Matrix disimpan: output_knn/confusion_matrix_k{best_k}.png")
plt.close()

# Plot 3: Cross-Validation Scores
fig, ax = plt.subplots(figsize=(10, 6))
cv_data = pd.DataFrame({
    'K': list(cv_scores_all.keys()),
    'CV_Mean': [results[k]['cv_mean'] for k in cv_scores_all.keys()],
    'CV_Std': [results[k]['cv_std'] for k in cv_scores_all.keys()]
})
ax.errorbar(cv_data['K'], cv_data['CV_Mean'], yerr=cv_data['CV_Std'], 
            marker='o', linestyle='-', linewidth=2, markersize=8, capsize=5)
ax.set_title('Cross-Validation Scores (5-Fold)')
ax.set_xlabel('K')
ax.set_ylabel('CV Accuracy')
ax.grid(True, alpha=0.3)
ax.set_xticks(cv_data['K'])
plt.tight_layout()
plt.savefig('output_knn/cross_validation_scores.png', dpi=300, bbox_inches='tight')
print("✓ Cross-Validation scores disimpan: output_knn/cross_validation_scores.png")
plt.close()

# ==================== PREDIKSI DATA BARU ====================
print("\n" + "="*50)
print("PREDIKSI DATA BARU")
print("="*50)

def predict_new_data(data_baru, scaler, model):
    """Prediksi data baru
    Parameter:
    - data_baru: DataFrame dengan kolom numerik yang sama dengan training
    - scaler: MinMaxScaler yang sudah di-fit
    - model: Model KNN yang sudah di-train
    """
    # Normalisasi data baru
    data_norm = data_baru[numeric_cols].copy()
    data_norm[numeric_cols] = scaler.transform(data_norm[numeric_cols])
    
    # Prediksi
    prediction = model.predict(data_norm)
    probability = model.predict_proba(data_norm)
    
    return prediction, probability

# Contoh prediksi: gunakan sample dari test set
sample_idx = 0
sample_data = X_test.iloc[[sample_idx]]
pred, prob = predict_new_data(X_test.iloc[[sample_idx]], scaler, best_model)

label_map = {0: 'Rendah', 1: 'Sedang', 2: 'Tinggi'}
print(f"\nContoh Prediksi Data Baru:")
print(f"Data sample #{sample_idx}:")
print(f"Prediksi: {label_map[pred[0]]}")
print(f"Confidence: {prob[0][pred[0]]:.2%}")
print(f"\nProbalitas setiap class:")
for i, class_name in label_map.items():
    print(f"  {class_name}: {prob[0][i]:.2%}")

# Simpan fungsi prediksi ke file
prediction_results = pd.DataFrame({
    'Sample': range(len(X_test)),
    'Predicted': [label_map[p] for p in results[best_k]['y_pred']],
    'True': [label_map[t] for t in y_test.values]
})
prediction_results.to_csv('output_knn/prediksi_detail.csv', index=False)
print("\n✓ Hasil prediksi detail disimpan: output_knn/prediksi_detail.csv")

# ==================== RINGKASAN ====================
print("\n" + "="*50)
print("RINGKASAN TRAINING KNN")
print("="*50)
print(f"✓ Total data: {len(df)}")
print(f"✓ Training: {len(X_train)}, Testing: {len(X_test)}")
print(f"✓ Best K: {best_k}")
print(f"✓ Best Accuracy: {results[best_k]['acc']:.4f}")
print(f"✓ All files saved to: output_knn/")
print("="*50)


