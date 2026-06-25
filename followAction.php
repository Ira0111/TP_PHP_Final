<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

foreach (['api_id', 'api_source', 'type', 'status'] as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Champ manquant : $field"]);
        exit;
    }
}

$userId    = (int) $_SESSION['user_id'];
$apiId     = htmlspecialchars($data['api_id'],     ENT_QUOTES);
$apiSource = htmlspecialchars($data['api_source'], ENT_QUOTES);
$type      = htmlspecialchars($data['type'],       ENT_QUOTES);
$title     = htmlspecialchars($data['title']  ?? '', ENT_QUOTES);
$poster    = htmlspecialchars($data['poster'] ?? '', ENT_QUOTES);
$status    = $data['status'];

$statuts_ok = ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch'];
if (!in_array($status, $statuts_ok)) {
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit;
}

// Totaux pour calcul auto du % (envoyés depuis media.js)
$durationMinutes = isset($data['duration_minutes']) ? (int)$data['duration_minutes'] : null;
$totalSeasons    = isset($data['total_seasons'])    ? (int)$data['total_seasons']    : null;
$totalEpisodes   = isset($data['total_episodes'])   ? (int)$data['total_episodes']   : null;
$totalPages      = isset($data['total_pages'])      ? (int)$data['total_pages']      : null;

$typeMap = [
    'film'  => 'movie',
    'serie' => 'serie',
    'anime' => 'anime',
    'jeu'   => 'game',
    'livre' => 'book',
];
$typeDB = $typeMap[$type] ?? 'movie';

try {
    global $pdo;

    // 1. Trouver ou créer le média
    $stmt = $pdo->prepare(
        'SELECT media_id FROM media WHERE api_id = ? AND api_source = ? LIMIT 1'
    );
    $stmt->execute([$apiId, $apiSource]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        $ins = $pdo->prepare(
            'INSERT INTO media
             (title, type, image, date, created_at, api_id, api_source,
              duration_minutes, total_seasons, total_episodes, total_pages)
             VALUES (?, ?, ?, CURDATE(), NOW(), ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([
            $title,
            $typeDB,
            $poster,
            $apiId,
            $apiSource,
            $durationMinutes,
            $totalSeasons,
            $totalEpisodes,
            $totalPages,
        ]);
        $mediaId = (int) $pdo->lastInsertId();
    } else {
        $mediaId = (int) $media['media_id'];

        // Met à jour les totaux s'ils sont maintenant disponibles
        if ($durationMinutes || $totalSeasons || $totalEpisodes || $totalPages) {
            $pdo->prepare(
                'UPDATE media
                 SET duration_minutes = COALESCE(:dm, duration_minutes),
                     total_seasons    = COALESCE(:ts, total_seasons),
                     total_episodes   = COALESCE(:te, total_episodes),
                     total_pages      = COALESCE(:tp, total_pages)
                 WHERE media_id = :id'
            )->execute([
                'dm' => $durationMinutes,
                'ts' => $totalSeasons,
                'te' => $totalEpisodes,
                'tp' => $totalPages,
                'id' => $mediaId,
            ]);
        }
    }

    // 2. Follow existant ou nouveau
    $check = $pdo->prepare(
        'SELECT follow_id FROM follow WHERE user_id = ? AND media_id = ? LIMIT 1'
    );
    $check->execute([$userId, $mediaId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare(
            'UPDATE follow SET status = ?, update_at = NOW() WHERE follow_id = ?'
        )->execute([$status, $existing['follow_id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO follow (status, progress, update_at, user_id, media_id)
             VALUES (?, 0, NOW(), ?, ?)'
        )->execute([$status, $userId, $mediaId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('followAction.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
