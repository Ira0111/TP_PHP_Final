<?php

require_once __DIR__ . '/../models/Review.php';

class ReviewController
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;

        if (!($pdo instanceof PDO)) {
            throw new Exception(
                'Connexion BDD non initialisée. Vérifie que config.php inclut bien la connexion AVANT ce contrôleur.'
            );
        }

        $this->pdo = $pdo;
    }

    public function getAllByMedia(int $media_id): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM review WHERE media_id = :media_id ORDER BY created_at DESC");
        $stmt->execute(['media_id' => $media_id]);

        $reviews = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $reviews[] = new Review($row);
        }
        return $reviews;
    }

    public function get(int $id): ?Review
    {
        $stmt = $this->pdo->prepare("SELECT * FROM review WHERE review_id = :id");
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new Review($data) : null;
    }

    public function create(Review $review): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO review (note, comment, created_at, updated_at, user_id, media_id)
            VALUES (:note, :comment, NOW(), NOW(), :user_id, :media_id)
        ");

        return $stmt->execute([
            'note'     => $review->getNote(),
            'comment'  => $review->getComment(),
            'user_id'  => $review->getUser_id(),
            'media_id' => $review->getMedia_id(),
        ]);
    }

    public function update(Review $review): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE review
            SET note = :note, comment = :comment, updated_at = NOW()
            WHERE review_id = :id
        ");

        return $stmt->execute([
            'id'      => $review->getReview_id(),
            'note'    => $review->getNote(),
            'comment' => $review->getComment(),
        ]);
    }

    /**
     * Supprime une review
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM review WHERE review_id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
}
