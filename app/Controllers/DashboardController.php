<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!isset($_SESSION['user_id'])) {
            Flash::set('error', 'Silakan login terlebih dahulu.');
            $this->redirect('/login');
        }

        $this->view('dashboard.index', [
            'userName'  => $_SESSION['user_name']  ?? 'User',
            'userEmail' => $_SESSION['user_email'] ?? '',
            'userRole'  => $_SESSION['user_role']  ?? 'viewer',
        ]);
    }
}
