import pandas as pd
from sklearn.preprocessing import MinMaxScaler
from sklearn.neighbors import KNeighborsClassifier
from sklearn.model_selection import GridSearchCV

# Load data
df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')

# Hitung RPN
def severity(row):
    for col in ['PERGANTIAN_FCO', 'PERGANTIAN FCO', 'PERBAIKAN GROUNDING TRAFO']:
        if col in row.index and row.get(col, 0) > 0:
            return 9
    if row.get('PENYEIMBANGAN BEBAN GARDU', 0) > 0:
        return 6
    if row.get('PENGUKURAN', 0) > 0:
        return 4
    return 1


def occurrence(row):
    total = (
        row.get('TIER1_TEMUAN', 0) + row.get('TIER1 TEMUAN', 0)
        + row.get('TIER2_TEMUAN', 0) + row.get('TIER2 TEMUAN', 0)
    )
    if total == 0:
        return 1
    if total <= 10:
        return 4
    if total <= 20:
        return 7
    return 9


def detection(row):
    tier1 = row.get('TIER1_INPEKSI', 0) + row.get('TIER1 INPEKSI', 0)
    tier2 = row.get('TIER2_INPEKSI', 0) + row.get('TIER2 INPEKSI', 0)
    if tier1 > 0 and tier2 > 0:
        return 2
    if tier1 > 0 or tier2 > 0:
        return 5
    return 9


# Persiapan data
(df['S'], df['O'], df['D']) = (
    df.apply(severity, axis=1),
    df.apply(occurrence, axis=1),
    df.apply(detection, axis=1),
)
df['RPN'] = df['S'] * df['O'] * df['D']
df['LABEL'] = df['RPN'].apply(lambda x: 'Rendah' if x <= 9 else ('Sedang' if x <= 99 else 'Tinggi'))

cols = [
    c for c in df.select_dtypes(include=['float64', 'int64']).columns
    if c not in ['RPN', 'S', 'O', 'D']
]
scaler = MinMaxScaler()
df[cols] = scaler.fit_transform(df[cols])

X = df[cols].values
y = df['LABEL'].values

print('Menentukan nilai K terbaik...')
param_grid = {'n_neighbors': range(1, 21)}
grid_search = GridSearchCV(
    KNeighborsClassifier(), param_grid, cv=5, scoring='accuracy', n_jobs=-1
)
grid_search.fit(X, y)
best_k = grid_search.best_params_['n_neighbors']
print(f'K terbaik: {best_k}')
print(f'Skor cross-validation terbaik: {grid_search.best_score_:.4f}')

