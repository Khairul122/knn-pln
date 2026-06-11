<?php
/**
 * Regenerasi label FMEA seluruh data pemeliharaan memakai FmeaScorer.
 * Jalankan dari root proyek: php scripts/relabel_fmea.php [tahun]
 */
if (PHP_SAPI !== 'cli') {
    exit("Script ini hanya untuk CLI.\n");
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'knn-pln');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

require_once __DIR__ . '/../app/Services/FmeaScorer.php';

$tahun = (int)($argv[1] ?? 2025);

$db = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$rows = $db->prepare('SELECT * FROM pemeliharaan WHERE tahun = ?');
$rows->execute([$tahun]);

$upd = $db->prepare(
    'UPDATE labeling SET failure_mode = :failure_mode, severity = :severity, occurrence = :occurrence,
     detection = :detection, rpn = :rpn, risk_label = :risk_label, catatan = :catatan
     WHERE pemeliharaan_id = :pid'
);
$ins = $db->prepare(
    'INSERT INTO labeling (pemeliharaan_id, failure_mode, severity, occurrence, detection, rpn, risk_label, catatan, labeled_by)
     VALUES (:pid, :failure_mode, :severity, :occurrence, :detection, :rpn, :risk_label, :catatan, 1)'
);

$updated = $insertedN = 0;
foreach ($rows->fetchAll() as $pem) {
    $scored = FmeaScorer::score($pem);
    $params = $scored + ['pid' => $pem['id']];
    $upd->execute($params);
    if ($upd->rowCount() === 0) {
        $exists = $db->prepare('SELECT 1 FROM labeling WHERE pemeliharaan_id = ?');
        $exists->execute([$pem['id']]);
        if (!$exists->fetchColumn()) {
            $ins->execute($params);
            $insertedN++;
            continue;
        }
    }
    $updated++;
}

echo "Tahun {$tahun}: {$updated} label diperbarui, {$insertedN} label baru.\n";

$dist = $db->query("SELECT risk_label, COUNT(*) n FROM labeling GROUP BY risk_label")->fetchAll();
foreach ($dist as $d) echo "  {$d['risk_label']}: {$d['n']}\n";
