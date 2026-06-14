<?php
require_once 'config.php';
require_once 'header.php';

$type = $_GET['type'] ?? 'all';
?>

<section class="section">
    <div class="container">

        <div class="section__header">
            <h1 class="section__title">Catalogue</h1>

            <form method="GET" class="catalogue-search">
                <input type="text" id="searchInput" placeholder="Rechercher un média...">
                <button type="button" id="searchBtn" class="btn-outline btn-lg">Rechercher</button>
            </form>
        </div>

        <div class="catalogue-filters">
            <a href="catalogue.php?type=film" class="filter <?= $type === 'film' ? 'active' : '' ?> filter--film">Film</a>
            <a href="catalogue.php?type=serie" class="filter <?= $type === 'serie' ? 'active' : '' ?> filter--serie">Série</a>
            <a href="catalogue.php?type=anime" class="filter <?= $type === 'anime' ? 'active' : '' ?> filter--anime">Animé</a>
            <a href="catalogue.php?type=jeu" class="filter <?= $type === 'jeu' ? 'active' : '' ?> filter--jeu">Jeu</a>
            <a href="catalogue.php?type=livre" class="filter <?= $type === 'livre' ? 'active' : '' ?> filter--livre">Livre</a>
        </div>

        <div id="catalogue-grid" class="medias-grid"></div>

        <div class="pagination">
            <button id="prevPage" class="btn-outline btn-sm">Précédent</button>
            <span id="pageNumber">Page 1</span>
            <button id="nextPage" class="btn-outline btn-sm">Suivant</button>
        </div>

    </div>
</section>

<div id="popup" class="popup">
    <div class="popup-content">
        <h2 id="popupTitle"></h2>
        <p id="popupOverview"></p>
        <button id="closePopup" class="btn-primary btn-sm">Fermer</button>
    </div>
</div>

<script src="assets/catalogue.js"></script>
<?php require_once 'footer.php'; ?>

