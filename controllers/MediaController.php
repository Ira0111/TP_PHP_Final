<?php

class MediaController
{
    private PDO $pdo;

    public function __construct()
    {
        global $pdo;

        if (!($pdo instanceof PDO)) {
            throw new Exception(
                'Connexion BDD non initialisée. ' .
                'Vérifie que config.php fait bien "require_once ROOT . \'database.php\';" ' .
                'AVANT la création de MediaController.'
            );
        }

        $this->pdo = $pdo;
    }

    public function getAll(?string $type = null, ?string $search = null, ?int $userId = null): array
    {
        $sql = 'SELECT m.*, f.status AS follow_status
                FROM media m
                LEFT JOIN follow f
                    ON f.media_id = m.media_id
                    AND f.user_id = :user_id';

        $params = ['user_id' => $userId];
        $conditions = [];

        if (!empty($type)) {
            $conditions[] = 'm.type = :type';
            $params['type'] = $type;
        }

        if (!empty($search)) {
            $conditions[] = 'm.title LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY m.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $followStatus = $row['follow_status'] ?? null;
            unset($row['follow_status']);

            $results[] = [
                'media'         => new Media($row),
                'follow_status' => $followStatus,
            ];
        }

        return $results;
    }

    public function getById(int $id): ?Media
    {
        $stmt = $this->pdo->prepare('SELECT * FROM media WHERE media_id = :id');
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new Media($data) : null;
    }

    public function getByApi(string $api_id, string $api_source): ?Media
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM media
            WHERE api_id = :api_id
            AND api_source = :api_source
            LIMIT 1
        ");

        $stmt->execute([
            'api_id'      => $api_id,
            'api_source'  => $api_source
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new Media($data) : null;
    }

    public function create(Media $media): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO media (title, type, description, image, date, created_at, api_id, api_source)
             VALUES (:title, :type, :description, :image, :date, NOW(), :api_id, :api_source)'
        );

        return $stmt->execute([
            'title'       => $media->getTitle(),
            'type'        => $media->getType(),
            'description' => $media->getDescription(),
            'image'       => $media->getImage(),
            'date'        => $media->getDate(),
            'api_id'      => $media->getApiId(),
            'api_source'  => $media->getApiSource(),
        ]);
    }

    public function update(Media $media): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE media
             SET title = :title, type = :type, description = :description,
                 image = :image, date = :date, api_id = :api_id, api_source = :api_source
             WHERE media_id = :id'
        );

        return $stmt->execute([
            'id'          => $media->getId(),
            'title'       => $media->getTitle(),
            'type'        => $media->getType(),
            'description' => $media->getDescription(),
            'image'       => $media->getImage(),
            'date'        => $media->getDate(),
            'api_id'      => $media->getApiId(),
            'api_source'  => $media->getApiSource(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM media WHERE media_id = :id');
        return $stmt->execute(['id' => $id]);
    }
}