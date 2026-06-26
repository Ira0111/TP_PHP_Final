<?php

require_once __DIR__ . '/../models/Follow.php';

class FollowController
{
    private PDO $pdo;

    public function __construct()
    {
        require_once __DIR__ . '/../config/database.php';
        $this->pdo = getPDO();
    }

    // Vérifie si un média est terminé
    public function hasCompleted(int $user_id, int $media_id): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM follow
            WHERE user_id = :u AND media_id = :m AND status = 'completed'
        ");
        $stmt->execute(['u' => $user_id, 'm' => $media_id]);
        return (bool) $stmt->fetchColumn();
    }

    // Récupère un follow
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

    // Crée un follow
    public function create(Follow $follow): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO follow (status, progress_detail, update_at, user_id, media_id)
            VALUES (:s, :d, NOW(), :u, :m)
        ");

        return $stmt->execute([
            's' => $follow->getStatus(),
            'd' => $follow->getProgress_detail(), // peut être null
            'u' => $follow->getUser_id(),
            'm' => $follow->getMedia_id(),
        ]);
    }

    // Met à jour un follow (par user + media)
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
            's' => $follow->getStatus(),
            'd' => $follow->getProgress_detail(), // peut être null
            'u' => $follow->getUser_id(),
            'm' => $follow->getMedia_id(),
        ]);
    }

    // Supprime un follow
    public function delete(int $user_id, int $media_id): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM follow
            WHERE user_id = :u AND media_id = :m
        ");

        return $stmt->execute(['u' => $user_id, 'm' => $media_id]);
    }
}
