<?php

/**
 * review_update.php
 * Crée ou met à jour un avis depuis le dashboard.
 * Règle stricte : uniquement si le follow est "completed".
 */
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$mediaId  = (int) ($_POST['media_id']  ?? 0);
$reviewId = (int) ($_POST['review_id'] ?? 0);
$note     = (int) ($_POST['note']      ?? 0);
$comment  = trim($_POST['comment'] ?? '');

// Validation basique
if ($mediaId <= 0 || $note < 1 || $note > 5) {
    header('Location: dashboard.php?error=invalid_review');
    exit;
}

try {
    // Vérifie que le follow de cet utilisateur est bien "completed"
    $check = $pdo->prepare(
        'SELECT follow_id FROM follow
         WHERE user_id = ? AND media_id = ? AND status = "completed" LIMIT 1'
    );
    $check->execute([$userId, $mediaId]);

    if (!$check->fetch()) {
        // Pas terminé → refus silencieux
        header('Location: dashboard.php?error=not_completed');
        exit;
    }

    if ($reviewId > 0) {
        // Mise à jour — vérifie que la review appartient à l'utilisateur
        $own = $pdo->prepare(
            'SELECT review_id FROM review WHERE review_id = ? AND user_id = ? LIMIT 1'
        );
        $own->execute([$reviewId, $userId]);

        if ($own->fetch()) {
            $pdo->prepare(
                'UPDATE review SET note = ?, comment = ?, updated_at = NOW() WHERE review_id = ?'
            )->execute([$note, $comment, $reviewId]);
        }
    } else {
        // Insertion — vérifie qu'il n'en existe pas déjà une
        $exist = $pdo->prepare(
            'SELECT review_id FROM review WHERE user_id = ? AND media_id = ? LIMIT 1'
        );
        $exist->execute([$userId, $mediaId]);
        $existing = $exist->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Met à jour l'existante
            $pdo->prepare(
                'UPDATE review SET note = ?, comment = ?, updated_at = NOW() WHERE review_id = ?'
            )->execute([$note, $comment, $existing['review_id']]);
        } else {
            $pdo->prepare(
                'INSERT INTO review (note, comment, created_at, updated_at, user_id, media_id)
                 VALUES (?, ?, NOW(), NOW(), ?, ?)'
            )->execute([$note, $comment, $userId, $mediaId]);
        }
    }

    header('Location: dashboard.php');
    exit;
} catch (PDOException $e) {
    error_log('review_update.php error: ' . $e->getMessage());
    header('Location: dashboard.php?error=db');
    exit;
}
