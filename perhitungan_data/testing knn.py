import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import MinMaxScaler
from sklearn.neighbors import KNeighborsClassifier
from sklearn.metrics import classification_report, confusion_matrix
import matplotlib.pyplot as plt
import seaborn as sns

# Load data (sama seperti di train.py)
df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')

# Hitung RPN (sama seperti di train.py)
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

# Normalisasi (sama seperti di train.py)
cols = [c for c in df.select_dtypes(include=['float64', 'int64']).columns if c not in ['RPN', 'S', 'O', 'D']]
scaler = MinMaxScaler()
df[cols] = scaler.fit_transform(df[cols])

# Split data (sama seperti di train.py)
X, y = df[cols].values, df['LABEL'].values
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42, stratify=y)

# Train model (sama seperti di train.py)
knn = KNeighborsClassifier(n_neighbors=3)
knn.fit(X_train, y_train)

print("=" * 60)
print("TESTING KNN MODEL")
print("=" * 60)

# Prediksi data uji
y_test_pred = knn.predict(X_test)

# Buat DataFrame untuk hasil testing
X_test_df = pd.DataFrame(X_test, columns=cols)
test_data_df = X_test_df.copy()
test_data_df['Actual_Label'] = y_test
test_data_df['Predicted_Label'] = y_test_pred
test_data_df['Prediction_Match'] = test_data_df['Actual_Label'] == test_data_df['Predicted_Label']

print("Data Test:")
print(test_data_df.to_string(index=True, max_rows=11))

# Evaluasi model
test_accuracy = knn.score(X_test, y_test)
print(f"\nTest Accuracy: {test_accuracy:.4f} ({test_accuracy*100:.2f}%)")

print("\nClassification Report:")
print(classification_report(y_test, y_test_pred))

print("\nConfusion Matrix:")
cm = confusion_matrix(y_test, y_test_pred)
print(cm)

# Visualisasi Confusion Matrix
plt.figure(figsize=(8, 6))
sns.heatmap(cm, annot=True, fmt='d', cmap='Blues',
            xticklabels=['Rendah', 'Sedang', 'Tinggi'],
            yticklabels=['Rendah', 'Sedang', 'Tinggi'])
plt.title('Confusion Matrix - KNN Testing Results')
plt.xlabel('Predicted Label')
plt.ylabel('Actual Label')
plt.tight_layout()
plt.savefig('confusion_matrix.png', dpi=300, bbox_inches='tight')
plt.show()

# Simpan hasil ke Excel
test_data_df.to_excel('hasil_testing.xlsx', index=False)
print("\nTesting selesai!")
print("Hasil disimpan ke file: hasil_testing.xlsx")