<?php
require_once 'config.php';

// Sécurité : On vérifie que l'utilisateur est connecté et qu'il est bien admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$page_title = 'Gestion des Médias — Admin';
include 'header.php';

global $pdo;

// 1. Récupération de tous les médias existants
try {
    $stmt = $pdo->query("SELECT * FROM media ORDER BY media_id DESC");
    $medias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de récupération : " . $e->getMessage());
}

// 2. Gestion des messages de succès ou d'erreur (provenant des redirections)
$status = $_GET['status'] ?? null;
$message = '';
if ($status === 'added') $message = "Le média a bien été ajouté !";
if ($status === 'updated') $message = "Le média a bien été modifié !";
if ($status === 'deleted') $message = "Le média a bien été supprimé !";
if ($status === 'error') $message = "Une erreur est survenue lors de l'opération.";
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 4rem; color: #fff;">
    <h1 style="margin-bottom: 2rem; font-size: 2.5rem;">🛠️ Administration des Médias</h1>

    <?php if (!empty($message)): ?>
        <div style="background: <?= $status === 'error' ? '#ff4757' : '#2ed573' ?>; color: #fff; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; font-weight: bold;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 3rem; align-items: start;">
        
        <section style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); padding: 2rem; border-radius: 8px;">
            <h2 id="form-title" style="margin-bottom: 1.5rem; font-size: 1.5rem; color: #ff4757;">Ajouter un nouveau média</h2>
            
            <form action="admin_action.php" method="post" id="media-form" style="display: flex; flex-direction: column; gap: 1.2rem;">
                <input type="hidden" name="media_id" id="form-media-id" value="">

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label for="form-title-input" style="font-weight: 500;">Titre de l'œuvre</label>
                    <input type="text" name="title" id="form-title-input" placeholder="Ex: House of the Dragon" required 
                           style="background: #121212; border: 1px solid rgba(255,255,255,0.2); padding: 0.8rem; border-radius: 4px; color: #fff; width: 100%;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label for="form-type" style="font-weight: 500;">Type de média</label>
                    <select name="type" id="form-type" required 
                            style="background: #121212; border: 1px solid rgba(255,255,255,0.2); padding: 0.8rem; border-radius: 4px; color: #fff; width: 100%;">
                        <option value="film">Film</option>
                        <option value="serie">Série</option>
                        <option value="anime">Animé</option>
                        <option value="jeu">Jeu vidéo</option>
                        <option value="livre">Livre</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label for="form-api-id" style="font-weight: 500;">ID de l'API externe (api_id)</label>
                    <input type="text" name="api_id" id="form-api-id" placeholder="Ex: 115036" required 
                           style="background: #121212; border: 1px solid rgba(255,255,255,0.2); padding: 0.8rem; border-radius: 4px; color: #fff; width: 100%;">
                    <small style="color: #888;">ID TMDB pour films/séries, ID RAWG pour jeux, ID Google Books pour livres.</small>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" name="action" value="save" class="btn-primary" id="submit-btn" style="flex: 1; padding: 0.8rem; cursor: pointer;">
                        Enregistrer
                    </button>
                    <button type="button" id="cancel-btn" style="display: none; background: #333; color: #fff; border: none; padding: 0.8rem; border-radius: 4px; cursor: pointer;" onclick="resetForm()">
                        Annuler
                    </button>
                </div>
            </form>
        </section>

        <section style="background: rgba(255,255,255,0.01); border: 1px solid rgba(255,255,255,0.05); padding: 2rem; border-radius: 8px;">
            <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">📋 Catalogue existant (<?= count($medias) ?>)</h2>
            
            <?php if (empty($medias)): ?>
                <p style="color: #888;">Aucun média dans la base de données actuellement.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(255,255,255,0.1); color: #888;">
                                <th style="padding: 0.8rem;">ID</th>
                                <th style="padding: 0.8rem;">Titre</th>
                                <th style="padding: 0.8rem;">Type</th>
                                <th style="padding: 0.8rem;">API ID</th>
                                <th style="padding: 0.8rem; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medias as $m): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='none'">
                                    <td style="padding: 0.8rem; color: #888;"><?= $m['media_id'] ?></td>
                                    <td style="padding: 0.8rem; font-weight: bold;"><?= htmlspecialchars($m['title']) ?></td>
                                    <td style="padding: 0.8rem;"><span style="background: rgba(255,255,255,0.1); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($m['type']) ?></span></td>
                                    <td style="padding: 0.8rem; color: #ff4757; font-family: monospace;"><?= htmlspecialchars($m['api_id']) ?></td>
                                    <td style="padding: 0.8rem; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                        
                                        <button class="btn-outline btn-xs" 
                                                onclick="editMedia(<?= $m['media_id'] ?>, '<?= htmlspecialchars(addslashes($m['title'])) ?>', '<?= $m['type'] ?>', '<?= htmlspecialchars($m['api_id']) ?>')"
                                                style="cursor: pointer; padding: 0.4rem 0.8rem;">
                                            ✏️ Éditer
                                        </button>

                                        <form action="admin_action.php" method="post" onsubmit="return confirm('Es-tu sûr de vouloir supprimer définitivement ce média ?');" style="display: inline;">
                                            <input type="hidden" name="media_id" value="<?= $m['media_id'] ?>">
                                            <button type="submit" name="action" value="delete" class="btn-primary btn-xs" style="background: #ff4757; cursor: pointer; padding: 0.4rem 0.8rem;">
                                                🗑️ Supprimer
                                            </button>
                                        </form>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<script>
// Fonction appelée au clic sur "Éditer" pour faire passer le formulaire en mode Modification
function editMedia(id, title, type, apiId) {
    document.getElementById('form-title').textContent = "✏️ Modifier le média #" + id;
    document.getElementById('form-media-id').value = id;
    document.getElementById('form-title-input').value = title;
    document.getElementById('form-type').value = type;
    document.getElementById('form-api-id').value = apiId;
    
    document.getElementById('submit-btn').textContent = "Mettre à jour";
    document.getElementById('cancel-btn').style.display = "inline-block";
    
    // Défilement fluide vers le formulaire
    document.getElementById('media-form').scrollIntoView({ behavior: 'smooth' });
}

// Fonction pour réinitialiser le formulaire en mode "Ajout" d'origine
function resetForm() {
    document.getElementById('form-title').textContent = "Ajouter un nouveau média";
    document.getElementById('form-media-id').value = "";
    document.getElementById('media-form').reset();
    
    document.getElementById('submit-btn').textContent = "Enregistrer";
    document.getElementById('cancel-btn').style.display = "none";
}
</script>

<?php include 'footer.php'; ?>