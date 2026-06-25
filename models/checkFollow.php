<?php
require_once '../config.php';

$user_id = $_SESSION['user_id'] ?? null;
$api_id = $_GET['id'] ?? '';
$type = $_GET['type'] ?? '';

if (!$user_id) {
  echo json_encode(['follow' => false]);
  exit;
}

$stmt = $pdo->prepare("SELECT id FROM follow WHERE user_id = ? AND api_id = ? AND type = ?");
$stmt->execute([$user_id, $api_id, $type]);

echo json_encode(['follow' => $stmt->fetch() ? true : false]);
