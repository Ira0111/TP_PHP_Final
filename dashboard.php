<?php
require_once 'config.php';

// Page réservée aux utilisateurs connectés
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title    = 'Ma liste';
$userId        = (int) $_SESSION['user_id'];
$mediaCtrl     = new MediaController();

// ─── Récupère tous les suivis de l'utilisateur via JOIN ───
$sql = 'SELECT m.*, f.status AS follow_status, f.progress, f.follow_id
        FROM follow f
        JOIN media m ON m.media_id = f.media_id
        WHERE f.user_id = :uid
        ORDER BY f.update_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute(['uid' => $userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Répartition par statut ───
$enCours   = [];
$termines  = [];
$wishlist  = [];
$abandonnes = [];

foreach ($rows as $row) {
    $media = new Media($row);
    $entry = ['media' => $media, 'progress' => (float) $row['progress'], 'follow_id' => (int) $row['follow_id']];

    match ($row['follow_status']) {
        'watching'      => $enCours[]    = $entry,
        'completed'     => $termines[]   = $entry,
        'plan_to_watch' => $wishlist[]   = $entry,
        'dropped'       => $abandonnes[] = $entry,
        default         => null,
    };
}

// ─── Helpers ───
function getTypeLabel(string $type): string
{
    return match ($type) {
        'movie' => 'Film',
        'serie' => 'Série',
        'anime' => 'Animé',
        'game'  => 'Jeu',
        'book'  => 'Livre',
        default => ucfirst($type),
    };
}

function getSlug(string $type): string
{
    return match ($type) {
        'movie' => 'film',
        'game'  => 'jeu',
        'book'  => 'livre',
        default => $type,
    };
}

include 'header.php';
?>

<section class="section dashboard">
    <div class="container">

        <!-- En-tête -->
        <div class="dashboard__header">
            <div>
                <h1 class="dashboard__title">
                    Bonjour, <?= htmlspecialchars($_SESSION['user_nom']) ?>
                </h1>
                <p class="dashboard__subtitle">Voici ta collection de médias</p>
            </div>
            <a href="catalogue.php" class="btn-primary btn-sm">+ Découvrir des médias</a>
        </div>

        <!-- Stats -->
        <div class="dashboard__stats">
            <div class="stat-card">
                <span class="stat-card__number"><?= count($enCours) ?></span>
                <span class="stat-card__label">En cours</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($termines) ?></span>
                <span class="stat-card__label">Terminés</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($wishlist) ?></span>
                <span class="stat-card__label">Wishlist</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($abandonnes) ?></span>
                <span class="stat-card__label">Abandonnés</span>
            </div>
        </div>

        <!-- En cours -->
        <?php if (!empty($enCours)): ?>
            <div class="dashboard__section">
                <h2 class="dashboard__section-title">
                    <span class="badge badge--watching">▶ En cours</span>
                </h2>
                <div class="medias-grid">
                    <?php foreach ($enCours as $entry):
                        $m    = $entry['media'];
                        $slug = getSlug($m->getType());
                    ?>
                        <article class="media-card media-card--<?= $slug ?>">
                            <div class="media-card__thumb">
                                <?php if ($m->getImage()): ?>
                                    <img src="<?= htmlspecialchars($m->getImage()) ?>"
                                        alt="<?= htmlspecialchars($m->getTitle()) ?>"
                                        class="media-card__cover">
                                <?php else: ?>
                                    <img src="assets/icons/<?= $slug ?>L.png"
                                        alt="<?= getTypeLabel($m->getType()) ?>"
                                        class="media-card__thumb-icon">
                                <?php endif; ?>
                            </div>
                            <div class="media-card__body">
                                <span class="media-card__type"><?= getTypeLabel($m->getType()) ?></span>
                                <h3 class="media-card__title"><?= htmlspecialchars($m->getTitle()) ?></h3>
                                <div class="progress-bar">
                                    <div class="progress-bar__fill" style="width:<?= min(100, (int)$entry['progress']) ?>%;"></div>
                                </div>
                                <p class="media-card__progress"><?= (int)$entry['progress'] ?>% complété</p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Wishlist -->
        <?php if (!empty($wishlist)): ?>
            <div class="dashboard__section">
                <h2 class="dashboard__section-title">
                    <span class="badge badge--wishlist">★ Wishlist</span>
                </h2>
                <div class="medias-grid">
                    <?php foreach ($wishlist as $entry):
                        $m    = $entry['media'];
                        $slug = getSlug($m->getType());
                    ?>
                        <article class="media-card media-card--<?= $slug ?>">
                            <div class="media-card__thumb">
                                <?php if ($m->getImage()): ?>
                                    <img src="<?= htmlspecialchars($m->getImage()) ?>"
                                        alt="<?= htmlspecialchars($m->getTitle()) ?>"
                                        class="media-card__cover">
                                <?php else: ?>
                                    <img src="assets/icons/<?= $slug ?>L.png"
                                        alt="<?= getTypeLabel($m->getType()) ?>"
                                        class="media-card__thumb-icon">
                                <?php endif; ?>
                            </div>
                            <div class="media-card__body">
                                <span class="media-card__type"><?= getTypeLabel($m->getType()) ?></span>
                                <h3 class="media-card__title"><?= htmlspecialchars($m->getTitle()) ?></h3>
                                <p class="media-card__year"><?= $m->getYear() ?? '—' ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Terminés -->
        <?php if (!empty($termines)): ?>
            <div class="dashboard__section">
                <h2 class="dashboard__section-title">
                    <span class="badge badge--done">✓ Terminés</span>
                </h2>
                <div class="medias-grid">
                    <?php foreach ($termines as $entry):
                        $m    = $entry['media'];
                        $slug = getSlug($m->getType());
                    ?>
                        <article class="media-card media-card--<?= $slug ?>">
                            <div class="media-card__thumb">
                                <?php if ($m->getImage()): ?>
                                    <img src="<?= htmlspecialchars($m->getImage()) ?>"
                                        alt="<?= htmlspecialchars($m->getTitle()) ?>"
                                        class="media-card__cover">
                                <?php else: ?>
                                    <img src="assets/icons/<?= $slug ?>L.png"
                                        alt="<?= getTypeLabel($m->getType()) ?>"
                                        class="media-card__thumb-icon">
                                <?php endif; ?>
                            </div>
                            <div class="media-card__body">
                                <span class="media-card__type"><?= getTypeLabel($m->getType()) ?></span>
                                <h3 class="media-card__title"><?= htmlspecialchars($m->getTitle()) ?></h3>
                                <p class="media-card__year"><?= $m->getYear() ?? '—' ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Abandonné -->
        <?php if (!empty($abandonnes)): ?>
            <div class="dashboard__section">
                <h2 class="dashboard__section-title">
                    <span class="badge badge--dropped">✕ Abandonnés</span>
                </h2>
                <div class="medias-grid">
                    <?php foreach ($abandonnes as $entry):
                        $m    = $entry['media'];
                        $slug = getSlug($m->getType());
                    ?>
                        <article class="media-card media-card--<?= $slug ?>">
                            <div class="media-card__thumb">
                                <?php if ($m->getImage()): ?>
                                    <img src="<?= htmlspecialchars($m->getImage()) ?>"
                                        alt="<?= htmlspecialchars($m->getTitle()) ?>"
                                        class="media-card__cover">
                                <?php else: ?>
                                    <img src="assets/icons/<?= $slug ?>L.png"
                                        alt="<?= getTypeLabel($m->getType()) ?>"
                                        class="media-card__thumb-icon">
                                <?php endif; ?>
                            </div>
                            <div class="media-card__body">
                                <span class="media-card__type"><?= getTypeLabel($m->getType()) ?></span>
                                <h3 class="media-card__title"><?= htmlspecialchars($m->getTitle()) ?></h3>
                                <p class="media-card__year"><?= $m->getYear() ?? '—' ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Liste vide -->
        <?php if (empty($rows)): ?>
            <div class="dashboard__empty">
                <p class="dashboard__empty-text">Tu n'as encore rien dans ta liste.</p>
                <a href="catalogue.php" class="btn-primary btn-lg">Parcourir le catalogue</a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include 'footer.php'; ?>