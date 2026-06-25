<?php
require_once 'config.php';

$type = $_GET['type'] ?? '';
$id   = $_GET['id']   ?? '';

$types_valides = ['film', 'serie', 'anime', 'jeu', 'livre'];
if (!in_array($type, $types_valides) || empty($id)) {
    header('Location: catalogue.php');
    exit;
}

$page_title = 'Fiche média — Kultrack';
require_once 'header.php';

// Source API selon le type
$sourceMap = [
    'film'  => 'tmdb',
    'serie' => 'tmdb',
    'anime' => 'tmdb',
    'jeu'   => 'rawg',
    'livre' => 'google_books',
];
$apiSource = $sourceMap[$type] ?? 'tmdb';
?>

<section class="section media-detail">
    <div class="container">

        <!-- Loader -->
        <div id="media-loader" class="media-loader">
            <div class="skeleton skeleton--poster"></div>
            <div class="media-loader__info">
                <div class="skeleton skeleton--title"></div>
                <div class="skeleton skeleton--meta"></div>
                <div class="skeleton skeleton--text"></div>
                <div class="skeleton skeleton--text skeleton--text-short"></div>
            </div>
        </div>

        <!-- Contenu chargé par JS -->
        <div id="media-content" class="media-fiche" style="display:none">

            <div class="media-fiche__poster-wrap">
                <img id="media-poster" src="" alt="" class="media-fiche__poster">
                <span id="media-type-badge" class="media-badge"></span>
            </div>

            <div class="media-fiche__info">

                <nav class="breadcrumb">
                    <a href="catalogue.php">Catalogue</a>
                    <span class="breadcrumb-sep">›</span>
                    <a id="breadcrumb-type" href="catalogue.php?type=<?= htmlspecialchars($type) ?>"></a>
                    <span class="breadcrumb-sep">›</span>
                    <span id="breadcrumb-title"></span>
                </nav>

                <h1 id="media-title" class="media-fiche__title"></h1>

                <!-- Note communauté (injectée par JS) -->
                <div id="community-rating" class="meta-pill meta-pill--vote community-rating"></div>

                <div class="media-fiche__meta">
                    <span id="media-year" class="meta-pill"></span>
                    <span id="media-genre" class="meta-pill meta-pill--genre"></span>
                    <span id="media-extra" class="meta-pill meta-pill--extra"></span>
                </div>

                <p id="media-overview" class="media-fiche__overview"></p>

                <!-- Bouton suivi -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="media-fiche__actions" id="follow-wrap">
                        <select id="follow-status" class="follow-select">
                            <option value="">— Ajouter à ma liste —</option>
                            <option value="plan_to_watch">À voir / jouer / lire</option>
                            <option value="watching">En cours</option>
                            <option value="completed">Terminé</option>
                            <option value="on_hold">En pause</option>
                            <option value="dropped">Abandonné</option>
                        </select>
                        <button id="follow-btn" class="btn-primary btn-md">Enregistrer</button>
                        <span id="follow-msg" class="follow-msg" style="display:none"></span>
                    </div>
                <?php else: ?>
                    <div class="media-fiche__actions">
                        <a href="login.php" class="btn-outline btn-md">
                            Connecte-toi pour suivre ce média
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Infos complémentaires (tableau injecté par JS) -->
                <div id="media-extra-info" class="media-fiche__extra"></div>

                <a href="catalogue.php?type=<?= htmlspecialchars($type) ?>" class="media-fiche__back">
                    Retour au catalogue
                </a>

            </div>
        </div>

        <!-- Erreur -->
        <div id="media-error" class="media-error" style="display:none">
            <p>Impossible de charger ce média.
                <a href="catalogue.php?type=<?= htmlspecialchars($type) ?>">Retour au catalogue</a>
            </p>
        </div>

    </div>
</section>


<section class="section reviews-section" id="reviews-section" style="display:none">
    <div class="container">

        <h2 class="reviews-section__title">Avis de la communauté</h2>

        <!-- Commentaires chargés par JS -->
        <div id="community-comments" class="reviews-list"></div>

    </div>
</section>


<!-- Données PHP → JS -->
<script>
    const MEDIA_TYPE = <?= json_encode($type) ?>;
    const MEDIA_ID = <?= json_encode($id) ?>;
    const USER_ID = <?= isset($_SESSION['user_id'])
                        ? json_encode((int) $_SESSION['user_id'])
                        : 'null' ?>;
    const IS_ADMIN = <?= (($_SESSION['user_role'] ?? '') === 'admin') ? 'true' : 'false' ?>;
    const API_SOURCE = <?= json_encode($apiSource) ?>;
    const USER_INITIALS = <?= json_encode($_SESSION['user_initials'] ?? null) ?>;
</script>

<div class="popup" id="editReviewPopup">
    <div class="popup-content">
        <button type="button" class="popup-close" onclick="closeEditReview()">&times;</button>

        <h2 id="popupTitle">Modifier mon avis</h2>

        <form id="editReviewForm" action="review_update.php" method="post">

            <input type="hidden" name="review_id" id="popupReviewId">
            <input type="hidden" name="api_id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

            <div class="form-group">
                <label>Note</label>

                <div class="rating" id="popupRatingWidget">
                    <img data-value="1" src="./assets/icons/heartvoid(light).png" class="rating-heart">
                    <img data-value="2" src="./assets/icons/heartvoid(light).png" class="rating-heart">
                    <img data-value="3" src="./assets/icons/heartvoid(light).png" class="rating-heart">
                    <img data-value="4" src="./assets/icons/heartvoid(light).png" class="rating-heart">
                    <img data-value="5" src="./assets/icons/heartvoid(light).png" class="rating-heart">
                </div>

                <input type="hidden" name="note" id="popupNote">
            </div>

            <div class="form-group">
                <label for="popupComment">Commentaire</label>
                <textarea name="comment" id="popupComment" rows="4" placeholder="Ton avis…"></textarea>
            </div>

            <button type="submit" class="btn-primary" id="popupSubmit">Mettre à jour</button>
        </form>
    </div>
</div>

<script src="assets/js/media.js"></script>

<?php require_once 'footer.php'; ?>