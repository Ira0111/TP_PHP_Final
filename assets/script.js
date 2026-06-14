/* const apiKey = "f442994667b277e5713a208e0efef0e3";
const moviesContainer = document.getElementById("movies");
const pageNumber = document.getElementById("pageNumber");

let currentPage = 1;
let currentSort = "popularity.desc";
let currentGenre = "";

async function loadMovies(page) {
    const today = new Date().toISOString().split("T")[0];

    const url = `https://api.themoviedb.org/3/discover/movie?api_key=${apiKey}&language=fr-FR&page=${page}&sort_by=primary_release_date.desc&with_genres=${currentGenre}&primary_release_date.gte=1935-01-01&primary_release_date.lte=${today}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMovies(data.results);
    pageNumber.textContent = `Page ${page}`;
}A
async function searchMovies(query) {
    const url = `https://api.themoviedb.org/3/search/movie?api_key=${apiKey}&language=fr-FR&query=${encodeURIComponent(query)}`;

    const res = await fetch(url);
    const data = await res.json();

    displayMovies(data.results);
    pageNumber.textContent = `Résultats pour : "${query}"`;
}


function displayMovies(movies) {
    moviesContainer.innerHTML = "";

    movies.forEach(movie => {
        const card = document.createElement("div");
        card.classList.add("movie-card");

        const poster = movie.poster_path
            ? `https://image.tmdb.org/t/p/w500${movie.poster_path}`
            : "https://via.placeholder.com/500x750?text=No+Image";

        card.innerHTML = `
            <img src="${poster}" alt="${movie.title}">
            <h3>${movie.title}</h3>
            <p>${movie.release_date}</p>
            <a href="#" class="desc-btn">Description</a>
        `;

        // Ajout du popup au clic
        card.querySelector(".desc-btn").addEventListener("click", (e) => {
            e.preventDefault();
            openPopup(movie.title, movie.overview);
        });

        moviesContainer.appendChild(card);
    });
}

function openPopup(title, overview) {
    document.getElementById("popupTitle").textContent = title;
    document.getElementById("popupOverview").textContent = overview;

    document.getElementById("popup").style.display = "flex";
}
document.getElementById("closePopup").addEventListener("click", () => {
    document.getElementById("popup").style.display = "none";
});

document.getElementById("popup").addEventListener("click", (e) => {
    if (e.target.id === "popup") {
        document.getElementById("popup").style.display = "none";
    }
});



// Pagination
document.getElementById("nextPage").addEventListener("click", () => {
    currentPage++;
    loadMovies(currentPage);
});

document.getElementById("prevPage").addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        loadMovies(currentPage);
    }
});

// Tri
document.getElementById("sort").addEventListener("change", (e) => {
    currentSort = e.target.value;
    currentPage = 1;
    loadMovies(currentPage);
});

// Filtre genre
document.getElementById("genre").addEventListener("change", (e) => {
    currentGenre = e.target.value;
    currentPage = 1;
    loadMovies(currentPage);
});

document.getElementById("searchBtn").addEventListener("click", () => {
    const query = document.getElementById("searchInput").value.trim();
    if (query !== "") {
        searchMovies(query);
    }
});

document.getElementById("searchInput").addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
        const query = document.getElementById("searchInput").value.trim();
        if (query !== "") {
            searchMovies(query);
        }
    }
});



// Charger la première page
loadMovies(currentPage);
 */