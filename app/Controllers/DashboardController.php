<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH  . '/Models/Laporan.php';
require_once APP_PATH  . '/Models/KnnModel.php';
require_once APP_PATH  . '/Models/Labeling.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            $this->redirect('/login');
        }

        $laporan  = new Laporan();
        $knnModel = new KnnModel();
        $labeling = new Labeling();

        $years = $laporan->getAvailableYears();
        if (!in_array((int)date('Y'), array_map('intval', $years))) {
            $years[] = (int)date('Y');
        }
        rsort($years);

        $tahun = (int)($_GET['tahun'] ?? $years[0]);

        $summary        = $laporan->getSummary($tahun);
        $monthly        = $laporan->getMonthlyDistribution($tahun);
        $byPenyulang    = $laporan->getPenyulangRisk($tahun);
        $topHighRisk    = $laporan->getTopHighRisk($tahun, 8);
        $activity       = $laporan->getRecentActivity($tahun);
        $penyulangCount = $laporan->getPenyulangCount($tahun);
        $latestModel    = $knnModel->getLatest($tahun);
        $totalModels    = count($knnModel->getAll($tahun));
        $hasSplit       = $labeling->hasSplit($tahun);
        $hasPredictions = $latestModel && $knnModel->hasPredictions((int)$latestModel['id']);

        $this->view('dashboard.index', [
            'userName'       => $_SESSION['user_name']  ?? 'User',
            'userEmail'      => $_SESSION['user_email'] ?? '',
            'userRole'       => $_SESSION['user_role']  ?? 'viewer',
            'years'          => $years,
            'tahun'          => $tahun,
            'summary'        => $summary,
            'monthly'        => $monthly,
            'byPenyulang'    => $byPenyulang,
            'topHighRisk'    => $topHighRisk,
            'activity'       => $activity,
            'penyulangCount' => $penyulangCount,
            'latestModel'    => $latestModel,
            'totalModels'    => $totalModels,
            'hasSplit'       => $hasSplit,
            'hasPredictions' => $hasPredictions,
        ]);
    }
}
