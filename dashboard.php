<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Ma liste';
$userId     = (int) $_SESSION['user_id'];

// ─── Récupération follows + avis ───
$sql = "
    SELECT
        m.*,
        f.status AS follow_status,
        f.progress_detail,
        f.follow_id,
        r.review_id,
        r.note    AS review_note,
        r.comment AS review_comment
    FROM follow f
    JOIN  media  m ON m.media_id = f.media_id
    LEFT JOIN review r
        ON r.media_id = m.media_id AND r.user_id = f.user_id
    WHERE f.user_id = :uid
    ORDER BY f.update_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['uid' => $userId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Tri par statut ───
$enCours = $termines = $wishlist = $enPause = $abandonnes = [];

foreach ($rows as $row) {
    $media = new Media($row);
    $entry = [
        'media'           => $media,
        'progress_detail' => $row['progress_detail'],
        'follow_id'       => (int) $row['follow_id'],
        'status'          => $row['follow_status'],
        'review_id'       => $row['review_id'],
        'review_note'     => $row['review_note'],
        'review_comment'  => $row['review_comment'],
    ];
    match ($row['follow_status']) {
        'watching'      => $enCours[]    = $entry,
        'completed'     => $termines[]   = $entry,
        'plan_to_watch' => $wishlist[]   = $entry,
        'on_hold'       => $enPause[]    = $entry,
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

function renderHeartsPHP(int $note, int $max = 5): string
{
    $html = '<span class="card-hearts">';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $note) {
            $html .= '<img src="assets/icons/heart.png" alt="♥" width="14" height="14">';
        } else {
            $html .= '<img src="assets/icons/heartvoid(light).png" alt="♡" width="14" height="14">';
        }
    }
    return $html . '</span>';
}

// ─── Rendu des cartes ───
function renderMediaGrid(array $entries): void
{
    foreach ($entries as $entry):
        $m       = $entry['media'];
        $slug    = getSlug($m->getType());
        $detail  = $entry['progress_detail'];
        $isCompleted = $entry['status'] === 'completed';
        $hasReview   = !empty($entry['review_note']);
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

                <p class="media-card__progress">
                    <?= $detail ? htmlspecialchars($detail) : ($m->getYear() ?? '—') ?>
                </p>

                <?php if ($hasReview): ?>
                    <div class="media-card__review-summary">
                        <?= renderHeartsPHP((int)$entry['review_note']) ?>
                    </div>
                <?php endif; ?>

                <div class="media-card__actions">

                    <!-- Bouton Modifier progression -->
                    <button type="button"
                        class="btn-outline btn-xs media-card__edit"
                        data-follow-id="<?= $entry['follow_id'] ?>"
                        data-type="<?= $slug ?>"
                        data-title="<?= htmlspecialchars($m->getTitle()) ?>"
                        data-status="<?= $entry['status'] ?>"
                        data-detail="<?= htmlspecialchars($detail ?? '') ?>">
                        Progression
                    </button>

                    <!-- Bouton Avis -->
                    <?php if ($isCompleted): ?>
                        <button type="button"
                            class="btn-primary btn-xs"
                            onclick="openReviewEditPopup(
                                <?= $entry['review_id'] ?? 0 ?>,
                                <?= $entry['review_note'] ?? 0 ?>,
                                `<?= htmlspecialchars($entry['review_comment'] ?? '', ENT_QUOTES) ?>`,
                                <?= $m->getId() ?>
                            )">
                            <?= $hasReview ? 'Modifier avis' : '+ Avis' ?>
                        </button>

                        <?php if ($hasReview): ?>
                            <form action="review_delete.php" method="post" class="inline-form"
                                onsubmit="return confirm('Supprimer cet avis ?');">
                                <input type="hidden" name="review_id" value="<?= $entry['review_id'] ?>">
                                <input type="hidden" name="api_id" value="<?= htmlspecialchars($m->getApiId() ?? '') ?>">
                                <input type="hidden" name="type" value="<?= $slug ?>">
                                <button type="submit" class="btn-xs btn-danger">Supprimer avis</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>

        </article>
<?php
    endforeach;
}
?>

<?php include 'header.php'; ?>

<section class="section dashboard">
    <div class="container">

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
                <span class="stat-card__number"><?= count($enPause) ?></span>
                <span class="stat-card__label">En pause</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($wishlist) ?></span>
                <span class="stat-card__label">Wishlist</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($termines) ?></span>
                <span class="stat-card__label">Terminés</span>
            </div>
            <div class="stat-card">
                <span class="stat-card__number"><?= count($abandonnes) ?></span>
                <span class="stat-card__label">Abandonnés</span>
            </div>
        </div>

        <?php foreach (
            [
                ['entries' => $enCours,    'badge' => 'badge--watching', 'label' => '▶ En cours'],
                ['entries' => $enPause,    'badge' => 'badge--paused',   'label' => '⏸ En pause'],
                ['entries' => $wishlist,   'badge' => 'badge--wishlist', 'label' => '★ Wishlist'],
                ['entries' => $termines,   'badge' => 'badge--done',     'label' => '✓ Terminés'],
                ['entries' => $abandonnes, 'badge' => 'badge--dropped',  'label' => '✕ Abandonnés'],
            ] as $section
        ): ?>
            <?php if (!empty($section['entries'])): ?>
                <div class="dashboard__section">
                    <h2 class="dashboard__section-title">
                        <span class="badge <?= $section['badge'] ?>">
                            <?= $section['label'] ?>
                        </span>
                    </h2>
                    <div class="medias-grid">
                        <?php renderMediaGrid($section['entries']); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($rows)): ?>
            <div class="dashboard__empty">
                <p class="dashboard__empty-text">Tu n'as encore rien dans ta liste.</p>
                <a href="catalogue.php" class="btn-primary btn-lg">Parcourir le catalogue</a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php include 'popup_progress.php'; ?>
<?php include 'popup_review.php'; ?>

<script src="assets/js/dashboard.js"></script>

<?php include 'footer.php'; ?>
