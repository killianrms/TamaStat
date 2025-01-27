function toggleMenu() {
    const mobileMenu = document.querySelector('.nav-links.mobile');
    mobileMenu.classList.toggle('active');
}

function confirmSuppression(url) {
    const confirmation = confirm("Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.");
    if (confirmation) {
        window.location.href = url;
    } else {
        alert("Suppression annulée.");
    }
}