<?php

/**
 * followAction.php — Kultrack
 * Endpoint AJAX : enregistre ou met à jour le suivi d'un média.
 *
 * Flux :
 *  1. Si le média n'existe pas en BDD → l'insère dans `media`
 *  2. Si un follow existe déjà pour (user_id, media_id) → UPDATE
 *  3. Sinon → INSERT dans `follow`
 *
 * Reçoit : JSON { api_id, api_source, type, title, poster, status }
 * Retourne : JSON { success: bool, error?: string }
 */

require_once 'config.php';
header('Content-Type: application/json');

// Auth obligatoire
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

// Lecture du body JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'JSON invalide']);
    exit;
}

$required = ['api_id', 'api_source', 'type', 'status'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Champ manquant : $field"]);
        exit;
    }
}

$userId    = (int) $_SESSION['user_id'];
$apiId     = htmlspecialchars($data['api_id'],      ENT_QUOTES);
$apiSource = htmlspecialchars($data['api_source'],  ENT_QUOTES);
$type      = htmlspecialchars($data['type'],        ENT_QUOTES);
$title     = htmlspecialchars($data['title']  ?? '', ENT_QUOTES);
$poster    = htmlspecialchars($data['poster'] ?? '', ENT_QUOTES);
$status    = $data['status'];

// Statuts autorisés (correspond à l'ENUM BDD)
$statuts_ok = ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch'];
if (!in_array($status, $statuts_ok)) {
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit;
}

// Correspondance type slug → ENUM BDD
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

    /* ── 1. Trouver ou créer le média en BDD ── */
    $stmt = $pdo->prepare(
        'SELECT media_id FROM media WHERE api_id = ? AND api_source = ? LIMIT 1'
    );
    $stmt->execute([$apiId, $apiSource]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        $ins = $pdo->prepare(
            'INSERT INTO media (title, type, image, date, created_at, api_id, api_source)
             VALUES (?, ?, ?, CURDATE(), NOW(), ?, ?)'
        );
        $ins->execute([$title, $typeDB, $poster, $apiId, $apiSource]);
        $mediaId = (int) $pdo->lastInsertId();
    } else {
        $mediaId = (int) $media['media_id'];
    }

    /* ── 2. Vérifier si un follow existe déjà ── */
    $check = $pdo->prepare(
        'SELECT follow_id FROM follow WHERE user_id = ? AND media_id = ? LIMIT 1'
    );
    $check->execute([$userId, $mediaId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        /* Mise à jour du statut */
        $upd = $pdo->prepare(
            'UPDATE follow SET status = ?, update_at = NOW() WHERE follow_id = ?'
        );
        $upd->execute([$status, $existing['follow_id']]);
    } else {
        /* Nouveau suivi */
        $new = $pdo->prepare(
            'INSERT INTO follow (status, progress, update_at, user_id, media_id)
             VALUES (?, 0, NOW(), ?, ?)'
        );
        $new->execute([$status, $userId, $mediaId]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('followAction.php PDO error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
