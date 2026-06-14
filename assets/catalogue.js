/* ============================
   CONFIG
============================ */

const TMDB_KEY = "f442994667b277e5713a208e0efef0e3";
const RAWG_KEY = "a02d70de4ce242308e5d60335a4e7479";
const GOOGLE_KEY = "AIzaSyDMuRrZ1LKaDFnZ13NPPV2V0yJ63to_tUo";

const grid = document.getElementById("catalogue-grid");
const type = document.body.dataset.type; // film / serie / anime / jeu / livre / all

let currentPage = 1;


/* ============================
   ROUTEUR SELON LE TYPE
============================ */

function loadContent(page = 1) {
    switch (type) {
        case "film":
            loadMovies(page);
            break;

        case "serie":
            loadSeries(page);
            break;

        case "anime":
            loadAnime(page);
            break;

        case "jeu":
            loadGames(page);
            break;

        case "livre":
            loadBooks(page);
            break;

        default:
            grid.innerHTML = "<p>Sélectionne un type pour afficher les médias.</p>";
    }
}


/* ============================
   FILMS (TMDB)
============================ */

async function loadMovies(page) {
    const url = `https://api.themoviedb.org/3/discover/movie?api_key=${TMDB_KEY}&language=fr-FR&page=${page}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.results, "film");
}


/* ============================
   SERIES (TMDB)
============================ */

async function loadSeries(page) {
    const url = `https://api.themoviedb.org/3/discover/tv?api_key=${TMDB_KEY}&language=fr-FR&page=${page}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.results, "serie");
}


/* ============================
   ANIMES (TMDB)
============================ */

async function loadAnime(page) {
    const url = `https://api.themoviedb.org/3/discover/tv?api_key=${TMDB_KEY}&language=fr-FR&with_keywords=210024&page=${page}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.results, "anime");
}


/* ============================
   JEUX (RAWG)
============================ */

async function loadGames(page) {
    const url = `https://api.rawg.io/api/games?key=${RAWG_KEY}&page=${page}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.results, "jeu");
}


/* ============================
   LIVRES (GOOGLE BOOKS)
============================ */

async function loadBooks(page) {
    const url = `https://www.googleapis.com/books/v1/volumes?q=popular&maxResults=20&key=${GOOGLE_KEY}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.items || [], "livre");
}

/* ============================
   AFFICHAGE UNIFIÉ
============================ */

function displayMedias(items, type) {
    grid.innerHTML = "";

    items.forEach(item => {
        let title, image, date, overview;

        switch (type) {
            case "film":
                title = item.title;
                image = item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : "";
                date = item.release_date;
                overview = item.overview;
                break;

            case "serie":
            case "anime":
                title = item.name;
                image = item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : "";
                date = item.first_air_date;
                overview = item.overview;
                break;

            case "jeu":
                title = item.name;
                image = item.background_image;
                date = item.released;
                overview = "Aucune description disponible.";
                break;

            case "livre":
                title = item.volumeInfo.title;
                image = item.volumeInfo.imageLinks?.thumbnail || "";
                date = item.volumeInfo.publishedDate;
                overview = item.volumeInfo.description || "Aucune description.";
                break;
        }

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
                <a href="#" class="btn-primary btn-xs desc-btn">Description</a>
            </div>
        `;

        card.querySelector(".desc-btn").addEventListener("click", e => {
            e.preventDefault();
            openPopup(title, overview);
        });

        grid.appendChild(card);
    });
}


/* ============================
   POPUP
============================ */

function openPopup(title, overview) {
    document.getElementById("popupTitle").textContent = title;
    document.getElementById("popupOverview").textContent = overview;
    document.getElementById("popup").style.display = "flex";
}

document.getElementById("closePopup").addEventListener("click", () => {
    document.getElementById("popup").style.display = "none";
});


/* ============================
   PAGINATION
============================ */

document.getElementById("nextPage").addEventListener("click", () => {
    currentPage++;
    loadContent(currentPage);
});

document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        loadContent(currentPage);
    }
});


/* ============================
   RECHERCHE (uniquement films)
============================ */

async function searchMovies(query) {
    const url = `https://api.themoviedb.org/3/search/movie?api_key=${TMDB_KEY}&language=fr-FR&query=${encodeURIComponent(query)}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMedias(data.results, "film");
}

document.getElementById("searchBtn").addEventListener("click", () => {
    const query = document.getElementById("searchInput").value.trim();
    if (query !== "" && type === "film") searchMovies(query);
});


/* ============================
   CHARGEMENT INITIAL
============================ */

loadContent(currentPage);
