import pandas as pd

# Load the cleaned data to apply the finalized severity criteria
file_path = 'DATA_PLN.xlsx'
df = pd.read_excel(file_path)

# Mapping the new severity criteria from the second image to existing columns
# S=9: Komponen utama gardu, dampak signifikan -> Pergantian FCO atau Grounding (komponen vital)
# S=6: Gangguan distribusi beban, area terbatas -> Penyeimbangan Beban Gardu
# S=4: Deteksi dini, belum ada kerusakan nyata -> Pengukuran
# S=2: Perlindungan ringan, dampak minimal -> Penghalang Panjat
# S=1: Tidak ada indikasi gangguan -> Default

def calculate_severity_final(row):
    # Using the "highest value" principle for overlapping maintenance
    if row.get('PERGANTIAN_FCO', 0) > 0 or row.get('PERBAIKAN GROUNDING TRAFO', 0) > 0:
        return 9
    elif row.get('PENYEIMBANGAN BEBAN GARDU', 0) > 0:
        return 6
    elif row.get('PENGUKURAN', 0) > 0:
        return 4
    elif row.get('PENGHALANG_PANJAT', 0) > 0:
        return 2
    else:
        return 1

# Re-calculate O and D as per Bab 3 (Occurrence remains based on findings, Detection on inspections)
def calculate_occurrence(row):
    total_temuan = row['TIER1_TEMUAN'] + row['TIER2_TEMUAN']
    if total_temuan == 0: return 1
    elif 1 <= total_temuan <= 10: return 4
    elif 11 <= total_temuan <= 20: return 7
    else: return 9

def calculate_detection(row):
    if row['TIER1_INPEKSI'] > 0 and row['TIER2_INPEKSI'] > 0: return 2
    elif row['TIER1_INPEKSI'] > 0 or row['TIER2_INPEKSI'] > 0: return 5
    else: return 9

# Apply calculations
df['S'] = df.apply(calculate_severity_final, axis=1)
df['O'] = df.apply(calculate_occurrence, axis=1)
df['D'] = df.apply(calculate_detection, axis=1)
df['RPN'] = df['S'] * df['O'] * df['D']

def get_label(rpn):
    if rpn <= 9: return 'Rendah'
    elif 10 <= rpn <= 99: return 'Sedang'
    else: return 'Tinggi'

df['LABEL_RISIKO'] = df['RPN'].apply(get_label)

# Check distribution
print("Distribusi Label Risiko (Final Severity Criteria):")
print(df['LABEL_RISIKO'].value_counts())

# Save final version
final_output = 'PEMELIHARAAN_PLN_2024_LABELED.xlsx'
df.to_excel(final_output, index=False)