<?php
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Flash.php';
require_once APP_PATH . '/Models/User.php';

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login');
    }

    public function login(): void
    {
        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');

        if (empty($email) || empty($password)) {
            Flash::set('error', 'Email dan password wajib diisi.');
            $this->redirect('/login');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('error', 'Format email tidak valid.');
            $this->redirect('/login');
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            Flash::set('error', 'Email atau password salah.');
            $this->redirect('/login');
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        $this->userModel->updateLastLogin($user['id']);

        Flash::set('success', 'Login berhasil! Selamat datang, ' . $user['name'] . '.');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        $flash = ['type' => 'success', 'message' => 'Anda berhasil keluar dari aplikasi.'];
        session_destroy();
        session_start();
        $_SESSION['flash'] = $flash;
        $this->redirect('/login');
    }
}
