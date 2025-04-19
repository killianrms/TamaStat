# TamaStat

## Description

TamaStat est une application web développée en PHP destinée à la gestion et à l'analyse de données, potentiellement pour une activité de location de box de stockage. Elle permet aux utilisateurs de se connecter, d'importer des données via des fichiers CSV, de gérer les utilisateurs (pour les administrateurs) et de visualiser des statistiques et informations pertinentes sur un tableau de bord.

## Fonctionnalités Clés

*   **Authentification :** Connexion, déconnexion et gestion des mots de passe des utilisateurs.
*   **Gestion des Utilisateurs (Admin) :** Ajout, modification et suppression d'utilisateurs.
*   **Import de Données CSV :**
    *   Factures (`importer-factures`)
    *   Types de Box (`importer-box`)
    *   Récapitulatifs de Ventes (`importer-recap-ventes`)
    *   Contrats Actifs (`importer-contrats`)
    *   Contrats Clos (`importer-contrats-clos`)
*   **Configuration :** Définition des quantités pour différents types de box (`configurer-box`, `ajouterDonneesAccueil`).
*   **Visualisation :**
    *   Tableau de bord principal (`accueil`).
    *   Page de statistiques (`stats`).
    *   Profil utilisateur (`profil`).
*   **Pages Légales :** Accès aux Conditions Générales d'Utilisation (`cgu`) et Mentions Légales (`mentions-legales`).

## Structure du Projet

Le projet suit une structure MVC (Modèle-Vue-Contrôleur) de base :

*   `src/` : Contient le code source principal de l'application.
    *   `Configuration/` : Classes pour la configuration (ex: `ConfigurationBaseDeDonnees.php`, `ConnexionBD.php`).
    *   `Controleur/` : Contient les contrôleurs qui gèrent la logique métier et les interactions utilisateur (ex: `ControleurUtilisateur.php`, `ControleurCsv.php`).
    *   `Lib/` : (Potentiellement) Librairies ou classes utilitaires.
    *   `Modele/` : (Potentiellement) Classes responsables de l'interaction avec la base de données.
    *   `Vue/` : Fichiers PHP/HTML responsables de l'affichage et de la présentation des données.
*   `web/` : Répertoire racine web, point d'entrée de l'application (`index.php`, `routeur.php`).
*   `vendor/` : Dépendances PHP gérées par Composer.
*   `ressources/` : Contient les assets statiques (CSS, JavaScript, images, etc.).
*   `Legal/` : Contient les fichiers des pages légales.
*   `composer.json` : Fichier de configuration pour Composer, définissant les dépendances et l'autoloading (PSR-4).
*   `README.md` : Ce fichier.

## Installation

1.  **Cloner le dépôt :**
    ```bash
    git clone <url-du-depot>
    cd TamaStat
    ```
2.  **Installer les dépendances :**
    Assurez-vous d'avoir [Composer](https://getcomposer.org/) installé.
    ```bash
    composer install
    ```
3.  **Configurer la Base de Données :**
    *   Modifiez le fichier `src/Configuration/ConfigurationBaseDeDonnees.php` avec les informations de connexion de votre serveur MySQL (hôte, nom de la base, port, utilisateur, mot de passe).
    *   Créez la base de données spécifiée si elle n'existe pas.
    *   Importez la structure de la base de données (schéma SQL). *Note : Le schéma SQL n'est pas fourni dans ce dépôt et doit être obtenu séparément.*
4.  **Configurer le Serveur Web :**
    *   Configurez votre serveur web (Apache, Nginx, etc.) pour que le `DocumentRoot` pointe vers le répertoire `web/` du projet.
    *   Assurez-vous que le serveur exécute PHP correctement. La réécriture d'URL n'est à priori pas nécessaire car l'application utilise un paramètre `route` dans l'URL (`routeur.php?route=...`).

## Utilisation

1.  Accédez à l'application via l'URL configurée sur votre serveur web (ex: `http://localhost/` ou `http://votredomaine.com/`).
2.  La page de connexion (`routeur.php?route=connexion`) devrait s'afficher par défaut si vous n'êtes pas connecté.
3.  Connectez-vous avec un compte utilisateur valide.
4.  Naviguez dans l'application en utilisant les liens ou en accédant directement aux routes via l'URL (ex: `routeur.php?route=accueil`, `routeur.php?route=stats`).
5.  Utilisez les formulaires disponibles pour importer des fichiers CSV ou configurer les données des box.
