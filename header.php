<?php
$page_actuelle = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr-FR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Kultrack — Suis tes films, séries, animés, jeux, musiques et livres.">
  <link rel="stylesheet" href="assets/style.css">
  <link rel="shortcut icon" href="assets/icons/favicon.ico" type="image/x-icon">
  <title><?= $page_title ?? 'Kultrack' ?></title>
</head>

<body>

  <header class="navbar">

    <!-- LOGO -->
    <a href="index.php" class="navbar__logo">
      <img src="assets/icons/logo.svg" alt="Logo Kultrack" class="navbar__logo-icon">
      Kul<span>track</span>
    </a>

    <!-- LIENS -->
    <nav class="navbar__links">
      <a href="index.php"
        class="navbar__link <?= $page_actuelle === 'index'? 'navbar__link--active' : '' ?>">
        Accueil
      </a>
      <a href="catalogue.php"
        class="navbar__link <?= $page_actuelle === 'catalogue'? 'navbar__link--active' : '' ?>">
        Catalogue
      </a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php"
          class="navbar__link <?= $page_actuelle === 'dashboard'? 'navbar__link--active' : '' ?>">
          Ma liste
        </a>
      <?php endif; ?>
      <a href="contact.php"
        class="navbar__link <?= $page_actuelle === 'contact'? 'navbar__link--active' : '' ?>">
        Contact
      </a>
    </nav>

    <!-- ACTIONS DROITE -->
    <div class="navbar__actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php" class="navbar__avatar">
          <?= strtoupper(substr($_SESSION['user_nom'], 0, 2)) ?>
        </a>
        <a href="logout.php" class="btn btn--ghost btn--sm">Déconnexion</a>
      <?php else: ?>
        <a href="login.php" class="btn btn--outline btn--sm">Connexion</a>
        <a href="register.php" class="btn btn--primary btn--sm">S'inscrire</a>
      <?php endif; ?>
    </div>

  </header>

  <main>