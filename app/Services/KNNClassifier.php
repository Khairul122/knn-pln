<?php

class KNNClassifier
{
    public int    $k        = 5;
    public string $metric   = 'euclidean';
    public array  $features = ['severity', 'occurrence', 'detection'];

    private array $trainingData = [];
    private array $normMin      = [];
    private array $normMax      = [];

    public function fit(array $data): void
    {
        $this->trainingData = array_values($data);
        foreach ($this->features as $f) {
            $vals = array_map(fn($r) => (float)($r[$f] ?? 0), $data);
            $this->normMin[$f] = min($vals);
            $this->normMax[$f] = max($vals);
            if ($this->normMax[$f] <= $this->normMin[$f]) {
                $this->normMax[$f] = $this->normMin[$f] + 1;
            }
        }
    }

    public function predict(array $point): array
    {
        if (empty($this->trainingData)) {
            return ['predicted_label' => null, 'confidence' => 0.0, 'votes' => [], 'neighbors' => []];
        }

        $pNorm = $this->normalize($point);
        $dists = [];
        foreach ($this->trainingData as $i => $train) {
            $dists[$i] = $this->calcDist($pNorm, $this->normalize($train));
        }
        asort($dists);
        $k    = min($this->k, count($this->trainingData));
        $topK = array_slice($dists, 0, $k, true);

        $votes     = [];
        $neighbors = [];
        foreach ($topK as $i => $dist) {
            $lbl         = $this->trainingData[$i]['risk_label'];
            $votes[$lbl] = ($votes[$lbl] ?? 0) + 1;
            $neighbors[] = [
                'penyulang'  => $this->trainingData[$i]['penyulang'] ?? '-',
                'risk_label' => $lbl,
                'severity'   => $this->trainingData[$i]['severity'],
                'occurrence' => $this->trainingData[$i]['occurrence'],
                'detection'  => $this->trainingData[$i]['detection'],
                'rpn'        => $this->trainingData[$i]['rpn'],
                'distance'   => round($dist, 5),
            ];
        }
        arsort($votes);
        $pred       = array_key_first($votes);
        $confidence = $votes[$pred] / $k;

        return [
            'predicted_label' => $pred,
            'confidence'      => round($confidence, 4),
            'votes'           => $votes,
            'neighbors'       => $neighbors,
        ];
    }

    public function evaluate(array $testData): array
    {
        $classes = ['Rendah', 'Sedang', 'Tinggi'];
        $cm      = [];
        foreach ($classes as $a) {
            foreach ($classes as $b) {
                $cm[$a][$b] = 0;
            }
        }

        $correct = 0;
        $rows    = [];
        foreach ($testData as $row) {
            $pred   = $this->predict($row);
            $actual = $row['risk_label'];
            if (isset($cm[$actual][$pred['predicted_label']])) {
                $cm[$actual][$pred['predicted_label']]++;
            }
            if ($pred['predicted_label'] === $actual) $correct++;
            $rows[] = $row + [
                'predicted_label' => $pred['predicted_label'],
                'confidence'      => $pred['confidence'],
                'is_correct'      => $pred['predicted_label'] === $actual,
            ];
        }

        $n        = count($testData);
        $accuracy = $n > 0 ? $correct / $n : 0.0;

        $classMet = [];
        foreach ($classes as $cls) {
            $tp = $cm[$cls][$cls];
            $fp = 0;
            $fn = 0;
            foreach ($classes as $c) {
                if ($c !== $cls) {
                    $fp += ($cm[$c][$cls] ?? 0);
                    $fn += ($cm[$cls][$c] ?? 0);
                }
            }
            $p   = ($tp + $fp) > 0 ? $tp / ($tp + $fp) : 0.0;
            $r   = ($tp + $fn) > 0 ? $tp / ($tp + $fn) : 0.0;
            $f1  = ($p + $r)   > 0 ? 2 * $p * $r / ($p + $r) : 0.0;
            $sup = $tp + $fn;
            $classMet[$cls] = compact('p', 'r', 'f1', 'sup', 'tp', 'fp', 'fn');
        }

        return [
            'accuracy'        => $accuracy,
            'n_correct'       => $correct,
            'n_total'         => $n,
            'conf_matrix'     => $cm,
            'class_metrics'   => $classMet,
            'macro_precision' => array_sum(array_column($classMet, 'p'))  / count($classes),
            'macro_recall'    => array_sum(array_column($classMet, 'r'))  / count($classes),
            'macro_f1'        => array_sum(array_column($classMet, 'f1')) / count($classes),
            'rows'            => $rows,
        ];
    }

    public function getTrainingCount(): int { return count($this->trainingData); }

    public function save(string $path): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return (bool) file_put_contents($path, serialize([
            'trainingData' => $this->trainingData,
            'k'            => $this->k,
            'metric'       => $this->metric,
            'features'     => $this->features,
            'normMin'      => $this->normMin,
            'normMax'      => $this->normMax,
        ]));
    }

    public static function load(string $path): ?self
    {
        if (!file_exists($path)) return null;
        $data = @unserialize(file_get_contents($path));
        if (!is_array($data)) return null;
        $clf               = new self();
        $clf->trainingData = $data['trainingData'];
        $clf->k            = $data['k'];
        $clf->metric       = $data['metric'];
        $clf->features     = $data['features'];
        $clf->normMin      = $data['normMin'];
        $clf->normMax      = $data['normMax'];
        return $clf;
    }

    private function normalize(array $p): array
    {
        $out = [];
        foreach ($this->features as $f) {
            $range   = $this->normMax[$f] - $this->normMin[$f];
            $out[$f] = $range > 0 ? ((float)($p[$f] ?? 0) - $this->normMin[$f]) / $range : 0.0;
        }
        return $out;
    }

    private function calcDist(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($this->features as $f) {
            $d   = ($a[$f] ?? 0.0) - ($b[$f] ?? 0.0);
            $sum += $this->metric === 'manhattan' ? abs($d) : $d * $d;
        }
        return $this->metric === 'manhattan' ? $sum : sqrt($sum);
    }
}
