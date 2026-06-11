<?php
/**
 * Penilaian FMEA otomatis dari satu baris data pemeliharaan.
 * Logika S/O/D identik dengan referensi Python (perhitungan_data/fmea.py).
 */
class FmeaScorer
{
    /**
     * @param array $p baris tabel pemeliharaan
     * @return array failure_mode, severity, occurrence, detection, rpn, risk_label, catatan
     */
    public static function score(array $p): array
    {
        $t1i = (float)$p['tier1_inpeksi'];
        $t1t = (float)$p['tier1_temuan'];
        $t2i = (float)$p['tier2_inpeksi'];
        $t2t = (float)$p['tier2_temuan'];
        $ukur = (float)$p['pengukuran'];
        $fco  = (float)$p['pergantian_fco'];
        $beban = (float)$p['penyeimbangan_beban_gardu'];
        $gnd  = (float)$p['perbaikan_grounding_trafo'];
        $pjt  = (float)$p['penghalang_panjat'];

        // Severity (S) — disamakan dengan referensi Python severity()
        $s = 1; // Default
        if ($fco > 0 || $gnd > 0) {
            $s = 9; // Pergantian FCO / perbaikan grounding trafo
        } elseif ($beban > 0) {
            $s = 6; // Penyeimbangan beban gardu
        } elseif ($ukur > 0) {
            $s = 4; // Pengukuran
        } elseif ($pjt > 0) {
            $s = 2; // Penghalang panjat
        }

        // Occurrence (O) — batas rentang identik dengan referensi Python (fmea.py):
        // total di luar rentang 1–10 dan 11–20 (mis. 0.6 atau 10.3) jatuh ke 9
        $totalTemuan = $t1t + $t2t;
        $o = match(true) {
            $totalTemuan == 0                          => 1,
            $totalTemuan >= 1 && $totalTemuan <= 10    => 4,
            $totalTemuan >= 11 && $totalTemuan <= 20   => 7,
            default                                    => 9,
        };

        // Detection (D) berdasarkan realisasi inspeksi
        $d = match(true) {
            $t1i > 0 && $t2i > 0 => 2, // Ada realisasi Tier 1 dan Tier 2
            $t1i > 0 || $t2i > 0 => 5, // Hanya ada Tier 1 atau Tier 2 saja
            default              => 9, // Deteksi sangat buruk (tidak ada inspeksi)
        };

        $rpn   = $s * $o * $d;
        $label = $rpn <= 9 ? 'Rendah' : ($rpn <= 99 ? 'Sedang' : 'Tinggi');

        // Determine failure_mode from dominant issue
        $maxVal  = max($fco, $gnd, $beban, $pjt, $t2t, $t1t);
        $failure = match(true) {
            $maxVal == 0           => 'Tidak ada kegagalan teridentifikasi',
            $gnd  == $maxVal       => 'Gangguan grounding tidak memadai',
            $fco  == $maxVal       => 'Kegagalan FCO (Fuse Cut Out)',
            $beban == $maxVal      => 'Overload beban melebihi kapasitas',
            $pjt  == $maxVal       => 'Vegetasi menghalangi jaringan',
            $t2t  >= $t1t          => 'Kerusakan mekanik tiang / konduktor',
            default                => 'Kegagalan isolasi trafo',
        };

        return [
            'failure_mode' => $failure,
            'severity'     => $s,
            'occurrence'   => $o,
            'detection'    => $d,
            'rpn'          => $rpn,
            'risk_label'   => $label,
            'catatan'      => 'Dilabeli otomatis berdasarkan data pemeliharaan.',
        ];
    }
}
