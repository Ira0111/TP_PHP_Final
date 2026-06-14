<?php
require_once 'config.php';

$page_title = 'Inscription';
$errors    = [];
$firstName = '';
$lastName  = '';
$email     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName    = trim($_POST['first_name'] ?? '');
    $lastName     = trim($_POST['last_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $motDePasse   = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    $result = $userController->register($firstName, $lastName, $email, $motDePasse, $confirmation);

    if ($result['success']) {
        // Connexion automatique après inscription
        $userController->login($email, $motDePasse);
        header('Location: index.php');
        exit;
    }

    $errors = $result['errors'];
}

include 'header.php';
?>

<section class="auth">
    <div class="auth__card">
        <h1 class="auth__title">Créer un compte</h1>
        <p class="auth__subtitle">Rejoins Kultrack et commence à tracer ta culture.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="register.php" class="auth__form">

            <div class="form-group">
                <label for="first_name">Prénom</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    placeholder="Ton prénom"
                    value="<?= htmlspecialchars($firstName) ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="last_name">Nom</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    placeholder="Ton nom"
                    value="<?= htmlspecialchars($lastName) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="toi@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    placeholder="8 caractères minimum"
                    required
                    minlength="8"
                >
            </div>

            <div class="form-group">
                <label for="confirmation">Confirmer le mot de passe</label>
                <input
                    type="password"
                    id="confirmation"
                    name="confirmation"
                    placeholder="••••••••"
                    required
                    minlength="8"
                >
            </div>

            <button type="submit" class="btn-primary auth__submit">Créer mon compte</button>
        </form>

        <p class="auth__switch">
            Déjà un compte ? <a href="login.php">Connecte-toi</a>
        </p>
    </div>
</section>

<?php include 'footer.php'; ?>