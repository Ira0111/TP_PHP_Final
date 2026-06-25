<?php
require_once 'config.php';

// Déjà connecté → accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$page_title = 'Connexion';
$errors     = [];
$email      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    $result = $userController->login($email, $motDePasse);

    if ($result['success']) {
        header('Location: index.php');
        exit;
    }

    $errors = $result['errors'];
}

include 'header.php';
?>

<section class="auth">
    <div class="auth__card">

        <h1 class="auth__title">Connexion</h1>
        <p class="auth__subtitle">Content de te revoir sur Kultrack.</p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="login.php" class="auth__form">

            <div class="form-group">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="toi@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                    autofocus>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    placeholder="••••••••"
                    required>
            </div>

            <button type="submit" class="btn-primary auth__submit">Se connecter</button>

        </form>

        <p class="auth__switch">
            Pas encore de compte ?
            <a href="register.php">Inscris-toi</a>
        </p>

    </div>
</section>

<?php include 'footer.php'; ?>