<div id="updatePopup" class="popup">
    <div class="popup-content">

        <button type="button" id="popupClose" class="popup-close" aria-label="Fermer">&times;</button>
        <h2 id="popupTitle">Modifier la progression</h2>
        <div id="popupError" class="alert alert--error" style="display:none"></div>

        <form id="updateForm">
            <input type="hidden" id="popupFollowId">
            <input type="hidden" id="popupType">

            <!-- Statut -->
            <div class="form-group">
                <label for="popupStatus">Statut</label>
                <select id="popupStatus">
                    <option value="watching">En cours</option>
                    <option value="on_hold">En pause</option>
                    <option value="plan_to_watch">À voir / jouer / lire</option>
                    <option value="completed">Terminé ✓</option>
                    <option value="dropped">Abandonné</option>
                </select>
            </div>

            <!-- Film -->
            <div class="popup-field" data-field="film">
                <div class="form-group">
                    <label for="popupTimecode">Temps visionné</label>
                    <input type="text" id="popupTimecode" placeholder="ex : 1h23 ou 83">
                    <small class="form-hint">Format accepté : 1h23 · 1:23:00 · ou minutes (ex : 83)</small>
                </div>
            </div>

            <!-- Série -->
            <div class="popup-field" data-field="serie">
                <div class="form-row">
                    <div class="form-group">
                        <label for="popupSeason">Saison</label>
                        <input type="number" id="popupSeason" min="1" placeholder="ex : 2">
                    </div>
                    <div class="form-group">
                        <label for="popupEpisode">Épisode</label>
                        <input type="number" id="popupEpisode" min="1" placeholder="ex : 5">
                    </div>
                </div>
                <div class="form-group">
                    <label for="popupTimecodeSerie">Timecode épisode <span class="form-hint">(optionnel)</span></label>
                    <input type="text" id="popupTimecodeSerie" placeholder="ex : 12:34">
                </div>
            </div>

            <!-- Animé -->
            <div class="popup-field" data-field="anime">
                <div class="form-row">
                    <div class="form-group">
                        <label for="popupSeasonAnime">Saison</label>
                        <input type="number" id="popupSeasonAnime" min="1" placeholder="ex : 1">
                    </div>
                    <div class="form-group">
                        <label for="popupEpisodeAnime">Épisode</label>
                        <input type="number" id="popupEpisodeAnime" min="1" placeholder="ex : 12">
                    </div>
                </div>
                <div class="form-group">
                    <label for="popupTimecodeAnime">Timecode épisode <span class="form-hint">(optionnel)</span></label>
                    <input type="text" id="popupTimecodeAnime" placeholder="ex : 12:34">
                </div>
            </div>

            <!-- Jeu — aucun champ de progression, le statut suffit -->
            <div class="popup-field" data-field="jeu">
                <p class="form-hint" style="margin-top: 0.5rem">
                    Aucune progression détaillée pour les jeux. Le statut suffit.
                </p>
            </div>

            <!-- Livre -->
            <div class="popup-field" data-field="livre">
                <div class="form-group">
                    <label for="popupPage">Page actuelle</label>
                    <input type="number" id="popupPage" min="1" placeholder="ex : 120">
                </div>
            </div>

            <button id="popupSubmit" type="submit" class="btn-primary">Enregistrer</button>
        </form>

    </div>
</div>