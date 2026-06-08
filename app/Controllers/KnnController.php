<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH  . '/Models/KnnModel.php';
require_once APP_PATH  . '/Models/Labeling.php';
require_once APP_PATH  . '/Models/Pemeliharaan.php';
require_once APP_PATH  . '/Services/KNNClassifier.php';

class KnnController extends Controller
{
    private KnnModel     $knnModel;
    private Labeling     $labelModel;
    private Pemeliharaan $pemModel;

    /** Kolom mentah data pemeliharaan yang dipakai sebagai fitur KNN (mengikuti referensi training Python). */
    public const RAW_FEATURES = [
        'tier1_inpeksi', 'tier1_temuan', 'tier2_inpeksi', 'tier2_temuan',
        'pengukuran', 'pergantian_fco', 'penyeimbangan_beban_gardu',
        'perbaikan_grounding_trafo', 'penghalang_panjat',
    ];

    public function __construct()
    {
        $this->requireAuth();
        $this->knnModel   = new KnnModel();
        $this->labelModel = new Labeling();
        $this->pemModel   = new Pemeliharaan();
    }

    // GET /knn/train
    public function trainForm(): void
    {
        $tahun      = (int)($_GET['tahun'] ?? 2025);
        $years      = $this->getYears();
        $splitStats = $this->labelModel->getSplitStats($tahun);
        $hasSplit   = $this->labelModel->hasSplit($tahun);
        $history    = $this->knnModel->getAll($tahun);

        $trainCount = array_sum(array_column($splitStats, 'train'));
        $testCount  = array_sum(array_column($splitStats, 'test'));

        $this->view('knn.train', compact(
            'tahun', 'years', 'splitStats', 'hasSplit', 'history', 'trainCount', 'testCount'
        ));
    }

    // POST /knn/train
    public function train(): void
    {
        $tahun  = (int) $this->input('tahun', 2025);
        $k      = max(1, min(20, (int) $this->input('k_value', 5)));
        $metric = in_array($this->input('distance_metric'), ['euclidean', 'manhattan'])
                  ? $this->input('distance_metric') : 'euclidean';

        $rawFeats   = $_POST['features'] ?? self::RAW_FEATURES;
        $feats      = array_values(array_intersect((array)$rawFeats, self::RAW_FEATURES));
        if (empty($feats)) $feats = self::RAW_FEATURES;

        $trainData = $this->labelModel->getSplitData($tahun, 'train');
        if (empty($trainData)) {
            Flash::set('error', 'Tidak ada data train. Lakukan split data terlebih dahulu di halaman Labeling.');
            $this->redirect('/knn/train?tahun=' . $tahun);
        }
        if (count($trainData) < $k) {
            Flash::set('error', 'Nilai K (' . $k . ') melebihi jumlah data train (' . count($trainData) . '). Kurangi nilai K.');
            $this->redirect('/knn/train?tahun=' . $tahun);
        }

        $clf          = new KNNClassifier();
        $clf->k       = $k;
        $clf->metric  = $metric;
        $clf->features = $feats;
        $clf->fit($trainData);

        $testData = $this->labelModel->getSplitData($tahun, 'test');
        $eval     = !empty($testData) ? $clf->evaluate($testData) : null;

        $dir       = ROOT_PATH . '/storage/models';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $modelPath = $dir . '/knn_' . $tahun . '_' . time() . '.bin';
        $clf->save($modelPath);

        $modelId = $this->knnModel->create([
            'tahun'            => $tahun,
            'k_value'          => $k,
            'feature_columns'  => implode(',', $feats),
            'distance_metric'  => $metric,
            'train_count'      => count($trainData),
            'test_count'       => count($testData),
            'accuracy'         => $eval ? $eval['accuracy']        : null,
            'precision_score'  => $eval ? $eval['macro_precision'] : null,
            'recall_score'     => $eval ? $eval['macro_recall']    : null,
            'f1_score'         => $eval ? $eval['macro_f1']        : null,
            'model_path'       => $modelPath,
            'trained_by'       => (int) $_SESSION['user_id'],
        ]);

        $accMsg = $eval
            ? ' | Akurasi: ' . round($eval['accuracy'] * 100, 1) . '%'
            : ' (tidak ada data test)';
        Flash::set('success', "Model KNN berhasil dilatih — K={$k}, {$metric}{$accMsg}.");
        $this->redirect('/knn/evaluate?model_id=' . $modelId . '&tahun=' . $tahun);
    }

    // GET /knn/evaluate
    public function evaluate(): void
    {
        $tahun   = (int)($_GET['tahun'] ?? 2025);
        $modelId = (int)($_GET['model_id'] ?? 0);
        $years   = $this->getYears();
        $history = $this->knnModel->getAll($tahun);

        $selected = $modelId
            ? $this->knnModel->findById($modelId)
            : (!empty($history) ? $history[0] : null);

        $evalResult  = null;
        $kAccuracies = [];

        if ($selected && !empty($selected['model_path']) && file_exists($selected['model_path'])) {
            $clf      = KNNClassifier::load($selected['model_path']);
            $testData = $this->labelModel->getSplitData($tahun, 'test');

            if ($clf && !empty($testData)) {
                $evalResult = $clf->evaluate($testData);

                $maxK = min(15, $clf->getTrainingCount());
                for ($kv = 1; $kv <= $maxK; $kv++) {
                    $tmp    = clone $clf;
                    $tmp->k = $kv;
                    $ev     = $tmp->evaluate($testData);
                    $kAccuracies[$kv] = round($ev['accuracy'] * 100, 2);
                }
            }
        }

        $this->view('knn.evaluate', compact(
            'tahun', 'years', 'history', 'selected', 'evalResult', 'kAccuracies'
        ));
    }

    // GET /knn/predict
    public function predictForm(): void
    {
        $tahun   = (int)($_GET['tahun'] ?? 2025);
        $modelId = (int)($_GET['model_id'] ?? 0);
        $years   = $this->getYears();
        $history = $this->knnModel->getAll($tahun);

        $selected = $modelId
            ? $this->knnModel->findById($modelId)
            : (!empty($history) ? $history[0] : null);

        $batchResult = null;
        $hasBatch    = $selected ? $this->knnModel->hasPredictions((int)$selected['id']) : false;
        if ($hasBatch && $selected) {
            $batchResult = $this->knnModel->getPredictions((int)$selected['id']);
        }

        $manualInput  = array_fill_keys(self::RAW_FEATURES, 0);
        $manualResult = null;

        $this->view('knn.predict', compact(
            'tahun', 'years', 'history', 'selected', 'batchResult', 'hasBatch',
            'manualResult', 'manualInput'
        ));
    }

    // POST /knn/predict  (manual)
    public function predictManual(): void
    {
        $modelId  = (int) $this->input('model_id', 0);
        $tahun    = (int) $this->input('tahun', 2025);
        $years    = $this->getYears();
        $history  = $this->knnModel->getAll($tahun);
        $selected = $this->knnModel->findById($modelId) ?: (!empty($history) ? $history[0] : null);

        $manualInput = [];
        foreach (self::RAW_FEATURES as $f) {
            $manualInput[$f] = max(0, (int) $this->input($f, 0));
        }

        $manualResult = null;
        if ($selected && !empty($selected['model_path']) && file_exists($selected['model_path'])) {
            $clf          = KNNClassifier::load($selected['model_path']);
            $manualResult = $clf->predict($manualInput);
            $manualResult['input'] = $manualInput;
        } else {
            Flash::set('error', 'Model tidak ditemukan. Latih model terlebih dahulu.');
        }

        $hasBatch    = $selected ? $this->knnModel->hasPredictions((int)($selected['id'] ?? 0)) : false;
        $batchResult = null;
        if ($hasBatch && $selected) {
            $batchResult = $this->knnModel->getPredictions((int)$selected['id']);
        }

        $this->view('knn.predict', compact(
            'tahun', 'years', 'history', 'selected', 'manualResult',
            'hasBatch', 'batchResult', 'manualInput'
        ));
    }

    // POST /knn/predict/batch
    public function predictBatch(): void
    {
        $modelId  = (int) $this->input('model_id', 0);
        $selected = $this->knnModel->findById($modelId);

        if (!$selected || empty($selected['model_path']) || !file_exists($selected['model_path'])) {
            Flash::set('error', 'Model tidak ditemukan. Latih model terlebih dahulu.');
            $this->redirect('/knn/predict?tahun=' . $this->input('tahun', 2025));
        }

        $clf        = KNNClassifier::load($selected['model_path']);
        $tahun      = (int) $selected['tahun'];
        $allLabeled = $this->labelModel->getAllLabeled($tahun);

        $toSave = [];
        foreach ($allLabeled as $row) {
            $pred     = $clf->predict($row);
            $toSave[] = [
                'pemeliharaan_id' => (int) $row['pemeliharaan_id'],
                'predicted_label' => $pred['predicted_label'],
                'actual_label'    => $row['risk_label'],
                'confidence'      => $pred['confidence'],
                'neighbors_json'  => json_encode($pred['neighbors']),
            ];
        }

        $this->knnModel->clearPredictions($modelId);
        $this->knnModel->savePredictions($modelId, $toSave);

        Flash::set('success', count($toSave) . ' prediksi berhasil dihitung.');
        $this->redirect('/knn/predict?model_id=' . $modelId . '&tahun=' . $tahun);
    }

    // POST /knn/predict/clear
    public function clearPredictionsBatch(): void
    {
        $modelId = (int) $this->input('model_id', 0);
        $tahun   = (int) $this->input('tahun', 2025);
        if ($modelId) {
            $this->knnModel->clearPredictions($modelId);
            Flash::set('success', 'Seluruh hasil prediksi batch berhasil dihapus.');
        }
        $this->redirect('/knn/predict?model_id=' . $modelId . '&tahun=' . $tahun);
    }

    // POST /knn/delete/{id}
    public function deleteModel(string $id): void
    {
        $rec = $this->knnModel->findById((int) $id);
        if ($rec) {
            $this->knnModel->delete((int) $id);
            Flash::set('success', 'Model KNN berhasil dihapus.');
        }
        $this->redirect('/knn/train?tahun=' . ($rec['tahun'] ?? 2025));
    }

    // ── private ───────────────────────────────────────────────────────────────

    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function getYears(): array
    {
        $years = $this->pemModel->getAvailableYears();
        if (!in_array(2025, $years)) $years[] = 2025;
        rsort($years);
        return $years;
    }
}
