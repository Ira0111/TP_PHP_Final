const API_KEYS = {
  tmdb: 'f442994667b277e5713a208e0efef0e3',
  rawg: 'a02d70de4ce242308e5d60335a4e7479',
  books: 'AIzaSyDMuRrZ1LKaDFnZ13NPPV2V0yJ63to_tUo',
};

// Variable globale pour mémoriser les informations récoltées depuis l'API externe
let currentMediaData = null;

/* ─────────────────────────────────────────────
   Point d'entrée
───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    console.log("[KUL] Initialisation de la page. Type :", MEDIA_TYPE, "| ID :", MEDIA_ID);
    const data = await fetchMedia(MEDIA_TYPE, MEDIA_ID);

    // Sauvegarde globale des données récoltées
    currentMediaData = data;
    console.log("[KUL] Données de l'API sauvegardées en global :", currentMediaData);

    renderMedia(MEDIA_TYPE, data);
    initFollowBtn();
    await loadCommunityData(MEDIA_TYPE, MEDIA_ID);
    await checkIfFollowed(MEDIA_TYPE, MEDIA_ID);
  } catch (e) {
    console.error('[KUL] media.js error:', e);
    showError();
  }
});

/* ─────────────────────────────────────────────
   Helpers
───────────────────────────────────────────── */
function isEnglish(text) {
  if (!text) return false;
  const words = ['the', 'and', 'with', 'from', 'this', 'that', 'for', 'movie', 'series', 'story', 'game', 'book'];
  const lower = text.toLowerCase();
  return words.filter(w => lower.includes(w)).length >= 2;
}

function cleanText(text) {
  return text.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim();
}

async function translateToFrench(text) {
  try {
    const res = await fetch('translate.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'text=' + encodeURIComponent(text),
    });
    const data = await res.json();
    return data.translations?.[0]?.text || text;
  } catch {
    return text;
  }
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (!el) return;
  if (val) { el.textContent = val; }
  else { el.style.display = 'none'; }
}

function showError() {
  const loader = document.getElementById('media-loader');
  const errBox = document.getElementById('media-error');
  if (loader) loader.style.display = 'none';
  if (errBox) errBox.style.display = 'block';
}

/* ─────────────────────────────────────────────
   Fetch selon le type
───────────────────────────────────────────── */
async function fetchMedia(type, id) {
  switch (type) {
    case 'film': return fetchTMDB('movie', id);
    case 'serie': return fetchTMDB('tv', id);
    case 'anime': return fetchTMDB('tv', id);
    case 'jeu': return fetchRAWG(id);
    case 'livre': return fetchBooks(id);
    default: throw new Error('Type inconnu : ' + type);
  }
}

/* ── TMDB ── */
async function fetchTMDB(tmdbType, id) {
  const res = await fetch(
    `https://api.themoviedb.org/3/${tmdbType}/${id}?api_key=${API_KEYS.tmdb}&language=fr-FR`
  );
  if (!res.ok) throw new Error('TMDB error');
  const d = await res.json();

  console.log("[KUL] Réponse brute TMDB reçue :", d);

  let overview = d.overview || 'Aucune description disponible.';
  if (isEnglish(overview)) overview = await translateToFrench(overview);

  return {
    title: d.title || d.name,
    poster: d.poster_path ? `https://image.tmdb.org/t/p/w500${d.poster_path}` : 'assets/icons/filmL.png',
    year: (d.release_date || d.first_air_date || '').slice(0, 4),
    genre: (d.genres || []).map(g => g.name).join(', '),
    overview,
    extra: tmdbType === 'movie'
      ? (d.runtime ? d.runtime + ' min' : '')
      : `${d.number_of_seasons ?? '?'} saison(s) · ${d.number_of_episodes ?? '?'} épisodes`,
    extraInfo: buildTMDBExtra(d, tmdbType),
    durationMinutes: tmdbType === 'movie' ? (d.runtime || null) : null,
    // Permet une double compatibilité de secours sur les noms des propriétés
    totalSeasons: d.number_of_seasons || null,
    totalEpisodes: d.number_of_episodes || null,
    number_of_seasons: d.number_of_seasons || null,
    number_of_episodes: d.number_of_episodes || null
  };
}

function buildTMDBExtra(d, tmdbType) {
  const rows = [];
  if (d.production_countries?.length)
    rows.push(['Pays', d.production_countries.map(c => c.name).join(', ')]);
  if (d.spoken_languages?.length)
    rows.push(['Langue(s)', d.spoken_languages.map(l => l.name).join(', ')]);
  if (tmdbType === 'movie' && d.budget)
    rows.push(['Budget', '$' + d.budget.toLocaleString('fr-FR')]);
  if (d.status)
    rows.push(['Statut', d.status]);
  return rows;
}

/* ── RAWG ── */
async function fetchRAWG(id) {
  const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${API_KEYS.rawg}`);
  if (!res.ok) throw new Error('RAWG error');
  const d = await res.json();

  let overview = cleanText(d.description_raw || 'Aucune description disponible.');
  if (isEnglish(overview)) overview = await translateToFrench(overview);

  return {
    title: d.name,
    poster: d.background_image || 'assets/icons/jeuL.png',
    year: (d.released || '').slice(0, 4),
    genre: (d.genres || []).map(g => g.name).join(', '),
    overview,
    extra: (d.platforms || []).map(p => p.platform.name).join(' · '),
    extraInfo: buildRAWGExtra(d),
  };
}

function buildRAWGExtra(d) {
  const rows = [];
  if (d.developers?.length) rows.push(['Développeur(s)', d.developers.map(x => x.name).join(', ')]);
  if (d.publishers?.length) rows.push(['Éditeur(s)', d.publishers.map(x => x.name).join(', ')]);
  if (d.metacritic) rows.push(['Metacritic', d.metacritic + ' / 100']);
  return rows;
}

/* ── Google Books ── */
async function fetchBooks(id) {
  const res = await fetch(
    `https://www.googleapis.com/books/v1/volumes/${id}?key=${API_KEYS.books}`
  );
  if (!res.ok) throw new Error('Books error');
  const d = await res.json();
  const i = d.volumeInfo || {};

  let overview = i.description || 'Aucune description disponible.';
  if (isEnglish(overview)) overview = await translateToFrench(overview);

  return {
    title: i.title || 'Sans titre',
    poster: (i.imageLinks?.thumbnail || 'assets/icons/livreL.png').replace('http://', 'https://'),
    year: (i.publishedDate || '').slice(0, 4),
    genre: (i.categories || []).join(', '),
    overview,
    extra: i.pageCount ? i.pageCount + ' pages' : '',
    extraInfo: buildBooksExtra(i),
    totalPages: i.pageCount || null,
    pageCount: i.pageCount || null
  };
}

function buildBooksExtra(i) {
  const rows = [];
  if (i.authors?.length) rows.push(['Auteur(s)', i.authors.join(', ')]);
  if (i.publisher) rows.push(['Éditeur', i.publisher]);
  if (i.language) rows.push(['Langue', i.language.toUpperCase()]);
  const isbn = (i.industryIdentifiers || []).find(x => x.type === 'ISBN_13');
  if (isbn) rows.push(['ISBN', isbn.identifier]);
  return rows;
}

/* ─────────────────────────────────────────────
   Rendu HTML
───────────────────────────────────────────── */
const TYPE_LABELS = { film: 'Film', serie: 'Série', anime: 'Animé', jeu: 'Jeu', livre: 'Livre' };

function renderMedia(type, data) {
  document.getElementById('media-poster').src = data.poster;
  document.getElementById('media-poster').alt = data.title;

  const badge = document.getElementById('media-type-badge');
  badge.textContent = TYPE_LABELS[type] || type;
  badge.className = `media-badge media-badge--${type}`;

  document.getElementById('breadcrumb-type').textContent = TYPE_LABELS[type];
  document.getElementById('breadcrumb-title').textContent = data.title;
  document.getElementById('media-title').textContent = data.title;
  document.title = `${data.title} — Kultrack`;

  setText('media-year', data.year);
  setText('media-genre', data.genre);
  setText('media-extra', data.extra);
  document.getElementById('media-overview').textContent = data.overview;

  if (data.extraInfo?.length) {
    const table = document.createElement('table');
    table.className = 'media-extra-table';
    data.extraInfo.forEach(([label, value]) => {
      const tr = table.insertRow();
      tr.insertCell().textContent = label;
      tr.insertCell().textContent = value;
    });
    document.getElementById('media-extra-info').appendChild(table);
  }

  document.getElementById('media-loader').style.display = 'none';
  document.getElementById('media-content').style.display = 'flex';

  // Stocke pour le bouton follow
  window._mediaData = {
    type, id: MEDIA_ID,
    title: data.title,
    poster: data.poster,
    year: data.year,
    durationMinutes: data.durationMinutes || null,
    totalSeasons: data.totalSeasons || data.number_of_seasons || null,
    totalEpisodes: data.totalEpisodes || data.number_of_episodes || null,
    totalPages: data.totalPages || data.pageCount || null,
  };
}

/* ─────────────────────────────────────────────
   Suivi (follow)
───────────────────────────────────────────── */
function initFollowBtn() {
  const btn = document.getElementById('follow-btn');
  const select = document.getElementById('follow-status');
  const msg = document.getElementById('follow-msg');
  if (!btn || !USER_ID) return;

  btn.addEventListener('click', async () => {
    const status = select.value;
    if (!status) { showMsg(msg, 'Choisis un statut d\'abord.', 'error'); return; }

    btn.disabled = true;
    btn.textContent = 'Enregistrement…';

    let totalSeasons = null;
    let totalEpisodes = null;
    let totalPages = null;

    // Analyse approfondie de la source globale ou de la structure window secondaire
    if (currentMediaData) {
      if (MEDIA_TYPE === 'serie' || MEDIA_TYPE === 'anime') {
        totalSeasons = currentMediaData.totalSeasons || currentMediaData.number_of_seasons || null;
        totalEpisodes = currentMediaData.totalEpisodes || currentMediaData.number_of_episodes || null;
      } else if (MEDIA_TYPE === 'livre') {
        totalPages = currentMediaData.totalPages || currentMediaData.pageCount || null;
      }
    }

    // Sécurité de secours via l'objet window._mediaData si configuré par le render
    if (!totalSeasons && window._mediaData) {
      totalSeasons = window._mediaData.totalSeasons || null;
      totalEpisodes = window._mediaData.totalEpisodes || null;
      totalPages = window._mediaData.totalPages || null;
    }

    const payload = {
      api_id: MEDIA_ID,
      api_source: getApiSource(MEDIA_TYPE),
      type: MEDIA_TYPE,
      title: currentMediaData?.title || window._mediaData?.title || '',
      poster: currentMediaData?.poster || window._mediaData?.poster || '',
      status,
      total_seasons: totalSeasons,
      total_episodes: totalEpisodes,
      total_pages: totalPages
    };

    console.log("[KUL] Clic bouton suivi. Payload envoyé ->", payload);

    try {
      const res = await fetch('followAction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      console.log("[KUL] Réponse reçue de followAction.php :", json);

      if (json.success) {
        showMsg(msg, '✓ Ajouté à ta liste !', 'success');
        enableReviewForm();
      } else {
        showMsg(msg, json.error || 'Erreur serveur.', 'error');
      }
    } catch (err) {
      console.error("[KUL] Erreur réseau lors de l'enregistrement :", err);
      showMsg(msg, 'Erreur réseau.', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = 'Enregistrer';
    }
  });
}

async function checkIfFollowed(type, id) {
  if (!USER_ID) return;
  try {
    const res = await fetch(`models/checkFollow.php?type=${type}&id=${id}`);
    const data = await res.json();
    if (data.follow) {
      const select = document.getElementById('follow-status');
      if (select && data.status) select.value = data.status;
      enableReviewForm();
    }
  } catch { /* silencieux */ }
}

function getApiSource(type) {
  if (['film', 'serie', 'anime'].includes(type)) return 'tmdb';
  if (type === 'jeu') return 'rawg';
  if (type === 'livre') return 'google_books';
  return 'unknown';
}

function showMsg(el, text, cls) {
  el.textContent = text;
  el.className = `follow-msg follow-msg--${cls}`;
  el.style.display = 'inline-block';
  setTimeout(() => { el.style.display = 'none'; }, 4000);
}

/* ─────────────────────────────────────────────
   Formulaire review — activer / désactiver
───────────────────────────────────────────── */
function enableReviewForm() {
  const form = document.getElementById('review-form');
  const hint = document.getElementById('review-hint');
  const btn = document.getElementById('review-submit');
  if (!form) return;
  form.style.display = '';
  if (hint) hint.style.display = 'none';
  if (btn) btn.disabled = false;
}

function disableReviewForm() {
  const form = document.getElementById('review-form');
  const hint = document.getElementById('review-hint');
  const btn = document.getElementById('review-submit');
  if (!form) return;
  form.style.display = 'none';
  if (hint) { hint.style.display = ''; }
  if (btn) btn.disabled = true;
}

/* ─────────────────────────────────────────────
   Données communauté (note + commentaires)
───────────────────────────────────────────── */
async function loadCommunityData(type, id) {
  const ratingEl = document.getElementById('community-rating');
  const commentsEl = document.getElementById('community-comments');
  const section = document.getElementById('reviews-section');

  try {
    const res = await fetch(`models/getCommunityData.php?type=${type}&id=${encodeURIComponent(id)}&t=${Date.now()}`);
    const data = await res.json();
    console.log("[KUL] Données communauté reçues :", data);

    document.getElementById("reviews-section").style.display = "block";
    if (section) section.style.display = '';

    if (ratingEl) {
      if (data.rating && data.rating.total > 0) {
        const avg = parseFloat(data.rating.avg_note).toFixed(1);
        const total = data.rating.total;
        ratingEl.innerHTML = `${renderHearts(avg, 5)} <span>${avg}/5 (${total} avis)</span>`;
        ratingEl.style.display = '';
      } else {
        ratingEl.style.display = 'none';
      }
    }

    if (USER_ID) {
      if (data.media_id) {
        enableReviewForm();
      } else {
        disableReviewForm();
      }
    }

    if (USER_ID && data.comments.length) {
      const mine = data.comments.find(c => c.is_mine);
      if (mine) prefillReviewForm(mine);
    }

    if (!commentsEl) return;

    if (!data.comments.length) {
      commentsEl.innerHTML = '<p class="reviews-empty">Aucun avis pour le moment. Sois le premier !</p>';
      return;
    }

    commentsEl.innerHTML = data.comments.map(c => `
      <div class="review-card">
          <div class="review-card__header">
              <span class="review-card__avatar">
                  ${c.is_mine && USER_INITIALS ? USER_INITIALS : c.username.charAt(0).toUpperCase()}
              </span>
              <div class="review-card__meta">
                  <span class="review-card__date">${c.created_at}</span>
              </div>
              <div class="review-card__hearts">
                  ${renderHearts(c.note, 5)}
              </div>
          </div>
          ${c.comment ? `
              <p class="review-card__comment">${escHtml(c.comment)}</p>
          ` : ''}
          ${(c.is_mine || IS_ADMIN) ? `
              <div class="review-card__actions">
                  <button type="button" class="btn-edit"
                      onclick="openEditReview(${c.review_id}, ${c.note}, \`${escHtml(c.comment)}\`)">
                      Modifier
                  </button>
                  <form action="review_delete.php" method="post" class="review-card__delete">
                      <input type="hidden" name="review_id" value="${c.review_id}">
                      <input type="hidden" name="api_id"    value="${MEDIA_ID}">
                      <input type="hidden" name="type"      value="${MEDIA_TYPE}">
                      <button type="submit" class="btn-delete"
                          onclick="return confirm('Supprimer cet avis ?')">
                          Supprimer
                      </button>
                  </form>
              </div>
          ` : ''}
      </div>
    `).join('');

  } catch (err) {
    console.error('[KUL] loadCommunityData error:', err);
    if (ratingEl) ratingEl.style.display = 'none';
    if (commentsEl) commentsEl.innerHTML = '<p>Impossible de charger les avis.</p>';
    if (section) section.style.display = '';
  }
}

function openEditReview(id, note, comment) {
  console.log("[KUL] Ouverture popup modification avis.");
  const popup = document.getElementById("editReviewPopup");
  document.getElementById("popupReviewId").value = id;
  document.getElementById("popupNote").value = note;
  document.getElementById("popupComment").value = comment;
  updatePopupHearts(note);
  popup.style.display = "flex";
}

function closeEditReview() {
  document.getElementById("editReviewPopup").style.display = "none";
}

function updatePopupHearts(note) {
  const hearts = document.querySelectorAll("#popupRatingWidget .rating-heart");
  hearts.forEach(h => {
    const value = parseInt(h.dataset.value);
    h.src = value <= note
      ? "./assets/icons/heart.png"
      : "./assets/icons/heartvoid(light).png";
  });
}

document.querySelectorAll("#popupRatingWidget .rating-heart").forEach(h => {
  h.addEventListener("click", () => {
    const value = parseInt(h.dataset.value);
    document.getElementById("popupNote").value = value;
    updatePopupHearts(value);
  });
});

function prefillReviewForm(review) {
  const formTitle = document.getElementById('review-form-title');
  const submit = document.getElementById('review-submit');
  const textarea = document.querySelector('#review-form textarea');
  const radio = document.querySelector(`#review-form input[name=\"note\"][value=\"${review.note}\"]`);

  if (formTitle) formTitle.textContent = 'Modifier mon avis';
  if (submit) submit.textContent = 'Mettre à jour';
  if (textarea) textarea.value = review.comment;
  if (radio) radio.checked = true;
}

function renderHearts(note, max = 5) {
  const n = parseFloat(note);
  let html = '';
  for (let i = 1; i <= max; i++) {
    html += `<span class="${i <= Math.round(n) ? 'heart-full' : 'heart-empty'}">♥</span>`;
  }
  return html;
}

function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}