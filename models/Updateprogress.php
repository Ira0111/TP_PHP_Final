<?php
require_once '../config.php';
require_once '../database.php';
$pdo = getPDO();
header('Content-Type: application/json');

/* ── Helpers de log ── */
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

/* ── Authentification ── */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

/* ── Lecture et décodage du JSON entrant ── */
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
$type     = trim($data['type']   ?? '');   // film | serie | anime | jeu | livre
$status   = trim($data['status'] ?? '');

dbg("type=$type | status=$status | follow_id=$followId");

/* ── Validations de base ── */
if ($followId <= 0) {
    echo json_encode(['success' => false, 'error' => 'follow_id manquant']);
    exit;
}

$statuts_ok = ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch'];
if (!in_array($status, $statuts_ok, true)) {
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit;
}

try {
    /* ── Vérification ownership + récupération infos média ── */
    $stmt = $pdo->prepare(
        'SELECT f.user_id, f.media_id,
                m.type            AS media_type,
                m.duration_minutes,
                m.total_episodes,
                m.total_seasons,
                m.total_pages
         FROM   follow f
         JOIN   media  m ON m.media_id = f.media_id
         WHERE  f.follow_id = :id'
    );
    $stmt->execute(['id' => $followId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    dbg("Infos media", $row);

    if (!$row || (int) $row['user_id'] !== $userId) {
        echo json_encode(['success' => false, 'error' => 'Accès refusé']);
        exit;
    }

    $mediaType     = $row['media_type'];
    $durationMin   = $row['duration_minutes']  ? (int) $row['duration_minutes']  : null;
    $totalEpisodes = $row['total_episodes']     ? (int) $row['total_episodes']    : null;
    $totalSeasons  = $row['total_seasons']      ? (int) $row['total_seasons']     : null;
    $totalPages    = $row['total_pages']        ? (int) $row['total_pages']       : null;

    /* ── Progression : uniquement si l'utilisateur est "watching" ──
       Pour les autres statuts on efface le détail (ou on le conserve — cf. commentaire).
       Choix actuel : on CONSERVE le dernier détail connu (ne pas écraser avec null). ── */

    $progressDetail = null;   // sera mis à jour uniquement si watching

    if ($status === 'watching') {

        /* --- Film --- */
        if ($mediaType === 'movie') {
            $tc = trim($data['timecode'] ?? '');
            dbg("Film timecode", $tc);

            if ($tc !== '') {
                $minutes = parseTimecode($tc);
                dbg("Film minutes parsées", $minutes);

                if ($durationMin !== null && $minutes > $durationMin) {
                    echo json_encode([
                        'success' => false,
                        'error'   => "Le timecode ($tc) dépasse la durée totale du film ({$durationMin} min)",
                    ]);
                    exit;
                }
                $progressDetail = $tc;
            }
        }
        
        if ($mediaType === 'serie' || $mediaType === 'anime') {
            $season  = (int) ($data['season']        ?? $data['season_anime']   ?? 0);
            $episode = (int) ($data['episode']       ?? $data['episode_anime']  ?? 0);
            $tc      = trim($data['timecode_serie']  ?? $data['timecode_anime'] ?? '');

            dbg("Serie/Anime S=$season E=$episode TC=$tc");

            if ($season <= 0 || $episode <= 0) {
                echo json_encode(['success' => false, 'error' => 'Saison et épisode requis (valeurs > 0)']);
                exit;
            }

            /* Validation saison */
            if ($totalSeasons !== null && $season > $totalSeasons) {
                echo json_encode([
                    'success' => false,
                    'error'   => "La saison $season dépasse le nombre total de saisons ($totalSeasons)",
                ]);
                exit;
            }

            /* Validation épisode — uniquement si total_episodes est renseigné
               et représente un total global (pas par saison).
               Si tu stockes le total d'épisodes par saison, adapte ici. */
            if ($totalEpisodes !== null && $episode > $totalEpisodes) {
                echo json_encode([
                    'success' => false,
                    'error'   => "L'épisode $episode dépasse le nombre d'épisodes ($totalEpisodes)",
                ]);
                exit;
            }

            $progressDetail = "Saison $season · Épisode $episode";
            if ($tc !== '') {
                $progressDetail .= " · $tc";
            }
        }

        /* --- Livre --- */
        if ($mediaType === 'book') {
            $page = (int) ($data['page'] ?? 0);
            dbg("Livre page", $page);

            if ($page <= 0) {
                echo json_encode(['success' => false, 'error' => 'Numéro de page invalide (doit être > 0)']);
                exit;
            }

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

        /* --- Jeu --- pas de détail de progression --- */
        if ($mediaType === 'game') {
            $progressDetail = null;
        }
    } else {
        /* Statut ≠ watching : on ne touche pas au progress_detail existant.
           On fait un UPDATE sans modifier la colonne progress_detail. */
        dbg("Statut non-watching : progression non modifiée");

        $pdo->prepare(
            'UPDATE follow
             SET    status    = :status,
                    update_at = NOW()
             WHERE  follow_id = :id'
        )->execute([
            'status' => $status,
            'id'     => $followId,
        ]);

        dbg("UPDATE statut uniquement OK");
        echo json_encode(['success' => true]);
        exit;
    }

    /* ── Mise à jour complète (statut + progression) ── */
    dbg("progress_detail final", $progressDetail ?? 'NULL');

    $pdo->prepare(
        'UPDATE follow
         SET    status          = :status,
                progress_detail = :detail,
                update_at       = NOW()
         WHERE  follow_id       = :id'
    )->execute([
        'status' => $status,
        'detail' => $progressDetail,
        'id'     => $followId,
    ]);

    dbg("UPDATE OK");
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    dbg("PDO ERROR", $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
exit;

/* ── Fonction utilitaire : convertit un timecode en minutes ── */
function parseTimecode(string $tc): int
{
    $tc = strtolower(trim($tc));

    // Format "1h23" ou "1h" ou "2h30"
    if (preg_match('/^(\d+)h(\d*)$/', $tc, $m)) {
        return (int) $m[1] * 60 + (int) ($m[2] ?: 0);
    }

    // Format "1:23" (mm:ss) ou "1:23:45" (h:mm:ss)
    if (preg_match('/^(\d+):(\d{2})(?::(\d{2}))?$/', $tc, $m)) {
        // Si 3 groupes → heures:minutes:secondes
        if (isset($m[3])) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        // Sinon → minutes:secondes → on retourne les minutes
        return (int) $m[1];
    }

    // Entier seul → minutes
    if (ctype_digit($tc)) {
        return (int) $tc;
    }

    return 0;
}
