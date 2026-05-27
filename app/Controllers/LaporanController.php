<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH  . '/Models/Laporan.php';
require_once APP_PATH  . '/Models/KnnModel.php';

class LaporanController extends Controller
{
    private Laporan  $model;
    private KnnModel $knnModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->model    = new Laporan();
        $this->knnModel = new KnnModel();
    }

    public function index(): void
    {
        $tahun   = (int)($_GET['tahun']    ?? 2024);
        $modelId = (int)($_GET['model_id'] ?? 0);

        $years = $this->model->getAvailableYears();
        if (!in_array(2024, $years)) $years[] = 2024;
        rsort($years);

        // KNN models for this year (for selector)
        $knnHistory = $this->knnModel->getAll($tahun);
        $latestKnn  = !empty($knnHistory) ? $knnHistory[0] : null;

        // Use selected model or latest
        $selectedModel = $modelId
            ? $this->knnModel->findById($modelId)
            : $latestKnn;
        $activeModelId = $selectedModel ? (int)$selectedModel['id'] : null;

        // Data aggregates
        $summary      = $this->model->getSummary($tahun);
        $monthly      = $this->model->getMonthlyDistribution($tahun);
        $byPenyulang  = $this->model->getPenyulangRisk($tahun);
        $highRiskList = $this->model->getHighRiskList($tahun);
        $detail       = $this->model->getDetailFull($tahun, $activeModelId);

        $disagreements = $activeModelId
            ? $this->model->getKnnDisagreements($tahun, $activeModelId)
            : [];

        $this->view('laporan.index', compact(
            'tahun', 'years',
            'knnHistory', 'selectedModel', 'activeModelId',
            'summary', 'monthly', 'byPenyulang',
            'highRiskList', 'detail', 'disagreements'
        ));
    }

    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
