import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns

# Load final labeled data
file_path = 'PEMELIHARAAN_PLN_2024_FINAL_LABELED.xlsx'
df = pd.read_excel(file_path)

# Set visual style
sns.set_theme(style="whitegrid")

# 1. Bar Chart: Distribusi Label Risiko
plt.figure(figsize=(8, 6))
ax = sns.countplot(data=df, x='LABEL_RISIKO', order=['Rendah', 'Sedang', 'Tinggi'], palette={'Rendah': '#a1c9f4', 'Sedang': '#ffb482', 'Tinggi': '#ff9f9b'})

# Add count labels on top of bars
for p in ax.patches:
    ax.annotate(f'{int(p.get_height())}', (p.get_x() + p.get_width() / 2., p.get_height()),
                ha = 'center', va = 'center', xytext = (0, 9), textcoords = 'offset points', fontweight='bold')

plt.title('Jumlah Penyulang Berdasarkan Tingkat Risiko', fontsize=14, fontweight='bold')
plt.xlabel('Kategori Risiko', fontsize=12)
plt.ylabel('Jumlah Data', fontsize=12)
plt.ylim(0, df['LABEL_RISIKO'].value_counts().max() + 50)
plt.savefig('bar_distribusi_risiko.png')
plt.show()

# 2. Pie Chart: Persentase Risiko
plt.figure(figsize=(8, 8))
label_counts = df['LABEL_RISIKO'].value_counts().reindex(['Rendah', 'Sedang', 'Tinggi'])
colors = ['#a1c9f4', '#ffb482', '#ff9f9b']

plt.pie(label_counts, labels=label_counts.index, autopct='%1.1f%%', startangle=140, colors=colors, 
        explode=(0.05, 0.05, 0.1), shadow=True, textprops={'fontweight': 'bold'})

plt.title('Persentase Tingkat Risiko Gangguan Jaringan', fontsize=14, fontweight='bold')
plt.savefig('pie_distribusi_risiko.png')
plt.show()

print("Grafik berhasil dibuat dan ditampilkan.")