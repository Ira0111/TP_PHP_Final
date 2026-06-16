/* ============================
   CONFIG — clés API centralisées
============================ */

const TMDB_KEY = "f442994667b277e5713a208e0efef0e3";
const RAWG_KEY = "a02d70de4ce242308e5d60335a4e7479";
const GOOGLE_KEY = "AIzaSyDMuRrZ1LKaDFnZ13NPPV2V0yJ63to_tUo";

const grid = document.getElementById("catalogue-grid");
const type = document.body.dataset.type;

let currentPage = 1;


/* ============================
   ROUTEUR
============================ */

function loadContent(page = 1) {
    switch (type) {
        case "film": loadMovies(page); break;
        case "serie": loadSeries(page); break;
        case "anime": loadAnime(page); break;
        case "jeu": loadGames(page); break;
        case "livre": loadBooks(page); break;
        default:
            grid.innerHTML = "<p>Sélectionne un type pour afficher les médias.</p>";
    }
}


/* ============================
   FETCH LISTE (catalogue)
============================ */

async function loadMovies(page) {
    const res = await fetch(`https://api.themoviedb.org/3/discover/movie?api_key=${TMDB_KEY}&language=fr-FR&page=${page}`);
    const data = await res.json();
    displayMedias(data.results, "film");
}

async function loadSeries(page) {
    const res = await fetch(`https://api.themoviedb.org/3/discover/tv?api_key=${TMDB_KEY}&language=fr-FR&page=${page}`);
    const data = await res.json();
    displayMedias(data.results, "serie");
}

async function loadAnime(page) {
    const res = await fetch(`https://api.themoviedb.org/3/discover/tv?api_key=${TMDB_KEY}&language=fr-FR&with_keywords=210024&page=${page}`);
    const data = await res.json();
    displayMedias(data.results, "anime");
}

async function loadGames(page) {
    const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_KEY}&page=${page}`);
    const data = await res.json();
    displayMedias(data.results, "jeu");
}

async function loadBooks(page) {
    const res = await fetch(`https://www.googleapis.com/books/v1/volumes?q=popular&maxResults=20&key=${GOOGLE_KEY}`);
    const data = await res.json();
    displayMedias(data.items || [], "livre");
}


/* ============================
   FETCH DETAIL (media.php)
   Retourne un objet normalisé
   { id, title, poster, year, genre, overview, extra }
============================ */

async function fetchMediaDetail(type, id) {
    switch (type) {
        case "film": return fetchMovieDetail(id);
        case "serie": return fetchTVDetail(id, "serie");
        case "anime": return fetchTVDetail(id, "anime");
        case "jeu": return fetchGameDetail(id);
        case "livre": return fetchBookDetail(id);
        default: throw new Error("Type inconnu : " + type);
    }
}

async function fetchMovieDetail(id) {
    const res = await fetch(`https://api.themoviedb.org/3/movie/${id}?api_key=${TMDB_KEY}&language=fr-FR`);
    if (!res.ok) throw new Error("TMDB movie error");
    const d = await res.json();
    return {
        id: d.id,
        title: d.title,
        poster: d.poster_path ? `https://image.tmdb.org/t/p/w500${d.poster_path}` : "assets/icons/filmL.png",
        year: (d.release_date || "").slice(0, 4),
        genre: (d.genres || []).map(g => g.name).join(", "),
        overview: d.overview || "Aucune description disponible.",
        extra: d.runtime ? d.runtime + " min" : "",
        vote: d.vote_average ? d.vote_average.toFixed(1) + " / 10" : null,
        extraRows: [
            d.production_countries?.length && ["Pays", d.production_countries.map(c => c.name).join(", ")],
            d.budget && ["Budget", "$" + d.budget.toLocaleString("fr-FR")],
            d.status && ["Statut", d.status],
        ].filter(Boolean),
    };
}

async function fetchTVDetail(id, mediaType) {
    const res = await fetch(`https://api.themoviedb.org/3/tv/${id}?api_key=${TMDB_KEY}&language=fr-FR`);
    if (!res.ok) throw new Error("TMDB tv error");
    const d = await res.json();
    return {
        id: d.id,
        title: d.name,
        poster: d.poster_path ? `https://image.tmdb.org/t/p/w500${d.poster_path}` : "assets/icons/serieL.png",
        year: (d.first_air_date || "").slice(0, 4),
        genre: (d.genres || []).map(g => g.name).join(", "),
        overview: d.overview || "Aucune description disponible.",
        extra: `${d.number_of_seasons ?? "?"} saison(s) · ${d.number_of_episodes ?? "?"} épisodes`,
        vote: d.vote_average ? d.vote_average.toFixed(1) + " / 10" : null,
        extraRows: [
            d.status && ["Statut", d.status],
            d.spoken_languages?.length && ["Langue(s)", d.spoken_languages.map(l => l.name).join(", ")],
        ].filter(Boolean),
    };
}

async function fetchGameDetail(id) {
    const res = await fetch(`https://api.rawg.io/api/games/${id}?key=${RAWG_KEY}`);
    if (!res.ok) throw new Error("RAWG error");
    const d = await res.json();
    return {
        id: d.id,
        title: d.name,
        poster: d.background_image || "assets/icons/jeuL.png",
        year: (d.released || "").slice(0, 4),
        genre: (d.genres || []).map(g => g.name).join(", "),
        overview: d.description_raw || "Aucune description disponible.",
        extra: (d.platforms || []).map(p => p.platform.name).join(" · "),
        vote: d.rating ? d.rating.toFixed(1) + " / 5" : null,
        extraRows: [
            d.developers?.length && ["Développeur(s)", d.developers.map(x => x.name).join(", ")],
            d.publishers?.length && ["Éditeur(s)", d.publishers.map(x => x.name).join(", ")],
            d.metacritic && ["Metacritic", d.metacritic + " / 100"],
        ].filter(Boolean),
    };
}

async function fetchBookDetail(id) {
    const res = await fetch(`https://www.googleapis.com/books/v1/volumes/${id}?key=${GOOGLE_KEY}`);
    if (!res.ok) throw new Error("Books error");
    const d = await res.json();
    const i = d.volumeInfo || {};
    const isbn = (i.industryIdentifiers || []).find(x => x.type === "ISBN_13");
    return {
        id: d.id,
        title: i.title || "Sans titre",
        poster: (i.imageLinks?.thumbnail || "assets/icons/livreL.png").replace("http://", "https://"),
        year: (i.publishedDate || "").slice(0, 4),
        genre: (i.categories || []).join(", "),
        overview: i.description || "Aucune description disponible.",
        extra: i.pageCount ? i.pageCount + " pages" : "",
        vote: i.averageRating ? i.averageRating + " / 5" : null,
        extraRows: [
            i.authors?.length && ["Auteur(s)", i.authors.join(", ")],
            i.publisher && ["Éditeur", i.publisher],
            i.language && ["Langue", i.language.toUpperCase()],
            isbn && ["ISBN", isbn.identifier],
        ].filter(Boolean),
    };
}

/* Retourne la source API à stocker en BDD */
function getApiSource(type) {
    if (["film", "serie", "anime"].includes(type)) return "tmdb";
    if (type === "jeu") return "rawg";
    if (type === "livre") return "google_books";
    return "unknown";
}


/* ============================
   AFFICHAGE UNIFIÉ (catalogue)
============================ */

function normalizeItem(item, type) {
    switch (type) {
        case "film":
            return {
                id: item.id,
                title: item.title,
                image: item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : "",
                date: item.release_date,
                overview: item.overview,
            };
        case "serie":
        case "anime":
            return {
                id: item.id,
                title: item.name,
                image: item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : "",
                date: item.first_air_date,
                overview: item.overview,
            };
        case "jeu":
            return {
                id: item.id,
                title: item.name,
                image: item.background_image || "",
                date: item.released,
                overview: "Aucune description disponible.",
            };
        case "livre":
            return {
                id: item.id,
                title: item.volumeInfo.title,
                image: (item.volumeInfo.imageLinks?.thumbnail || "").replace("http://", "https://"),
                date: item.volumeInfo.publishedDate,
                overview: item.volumeInfo.description || "Aucune description.",
            };
    }
}

function displayMedias(items, type) {
    grid.innerHTML = "";

    items.forEach(item => {
        const { id, title, image, date, overview } = normalizeItem(item, type);

        const card = document.createElement("article");
        card.classList.add("media-card", `media-card--${type}`);

        card.innerHTML = `
            <div class="media-card__thumb">
                <img src="${image}" alt="${title}">
            </div>
            <div class="media-card__body">
                <span class="media-card__type">${type.toUpperCase()}</span>
                <h3 class="media-card__title">${title}</h3>
                <p class="media-card__year">${date || "Date inconnue"}</p>
                <div class="media-card__actions">
                    <a href="media.php?type=${type}&id=${id}" class="btn-primary btn-xs">Voir la fiche</a>
                </div>
            </div>
        `;
        grid.appendChild(card);
    });
}

/* ============================
   PAGINATION
============================ */

document.getElementById("nextPage").addEventListener("click", () => {
    currentPage++;
    loadContent(currentPage);
    window.scrollTo({ top: 0, behavior: "smooth" });
});

document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        loadContent(currentPage);
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
});

function updatePagination() {
    document.getElementById("pageNumber").textContent = `Page ${currentPage}`;
    document.getElementById("prevPage").disabled = currentPage === 1;
}


/* ============================
   RECHERCHE — tous types
============================ */

async function searchContent(query) {
    if (!query) return;

    switch (type) {
        case "film": {
            const res = await fetch(`https://api.themoviedb.org/3/search/movie?api_key=${TMDB_KEY}&language=fr-FR&query=${encodeURIComponent(query)}`);
            const data = await res.json();
            displayMedias(data.results, "film");
            break;
        }
        case "serie": {
            const res = await fetch(`https://api.themoviedb.org/3/search/tv?api_key=${TMDB_KEY}&language=fr-FR&query=${encodeURIComponent(query)}`);
            const data = await res.json();
            displayMedias(data.results, "serie");
            break;
        }
        case "anime": {
            // Recherche TV + filtre côté client sur les résultats qui ont l'air d'animés
            const res = await fetch(`https://api.themoviedb.org/3/search/tv?api_key=${TMDB_KEY}&language=fr-FR&query=${encodeURIComponent(query)}`);
            const data = await res.json();
            displayMedias(data.results, "anime");
            break;
        }
        case "jeu": {
            const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_KEY}&search=${encodeURIComponent(query)}`);
            const data = await res.json();
            displayMedias(data.results, "jeu");
            break;
        }
        case "livre": {
            const res = await fetch(`https://www.googleapis.com/books/v1/volumes?q=${encodeURIComponent(query)}&maxResults=20&key=${GOOGLE_KEY}`);
            const data = await res.json();
            displayMedias(data.items || [], "livre");
            break;
        }
        default:
            grid.innerHTML = "<p>Sélectionne un type pour rechercher.</p>";
    }
}

document.getElementById("searchBtn").addEventListener("click", () => {
    const query = document.getElementById("searchInput").value.trim();
    if (query) searchContent(query);
});

document.getElementById("searchInput").addEventListener("keydown", e => {
    if (e.key === "Enter") {
        const query = e.target.value.trim();
        if (query) searchContent(query);
    }
});


/* ============================
   INIT
============================ */

loadContent(currentPage);