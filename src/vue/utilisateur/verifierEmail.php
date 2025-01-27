<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="fr">
<body>
<div class="mail-container">
    <h1>Vérifiez votre email</h1>
    <p>Un email de vérification a été envoyé à votre adresse email. Veuillez consulter votre boîte de réception (et éventuellement votre dossier "spam") pour confirmer votre adresse.</p>
</div>
</body>
</html>
