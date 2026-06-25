<?php
require_once '../config.php';

header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

$map = [
    'film' => 'movie',
    'serie' => 'serie',
    'anime' => 'anime',
    'jeu' => 'game',
    'livre' => 'book'
];

if (isset($map[$type])) {
    $type = $map[$type];
}

$api_id = $_GET['id'] ?? '';

if (!$type || !$api_id) {
    echo json_encode([
        "error" => "missing parameters",
        "rating" => null,
        "comments" => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT media_id 
    FROM media 
    WHERE api_id = ? AND type = ?
");
$stmt->execute([$api_id, $type]);
$mediaRow = $stmt->fetch(PDO::FETCH_ASSOC);

$media_id = $mediaRow['media_id'] ?? null;

/* Si le média n'existe pas encore en BDD */
if (!$media_id) {
    echo json_encode([
        "media_id" => null,
        "rating" => ["avg_note" => null, "total" => 0],
        "comments" => []
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT AVG(note) AS avg_note, COUNT(*) AS total
    FROM review
    WHERE media_id = ?
");
$stmt->execute([$media_id]);
$rating = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        r.review_id,
        r.note,
        r.comment,
        r.user_id,
        CONCAT(u.first_name, ' ', u.last_name) AS username,
        r.created_at
    FROM review r
    JOIN user u ON u.user_id = r.user_id
    WHERE r.media_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$media_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$current_user = $_SESSION['user_id'] ?? null;

foreach ($comments as &$c) {
    $c['is_mine'] = ($current_user && $c['user_id'] == $current_user);
}

echo json_encode([
    "media_id" => $media_id,
    "rating"   => $rating ?: ["avg_note" => null, "total" => 0],
    "comments" => $comments
]);

exit;
