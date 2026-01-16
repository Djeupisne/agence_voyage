document.addEventListener('DOMContentLoaded', function() {
    // Validation côté client
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const email = form.querySelector('input[type="email"]');
            if (email && !email.value.includes('@')) {
                e.preventDefault();
                alert('Veuillez entrer un email valide.');
            }

            const password = form.querySelector('input[type="password"]');
            if (password && password.value.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
            }
        });
    });

    // Animation de défilement fluide pour les liens de navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});