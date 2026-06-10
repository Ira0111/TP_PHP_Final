<?php
session_start();
$page_title = 'Accueil';
include 'header.php';

$medias_tendances = [
    ['id' => 1, 'titre' => 'Severance',              'type' => 'serie',   'annee' => 2022, 'note' => 4],
    ['id' => 2, 'titre' => 'Elden Ring',              'type' => 'jeu',     'annee' => 2022, 'note' => 5],
    ['id' => 3, 'titre' => 'Frieren',                 'type' => 'anime',   'annee' => 2023, 'note' => 5],
    ['id' => 4, 'titre' => 'Dune',                    'type' => 'livre',   'annee' => 1965, 'note' => 3],
    ['id' => 5, 'titre' => 'Oppenheimer',             'type' => 'film',    'annee' => 2023, 'note' => 4],
    ['id' => 6, 'titre' => 'Random Access Memories',  'type' => 'musique', 'annee' => 2013, 'note' => 5],
];

$categories = [
    ['type' => 'film',    'label' => 'Films'],
    ['type' => 'serie',   'label' => 'Séries'],
    ['type' => 'anime',   'label' => 'Animés'],
    ['type' => 'jeu',     'label' => 'Jeux vidéo'],
    ['type' => 'musique', 'label' => 'Musique'],
    ['type' => 'livre',   'label' => 'Livres'],
];

function getTypeColor(string $type): string
{
    return match ($type) {
        'film'    => '#E24B4A',
        'serie'   => '#7C6EFF',
        'anime'   => '#A07CFF',
        'jeu'     => '#1D9E75',
        'musique' => '#378ADD',
        'livre'   => '#BA7517',
        default   => '#7C6EFF',
    };
}

function getTypeBg(string $type): string
{
    return match ($type) {
        'film'    => '#2b0f0f',
        'serie'   => '#2d2260',
        'anime'   => '#1a0d2b',
        'jeu'     => '#0d2b1e',
        'musique' => '#0d1a2b',
        'livre'   => '#2b1a00',
        default   => '#1E1B30',
    };
}

function getTypeLabel(string $type): string
{
    return match ($type) {
        'film'    => 'Film',
        'serie'   => 'Série',
        'anime'   => 'Animé',
        'jeu'     => 'Jeu vidéo',
        'musique' => 'Musique',
        'livre'   => 'Livre',
        default   => $type,
    };
}

function renderHearts(int $note): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $note ? 'heart-full' : 'heart-empty';
        $html .= "<span class=\"{$class}\">♥</span>";
    }
    return $html;
}
?>

<!-- ═══════════ HERO ═══════════ -->
<section class="hero">
    <div class="hero__glow" aria-hidden="true"></div>
    <div class="hero__content">
        <p class="hero__eyebrow">Films · Séries · Animés · Jeux · Musique · Livres</p>
        <h1 class="hero__title">Ta culture,<br><span>tracée.</span></h1>
        <p class="hero__subtitle">
            Suis tes médias en cours, retrouve ce que tu as déjà vu
            et organise ta wishlist au même endroit.
        </p>
        <div class="hero__cta">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php" class="btn-primary btn-lg">Mon tableau de bord</a>
                <a href="catalogue.php" class="btn-outline btn-lg">Parcourir le catalogue</a>
            <?php else: ?>
                <a href="register.php" class="btn-primary btn-lg">Commencer gratuitement</a>
                <a href="catalogue.php" class="btn-outline btn-lg">Voir le catalogue</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════ CATÉGORIES ═══════════ -->
<section class="section categories">
    <div class="container">
        <h2 class="section__title">Explorer par type</h2>
        <div class="categories__grid">
            <?php foreach ($categories as $cat): ?>
                <a href="catalogue.php?type=<?= $cat['type'] ?>"
                    class="category-card category-card--<?= $cat['type'] ?>">
                    <img src="assets/icons/<?= $cat['type'] ?>.png"
                        alt="<?= $cat['label'] ?>"
                        class="category-card__icon">
                    <span class="category-card__label"><?= $cat['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════ TENDANCES ═══════════ -->
<section class="section tendances">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Tendances cette semaine</h2>
            <a href="catalogue.php" class="btn-ghost">Voir tout →</a>
        </div>

        <div class="medias-grid">
            <?php foreach ($medias_tendances as $media): ?>
                <?php
                $color  = getTypeColor($media['type']);
                $bg     = getTypeBg($media['type']);
                $label  = getTypeLabel($media['type']);
                $hearts = renderHearts($media['note']);
                ?>
                <article class="media-card">
                    <div class="media-card__thumb" style="background: <?= $bg ?>;">
                        <img src="assets/icons/<?= $media['type'] ?>.png"
                            alt="<?= $label ?>"
                            class="media-card__thumb-icon">
                    </div>
                    <div class="media-card__body">
                        <span class="media-card__type" style="color: <?= $color ?>;">
                            <?= $label ?>
                        </span>
                        <h3 class="media-card__title">
                            <?= htmlspecialchars($media['titre']) ?>
                        </h3>
                        <div class="media-card__meta">
                            <div class="media-card__hearts"><?= $hearts ?></div>
                            <span class="media-card__year"><?= $media['annee'] ?></span>
                        </div>
                        <a href="media.php?id=<?= $media['id'] ?>" class="btn-primary btn-xs media-card__btn">
                            <?= isset($_SESSION['user_id']) ? '+ Ajouter' : 'Voir la fiche' ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════ CTA BAS (visiteurs non connectés) ═══════════ -->
<?php if (!isset($_SESSION['user_id'])): ?>
    <section class="section cta-banner">
        <div class="container">
            <div class="cta-banner__inner">
                <div>
                    <h2 class="cta-banner__title">Prêt à tracer ta culture ?</h2>
                    <p class="cta-banner__sub">
                        Crée ton compte gratuitement et commence à suivre tes médias.
                    </p>
                </div>
                <a href="register.php" class="btn-primary btn-lg">C'est parti →</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include 'footer.php'; ?>