<script>
function openSidebar() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
    document.body.style.overflow = '';
}
function confirmLogout() {
    showDialog({
        title:       'Keluar dari Aplikasi',
        message:     'Anda akan keluar dari sesi ini. Pastikan semua pekerjaan sudah tersimpan.',
        type:        'warning',
        confirmText: 'Ya, Keluar',
        cancelText:  'Batal',
        onConfirm:   () => document.getElementById('logoutForm').submit(),
    });
}
</script>
