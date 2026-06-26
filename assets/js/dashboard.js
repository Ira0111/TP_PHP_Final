/* ============================================================
   dashboard.js — Kultrack
   - Pas de pourcentage
   - Champs désactivés si statut ≠ "watching"
   - Logs préfixés [KUL] pour filtrer facilement dans la console
   ============================================================ */

console.log("[KUL] dashboard.js chargé");

/* ============================================================
   POPUP AVIS — fonctions globales (appelées depuis PHP inline)
   ============================================================ */

function openReviewEditPopup(reviewId, note, comment, mediaId) {
    console.log("[KUL] openReviewEditPopup()", { reviewId, note, comment, mediaId });

    const reviewPopup = document.getElementById('reviewEditPopup');
    if (!reviewPopup) {
        console.warn("[KUL] #reviewEditPopup introuvable");
        return;
    }

    document.getElementById('reviewPopupReviewId').value = reviewId || 0;
    document.getElementById('reviewPopupMediaId').value = mediaId || 0;
    document.getElementById('reviewPopupComment').value = comment || '';

    const reviewNoteInput = document.getElementById('reviewPopupNote');
    const reviewPopupTitle = document.getElementById('reviewPopupTitle');
    const submitBtn = document.getElementById('reviewPopupSubmit');

    if (reviewNoteInput) reviewNoteInput.value = note || 0;
    if (reviewPopupTitle) reviewPopupTitle.textContent = reviewId > 0 ? 'Modifier mon avis' : 'Laisser un avis';
    if (submitBtn) submitBtn.textContent = reviewId > 0 ? 'Mettre à jour' : 'Publier mon avis';

    updateReviewHearts(parseInt(note) || 0);
    reviewPopup.style.display = 'flex';
}

function closeReviewEditPopup() {
    console.log("[KUL] closeReviewEditPopup()");
    const reviewPopup = document.getElementById('reviewEditPopup');
    if (reviewPopup) reviewPopup.style.display = 'none';
}

function updateReviewHearts(value) {
    document.querySelectorAll('#reviewPopupRatingWidget .rating-heart').forEach(h => {
        const v = parseInt(h.dataset.value);
        h.src = v <= value
            ? './assets/icons/heart.png'
            : './assets/icons/heartvoid(light).png';
    });
}

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {

    console.log("[KUL] DOMContentLoaded — init popup progression");

    /* ── Éléments du popup ── */
    const updatePopup = document.getElementById('updatePopup');
    const form = document.getElementById('updateForm');
    const closeBtn = document.getElementById('popupClose');
    const errorBox = document.getElementById('popupError');
    const popupTitle = document.getElementById('popupTitle');

    // Vérification défensive : si le popup n'existe pas, on arrête tout
    if (!updatePopup || !form || !closeBtn || !popupTitle) {
        console.error("[KUL] Éléments du popup manquants dans le DOM", {
            updatePopup, form, closeBtn, popupTitle
        });
        return;
    }

    const fFollowId = document.getElementById('popupFollowId');
    const fType = document.getElementById('popupType');
    const fStatus = document.getElementById('popupStatus');
    const fTimecode = document.getElementById('popupTimecode');
    const fSeason = document.getElementById('popupSeason');
    const fEpisode = document.getElementById('popupEpisode');
    const fTimecodeSerie = document.getElementById('popupTimecodeSerie');
    const fSeasonA = document.getElementById('popupSeasonAnime');
    const fEpisodeA = document.getElementById('popupEpisodeAnime');
    const fTimecodeA = document.getElementById('popupTimecodeAnime');
    const fPage = document.getElementById('popupPage');

    const fieldGroups = document.querySelectorAll('.popup-field[data-field]');

    /* ── Affichage des champs selon le type de média ── */
    function showFieldsForType(type) {
        console.log("[KUL] showFieldsForType()", type);
        fieldGroups.forEach(group => {
            group.style.display = group.dataset.field === type ? 'block' : 'none';
        });
    }

    /* ── Activation / désactivation des champs selon le statut ──
       Règle : UNIQUEMENT "watching" autorise la saisie.
       Tous les autres statuts (on_hold, plan_to_watch, completed, dropped)
       désactivent tous les inputs de progression.
    ── */
    function updateFieldEditability() {
        const status = fStatus.value;
        const isWatching = status === "watching";

        console.log("[KUL] updateFieldEditability() — statut:", status, "| éditable:", isWatching);

        // On cible TOUS les inputs/selects dans les blocs .popup-field
        updatePopup.querySelectorAll(".popup-field input, .popup-field select").forEach(el => {
            el.disabled = !isWatching;
            // Feedback visuel optionnel : opacité réduite si désactivé
            el.style.opacity = isWatching ? '1' : '0.45';
            el.style.cursor = isWatching ? '' : 'not-allowed';
        });
    }

    /* Recalculer à chaque changement de statut */
    fStatus.addEventListener("change", () => {
        console.log("[KUL] Statut changé →", fStatus.value);
        updateFieldEditability();
    });

    /* ── Ouverture du popup depuis les boutons "Progression" ── */
    const editBtns = document.querySelectorAll('.media-card__edit');
    console.log("[KUL] Boutons .media-card__edit trouvés :", editBtns.length);

    editBtns.forEach(btn => {
        btn.addEventListener('click', () => {

            const followId = btn.dataset.followId;
            const type = btn.dataset.type;
            const title = btn.dataset.title;
            const status = btn.dataset.status;
            const detail = btn.dataset.detail || '';

            console.log("[KUL] Ouverture popup", { followId, type, title, status, detail });

            /* Titre + champs cachés */
            popupTitle.textContent = `Progression — ${title}`;
            fFollowId.value = followId;
            fType.value = type;

            fStatus.value = status;

            /* Remise à zéro de tous les champs */
            [fTimecode, fSeason, fEpisode, fTimecodeSerie,
                fSeasonA, fEpisodeA, fTimecodeA, fPage].forEach(el => {
                    if (el) el.value = '';
                });

            /* Pré-remplissage depuis le detail existant */
            if (type === 'film' && detail) {
                fTimecode.value = detail;
            }

            if (type === 'serie' && detail) {
                // Format attendu : "Saison 3 · Épisode 2" ou "Saison 3 · Épisode 2 · 12:34"
                const mSE = detail.match(/Saison\s*(\d+)\s*·\s*Épisode\s*(\d+)/i);
                if (mSE) { fSeason.value = mSE[1]; fEpisode.value = mSE[2]; }

                const mTC = detail.match(/Épisode\s*\d+\s*·\s*(.+)$/i);
                if (mTC) fTimecodeSerie.value = mTC[1].trim();
            }

            if (type === 'anime' && detail) {
                const mSE = detail.match(/Saison\s*(\d+)\s*·\s*Épisode\s*(\d+)/i);
                if (mSE) { fSeasonA.value = mSE[1]; fEpisodeA.value = mSE[2]; }

                const mTC = detail.match(/Épisode\s*\d+\s*·\s*(.+)$/i);
                if (mTC) fTimecodeA.value = mTC[1].trim();
            }

            if (type === 'livre' && detail) {
                const m = detail.match(/Page\s*(\d+)/i);
                if (m) fPage.value = m[1];
            }

            // Jeu : aucun champ de progression, rien à pré-remplir

            /* Affichage des bons champs + activation/désactivation */
            showFieldsForType(type);
            updateFieldEditability();     // ← appliqué APRÈS avoir posé fStatus.value

            errorBox.style.display = 'none';
            errorBox.textContent = '';
            updatePopup.style.display = 'flex';
        });
    });

    /* ── Fermeture ── */
    closeBtn.addEventListener('click', () => {
        updatePopup.style.display = 'none';
    });

    updatePopup.addEventListener('click', e => {
        if (e.target === updatePopup) updatePopup.style.display = 'none';
    });

    /* ── Soumission ── */
    form.addEventListener('submit', async e => {
        e.preventDefault();

        const type = fType.value;
        const status = fStatus.value;

        /* Validation front : si pas "watching", on envoie quand même le
           changement de statut (ex. passer de watching à completed),
           mais sans données de progression */
        const payload = {
            follow_id: fFollowId.value,
            type,
            status,
        };

        if (status === 'watching') {
            if (type === 'film') payload.timecode = fTimecode.value;
            if (type === 'serie') {
                payload.season = fSeason.value;
                payload.episode = fEpisode.value;
                payload.timecode_serie = fTimecodeSerie.value;
            }
            if (type === 'anime') {
                payload.season_anime = fSeasonA.value;
                payload.episode_anime = fEpisodeA.value;
                payload.timecode_anime = fTimecodeA.value;
            }
            if (type === 'livre') payload.page = fPage.value;
            // jeu : aucun champ
        }

        console.log("[KUL] Payload envoyé →", payload);

        try {
            const res = await fetch('models/Updateprogress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const raw = await res.text();
            console.log("[KUL] Réponse brute →", raw);

            let json;
            try {
                json = JSON.parse(raw);
            } catch (parseErr) {
                console.error("[KUL] Réponse non-JSON :", raw);
                errorBox.textContent = "Réponse inattendue du serveur (voir console).";
                errorBox.style.display = 'block';
                return;
            }

            if (json.success) {
                console.log("[KUL] Mise à jour OK");
                window.location.reload();
            } else {
                console.warn("[KUL] Erreur serveur :", json.error);
                errorBox.textContent = json.error || "Erreur inconnue.";
                errorBox.style.display = 'block';
            }

        } catch (netErr) {
            console.error("[KUL] Erreur réseau :", netErr);
            errorBox.textContent = "Erreur réseau. Vérifie ta connexion.";
            errorBox.style.display = 'block';
        }
    });

});