<?php
require_once '../config.php';
require_once '../database.php';

header('Content-Type: application/json');

function dbg(string $label, $value = null): void
{
    $line = "[KUL] $label";
    if ($value !== null) {
        $line .= " : " . (is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
    }
    file_put_contents(
        __DIR__ . '/../debug_update.txt',
        date('[Y-m-d H:i:s] ') . $line . "\n",
        FILE_APPEND
    );
}

dbg("===== Nouvelle requête =====");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

$pdo = getPDO();

/* Lecture JSON */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

dbg("RAW INPUT", $raw);
dbg("JSON décodé", $data);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$followId = (int) ($data['follow_id'] ?? 0);
$status   = $data['status'] ?? null;

try {
    // 1. CORRECTION : On récupère le follow ET les détails du média associé grâce au JOIN
    $stmt = $pdo->prepare('
    SELECT f.*, m.type AS media_type, m.total_seasons, m.total_episodes, m.total_pages 
    FROM follow f
    JOIN media m ON f.media_id = m.media_id
    WHERE f.follow_id = ? AND f.user_id = ?
    ');

    $stmt->execute([$followId, $userId]);
    $follow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$follow) {
        echo json_encode(['success' => false, 'error' => 'Suivi introuvable']);
        exit;
    }

    // Extraction sécurisée des types et maximums issus de la jointure
    $mediaType     = $follow['media_type'];
    $totalSeasons  = $follow['total_seasons']  !== null ? (int)$follow['total_seasons']  : null;
    $totalEpisodes = $follow['total_episodes'] !== null ? (int)$follow['total_episodes'] : null;
    $totalPages    = $follow['total_pages']    !== null ? (int)$follow['total_pages']    : null;

    if (!$status) {
        $status = $follow['status'];
    }

    $progressDetail = $follow['progress_detail'];

    // 2. RÈGLE : La progression n'est modifiable que si le statut = watching
    if ($status === 'watching') {

        if ($mediaType === 'serie' || $mediaType === 'anime') {
            $season  = isset($data['season'])  ? (int)$data['season']  : 1;
            $episode = isset($data['episode']) ? (int)$data['episode'] : 1;
            $tc      = isset($data['timecode']) ? trim($data['timecode']) : '';

            // Validation stricte des maximums
            if ($totalSeasons !== null && $season > $totalSeasons) {
                echo json_encode([
                    'success' => false,
                    'error'   => "La saison $season dépasse le total ($totalSeasons saisons)",
                ]);
                exit;
            }
            if ($totalEpisodes !== null && $episode > $totalEpisodes) {
                echo json_encode([
                    'success' => false,
                    'error'   => "L'épisode $episode dépasse le total ($totalEpisodes épisodes)",
                ]);
                exit;
            }

            $progressDetail = "Saison $season · Épisode $episode";
            if ($tc !== '') {
                $progressDetail .= " · $tc";
            }
        }

        if ($mediaType === 'movie') {
            $tc = isset($data['timecode']) ? trim($data['timecode']) : '00:00';
            $progressDetail = "Timecode : $tc";
        }

        if ($mediaType === 'book') {
            $page = isset($data['page']) ? (int)$data['page'] : 0;

            if ($totalPages !== null && $page > $totalPages) {
                echo json_encode([
                    'success' => false,
                    'error'   => "La page $page dépasse le total ($totalPages pages)",
                ]);
                exit;
            }

            $progressDetail = "Page $page";
            if ($totalPages !== null) {
                $progressDetail .= " / $totalPages";
            }
        }

        if ($mediaType === 'game') {
            $progressDetail = null;
        }
    }

    // RÈGLE : Si marqué comme completed, on met automatiquement à 100% (ex: Livres)
    if ($status === 'completed' && $mediaType === 'book' && $totalPages !== null) {
        $progressDetail = "Page $totalPages / $totalPages";
    }

    dbg("progress_detail final", $progressDetail ?? 'NULL');

    // 3. Mise à jour de la progression
    $pdo->prepare(
        'UPDATE follow
         SET status = :status,
             progress_detail = :detail,
             update_at = NOW()
         WHERE follow_id = :id'
    )->execute([
        'status' => $status,
        'detail' => $progressDetail,
        'id'     => $followId,
    ]);

    // RÈGLE 1 : Si le statut change et n'est plus "completed", on supprime l'avis automatiquement
    if ($status !== 'completed') {
        $pdo->prepare('DELETE FROM review WHERE user_id = ? AND media_id = ?')
            ->execute([$userId, $follow['media_id']]);
    }

    dbg("UPDATE OK");
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    dbg("PDO ERROR", $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
exit;

function parseTimecode(string $tc): int
{
    $tc = strtolower(trim($tc));

    if (preg_match('/^(\\d+)h(\\d*)$/', $tc, $m)) {
        return (int) $m[1] * 60 + (int) ($m[2] ?? 0);
    }
    if (preg_match('/^(\\d+):(\\d+)$/', $tc, $m)) {
        return (int) $m[1];
    }
    return (int) $tc;
}
