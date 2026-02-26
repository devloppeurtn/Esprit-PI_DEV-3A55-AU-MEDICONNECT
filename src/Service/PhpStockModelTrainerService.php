<?php

namespace App\Service;

class PhpStockModelTrainerService
{
    /**
     * @param list<array<string, int|float|string>> $rows
     * @return array<string, mixed>
     */
    public function trainAndSave(array $rows, string $outputPath): array
    {
        if (count($rows) < 50) {
            throw new \RuntimeException('Not enough rows to train model (minimum 50).');
        }

        $featureNames = [
            'last_1d',
            'last_3d',
            'last_7d',
            'prev_7d',
            'last_14d',
            'trend_7d',
            'dow',
            'month',
        ];

        $split = (int) floor(count($rows) * 0.8);
        if ($split < 30) {
            $split = max(1, count($rows) - 10);
        }

        $trainRows = array_slice($rows, 0, $split);
        $testRows = array_slice($rows, $split);
        if ($testRows === []) {
            $testRows = array_slice($rows, -10);
        }

        $means = [];
        $stds = [];
        foreach ($featureNames as $name) {
            $values = array_map(
                static fn (array $row): float => (float) ($row[$name] ?? 0.0),
                $trainRows
            );
            $means[$name] = $this->mean($values);
            $std = sqrt($this->variance($values, $means[$name]));
            $stds[$name] = ($std > 1.0e-12) ? $std : 1.0;
        }

        $xTrain = $this->buildX($trainRows, $featureNames, $means, $stds);
        $yTrain = array_map(static fn (array $row): float => (float) ($row['target'] ?? 0.0), $trainRows);
        $xTest = $this->buildX($testRows, $featureNames, $means, $stds);
        $yTest = array_map(static fn (array $row): float => (float) ($row['target'] ?? 0.0), $testRows);

        [$weights, $bias] = $this->fitLinearSgd($xTrain, $yTrain);

        $predTrain = $this->predict($xTrain, $weights, $bias);
        $predTest = $this->predict($xTest, $weights, $bias);

        $model = [
            'version' => 1,
            'algorithm' => 'linear_sgd_php',
            'trained_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
            'feature_names' => $featureNames,
            'feature_means' => $means,
            'feature_stds' => $stds,
            'weights' => array_combine($featureNames, $weights),
            'bias' => $bias,
            'metrics' => [
                'rows_total' => count($rows),
                'rows_train' => count($trainRows),
                'rows_test' => count($testRows),
                'mae_train' => $this->mae($yTrain, $predTrain),
                'rmse_train' => $this->rmse($yTrain, $predTrain),
                'mae_test' => $this->mae($yTest, $predTest),
                'rmse_test' => $this->rmse($yTest, $predTest),
            ],
        ];

        $dir = dirname($outputPath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create model output directory.');
        }

        $written = file_put_contents($outputPath, json_encode($model, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if ($written === false) {
            throw new \RuntimeException('Unable to save trained model.');
        }

        return $model;
    }

    /**
     * @param list<list<float>> $xTrain
     * @param list<float> $yTrain
     * @return array{0:list<float>,1:float}
     */
    private function fitLinearSgd(array $xTrain, array $yTrain): array
    {
        if ($xTrain === []) {
            return [[], 0.0];
        }

        $featureCount = count($xTrain[0]);
        $weights = array_fill(0, $featureCount, 0.0);
        $bias = $this->mean($yTrain);
        $lr = 0.03;
        $l2 = 1.0e-4;
        $epochs = 350;

        mt_srand(42);
        $indexes = range(0, count($xTrain) - 1);

        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            shuffle($indexes);
            foreach ($indexes as $idx) {
                $x = $xTrain[$idx];
                $y = $yTrain[$idx];

                $pred = $bias;
                for ($j = 0; $j < $featureCount; $j++) {
                    $pred += $weights[$j] * $x[$j];
                }

                $err = $pred - $y;
                for ($j = 0; $j < $featureCount; $j++) {
                    $grad = ($err * $x[$j]) + ($l2 * $weights[$j]);
                    $weights[$j] -= $lr * $grad;
                }
                $bias -= $lr * $err;
            }
            $lr *= 0.995;
        }

        return [$weights, $bias];
    }

    /**
     * @param list<array<string, int|float|string>> $rows
     * @param list<string> $featureNames
     * @param array<string, float> $means
     * @param array<string, float> $stds
     * @return list<list<float>>
     */
    private function buildX(array $rows, array $featureNames, array $means, array $stds): array
    {
        $x = [];
        foreach ($rows as $row) {
            $vector = [];
            foreach ($featureNames as $name) {
                $raw = (float) ($row[$name] ?? 0.0);
                $mean = $means[$name] ?? 0.0;
                $std = $stds[$name] ?? 1.0;
                if ($std <= 0.0) {
                    $std = 1.0;
                }
                $vector[] = ($raw - $mean) / $std;
            }
            $x[] = $vector;
        }

        return $x;
    }

    /**
     * @param list<list<float>> $x
     * @param list<float> $weights
     * @return list<float>
     */
    private function predict(array $x, array $weights, float $bias): array
    {
        $preds = [];
        foreach ($x as $row) {
            $val = $bias;
            foreach ($row as $i => $v) {
                $val += ($weights[$i] ?? 0.0) * $v;
            }
            $preds[] = max(0.0, $val);
        }

        return $preds;
    }

    /**
     * @param list<float> $values
     */
    private function mean(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * @param list<float> $values
     */
    private function variance(array $values, float $mean): float
    {
        if ($values === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }

        return $sum / count($values);
    }

    /**
     * @param list<float> $yTrue
     * @param list<float> $yPred
     */
    private function mae(array $yTrue, array $yPred): float
    {
        if ($yTrue === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($yTrue as $i => $y) {
            $sum += abs($y - ($yPred[$i] ?? 0.0));
        }

        return $sum / count($yTrue);
    }

    /**
     * @param list<float> $yTrue
     * @param list<float> $yPred
     */
    private function rmse(array $yTrue, array $yPred): float
    {
        if ($yTrue === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($yTrue as $i => $y) {
            $err = $y - ($yPred[$i] ?? 0.0);
            $sum += $err * $err;
        }

        return sqrt($sum / count($yTrue));
    }
}

