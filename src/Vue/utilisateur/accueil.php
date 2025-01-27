
<h1>Bienvenue, <?= htmlspecialchars($utilisateur['nom_utilisateur']) ?> !</h1>

<form action="/utilisateur/importerCsv" method="post" enctype="multipart/form-data">
    <label for="fichier">Importer un fichier CSV :</label>
    <input type="file" id="fichier" name="fichier" accept=".csv" required>
    <button type="submit">Importer</button>
</form>
