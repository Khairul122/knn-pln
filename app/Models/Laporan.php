<?php
require_once CORE_PATH . '/Model.php';

class Laporan extends Model
{
    public function getSummary(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(p.id)                          AS total_pemeliharaan,
                COUNT(l.id)                          AS total_labeled,
                SUM(l.risk_label = 'Rendah')         AS rendah,
                SUM(l.risk_label = 'Sedang')         AS sedang,
                SUM(l.risk_label = 'Tinggi')         AS tinggi,
                ROUND(AVG(l.rpn), 1)                 AS avg_rpn,
                MAX(l.rpn)                           AS max_rpn,
                MIN(l.rpn)                           AS min_rpn,
                ROUND(AVG(l.severity),   1)          AS avg_severity,
                ROUND(AVG(l.occurrence), 1)          AS avg_occurrence,
                ROUND(AVG(l.detection),  1)          AS avg_detection
            FROM pemeliharaan p
            LEFT JOIN labeling l ON l.pemeliharaan_id = p.id
            WHERE p.tahun = ?
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetch() ?: [];
    }

    public function getMonthlyDistribution(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT p.bulan,
                   SUM(l.risk_label = 'Rendah') AS rendah,
                   SUM(l.risk_label = 'Sedang') AS sedang,
                   SUM(l.risk_label = 'Tinggi') AS tinggi,
                   ROUND(AVG(l.rpn), 1)         AS avg_rpn,
                   COUNT(l.id)                  AS total
            FROM pemeliharaan p
            LEFT JOIN labeling l ON l.pemeliharaan_id = p.id
            WHERE p.tahun = ?
            GROUP BY p.bulan
            ORDER BY p.bulan ASC
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetchAll();
    }

    public function getPenyulangRisk(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.penyulang,
                COUNT(l.id)                          AS total_bulan,
                SUM(l.risk_label = 'Rendah')         AS rendah,
                SUM(l.risk_label = 'Sedang')         AS sedang,
                SUM(l.risk_label = 'Tinggi')         AS tinggi,
                MAX(l.rpn)                           AS max_rpn,
                ROUND(AVG(l.rpn), 1)                 AS avg_rpn,
                MAX(l.risk_label = 'Tinggi')         AS has_tinggi,
                -- Dominant label (most frequent)
                (SELECT l2.risk_label
                 FROM labeling l2
                 JOIN pemeliharaan p2 ON p2.id = l2.pemeliharaan_id
                 WHERE p2.penyulang = p.penyulang AND p2.tahun = ?
                 GROUP BY l2.risk_label
                 ORDER BY COUNT(*) DESC LIMIT 1)      AS dominant_risk
            FROM pemeliharaan p
            JOIN labeling l ON l.pemeliharaan_id = p.id
            WHERE p.tahun = ?
            GROUP BY p.penyulang
            ORDER BY tinggi DESC, max_rpn DESC, sedang DESC
        ");
        $stmt->execute([$tahun, $tahun]);
        return $stmt->fetchAll();
    }

    public function getHighRiskList(int $tahun): array
    {
        $stmt = $this->db->prepare("
            SELECT p.penyulang, p.bulan, l.rpn,
                   l.severity, l.occurrence, l.detection,
                   l.failure_mode, l.risk_label
            FROM labeling l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            WHERE p.tahun = ? AND l.risk_label = 'Tinggi'
            ORDER BY l.rpn DESC
        ");
        $stmt->execute([$tahun]);
        return $stmt->fetchAll();
    }

    public function getDetailFull(int $tahun, ?int $modelId): array
    {
        if ($modelId) {
            $stmt = $this->db->prepare("
                SELECT p.penyulang, p.bulan, p.tahun,
                       l.id AS label_id, l.severity, l.occurrence, l.detection,
                       l.rpn, l.risk_label, l.failure_mode, l.split_type,
                       kp.predicted_label, kp.confidence
                FROM labeling l
                JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
                LEFT JOIN knn_predictions kp
                       ON kp.pemeliharaan_id = p.id AND kp.model_id = ?
                WHERE p.tahun = ?
                ORDER BY l.rpn DESC, p.bulan ASC
            ");
            $stmt->execute([$modelId, $tahun]);
        } else {
            $stmt = $this->db->prepare("
                SELECT p.penyulang, p.bulan, p.tahun,
                       l.id AS label_id, l.severity, l.occurrence, l.detection,
                       l.rpn, l.risk_label, l.failure_mode, l.split_type,
                       NULL AS predicted_label, NULL AS confidence
                FROM labeling l
                JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
                WHERE p.tahun = ?
                ORDER BY l.rpn DESC, p.bulan ASC
            ");
            $stmt->execute([$tahun]);
        }
        return $stmt->fetchAll();
    }

    public function getKnnDisagreements(int $tahun, int $modelId): array
    {
        $stmt = $this->db->prepare("
            SELECT p.penyulang, p.bulan,
                   l.risk_label AS fmea_label, l.rpn,
                   kp.predicted_label AS knn_label, kp.confidence
            FROM labeling l
            JOIN pemeliharaan p ON p.id = l.pemeliharaan_id
            JOIN knn_predictions kp ON kp.pemeliharaan_id = p.id AND kp.model_id = ?
            WHERE p.tahun = ? AND l.risk_label <> kp.predicted_label
            ORDER BY l.rpn DESC
        ");
        $stmt->execute([$modelId, $tahun]);
        return $stmt->fetchAll();
    }

    public function getAvailableYears(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT tahun FROM pemeliharaan ORDER BY tahun DESC");
        return array_column($stmt->fetchAll(), 'tahun');
    }

    public function getPenyulangCount(int $tahun): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT penyulang) FROM pemeliharaan WHERE tahun = ?");
        $stmt->execute([$tahun]);
        return (int) $stmt->fetchColumn();
    }
}
