import pickle
import os
import pandas as pd
from datetime import datetime

def load_latest_model():
    """Load model KNN terbaru dari folder models/"""
    if not os.path.exists('models'):
        print("Folder models/ tidak ditemukan!")
        return None, None, None

    # Cari file model terbaru
    model_files = [f for f in os.listdir('models') if f.startswith('knn_model_') and f.endswith('.pkl')]
    if not model_files:
        print("Tidak ada model yang ditemukan!")
        return None, None, None

    # Sort berdasarkan timestamp (terbaru dulu)
    model_files.sort(reverse=True)
    latest_model = model_files[0]

    # Ekstrak timestamp dari nama file
    timestamp = latest_model.replace('knn_model_', '').replace('.pkl', '')

    try:
        # Load model
        with open(f'models/knn_model_{timestamp}.pkl', 'rb') as f:
            model = pickle.load(f)

        # Load scaler
        with open(f'models/scaler_{timestamp}.pkl', 'rb') as f:
            scaler = pickle.load(f)

        # Load label mapping
        with open(f'models/label_mapping_{timestamp}.pkl', 'rb') as f:
            label_map = pickle.load(f)

        print(f"✓ Model berhasil dimuat: {timestamp}")
        print(f"  Model: {type(model).__name__}")
        print(f"  Scaler: {type(scaler).__name__}")
        print(f"  Label mapping: {label_map}")

        return model, scaler, label_map

    except Exception as e:
        print(f"Error loading model: {e}")
        return None, None, None

def predict_sample(model, scaler, label_map, sample_data):
    """Prediksi satu sample data"""
    # Normalisasi data
    sample_normalized = scaler.transform([sample_data])

    # Prediksi
    prediction = model.predict(sample_normalized)[0]

    # Mapping ke label asli
    label_dict = {v: k for k, v in label_map.items()}
    risk_level = label_dict.get(prediction, prediction)

    return risk_level

# ==================== MAIN ====================
if __name__ == "__main__":
    print("LOADING KNN MODEL")
    print("=" * 40)

    # Load model terbaru
    model, scaler, label_map = load_latest_model()

    if model is None:
        print("Gagal load model!")
        exit()

    # Load training info jika ada
    try:
        info_files = [f for f in os.listdir('models') if f.startswith('training_info_') and f.endswith('.json')]
        if info_files:
            info_files.sort(reverse=True)
            latest_info = info_files[0]
            import json
            with open(f'models/{latest_info}', 'r') as f:
                info = json.load(f)

            print(f"\nTraining Info:")
            print(f"  Data size: {info['data_size']}")
            print(f"  Train/Test: {info['train_size']}/{info['test_size']}")
            print(f"  Features: {info['features']}")
            print(f"  Accuracy: Train={info['train_acc']:.4f}, Test={info['test_acc']:.4f}")
    except:
        pass

    print(f"\nModel siap digunakan!")
    print("Gunakan: predict_sample(model, scaler, label_map, your_data)")

    # Contoh penggunaan
    # Contoh penggunaan dengan data sample
    print(f"\nContoh prediksi dengan data sample:")

    # Load data asli untuk contoh
    try:
        df = pd.read_excel('PEMELIHARAAN_PLN_2024_CLEANED.xlsx')
        numeric_cols = [c for c in df.select_dtypes(include=['float64', 'int64']).columns if c not in ['RPN', 'S', 'O', 'D']]

        # Hitung RPN untuk mendapatkan label aktual
        def calc_rpn(row):
            s = 1
            for col in ['PERGANTIAN_FCO', 'PERGANTIAN FCO', 'PERBAIKAN GROUNDING TRAFO']:
                if col in row.index and row.get(col, 0) > 0: s = 9
            if row.get('PENYEIMBANGAN BEBAN GARDU', 0) > 0: s = 6
            if row.get('PENGUKURAN', 0) > 0: s = 4

            total = row.get('TIER1_TEMUAN', 0) + row.get('TIER1 TEMUAN', 0) + row.get('TIER2_TEMUAN', 0) + row.get('TIER2 TEMUAN', 0)
            o = 1 if total == 0 else (4 if total <= 10 else (7 if total <= 20 else 9))

            tier1 = row.get('TIER1_INPEKSI', 0) + row.get('TIER1 INPEKSI', 0)
            tier2 = row.get('TIER2_INPEKSI', 0) + row.get('TIER2 INPEKSI', 0)
            d = 2 if tier1 > 0 and tier2 > 0 else (5 if tier1 > 0 or tier2 > 0 else 9)

            return s * o * d

        df['RPN'] = df.apply(calc_rpn, axis=1)
        df['LABEL'] = df['RPN'].apply(lambda x: 'Rendah' if x <= 9 else ('Sedang' if x <= 99 else 'Tinggi'))

        # Ambil sample pertama
        sample = df[numeric_cols].iloc[0].values
        actual_label = df['LABEL'].iloc[0]

        print(f"Sample data: {sample[:3]}...")  # tampilkan 3 fitur pertama
        print(f"RPN: {df['RPN'].iloc[0]}, Label aktual: {actual_label}")

        # Prediksi
        prediction = predict_sample(model, scaler, label_map, sample)
        print(f"Prediksi model: {prediction}")

        print(f"✓ Prediksi {'BENAR' if prediction == actual_label else 'SALAH'}!")

    except Exception as e:
        print(f"Error dalam contoh prediksi: {e}")
        print("Contoh penggunaan manual:")
        print("sample_data = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9]  # 9 fitur")
        print("result = predict_sample(model, scaler, label_map, sample_data)")