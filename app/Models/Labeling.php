<?php
require_once CORE_PATH . '/Model.php';

class Labeling extends Model
{
    protected string $table = 'labeling';

    public function getListWithPemeliharaan(int $tahun, ?string $search, ?string $status, ?string $label, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildWhere($tahun, $search, $status, $label);
        $stmt = $this->db->prepare("
            SELECT p.id AS pemeliharaan_id, p.penyulang, p.bulan, p.tahun,
                   l.id AS label_id, l.severity, l.occurrence, l.detection,
                   l.rpn, l.risk_label, l.split_type, l.failure_mode, l.catatan, l.updated_at AS labeled_at
            FROM pemeliharaan p
            LEFT JOIN {$this->table} l ON l.pemeliharaan_id = p.id
            WHERE {$where}
            ORDER BY p.bulan ASC, p.penyulang ASC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countList(int $tahun, ?string $search, ?string $status, ?string $label): int
    {
        [$where, $params] = $this->buildWhere($tahun, $search, $status, $label);
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM pemeliharaan p
            LEFT JOIN {$this->table} l ON l.pemeliharaan_id = p.id
            WHERE {$where}
        ");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findByPemeliharaan(int $pemId): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE pemeliharaan_id = ? LIMIT 1");
        $stmt->execute([$pemId]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT l.*, p.penyulang, p.bulan, p.tahun
            FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE l.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (pemeliharaan_id, failure_mode, severity, occurrence, detection, rpn, risk_label, catatan, labeled_by)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $data['pemeliharaan_id'], $data['failure_mode'],
            $data['severity'], $data['occurrence'], $data['detection'],
            $data['rpn'], $data['risk_label'], $data['catatan'] ?: null,
            $data['labeled_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET failure_mode=?, severity=?, occurrence=?, detection=?, rpn=?, risk_label=?, catatan=?
            WHERE id=?
        ");
        return $stmt->execute([
            $data['failure_mode'], $data['severity'], $data['occurrence'],
            $data['detection'], $data['rpn'], $data['risk_label'],
            $data['catatan'] ?: null, $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function getSummary(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(p.id)                                              AS total,
                COUNT(l.id)                                              AS labeled,
                SUM(l.risk_label = 'Rendah')                            AS low,
                SUM(l.risk_label = 'Sedang')                            AS medium,
                SUM(l.risk_label = 'Tinggi')                            AS high,
                ROUND(AVG(l.rpn), 1)                                    AS avg_rpn,
                MAX(l.rpn)                                               AS max_rpn
            FROM pemeliharaan p
            LEFT JOIN {$this->table} l ON l.pemeliharaan_id = p.id
            WHERE p.tahun = ?
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetch() ?: [];
    }

    public function getSplitStats(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT
                l.risk_label,
                COUNT(*)                          AS total,
                SUM(l.split_type = 'train')       AS train,
                SUM(l.split_type = 'test')        AS test,
                SUM(l.split_type IS NULL)         AS unassigned
            FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ?
            GROUP BY l.risk_label
            ORDER BY FIELD(l.risk_label,'Rendah','Sedang','Tinggi')
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetchAll();
    }

    public function hasSplit(int $tahun): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ? AND l.split_type IS NOT NULL
        ");
        $stmt->execute([$tahun]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function applyStratifiedSplit(int $tahun, float $trainRatio): array
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.risk_label
            FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ?
            ORDER BY l.risk_label, l.id
        ");
        $stmt->execute([$tahun]);
        $rows = $stmt->fetchAll();

        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['risk_label']][] = (int)$row['id'];
        }

        $trainIds = [];
        $testIds  = [];
        foreach ($groups as $ids) {
            shuffle($ids);
            $n = (int) round(count($ids) * $trainRatio);
            $trainIds = array_merge($trainIds, array_slice($ids, 0, $n));
            $testIds  = array_merge($testIds,  array_slice($ids, $n));
        }

        if ($trainIds) {
            $ph = implode(',', array_fill(0, count($trainIds), '?'));
            $this->db->prepare("UPDATE {$this->table} SET split_type='train' WHERE id IN ({$ph})")->execute($trainIds);
        }
        if ($testIds) {
            $ph = implode(',', array_fill(0, count($testIds), '?'));
            $this->db->prepare("UPDATE {$this->table} SET split_type='test' WHERE id IN ({$ph})")->execute($testIds);
        }

        return ['train' => count($trainIds), 'test' => count($testIds)];
    }

    public function resetSplit(int $tahun): void
    {
        $this->db->prepare("
            UPDATE {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            SET l.split_type = NULL
            WHERE p.tahun = ?
        ")->execute([$tahun]);
    }

    public function getSplitData(int $tahun, string $splitType): array
    {
        $stmt = $this->db->prepare("
            SELECT l.id AS label_id, l.pemeliharaan_id,
                   l.severity, l.occurrence, l.detection, l.rpn,
                   l.risk_label, p.penyulang, p.bulan
            FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ? AND l.split_type = ?
            ORDER BY l.id
        ");
        $stmt->execute([$tahun, $splitType]);
        return $stmt->fetchAll();
    }

    public function getAllLabeled(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT l.id AS label_id, l.pemeliharaan_id,
                   l.severity, l.occurrence, l.detection, l.rpn,
                   l.risk_label, l.split_type, p.penyulang, p.bulan
            FROM {$this->table} l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ?
            ORDER BY p.bulan, p.penyulang
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetchAll();
    }

    private function buildWhere(int $tahun, ?string $search, ?string $status, ?string $label): array
    {
        $where  = 'p.tahun = ?';
        $params = [$tahun];

        if ($search !== null && $search !== '') {
            $where   .= ' AND p.penyulang LIKE ?';
            $params[] = '%' . $search . '%';
        }
        if ($status === 'labeled') {
            $where .= ' AND l.id IS NOT NULL';
        } elseif ($status === 'unlabeled') {
            $where .= ' AND l.id IS NULL';
        }
        if ($label && in_array($label, ['Rendah','Sedang','Tinggi'])) {
            $where   .= ' AND l.risk_label = ?';
            $params[] = $label;
        }
        return [$where, $params];
    }
}
