-- Seeder: 001_seed_users
-- Password di-generate dengan password_hash($plain, PASSWORD_BCRYPT)
-- Gunakan script PHP di bawah untuk generate ulang hash jika perlu:
--   php -r "echo password_hash('password_baru', PASSWORD_BCRYPT);"

USE `knn-pln`;

INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@pln.co.id',
 '$2y$10$...', -- ganti dengan hash dari: password_hash('admin123', PASSWORD_BCRYPT)
 'admin'),

('Operator PLN', 'operator@pln.co.id',
 '$2y$10$...', -- ganti dengan hash dari: password_hash('operator123', PASSWORD_BCRYPT)
 'operator'),

('Viewer PLN', 'viewer@pln.co.id',
 '$2y$10$...', -- ganti dengan hash dari: password_hash('viewer123', PASSWORD_BCRYPT)
 'viewer');
