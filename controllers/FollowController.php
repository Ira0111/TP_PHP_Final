<?php

require_once __DIR__ . '/../models/Follow.php';

class FollowController
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;

        if (!($pdo instanceof PDO)) {
            throw new Exception("Connexion PDO non initialisée.");
        }

        $this->pdo = $pdo;
    }

    /* Vérifie si un média est terminé */
    public function hasCompleted(int $user_id, int $media_id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM follow
            WHERE user_id = :u AND media_id = :m AND status = 'completed'
        ");
        $stmt->execute(['u' => $user_id, 'm' => $media_id]);
        return (bool) $stmt->fetchColumn();
    }

    /* Récupère un follow */
    public function get(int $user_id, int $media_id): ?Follow
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM follow
            WHERE user_id = :u AND media_id = :m
        ");
        $stmt->execute(['u' => $user_id, 'm' => $media_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? new Follow($data) : null;
    }

    /* Crée un follow */
    public function create(Follow $follow): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO follow (user_id, media_id, status, progress_detail, created_at, update_at)
            VALUES (:u, :m, :s, :d, NOW(), NOW())
        ");

        return $stmt->execute([
            'u' => $follow->getUser_id(),
            'm' => $follow->getMedia_id(),
            's' => $follow->getStatus(),
            'd' => $follow->getProgress_detail(), // peut être null
        ]);
    }

    /* Met à jour un follow */
    public function update(Follow $follow): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE follow
            SET status = :s,
                progress_detail = :d,
                update_at = NOW()
            WHERE user_id = :u AND media_id = :m
        ");

        return $stmt->execute([
            'u' => $follow->getUser_id(),
            'm' => $follow->getMedia_id(),
            's' => $follow->getStatus(),
            'd' => $follow->getProgress_detail(), // peut être null
        ]);
    }

    public function delete(int $user_id, int $media_id): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM follow
            WHERE user_id = :u AND media_id = :m
        ");

        return $stmt->execute(['u' => $user_id, 'm' => $media_id]);
    }
}
