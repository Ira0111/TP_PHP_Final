<?php
require_once 'config.php';

// Paramètres GET obligatoires
$type  = $_GET['type']  ?? '';
$id    = $_GET['id']    ?? '';

$types_valides = ['film', 'serie', 'anime', 'jeu', 'livre'];
if (!in_array($type, $types_valides) || empty($id)) {
    header('Location: catalogue.php');
    exit;
}

$page_title = 'Fiche média — Kultrack';
require_once 'header.php';
?>

<section class="section media-detail">
  <div class="container">

    <!-- Loader affiché pendant le fetch JS -->
    <div id="media-loader" class="media-loader">
      <div class="skeleton skeleton--poster"></div>
      <div class="media-loader__info">
        <div class="skeleton skeleton--title"></div>
        <div class="skeleton skeleton--meta"></div>
        <div class="skeleton skeleton--text"></div>
        <div class="skeleton skeleton--text skeleton--text-short"></div>
      </div>
    </div>

    <!-- Contenu injecté par JS -->
    <div id="media-content" class="media-fiche" style="display:none">

      <div class="media-fiche__poster-wrap">
        <img id="media-poster" src="" alt="" class="media-fiche__poster">
        <span id="media-type-badge" class="media-badge"></span>
      </div>

      <div class="media-fiche__info">
        <nav class="breadcrumb">
          <a href="catalogue.php">Catalogue</a>
          <span>›</span>
          <a id="breadcrumb-type" href="catalogue.php?type=<?= htmlspecialchars($type) ?>"></a>
          <span>›</span>
          <span id="breadcrumb-title"></span>
        </nav>

        <h1 id="media-title" class="media-fiche__title"></h1>

        <div class="media-fiche__meta">
          <span id="media-year"  class="meta-pill"></span>
          <span id="media-genre" class="meta-pill meta-pill--genre"></span>
          <span id="media-extra" class="meta-pill meta-pill--extra"></span>
        </div>

        <p id="media-overview" class="media-fiche__overview"></p>

        <!-- Bouton suivi — visible uniquement si connecté -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="media-fiche__actions">
          <select id="follow-status" class="follow-select">
            <option value="">— Ajouter à ma liste —</option>
            <option value="plan_to_watch">À voir / jouer / lire</option>
            <option value="watching">En cours</option>
            <option value="completed">Terminé</option>
            <option value="on_hold">En pause</option>
            <option value="dropped">Abandonné</option>
          </select>
          <button id="follow-btn" class="btn-primary btn-md">
            <img src="assets/icons/heart.png" alt="" width="14" height="14">
            Enregistrer
          </button>
          <span id="follow-msg" class="follow-msg" style="display:none"></span>
        </div>
        <?php else: ?>
        <div class="media-fiche__actions">
          <a href="login.php" class="btn-outline btn-md">
            Connecte-toi pour suivre ce média
          </a>
        </div>
        <?php endif; ?>

        <!-- Infos complémentaires selon le type -->
        <div id="media-extra-info" class="media-fiche__extra"></div>
      </div>

    </div>

    <!-- Message d'erreur -->
    <div id="media-error" class="media-error" style="display:none">
      <p>Impossible de charger ce média. <a href="catalogue.php?type=<?= htmlspecialchars($type) ?>">Retour au catalogue</a></p>
    </div>

  </div>
</section>

<!-- Données PHP transmises au JS -->
<script>
  const MEDIA_TYPE = <?= json_encode($type) ?>;
  const MEDIA_ID   = <?= json_encode($id)   ?>;
  <?php if (isset($_SESSION['user_id'])): ?>
  const USER_ID    = <?= json_encode($_SESSION['user_id']) ?>;
  <?php else: ?>
  const USER_ID    = null;
  <?php endif; ?>
</script>
<script src="assets/media.js"></script>

<?php require_once 'footer.php'; ?>