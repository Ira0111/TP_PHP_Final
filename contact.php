<?php
require_once 'config.php';

$page_title = 'Contact';
$success    = false;
$errors     = [];
$fields     = ['nom' => '', 'email' => '', 'sujet' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields['nom']     = trim($_POST['nom']     ?? '');
    $fields['email']   = trim($_POST['email']   ?? '');
    $fields['sujet']   = trim($_POST['sujet']   ?? '');
    $fields['message'] = trim($_POST['message'] ?? '');

    // Pré-remplissage si connecté
    if (empty($fields['nom'])   && isset($_SESSION['user_nom']))   $fields['nom']   = $_SESSION['user_nom'];
    if (empty($fields['email']) && isset($_SESSION['user_email'])) $fields['email'] = $_SESSION['user_email'];

    // Validation
    if ($fields['nom'] === '')     $errors[] = 'Le nom est obligatoire.';
    if (!filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide.";
    if ($fields['sujet'] === '')   $errors[] = 'Le sujet est obligatoire.';
    if (strlen($fields['message']) < 10) $errors[] = 'Le message doit contenir au moins 10 caractères.';

    if (empty($errors)) {
        // Ici tu pourras ajouter mail() ou un enregistrement en BDD
        $success = true;
        $fields  = ['nom' => '', 'email' => '', 'sujet' => '', 'message' => ''];
    }
} else {
    // Pré-remplissage si connecté
    if (isset($_SESSION['user_nom']))   $fields['nom']   = $_SESSION['user_nom'];
}

include 'header.php';
?>

<section class="contact">
    <div class="container">

        <div class="contact__header">
            <h1 class="contact__title">Nous contacter</h1>
            <p class="contact__subtitle">
                Une question, un bug, une suggestion ? On te répond rapidement.
            </p>
        </div>

        <div class="contact__grid">

            <!-- Formulaire -->
            <div class="contact__form-wrap">

                <?php if ($success): ?>
                    <div class="alert alert--success">
                        ✓ Ton message a bien été envoyé. On te répond bientôt !
                    </div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>

                <form method="post" action="contact.php" class="contact__form">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input
                                type="text"
                                id="nom"
                                name="nom"
                                placeholder="Ton nom"
                                value="<?= htmlspecialchars($fields['nom']) ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="toi@email.com"
                                value="<?= htmlspecialchars($fields['email']) ?>"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <select id="sujet" name="sujet" required>
                            <option value="" disabled <?= $fields['sujet'] === '' ? 'selected' : '' ?>>
                                Choisis un sujet
                            </option>
                            <option value="bug" <?= $fields['sujet'] === 'bug'        ? 'selected' : '' ?>>🐛 Signaler un bug</option>
                            <option value="suggestion" <?= $fields['sujet'] === 'suggestion' ? 'selected' : '' ?>>💡 Suggestion</option>
                            <option value="question" <?= $fields['sujet'] === 'question'   ? 'selected' : '' ?>>❓ Question générale</option>
                            <option value="autre" <?= $fields['sujet'] === 'autre'      ? 'selected' : '' ?>>✉️ Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            placeholder="Écris ton message ici..."
                            required><?= htmlspecialchars($fields['message']) ?></textarea>
                    </div>

                    <button type="submit" class="btn-primary contact__submit">
                        Envoyer le message →
                    </button>

                </form>
            </div>

            <!-- Infos contact -->
            <aside class="contact__info">

                <div class="contact__info-card">
                    <div class="contact__info-icon">📧</div>
                    <div>
                        <h3 class="contact__info-title">Email</h3>
                        <p class="contact__info-text">contact@kultrack.com</p>
                    </div>
                </div>

                <div class="contact__info-card">
                    <div class="contact__info-icon">⏱️</div>
                    <div>
                        <h3 class="contact__info-title">Temps de réponse</h3>
                        <p class="contact__info-text">Sous 48h en général</p>
                    </div>
                </div>

                <div class="contact__info-card">
                    <div class="contact__info-icon">🐛</div>
                    <div>
                        <h3 class="contact__info-title">Bug urgent ?</h3>
                        <p class="contact__info-text">
                            Signale-le directement sur
                            <a href="https://github.com/Ira0111/TP_PHP_Final/issues" target="_blank">GitHub</a>
                        </p>
                    </div>
                </div>

            </aside>

        </div>

    </div>
</section>

<?php include 'footer.php'; ?>