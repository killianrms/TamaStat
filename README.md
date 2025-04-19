
# TamaStat

## Description

Application web PHP développée pour la gestion et la visualisation de statistiques. Elle semble utiliser une architecture de type MVC et inclut des fonctionnalités de gestion des utilisateurs et d'interaction avec une base de données, ainsi qu'un potentiel traitement de fichiers CSV.

## Fonctionnalités

*   **Authentification des utilisateurs :** Connexion et inscription.
*   **Gestion des utilisateurs :** Consultation et modification du profil, changement de mot de passe, gestion des utilisateurs (potentiellement pour les administrateurs).
*   **Interaction avec la base de données :** Configuration et connexion à une base de données pour la persistance des données.
*   **Traitement de fichiers CSV :** Capacités potentielles pour importer ou traiter des données depuis des fichiers CSV.
*   **Affichage de statistiques :** Visualisation de données statistiques.
*   **Pages légales :** Inclut des pages pour les Conditions Générales d'Utilisation (CGU) et les Mentions Légales.
*   **Ressources Front-end :** Gestion des feuilles de style CSS, des scripts JavaScript et des images/GIFs.

## Prérequis

*   PHP (version 8.0 ou supérieure recommandée)
*   Serveur Web (par exemple Apache, Nginx)
*   Composer (pour la gestion des dépendances PHP)
*   Un système de gestion de base de données (par exemple MySQL, PostgreSQL, MariaDB)

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

3.  **Configurer la base de données :**
    *   La configuration de la base de données est maintenant gérée via un fichier `.env` à la racine du projet.
    *   Créez un fichier nommé `.env` à la racine du projet s'il n'existe pas.
    *   Ajoutez les variables d'environnement suivantes dans le fichier `.env` et remplacez les valeurs par vos informations de connexion réelles :
        ```dotenv
        DB_HOST=votre_hote_bd
        DB_NAME=votre_nom_bd
        DB_USER=votre_utilisateur_bd
        DB_PASS=votre_mot_de_passe_bd
        ```
    *   **Important :** Le fichier `.env` est listé dans `.gitignore` et ne doit pas être versionné (commit) pour des raisons de sécurité.
    *   Le projet utilise `vlucas/phpdotenv` (installé via Composer) pour charger automatiquement ces variables d'environnement.
    *   Assurez-vous que la base de données (`votre_nom_bd`) et les tables nécessaires existent. (Des migrations ou un script SQL pourraient être nécessaires - à vérifier dans le projet).

4.  **Configurer le serveur web :**
    *   Configurez la racine du document (DocumentRoot pour Apache, root pour Nginx) de votre serveur web pour qu'elle pointe vers le répertoire `web/` du projet.
    *   Assurez-vous que la réécriture d'URL est activée (par exemple, `mod_rewrite` pour Apache).

## Utilisation

Une fois l'installation et la configuration terminées :

1.  Ouvrez votre navigateur web.
2.  Accédez à l'URL que vous avez configurée pour votre serveur web (par exemple, `http://localhost/` ou `http://tamastat.local/` selon votre configuration). L'application devrait se charger via `web/index.php`.
