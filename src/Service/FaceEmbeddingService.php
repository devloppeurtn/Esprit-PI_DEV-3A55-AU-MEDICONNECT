<?php

namespace App\Service;

use App\Entity\Utilisateur;

/**
 * Stockage et comparaison des embeddings faciaux (face-api.js).
 * Distance euclidienne, seuil configurable. Pas de cloud, tout en PHP.
 */
class FaceEmbeddingService
{
    public function __construct(
        private readonly float $threshold = 0.55
    ) {
    }

    /**
     * Enregistre l'embedding facial pour l'utilisateur.
     *
     * @param list<float> $embedding 128 floats (Float32Array côté front)
     */
    public function storeEmbedding(Utilisateur $user, array $embedding): void
    {
        $user->setFaceEmbedding($this->normalizeEmbedding($embedding));
        $user->setBiometricEnabled(true);
    }

    /**
     * Supprime l'embedding facial de l'utilisateur.
     */
    public function clearEmbedding(Utilisateur $user): void
    {
        $user->clearFaceEmbedding();
        $user->setBiometricEnabled(false);
    }

    /**
     * Compare l'embedding fourni à celui enregistré pour l'utilisateur.
     * Retourne true si la distance euclidienne est <= seuil.
     *
     * @param list<float> $embedding 128 floats
     */
    public function verifyEmbedding(Utilisateur $user, array $embedding): bool
    {
        $stored = $user->getFaceEmbedding();
        if ($stored === null || $stored === []) {
            return false;
        }
        $distance = $this->euclideanDistance($stored, $this->normalizeEmbedding($embedding));
        return $distance <= $this->threshold;
    }

    /**
     * Distance euclidienne entre deux vecteurs 128-d.
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public function euclideanDistance(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return \PHP_FLOAT_MAX;
        }
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $d = (float) $a[$i] - (float) $b[$i];
            $sum += $d * $d;
        }
        return sqrt($sum);
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    /**
     * @param list<float> $embedding
     * @return list<float>
     */
    private function normalizeEmbedding(array $embedding): array
    {
        $out = [];
        foreach (array_slice($embedding, 0, 128) as $v) {
            $out[] = (float) $v;
        }
        return $out;
    }
}
