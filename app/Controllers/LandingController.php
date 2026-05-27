<?php
require_once CORE_PATH . '/Controller.php';
require_once APP_PATH  . '/Models/Laporan.php';
require_once APP_PATH  . '/Models/KnnModel.php';

class LandingController extends Controller
{
    public function index(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $laporan  = new Laporan();
        $knnModel = new KnnModel();

        // Latest year with data, fallback to current year
        $years = $laporan->getAvailableYears();
        $tahun = !empty($years) ? (int)$years[0] : (int)date('Y');

        // ── Evaluasi stats ──────────────────────────────────────────────────
        $summary        = $laporan->getSummary($tahun);
        $monthly        = $laporan->getMonthlyDistribution($tahun);
        $penyulangCount = $laporan->getPenyulangCount($tahun);

        // ── Prediksi / KNN stats ────────────────────────────────────────────
        $latestModel = $knnModel->getLatest($tahun);
        $totalModels = count($knnModel->getAll());

        $this->view('landing.index', compact(
            'tahun', 'summary', 'monthly', 'penyulangCount',
            'latestModel', 'totalModels'
        ));
    }
}
