<?php
require_once 'config.php';

// Vérifie la session avec le bon format (user_id, pas user)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$reviewId = (int) ($_POST['review_id'] ?? 0);
$userId   = (int) $_SESSION['user_id'];

if ($reviewId <= 0) {
    header('Location: dashboard.php?error=invalid');
    exit;
}

try {
    // Vérifie que la review appartient bien à l'utilisateur connecté
    $check = $pdo->prepare(
        'SELECT review_id FROM review WHERE review_id = ? AND user_id = ? LIMIT 1'
    );
    $check->execute([$reviewId, $userId]);

    if (!$check->fetch()) {
        header('Location: dashboard.php?error=unauthorized');
        exit;
    }

    $pdo->prepare('DELETE FROM review WHERE review_id = ?')->execute([$reviewId]);

    header('Location: dashboard.php');
    exit;
} catch (PDOException $e) {
    error_log('review_delete.php error: ' . $e->getMessage());
    header('Location: dashboard.php?error=db');
    exit;
}
