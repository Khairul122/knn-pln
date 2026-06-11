<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH  . '/Models/Labeling.php';
require_once APP_PATH  . '/Models/Pemeliharaan.php';
require_once APP_PATH  . '/Services/FmeaScorer.php';

class LabelingController extends Controller
{
    private Labeling     $model;
    private Pemeliharaan $pemModel;
    private const PER_PAGE = 15;

    public function __construct()
    {
        $this->requireAuth();
        $this->model     = new Labeling();
        $this->pemModel  = new Pemeliharaan();
    }

    public function index(): void
    {
        $tahun  = (int) ($_GET['tahun']  ?? 2025);
        $search = trim($_GET['search']   ?? '');
        $status = $_GET['status']        ?? '';   // labeled | unlabeled | ''
        $label  = $_GET['label']         ?? '';   // Low | Medium | High | ''
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $total   = $this->model->countList($tahun, $search ?: null, $status ?: null, $label ?: null);
        $items   = $this->model->getListWithPemeliharaan($tahun, $search ?: null, $status ?: null, $label ?: null, self::PER_PAGE, $offset);
        $summary = $this->model->getSummary($tahun);

        $years      = $this->pemModel->getAvailableYears();
        if (!in_array(2025, $years)) $years[] = 2025;
        rsort($years);

        $totalPages = (int) ceil($total / self::PER_PAGE);

        $this->view('labeling.index', compact(
            'items','total','page','offset','totalPages',
            'tahun','search','status','label','years','summary'
        ));
    }

    public function create(string $pemId): void
    {
        $pem = $this->pemModel->findById((int) $pemId);
        if (!$pem) {
            Flash::set('error', 'Data pemeliharaan tidak ditemukan.');
            $this->redirect('/labeling');
        }

        $existing = $this->model->findByPemeliharaan((int) $pemId);
        if ($existing) {
            $this->redirect('/labeling/edit/' . $existing['id']);
        }

        $this->view('labeling.form', ['pem' => $pem, 'record' => null, 'isEdit' => false]);
    }

    public function store(): void
    {
        $pemId = (int) $this->input('pemeliharaan_id', 0);
        $pem   = $this->pemModel->findById($pemId);
        if (!$pem) {
            Flash::set('error', 'Data pemeliharaan tidak ditemukan.');
            $this->redirect('/labeling');
        }

        if ($this->model->findByPemeliharaan($pemId)) {
            Flash::set('error', 'Data ini sudah memiliki label FMEA.');
            $this->redirect('/labeling');
        }

        $data = $this->collectFormData($pemId);
        $err  = $this->validateData($data);
        if ($err) { Flash::set('error', $err); $this->redirect('/labeling/create/' . $pemId); }

        $this->model->create($data);
        Flash::set('success', 'Label FMEA berhasil disimpan. RPN: ' . $data['rpn'] . ' → ' . $data['risk_label'] . ' Risk.');
        $this->redirect('/labeling?tahun=' . $pem['tahun']);
    }

    public function edit(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Label tidak ditemukan.');
            $this->redirect('/labeling');
        }

        $pem = $this->pemModel->findById($record['pemeliharaan_id']);
        $this->view('labeling.form', ['pem' => $pem, 'record' => $record, 'isEdit' => true]);
    }

    public function update(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Label tidak ditemukan.');
            $this->redirect('/labeling');
        }

        $data = $this->collectFormData($record['pemeliharaan_id']);
        $err  = $this->validateData($data);
        if ($err) { Flash::set('error', $err); $this->redirect('/labeling/edit/' . $id); }

        $this->model->update((int) $id, $data);
        Flash::set('success', 'Label FMEA diperbarui. RPN: ' . $data['rpn'] . ' → ' . $data['risk_label'] . ' Risk.');
        $this->redirect('/labeling?tahun=' . $record['tahun']);
    }

    public function delete(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Label tidak ditemukan.');
            $this->redirect('/labeling');
        }

        $this->model->delete((int) $id);
        Flash::set('success', 'Label FMEA berhasil dihapus.');
        $this->redirect('/labeling?tahun=' . $record['tahun']);
    }

    public function deleteAll(): void
    {
        $tahun = (int) $this->input('tahun', 2025);
        $this->model->deleteAll($tahun);
        Flash::set('success', 'Seluruh label FMEA tahun ' . $tahun . ' berhasil dihapus.');
        $this->redirect('/labeling?tahun=' . $tahun);
    }

    public function splitForm(): void
    {
        $tahun  = (int) ($_GET['tahun'] ?? 2025);
        $years  = $this->pemModel->getAvailableYears();
        if (!in_array(2025, $years)) $years[] = 2025;
        rsort($years);

        $stats   = $this->model->getSplitStats($tahun);
        $summary = $this->model->getSummary($tahun);
        $hasSplit = $this->model->hasSplit($tahun);

        $this->view('labeling.split', compact('tahun', 'years', 'stats', 'summary', 'hasSplit'));
    }

    public function executeSplit(): void
    {
        $tahun = (int) $this->input('tahun', 2025);
        $ratio = (float) $this->input('train_ratio', 0.8);
        $ratio = max(0.5, min(0.9, $ratio));

        $result = $this->model->applyStratifiedSplit($tahun, $ratio);
        $pct    = round($ratio * 100) . '/' . round((1 - $ratio) * 100);

        Flash::set('success', "Split berhasil (rasio {$pct}%): {$result['train']} data train, {$result['test']} data test.");
        $this->redirect('/labeling/split?tahun=' . $tahun);
    }

    public function resetSplitData(): void
    {
        $tahun = (int) $this->input('tahun', 2025);
        $this->model->resetSplit($tahun);
        Flash::set('success', 'Split data berhasil direset. Semua data kembali unassigned.');
        $this->redirect('/labeling/split?tahun=' . $tahun);
    }

    public function autoLabel(): void
    {
        $tahun     = (int) $this->input('tahun', 2025);
        $overwrite = $this->input('overwrite', '0') === '1';
        $userId    = (int) $_SESSION['user_id'];

        // Fetch all pemeliharaan for this year
        $all = $this->pemModel->getAll($tahun, null, 9999, 0);

        $inserted = 0;
        $skipped  = 0;

        foreach ($all as $pem) {
            $existing = $this->model->findByPemeliharaan((int) $pem['id']);

            if ($existing && !$overwrite) { $skipped++; continue; }

            $scored = $this->computeFmea($pem);

            if ($existing) {
                $this->model->update((int) $existing['id'], array_merge($scored, [
                    'labeled_by' => $userId,
                ]));
            } else {
                $this->model->create(array_merge($scored, [
                    'pemeliharaan_id' => (int) $pem['id'],
                    'labeled_by'      => $userId,
                ]));
            }
            $inserted++;
        }

        $msg = $inserted . ' data berhasil dilabeli secara otomatis';
        if ($skipped) $msg .= ', ' . $skipped . ' dilewati (sudah berlabel)';
        $msg .= '.';

        Flash::set('success', $msg);
        $this->redirect('/labeling?tahun=' . $tahun);
    }

    // ── Helpers ──────────────────────────────────────────

    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function collectFormData(int $pemId): array
    {
        $s   = max(1, min(10, (int) $this->input('severity',   1)));
        $o   = max(1, min(10, (int) $this->input('occurrence', 1)));
        $d   = max(1, min(10, (int) $this->input('detection',  1)));
        $rpn = $s * $o * $d;

        $label = $rpn <= 9 ? 'Rendah' : ($rpn <= 99 ? 'Sedang' : 'Tinggi');
        $override = $this->input('risk_label_override', '');
        if (in_array($override, ['Rendah','Sedang','Tinggi'])) $label = $override;

        return [
            'pemeliharaan_id' => $pemId,
            'failure_mode'    => trim($this->input('failure_mode', '')),
            'severity'        => $s,
            'occurrence'      => $o,
            'detection'       => $d,
            'rpn'             => $rpn,
            'risk_label'      => $label,
            'catatan'         => trim($this->input('catatan', '')),
            'labeled_by'      => $_SESSION['user_id'],
        ];
    }

    private function validateData(array $data): ?string
    {
        if (empty($data['failure_mode'])) return 'Mode kegagalan wajib diisi.';
        return null;
    }

    /**
     * Derive FMEA S, O, D, RPN, risk_label, failure_mode from pemeliharaan row.
     *
     * Severity  → based on dominant maintenance action taken (FCO/grounding > beban > pengukuran > none)
     * Occurrence → based on total maintenance workload (how often issues arise)
     * Detection  → inversely based on total inspections (more inspection = easier to detect)
     */
    private function computeFmea(array $p): array
    {
        return FmeaScorer::score($p);
    }
}
