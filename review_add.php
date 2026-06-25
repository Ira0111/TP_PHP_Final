<?php

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$apiId     = $_POST['api_id']     ?? '';
$apiSource = $_POST['api_source'] ?? '';
$type      = $_POST['type']       ?? '';
$note      = $_POST['note']       ?? '';
$comment   = trim($_POST['comment'] ?? '');

// Validation
$notesValides = ['1', '2', '3', '4', '5'];
if (!in_array($note, $notesValides) || empty($apiId) || empty($type)) {
    header("Location: media.php?type=$type&id=$apiId&error=invalid");
    exit;
}

// Fallback api_source depuis type si absent
if (!$apiSource) {
    $map = [
        'film'  => 'tmdb',
        'serie' => 'tmdb',
        'anime' => 'tmdb',
        'jeu'   => 'rawg',
        'livre' => 'google_books',
    ];
    $apiSource = $map[$type] ?? 'tmdb';
}

try {
    // Résolution api_id → media_id
    $stmt = $pdo->prepare(
        'SELECT media_id FROM media WHERE api_id = ? AND api_source = ? LIMIT 1'
    );
    $stmt->execute([$apiId, $apiSource]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$media) {
        header("Location: media.php?type=$type&id=$apiId&error=media_not_found");
        exit;
    }

    $mediaId = (int) $media['media_id'];
    $userId  = (int) $_SESSION['user_id'];

    // Avis existant ? → update, sinon → insert
    $check = $pdo->prepare(
        'SELECT review_id FROM review WHERE user_id = ? AND media_id = ? LIMIT 1'
    );
    $check->execute([$userId, $mediaId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $pdo->prepare(
            'UPDATE review SET note = ?, comment = ?, updated_at = NOW() WHERE review_id = ?'
        )->execute([$note, $comment, $existing['review_id']]);
    } else {
        $pdo->prepare(
            'INSERT INTO review (note, comment, created_at, updated_at, user_id, media_id)
             VALUES (?, ?, NOW(), NOW(), ?, ?)'
        )->execute([$note, $comment, $userId, $mediaId]);
    }

    header("Location: media.php?type=$type&id=$apiId");
    exit;
} catch (PDOException $e) {
    error_log('review_add.php error: ' . $e->getMessage());
    header("Location: media.php?type=$type&id=$apiId&error=db");
    exit;
}
