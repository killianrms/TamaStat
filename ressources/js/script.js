document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Empêche le rechargement de la page

            const submitBtn = form.querySelector('#submitBtn');
            const loader = form.querySelector('#loader');

            // Désactive le bouton et affiche le loader
            submitBtn.disabled = true;
            loader.style.display = 'block';

            // Envoi AJAX
            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error("Erreur réseau");
                    return response.json(); // Attendre la réponse JSON
                })
                .then(data => {
                    // Recharge la page après l'importation, même si tout est ok
                    window.location.reload();
                })
                .catch(error => {
                    alert("Erreur : " + error.message); // Affiche l'erreur si la requête échoue
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    loader.style.display = 'none';
                });
        });
    });
});
