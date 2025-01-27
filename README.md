# **TamaStat**
Une application web permettant d'importer des fichiers CSV pour générer des statistiques utiles à l'entreprise.

---

## **1. Fonctionnalités**
### **Actuellement disponibles** :
- Système d'identification utilisateur sécurisé (connexion et sessions).
- Importation de fichiers CSV.
- Stockage des données dans une base MySQL.

### **Fonctionnalités à venir** :
- Affichage des statistiques sous forme de tableaux et de graphiques.
- Gestion des rôles utilisateur (admin/utilisateur standard).
- Exportation des statistiques.

---

## **2. Installation**
### **Prérequis** :
- **Serveur local** : XAMPP, WAMP, ou autre (avec Apache et MySQL).
- **PHP** : Version 7.4 ou supérieure.
- **Base de données** : MySQL.

### **Étapes** :
1. **Clonez le projet** :
   ```bash
   git clone https://github.com/ton-repo/TamaStat.git
   cd TamaStat
   ```
2. **Configuration de la base de données** :
   - Importez le fichier `database.sql` (que tu peux créer) dans MySQL pour créer les tables nécessaires.
   - Mettez à jour les informations de connexion dans `src/Configuration/ConfigurationBaseDeDonnees.php` :
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'nom_de_la_base');
     define('DB_USER', 'root');
     define('DB_PASS', 'votre_mot_de_passe');
     ```

3. **Lancez le serveur** :
   - Si vous utilisez PHP en ligne de commande :
     ```bash
     php -S localhost:8000 -t web
     ```
   - Sinon, placez le projet dans le dossier `htdocs` de XAMPP/WAMP et démarrez Apache.

4. **Accès au site** :
   - Ouvrez [http://localhost:8000](http://localhost:8000) dans votre navigateur.

---

## **3. Utilisation**
### **Connexion** :
1. Connectez-vous avec les identifiants suivants :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `monmotdepasse` (par défaut).

### **Importation de fichiers CSV** :
1. Accédez à la page d'accueil après connexion.
2. Cliquez sur "Importer un fichier CSV".
3. Sélectionnez le fichier et validez.

---

## **4. Structure du Projet**
### **MVC (Modèle-Vue-Contrôleur)** :
Le projet respecte l'architecture MVC pour une séparation claire des responsabilités :
- **Modèle** : Gestion de la base de données et des données (dossier `src/Modele`).
- **Vue** : Fichiers PHP pour afficher les pages HTML (dossier `src/Vue`).
- **Contrôleur** : Traitement des actions utilisateur et gestion des flux (dossier `src/Controleur`).

### **Organisation des Dossiers** :
```
TamaStat/
│
├── ressources/               # Front-end (CSS, JS, images)
│   ├── css/
│   ├── images/
│   └── js/
│
├── src/                      # Code source (PHP)
│   ├── Configuration/        # Configuration de l'application
│   ├── Controleur/           # Contrôleurs
│   ├── Lib/                  # Fonctions générales
│   ├── Modele/               # Modèles (gestion des données)
│   └── Vue/                  # Fichiers affichés à l'utilisateur
│
├── web/                      # Point d'entrée (routeur)
├── logs/                     # Logs des erreurs (facultatif)
└── README.md                 # Documentation du projet
```

---

## **5. API ou Bibliothèques Utilisées**
- **PHP natif** : Gestion des sessions, traitement des fichiers CSV, connexion PDO.
- **Chart.js** (à venir) : Pour les graphiques statistiques.
- **Bootstrap** (facultatif) : Pour améliorer l'interface utilisateur.

---

## **6. À Faire / Roadmap**
- [x] Créer le système d'identification.
- [x] Importation des fichiers CSV.
- [ ] Affichage des statistiques en tableaux.
- [ ] Intégration de graphiques (via Chart.js).
- [ ] Création de rôles utilisateur (admin/utilisateur).
- [ ] Test unitaire des modules (PHPUnit).

---

## **7. Contributions**
Si vous souhaitez contribuer à ce projet :
1. Forkez le projet.
2. Créez une branche : `git checkout -b ma-feature`.
3. Faites vos modifications : `git commit -m 'Ajout d'une fonctionnalité'`.
4. Poussez votre branche : `git push origin ma-feature`.
5. Créez une Pull Request.

---

## **8. Problèmes connus**
- Vérifiez que les fichiers CSV sont bien formatés (UTF-8 et délimités par des virgules).
- Pas encore optimisé pour les grandes bases de données.

---

## **9. Contact**
Pour toute question ou suggestion :
- **Nom** : Ton nom
- **Email** : tonemail@example.com
- **Entreprise** : Nom de l'entreprise

---

Avec ce README, ton projet sera bien documenté et facile à comprendre pour ton chef ou d'autres collaborateurs. Si tu as des sections à ajouter ou besoin d'une aide supplémentaire, fais-moi signe ! 😊
