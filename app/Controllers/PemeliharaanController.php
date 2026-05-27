<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH  . '/Models/Pemeliharaan.php';

class PemeliharaanController extends Controller
{
    private Pemeliharaan $model;
    private const PER_PAGE  = 15;

    public function __construct()
    {
        $this->requireAuth();
        $this->model = new Pemeliharaan();
    }

    public function index(): void
    {
        $tahun  = (int) ($_GET['tahun']  ?? 2025);
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $total  = $this->model->countAll($tahun, $search ?: null);
        $data   = $this->model->getAll($tahun, $search ?: null, self::PER_PAGE, $offset);
        $years  = $this->model->getAvailableYears();
        if (!in_array(2025, $years)) $years[] = 2025;
        rsort($years);

        $this->view('pemeliharaan.index', [
            'items'      => $data,
            'total'      => $total,
            'page'       => $page,
            'offset'     => $offset,
            'perPage'    => self::PER_PAGE,
            'totalPages' => (int) ceil($total / self::PER_PAGE),
            'tahun'      => $tahun,
            'search'     => $search,
            'years'      => $years,
        ]);
    }

    public function create(): void
    {
        $this->view('pemeliharaan.form', [
            'record' => null,
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $data = $this->collectFormData();
        $err  = $this->validateData($data);

        if ($err) {
            Flash::set('error', $err);
            $this->redirect('/pemeliharaan/create');
        }

        $this->model->create($data);
        Flash::set('success', 'Data pemeliharaan berhasil ditambahkan.');
        $this->redirect('/pemeliharaan');
    }

    public function edit(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Data tidak ditemukan.');
            $this->redirect('/pemeliharaan');
        }

        $this->view('pemeliharaan.form', [
            'record' => $record,
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Data tidak ditemukan.');
            $this->redirect('/pemeliharaan');
        }

        $data = $this->collectFormData();
        $err  = $this->validateData($data);

        if ($err) {
            Flash::set('error', $err);
            $this->redirect('/pemeliharaan/edit/' . $id);
        }

        $this->model->update((int) $id, $data);
        Flash::set('success', 'Data pemeliharaan berhasil diperbarui.');
        $this->redirect('/pemeliharaan');
    }

    public function delete(string $id): void
    {
        $record = $this->model->findById((int) $id);
        if (!$record) {
            Flash::set('error', 'Data tidak ditemukan.');
            $this->redirect('/pemeliharaan');
        }

        $this->model->delete((int) $id);
        Flash::set('success', 'Data berhasil dihapus.');
        $this->redirect('/pemeliharaan');
    }

    // ── Helpers ─────────────────────────────────────────

    private function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }

    private function collectFormData(): array
    {
        return [
            'penyulang'                 => $this->input('penyulang', ''),
            'bulan'                     => $this->input('bulan', 1),
            'tahun'                     => $this->input('tahun', 2025),
            'tier1_inpeksi'             => $this->input('tier1_inpeksi', 0),
            'tier1_temuan'              => $this->input('tier1_temuan', 0),
            'tier2_inpeksi'             => $this->input('tier2_inpeksi', 0),
            'tier2_temuan'              => $this->input('tier2_temuan', 0),
            'pengukuran'                => $this->input('pengukuran', 0),
            'pergantian_fco'            => $this->input('pergantian_fco', 0),
            'penyeimbangan_beban_gardu' => $this->input('penyeimbangan_beban_gardu', 0),
            'perbaikan_grounding_trafo' => $this->input('perbaikan_grounding_trafo', 0),
            'penghalang_panjat'         => $this->input('penghalang_panjat', 0),
        ];
    }

    private function validateData(array $data): ?string
    {
        if (empty(trim($data['penyulang'])))       return 'Nama penyulang wajib diisi.';
        if ($data['bulan'] < 1 || $data['bulan'] > 12) return 'Bulan tidak valid (1–12).';
        if ($data['tahun'] < 2000 || $data['tahun'] > 2100) return 'Tahun tidak valid.';
        return null;
    }


}
