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
                    if (data.status === 'success') {
                        alert('Fichier importé avec succès');
                        window.location.reload(); // Recharge la page si succès
                    } else {
                        alert("Erreur : " + data.message); // Affiche le message d'erreur
                    }
                })
                .catch(error => {
                    alert("Erreur : " + error.message);
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    loader.style.display = 'none';
                });
        });
    });
});
