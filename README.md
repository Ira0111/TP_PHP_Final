# KulTrack

Application web de suivi de médias culturels développée en PHP avec une architecture MVC, dans le cadre du module **1PHPB – Développement backend PHP** (École Hexagone).

---

## Présentation

KulTrack permet à ses utilisateurs de suivre leur consommation culturelle : films, séries, animés, jeux vidéo et livres. Chaque utilisateur peut parcourir un catalogue, ajouter des médias à sa liste personnelle, renseigner son statut de visionnage/lecture et laisser une note accompagnée d'un commentaire.

---

## Fonctionnalités

- Inscription et connexion des utilisateurs (sessions PHP)
- Gestion des rôles : `user` et `admin`
- Catalogue de médias filtrable par type (film, série, animé, jeu, livre)
- CRUD complet sur les médias (entité principale)
- CRUD complet sur les avis/reviews (entité secondaire, liée aux médias par clé étrangère)
- Suivi personnalisé avec statuts : `watching`, `completed`, `on_hold`, `dropped`, `plan_to_watch`
- Intégration d'une API externe via `fetch` JavaScript (données en lien avec la thématique)
- Interface responsive en CSS maison (aucun framework CSS)

---

## Architecture MVC

```
TP_PHP_Final/
├── assets/             # Icônes et ressources statiques
├── controllers/        # Contrôleurs PHP (UserController, etc.)
├── models/             # Modèles PHP (classes entités)
├── config.php          # Configuration, autoloader, instanciation
├── database.php        # Connexion PDO
├── index.php           # Page d'accueil
├── catalogue.php       # Catalogue des médias
├── media.php           # Page média
├── header.php          # En-tête commun
├── footer.php          # Pied de page commun
├── style.css           # Feuille de styles CSS maison
└── kultrack.sql        # Dump de la base de données
```

- **Modèles** : classes PHP représentant chaque entité, avec attributs, getters/setters.
- **Vues** : fichiers PHP dédiés à l'affichage (liste, création, modification, détail, suppression).
- **Contrôleurs** : classes PHP centralisant les requêtes SQL via PDO.

---

## Base de données

Le fichier `kultrack.sql` contient le schéma complet. La base comporte 4 tables :

| Table    | Rôle                                                       |
|----------|------------------------------------------------------------|
| `user`   | Comptes utilisateurs avec rôles (`user` / `admin`)         |
| `media`  | Entité principale — films, séries, animés, jeux, livres    |
| `review` | Entité secondaire — notes et commentaires sur un média     |
| `follow` | Suivi utilisateur — statut et progression sur un média     |

---

## Installation

### Prérequis

- PHP 8.x
- MySQL / MariaDB
- Serveur local type MAMP / XAMPP / Laragon

### Étapes

1. Cloner le dépôt dans le dossier web de votre serveur local :
   ```bash
   git clone https://github.com/Ira0111/TP_PHP_Final.git
   ```

2. Importer la base de données :
   ```bash
   mysql -u root -p kultrack < kultrack.sql
   ```
   Ou via phpMyAdmin : créer une base `kultrack` et importer `kultrack.sql`.

3. Configurer la connexion dans `database.php` (hôte, utilisateur, mot de passe).

4. Vérifier la constante `URL_RACINE` dans `config.php` et l'adapter si besoin :
   ```php
   define('URL_RACINE', 'http://localhost/TP_PHP_Final/');
   ```

5. Accéder à l'application dans le navigateur à l'URL configurée.

---

## Technologies utilisées

| Couche    | Technologie                  |
|-----------|------------------------------|
| Backend   | PHP 8, PDO, sessions/cookies |
| Base de données | MySQL 5.7              |
| Frontend  | HTML5, CSS3 maison, JavaScript (fetch) |
| Gestion de projet | Trello (Kanban)      |
| Maquettage | Figma                       |
| Modélisation BDD | Looping (Merise)      |

---

## Contexte académique

Projet individuel réalisé dans le cadre du module **1PHPB – Développement backend PHP**, École Hexagone.  
Encadrant : Chris CHEVALIER