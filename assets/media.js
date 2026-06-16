const API_KEYS = {
  tmdb: 'f442994667b277e5713a208e0efef0e3',
  rawg: 'a02d70de4ce242308e5d60335a4e7479',
  books: 'AIzaSyDMuRrZ1LKaDFnZ13NPPV2V0yJ63to_tUo'
};

function isEnglish(text) {
  if (!text) return false;
  const common = ["the", "and", "with", "from", "this", "that", "for", "movie", "series", "story", "game", "book"];
  const lower = text.toLowerCase();
  let count = 0;
  common.forEach(w => { if (lower.includes(w)) count++; });
  return count >= 2;
}

function cleanText(text) {
  return text
    .replace(/<[^>]+>/g, '')   // supprime les balises HTML
    .replace(/\s+/g, ' ')      // nettoie les espaces
    .trim();
}

async function translateToFrench(text) {
  try {
    const res = await fetch("translate.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "text=" + encodeURIComponent(text)
    });

    const data = await res.json();
    return data.translations?.[0]?.text || text;
  } catch (err) {
    console.error("Erreur traduction", err);
    return text;
  }
}

/* ─────────────────────────────────────────────
   Point d'entrée
───────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const data = await fetchMedia(MEDIA_TYPE, MEDIA_ID);
    renderMedia(MEDIA_TYPE, data);
    initFollowBtn();
  } catch (e) {
    showError();
  }
});

/* ─────────────────────────────────────────────
   Fetch selon le type
───────────────────────────────────────────── */
async function fetchMedia(type, id) {
  switch (type) {
    case 'film': return fetchTMDB('movie', id);
    case 'serie': return fetchTMDB('tv', id);
    case 'anime': return fetchTMDB('tv', id);   // animés = TV sur TMDB
    case 'jeu': return fetchRAWG(id);
    case 'livre': return fetchBooks(id);
    default: throw new Error('Type inconnu');
  }
}

/* ─── TMDB (films + séries + animés) ─── */
async function fetchTMDB(tmdbType, id) {
  const lang = 'fr-FR';
  const base = 'https://api.themoviedb.org/3';
  const apiKey = API_KEYS.tmdb;

  const res = await fetch(`${base}/${tmdbType}/${id}?api_key=${apiKey}&language=${lang}`);
  if (!res.ok) throw new Error('TMDB error');
  const d = await res.json();

  return {
    title: d.title || d.name,
    poster: d.poster_path ? `https://image.tmdb.org/t/p/w500${d.poster_path}` : 'assets/icons/filmL.png',
    year: (d.release_date || d.first_air_date || '').slice(0, 4),
    genre: d.genres?.map(g => g.name).join(', ') || '',
    overview: await (async () => {
      const text = d.overview || 'Aucune description disponible.';
      return isEnglish(text) ? await translateToFrench(text) : text;
    })(),

    extra: tmdbType === 'movie'
      ? `${d.runtime ? d.runtime + ' min' : ''}`
      : `${d.number_of_seasons ?? ''} saison(s) · ${d.number_of_episodes ?? ''} épisodes`,
    extraInfo: buildTMDBExtra(d, tmdbType),
    vote: d.vote_average ? `${d.vote_average.toFixed(1)} / 10` : null,
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

/* ─── RAWG (jeux vidéo) ─── */
async function fetchRAWG(id) {
  const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${API_KEYS.rawg}`);
  if (!res.ok) throw new Error('RAWG error');
  const d = await res.json();
  return {
    title: d.name,
    poster: d.background_image || 'assets/icons/jeuL.png',
    year: (d.released || '').slice(0, 4),
    genre: d.genres?.map(g => g.name).join(', ') || '',

    overview: await (async () => {
      const raw = d.description_raw || 'Aucune description disponible.';
      const text = cleanText(raw);
      return isEnglish(text) ? await translateToFrench(text) : text;
    })(),

    extra: d.platforms?.map(p => p.platform.name).join(' · ') || '',
    extraInfo: buildRAWGExtra(d),
    vote: d.rating ? `${d.rating.toFixed(1)} / 5` : null,
  };
}

function buildRAWGExtra(d) {
  const rows = [];
  if (d.developers?.length)
    rows.push(['Développeur(s)', d.developers.map(x => x.name).join(', ')]);
  if (d.publishers?.length)
    rows.push(['Éditeur(s)', d.publishers.map(x => x.name).join(', ')]);
  if (d.esrb_rating)
    rows.push(['Classification', d.esrb_rating.name]);
  if (d.metacritic)
    rows.push(['Metacritic', d.metacritic + ' / 100']);
  return rows;
}

/* ─── Google Books (livres) ─── */
async function fetchBooks(id) {
  const key = API_KEYS.books ? `&key=${API_KEYS.books}` : '';
  const res = await fetch(`https://www.googleapis.com/books/v1/volumes/${id}${key}`);
  if (!res.ok) throw new Error('Books error');
  const d = await res.json();
  const i = d.volumeInfo || {};

  return {
    title: i.title || 'Sans titre',
    poster: i.imageLinks?.thumbnail?.replace('http://', 'https://') || 'assets/icons/livreL.png',
    year: (i.publishedDate || '').slice(0, 4),
    genre: i.categories?.join(', ') || '',
    overview: await (async () => {
      const text = i.description || 'Aucune description disponible.';
      return isEnglish(text) ? await translateToFrench(text) : text;
    })(),

    extra: i.pageCount ? `${i.pageCount} pages` : '',
    extraInfo: buildBooksExtra(i),
    vote: i.averageRating ? `${i.averageRating} / 5` : null,
  };
}

function buildBooksExtra(i) {
  const rows = [];
  if (i.authors?.length)
    rows.push(['Auteur(s)', i.authors.join(', ')]);
  if (i.publisher)
    rows.push(['Éditeur', i.publisher]);
  if (i.language)
    rows.push(['Langue', i.language.toUpperCase()]);
  if (i.industryIdentifiers?.length) {
    const isbn = i.industryIdentifiers.find(x => x.type === 'ISBN_13');
    if (isbn) rows.push(['ISBN', isbn.identifier]);
  }
  return rows;
}

/* ─────────────────────────────────────────────
   Rendu HTML
───────────────────────────────────────────── */
const TYPE_LABELS = {
  film: 'Film', serie: 'Série', anime: 'Animé', jeu: 'Jeu', livre: 'Livre'
};

function renderMedia(type, data) {
  // Poster
  const poster = document.getElementById('media-poster');
  poster.src = data.poster;
  poster.alt = data.title;

  // Badge type
  const badge = document.getElementById('media-type-badge');
  badge.textContent = TYPE_LABELS[type] || type;
  badge.className = `media-badge media-badge--${type}`;

  // Breadcrumb
  document.getElementById('breadcrumb-type').textContent = TYPE_LABELS[type];
  document.getElementById('breadcrumb-title').textContent = data.title;

  // Titre
  document.getElementById('media-title').textContent = data.title;
  document.title = `${data.title} — Kultrack`;

  // Meta pills
  setText('media-year', data.year);
  setText('media-genre', data.genre);
  setText('media-extra', data.extra);

  // Note si dispo
  if (data.vote) {
    const voteEl = document.createElement('span');
    voteEl.className = 'meta-pill meta-pill--vote';
    voteEl.textContent = '⭐ ' + data.vote;
    document.getElementById('media-extra').after(voteEl);
  }

  // Synopsis
  document.getElementById('media-overview').textContent = data.overview;

  // Tableau infos complémentaires
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

  // Transition loader → contenu
  document.getElementById('media-loader').style.display = 'none';
  document.getElementById('media-content').style.display = 'flex';

  // Stocker pour le bouton follow
  window._mediaData = { type, id: MEDIA_ID, title: data.title, poster: data.poster, year: data.year };
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (val) { el.textContent = val; }
  else { el.style.display = 'none'; }
}

function showError() {
  document.getElementById('media-loader').style.display = 'none';
  document.getElementById('media-error').style.display = 'block';
}

/* ─────────────────────────────────────────────
   Bouton follow (AJAX vers follow_action.php)
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

    try {
      const res = await fetch('follow_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          api_id: MEDIA_ID,
          api_source: getApiSource(MEDIA_TYPE),
          type: MEDIA_TYPE,
          title: window._mediaData?.title || '',
          poster: window._mediaData?.poster || '',
          status: status,
        })
      });
      const json = await res.json();
      if (json.success) {
        showMsg(msg, '✓ Ajouté à ta liste !', 'success');
      } else {
        showMsg(msg, json.error || 'Erreur serveur.', 'error');
      }
    } catch {
      showMsg(msg, 'Erreur réseau.', 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<img src="assets/icons/heart.png" alt="" width="14" height="14"> Enregistrer';
    }
  });
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