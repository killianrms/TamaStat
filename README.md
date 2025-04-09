
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
    *   Modifiez le fichier `src/Configuration/ConfigurationBaseDeDonnees.php` avec les informations de connexion à votre base de données (hôte, nom de la base, utilisateur, mot de passe).
    *   Assurez-vous que la base de données et les tables nécessaires existent. (Des migrations ou un script SQL pourraient être nécessaires - à vérifier dans le projet).

4.  **Configurer le serveur web :**
    *   Configurez la racine du document (DocumentRoot pour Apache, root pour Nginx) de votre serveur web pour qu'elle pointe vers le répertoire `web/` du projet.
    *   Assurez-vous que la réécriture d'URL est activée (par exemple, `mod_rewrite` pour Apache).

## Utilisation

Une fois l'installation et la configuration terminées :

1.  Ouvrez votre navigateur web.
2.  Accédez à l'URL que vous avez configurée pour votre serveur web (par exemple, `http://localhost/` ou `http://tamastat.local/` selon votre configuration). L'application devrait se charger via `web/index.php`.
