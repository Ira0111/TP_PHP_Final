<div class="popup" id="reviewEditPopup">
    <div class="popup-content">

        <button type="button" class="popup-close" onclick="closeReviewEditPopup()">&times;</button>
        <h2 id="reviewPopupTitle">Laisser un avis</h2>

        <form action="review_update.php" method="post" id="reviewEditForm">

            <input type="hidden" name="media_id" id="reviewPopupMediaId">
            <input type="hidden" name="review_id" id="reviewPopupReviewId">
            <input type="hidden" name="note" id="reviewPopupNote" value="0">

            <!-- Sélecteur de cœurs -->
            <div class="form-group">
                <label>Note</label>
                <div class="heart-rating heart-rating--popup" id="reviewPopupRatingWidget">
                    <img data-value="1" src="./assets/icons/heartvoid(light).png"
                        alt="1 cœur" class="rating-heart" width="28">
                    <img data-value="2" src="./assets/icons/heartvoid(light).png"
                        alt="2 cœurs" class="rating-heart" width="28">
                    <img data-value="3" src="./assets/icons/heartvoid(light).png"
                        alt="3 cœurs" class="rating-heart" width="28">
                    <img data-value="4" src="./assets/icons/heartvoid(light).png"
                        alt="4 cœurs" class="rating-heart" width="28">
                    <img data-value="5" src="./assets/icons/heartvoid(light).png"
                        alt="5 cœurs" class="rating-heart" width="28">
                </div>
                <small class="form-hint review-note-error" id="reviewNoteError"
                    style="display:none;color:#CC2222;">Sélectionne une note avant de publier.</small>
            </div>

            <div class="form-group">
                <label for="reviewPopupComment">Commentaire (facultatif)</label>
                <textarea name="comment" id="reviewPopupComment" rows="4"
                    placeholder="Partage ton avis…"></textarea>
            </div>

            <button type="submit" class="btn-primary" id="reviewPopupSubmit">
                Publier mon avis
            </button>

        </form>
    </div>
</div>