<?php
require_once CORE_PATH . '/Model.php';

class Pemeliharaan extends Model
{
    protected string $table = 'pemeliharaan';

    private const NUMERIC_COLS = [
        'tier1_inpeksi', 'tier1_temuan', 'tier2_inpeksi', 'tier2_temuan',
        'pengukuran', 'pergantian_fco', 'penyeimbangan_beban_gardu',
        'perbaikan_grounding_trafo', 'penghalang_panjat',
    ];

    public function getAll(int $tahun, ?string $search, int $limit, int $offset): array
    {
        $params = [$tahun];
        $where  = 'tahun = ?';

        if ($search !== null && $search !== '') {
            $where   .= ' AND penyulang LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY bulan ASC, penyulang ASC LIMIT ? OFFSET ?"
        );
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(int $tahun, ?string $search): int
    {
        $params = [$tahun];
        $where  = 'tahun = ?';

        if ($search !== null && $search !== '') {
            $where   .= ' AND penyulang LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $data = $this->sanitize($data);
        $cols = implode(', ', array_keys($data));
        $phs  = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$phs})");
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->sanitize($data);
        $set  = implode(', ', array_map(fn($c) => "{$c} = ?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");
        $vals = array_values($data);
        $vals[] = $id;
        return $stmt->execute($vals);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function truncateYear(int $tahun): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE tahun = ?");
        $stmt->execute([$tahun]);
    }

    public function bulkInsert(array $rows): void
    {
        if (empty($rows)) return;

        $cols = implode(', ', array_keys($rows[0]));
        $phs  = implode(', ', array_fill(0, count($rows[0]), '?'));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$phs})");

        $this->db->beginTransaction();
        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
        $this->db->commit();
    }

    public function getAvailableYears(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT tahun FROM {$this->table} ORDER BY tahun DESC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function sanitize(array $data): array
    {
        $out = [];
        if (isset($data['penyulang'])) $out['penyulang'] = trim($data['penyulang']);
        if (isset($data['bulan']))     $out['bulan']     = (int) $data['bulan'];
        if (isset($data['tahun']))     $out['tahun']     = (int) $data['tahun'];
        foreach (self::NUMERIC_COLS as $col) {
            if (isset($data[$col])) $out[$col] = max(0, (int) $data[$col]);
        }
        return $out;
    }
}
