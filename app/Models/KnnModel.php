<?php
require_once CORE_PATH . '/Model.php';

class KnnModel extends Model
{
    protected string $table    = 'knn_models';
    private   string $predTable = 'knn_predictions';

    public function create(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table}
                (tahun, k_value, feature_columns, distance_metric, train_count, test_count,
                 accuracy, precision_score, recall_score, f1_score, model_path, trained_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $d['tahun'], $d['k_value'], $d['feature_columns'], $d['distance_metric'],
            $d['train_count'], $d['test_count'],
            $d['accuracy'], $d['precision_score'], $d['recall_score'], $d['f1_score'],
            $d['model_path'], $d['trained_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAll(?int $tahun = null): array
    {
        if ($tahun) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tahun=? ORDER BY trained_at DESC");
            $stmt->execute([$tahun]);
        } else {
            $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY trained_at DESC");
        }
        return $stmt->fetchAll();
    }

    public function getLatest(int $tahun): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tahun=? ORDER BY trained_at DESC LIMIT 1");
        $stmt->execute([$tahun]);
        return $stmt->fetch();
    }

    public function delete(int $id): bool
    {
        $rec = $this->findById($id);
        if ($rec && !empty($rec['model_path']) && file_exists($rec['model_path'])) {
            @unlink($rec['model_path']);
        }
        $this->db->prepare("DELETE FROM {$this->predTable} WHERE model_id=?")->execute([$id]);
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id=?")->execute([$id]);
    }

    // ── Predictions ───────────────────────────────────────────────────────────

    public function clearPredictions(int $modelId): void
    {
        $this->db->prepare("DELETE FROM {$this->predTable} WHERE model_id=?")->execute([$modelId]);
    }

    public function savePredictions(int $modelId, array $rows): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO {$this->predTable}
                (model_id, pemeliharaan_id, predicted_label, actual_label, confidence, neighbors_json)
            VALUES (?,?,?,?,?,?)
        ");
        foreach ($rows as $row) {
            $stmt->execute([
                $modelId, $row['pemeliharaan_id'], $row['predicted_label'],
                $row['actual_label'] ?? null, $row['confidence'] ?? null,
                $row['neighbors_json'] ?? null,
            ]);
        }
    }

    public function getPredictions(int $modelId): array
    {
        $stmt = $this->db->prepare("
            SELECT kp.*, p.penyulang, p.bulan, p.tahun, l.split_type
            FROM {$this->predTable} kp
            JOIN pemeliharaan p ON p.id = kp.pemeliharaan_id
            LEFT JOIN labeling l ON l.pemeliharaan_id = kp.pemeliharaan_id
            WHERE kp.model_id=?
            ORDER BY l.split_type DESC, p.bulan, p.penyulang
        ");
        $stmt->execute([$modelId]);
        return $stmt->fetchAll();
    }

    public function hasPredictions(int $modelId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->predTable} WHERE model_id=?");
        $stmt->execute([$modelId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
