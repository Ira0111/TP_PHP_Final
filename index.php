<?php
require_once 'config.php';
$page_title = 'Accueil';
include 'header.php';

// Détection du statut de connexion
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int)$_SESSION['user_id'] : null;

// Si connecté, récupération des fiches "En cours" de l'utilisateur
$enCoursEntries = [];
if ($isLoggedIn) {
    try {
        global $pdo;
        $sql = "
            SELECT m.type, m.api_id, m.title, f.progress_detail
            FROM follow f
            JOIN media m ON m.media_id = f.media_id
            WHERE f.user_id = :uid AND f.status = 'watching'
            ORDER BY f.update_at DESC
            LIMIT 5
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $enCoursEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur index connecté : " . $e->getMessage());
    }
}

$medias_tendances = [
    ['id' => 1, 'titre' => 'Severance',   'type' => 'serie', 'annee' => 2022, 'note' => 4],
    ['id' => 2, 'titre' => 'Elden Ring',  'type' => 'jeu',   'annee' => 2022, 'note' => 5],
    ['id' => 3, 'titre' => 'Frieren',     'type' => 'anime', 'annee' => 2023, 'note' => 5],
    ['id' => 4, 'titre' => 'Dune',        'type' => 'livre', 'annee' => 1965, 'note' => 3],
    ['id' => 5, 'titre' => 'Oppenheimer', 'type' => 'film',  'annee' => 2023, 'note' => 4],
];

$categories = [
    ['type' => 'film',  'label' => 'Films'],
    ['type' => 'serie', 'label' => 'Séries'],
    ['type' => 'anime', 'label' => 'Animés'],
    ['type' => 'jeu',   'label' => 'Jeux vidéo'],
    ['type' => 'livre', 'label' => 'Livres'],
];

function getTypeLabel(string $type): string
{
    return match ($type) {
        'film'  => 'Film',
        'serie' => 'Série',
        'anime' => 'Animé',
        'jeu'   => 'Jeu vidéo',
        'livre' => 'Livre',
        default => ucfirst($type),
    };
}

function renderHearts(int $note): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $note ? 'heart-full' : 'heart-empty';
        $html .= "<span class=\"{$class}\"><img src=\"assets/icons/heart.png\" alt=\"Cœur\" height=\"12\" width=\"12\"></span>";
    }
    return $html;
}

/**
 * Fonction PHP qui va chercher l'image directement sur les APIs distantes (TMDB, RAWG, Google Books)
 */
function fetchPosterFromApi(string $type, string $id): string
{
    $tmdbKey = 'f442994667b277e5713a208e0efef0e3';
    $rawgKey = 'a02d70de4ce242308e5d60335a4e7479';

    // Configuration du contexte pour éviter les blocages de requêtes (User-Agent obligatoire pour certaines API)
    $options = ["http" => ["header" => "User-Agent: KultrackApp/1.0\r\n"]];
    $context = stream_context_create($options);

    try {
        if ($type === 'film' || $type === 'serie' || $type === 'anime') {
            $tmdbPath = ($type === 'film') ? 'movie' : 'tv';
            $url = "https://api.themoviedb.org/3/{$tmdbPath}/{$id}?api_key={$tmdbKey}&language=fr-FR";
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (!empty($data['poster_path'])) {
                    return "https://image.tmdb.org/t/p/w500" . $data['poster_path'];
                }
            }
        } elseif ($type === 'jeu') {
            $url = "https://api.rawg.io/api/games/{$id}?key={$rawgKey}";
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (!empty($data['background_image'])) {
                    return $data['background_image'];
                }
            }
        } elseif ($type === 'livre') {
            $url = "https://www.googleapis.com/books/v1/volumes/{$id}";
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                $thumb = $data['volumeInfo']['imageLinks']['thumbnail'] ?? null;
                if ($thumb) {
                    return str_replace('http://', 'https://', $thumb);
                }
            }
        }
    } catch (Exception $e) {
        // En cas d'erreur API, on laisse la fonction continuer vers l'image par défaut
    }

    // Image locale par défaut si l'API ne répond pas ou ne trouve rien
    return "assets/icons/{$type}L.png";
}
?>

<section class="hero">
    <div class="hero__content">
        <?php if ($isLoggedIn): ?>
            <?php
            // Récupération et découpage du nom complet pour obtenir le prénom
            $prenom = 'Aventurier';
            if (!empty($_SESSION['user_nom'])) {
                $segments = explode(' ', trim($_SESSION['user_nom']));
                $prenom = $segments[0];
            }
            ?>
            <p class="hero__eyebrow">Ravi de te revoir, <?= htmlspecialchars($prenom) ?> !</p>
            <h1 class="hero__title">
                Tes suivis<br>
                <span>en ce moment.</span>
            </h1>
            <p class="hero__subtitle">
                Retrouve ici un accès rapide aux œuvres que tu es en train de parcourir.
            </p>
        <?php else: ?>
            <p class="hero__eyebrow">Films · Séries · Animés · Jeux · Livres</p>
            <h1 class="hero__title">
                Ta culture,<br>
                <span>tracée.</span>
            </h1>
            <p class="hero__subtitle">
                Suis tes médias en cours, retrouve ce que tu as déjà vu
                et organise ta wishlist au même endroit.
            </p>
        <?php endif; ?>

        <div class="hero__cta">
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="btn-primary btn-lg">Mon tableau de bord</a>
                <a href="catalogue.php" class="btn-outline btn-lg">Parcourir le catalogue</a>
            <?php else: ?>
                <a href="register.php" class="btn-primary btn-lg">Commencer gratuitement</a>
                <a href="catalogue.php" class="btn-outline btn-lg">Voir le catalogue</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($isLoggedIn): ?>
    <section class="section user-watching">
        <div class="container">
            <div class="section__header">
                <h2 class="section__title">Continuer à regarder / lire</h2>
            </div>

            <?php if (!empty($enCoursEntries)): ?>
                <div class="medias-grid">
                    <?php foreach ($enCoursEntries as $row): ?>
                        <?php
                        $label = getTypeLabel($row['type'] ?? '');
                        $posterUrl = fetchPosterFromApi($row['type'] ?? '', $row['api_id'] ?? '');
                        ?>
                        <article class="media-card media-card--<?= htmlspecialchars($row['type'] ?? '') ?>">

                            <div class="media-card__thumb">
                                <img src="<?= htmlspecialchars($posterUrl) ?>"
                                    alt="<?= htmlspecialchars($row['title'] ?? '') ?>"
                                    class="media-card__thumb-icon"
                                    style="object-fit: cover; width: 100%; height: 100%;">
                            </div>

                            <div class="media-card__body">
                                <span class="media-card__type"><?= $label ?></span>
                                <h3 class="media-card__title"><?= htmlspecialchars($row['title'] ?? '') ?></h3>

                                <div class="media-card__meta">
                                    <?php if (!empty($row['progress_detail'])): ?>
                                        <span class="media-card__year" style="color: #ff4757; font-weight: bold;">
                                            <?= htmlspecialchars($row['progress_detail']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="media-card__year">En cours</span>
                                    <?php endif; ?>
                                </div>

                                <a href="media.php?type=<?= htmlspecialchars($row['type'] ?? '') ?>&id=<?= htmlspecialchars($row['api_id'] ?? '') ?>"
                                    class="btn-primary btn-xs media-card__btn">
                                    Mettre à jour
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); padding: 3rem; text-align: center; border-radius: 8px;">
                    <p style="margin-bottom: 1rem; color: #888;">Tu n'as aucun média marqué "En cours" pour le moment.</p>
                    <a href="catalogue.php" class="btn-primary btn-xs">Explorer le catalogue</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section categories">
    <div class="container">
        <h2 class="section__title">Explorer par type</h2>

        <div class="categories__grid">
            <?php foreach ($categories as $cat): ?>
                <a href="catalogue.php?type=<?= $cat['type'] ?>"
                    class="category-card category-card--<?= $cat['type'] ?>">
                    <img src="assets/icons/<?= $cat['type'] ?>L.png"
                        alt="<?= $cat['label'] ?>"
                        class="category-card__icon">
                    <span class="category-card__label"><?= $cat['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section tendances">
    <div class="container">

        <div class="section__header">
            <h2 class="section__title">Tendances cette semaine</h2>
            <a href="catalogue.php" class="btn-ghost">Voir tout →</a>
        </div>

        <div class="medias-grid">
            <?php foreach ($medias_tendances as $media): ?>
                <?php $label = getTypeLabel($media['type']); ?>

                <article class="media-card media-card--<?= $media['type'] ?>">
                    <div class="media-card__thumb">
                        <img src="assets/icons/<?= $media['type'] ?>L.png"
                            alt="<?= $label ?>"
                            class="media-card__thumb-icon">
                    </div>

                    <div class="media-card__body">
                        <span class="media-card__type">
                            <?= $label ?>
                        </span>

                        <h3 class="media-card__title">
                            <?= htmlspecialchars($media['titre']) ?>
                        </h3>

                        <div class="media-card__meta">
                            <div class="media-card__hearts"><?= renderHearts($media['note']) ?></div>
                            <span class="media-card__year"><?= $media['annee'] ?></span>
                        </div>

                        <a href="media.php?type=<?= $media['type'] ?>&id=<?= $media['id'] ?>"
                            class="btn-primary btn-xs media-card__btn">
                            <?= $isLoggedIn ? '+ Ajouter' : 'Voir la fiche' ?>
                        </a>
                    </div>
                </article>

            <?php endforeach; ?>
        </div>

    </div>
</section>

<?php if (!$isLoggedIn): ?>
    <section class="section cta-banner">
        <div class="container">
            <div class="cta-banner__inner">
                <div>
                    <h2 class="cta-banner__title">Prêt à tracer ta culture ?</h2>
                    <p class="cta-banner__sub">
                        Crée ton compte gratuitement et commence à suivre tes médias.
                    </p>
                </div>

                <a href="register.php" class="btn-primary btn-lg">C'est parti</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include 'footer.php'; ?>