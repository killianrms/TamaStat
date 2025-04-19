# TamaStat

## Description

TamaStat est une application web PHP conçue pour la gestion et la visualisation de statistiques utilisateurs. Elle suit une architecture de type Modèle-Vue-Contrôleur (MVC) et interagit avec une base de données pour la persistance des données. Le projet inclut également des fonctionnalités de traitement de fichiers CSV.

## Fonctionnalités Confirmées (basées sur `web/routeur.php`)

*   **Accueil :** Page principale de l'application.
*   **Authentification :** Connexion (`/connexion`), déconnexion (`/deconnexion`), inscription (`/inscription`).
*   **Gestion du Profil Utilisateur :** Affichage (`/monProfil`), mise à jour (`/modifierProfil`, `/modifierMotDePasse`).
*   **Statistiques Utilisateur :** Affichage des statistiques (`/stats`).
*   **Gestion des Utilisateurs (Admin) :** Liste des utilisateurs (`/utilisateurs`), mise à jour des rôles (`/modifierRole`), suppression (`/supprimerUtilisateur`).
*   **Traitement CSV :** Téléchargement de template (`/templateCsv`), import de données (`/importCsv`).
*   **Pages Légales :** Conditions Générales d'Utilisation (`/cgu`), Mentions Légales (`/mentionsLegales`).

## Prérequis

*   **PHP :** Version 8.1 ou supérieure (basé sur `composer.json`).
*   **Extensions PHP :** PDO (pour la connexion à la base de données, type spécifique selon votre SGBD), mbstring.
*   **Serveur Web :** Apache ou Nginx (avec support de la réécriture d'URL).
*   **Composer :** Pour la gestion des dépendances PHP.
*   **Système de Gestion de Base de Données (SGBD) :** MySQL, PostgreSQL, MariaDB, etc. (compatible PDO).

## Installation

1.  **Cloner le dépôt :**
    ```bash
    git clone git@github.com:killianrms/TamaStat.git
    cd TamaStat
    ```

2.  **Installer les dépendances :**
    ```bash
    composer install
    ```

3.  **Configurer l'environnement :**
    *   Copiez ou renommez `.env.example` en `.env` (si un fichier d'exemple existe, sinon créez `.env`).
    *   Configurez les variables d'environnement dans `.env`, notamment les accès à la base de données.
    *   *Alternative (si `.env` n'est pas utilisé)*: Modifiez directement `src/Configuration/ConfigurationBaseDeDonnees.php` avec vos identifiants de base de données. **Note:** L'utilisation de variables d'environnement (`.env`) est recommandée pour la sécurité et la flexibilité.

4.  **Configurer la Base de Données :**
    *   Créez une base de données pour l'application sur votre SGBD.
    *   Importez le schéma de la base de données. **Vérifiez si un fichier `.sql` est fourni dans le projet.** Si ce n'est pas le cas, la structure des tables devra être créée manuellement ou via un système de migration (non apparent actuellement).

5.  **Configurer le Serveur Web :**
    *   Configurez la racine de votre serveur web (DocumentRoot / root) pour pointer vers le répertoire `web/` du projet.
    *   Activez la réécriture d'URL (`mod_rewrite` pour Apache, configuration `try_files` pour Nginx). Exemple de configuration Apache (`.htaccess` dans `web/` ou configuration du VirtualHost) :
        ```apache
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^ index.php [QSA,L]
        ```

## Utilisation

Une fois l'installation et la configuration terminées :

1.  Ouvrez votre navigateur web.
2.  Accédez à l'URL configurée pour votre serveur web (ex: `http://localhost/`, `http://tamastat.local/`). L'application est servie par `web/index.php`.

## Structure du Projet (Simplifiée)

```
.
├── Legal/              # Fichiers des pages légales (CGU, Mentions Légales)
├── ressources/         # Assets (CSS, JS, images, GIFs)
├── src/                # Code source PHP de l'application
│   ├── Configuration/  # Configuration (ex: BDD)
│   ├── Controleur/     # Contrôleurs (logique métier)
│   ├── Lib/            # Librairies et classes utilitaires
│   ├── Modele/         # Modèles (interaction BDD, objets métier)
│   └── Vue/            # Vues (templates HTML/PHP)
├── vendor/             # Dépendances Composer
├── web/                # Racine web publique
│   ├── index.php       # Point d'entrée (Front Controller)
│   └── routeur.php     # Gestionnaire des routes
├── .env.example        # Fichier d'exemple pour les variables d'environnement (si applicable)
├── .gitignore          # Fichiers ignorés par Git
├── composer.json       # Dépendances et configuration du projet
└── README.md           # Ce fichier
