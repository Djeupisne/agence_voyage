<?php
require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'Utilisation - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #f0f4f8, #d9e4f5);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: 'Arial', sans-serif;
        animation: gradientAnimation 10s ease infinite;
    }

    @keyframes gradientAnimation {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .content {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px 0;
    }

    .terms-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        max-width: 800px;
        width: 100%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .terms-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    h1 {
        color: #1a3c6d;
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    h2 {
        color: #1a3c6d;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        position: relative;
    }

    h2::before {
        content: "\f0da";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: 0.5rem;
        color: #2a5ca0;
    }

    p,
    ul {
        color: #333;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    strong {
        color: #1a3c6d;
    }

    ul {
        padding-left: 20px;
    }

    li {
        margin-bottom: 0.5rem;
    }

    footer {
        margin-top: auto;
        text-align: center;
        padding: 1rem 0;
        color: #666;
        background: rgba(248, 249, 250, 0.9);
        backdrop-filter: blur(5px);
    }

    @media (max-width: 768px) {
        .terms-card {
            padding: 1.5rem;
        }

        h1 {
            font-size: 1.75rem;
        }

        h2 {
            font-size: 1.25rem;
        }
    }
    </style>
</head>

<body>
    <div class="content">
        <div class="terms-card">
            <h1><i class="fas fa-gavel"></i> Conditions d'Utilisation</h1>
            <p><strong>Date de dernière mise à jour : 01 mai 2025</strong></p>

            <p>Bienvenue sur la plateforme de gestion de réservations de voyages (ci-après dénommée "la Plateforme"). En
                accédant ou en utilisant la Plateforme, vous acceptez d'être lié par les présentes Conditions
                d'Utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser la Plateforme.</p>

            <h2>1. Objet de la Plateforme</h2>
            <p>La Plateforme permet aux utilisateurs de réserver des voyages, de gérer leurs réservations, de consulter
                leur historique, et aux administrateurs de gérer les réservations, les utilisateurs, et les factures.
                Elle est exploitée par AGENCE-VOYAGE, une entité enregistrée à Lomé, TOGO.
            </p>

            <h2>2. Accès à la Plateforme</h2>
            <ul>
                <li><strong>Éligibilité</strong> : Pour utiliser la Plateforme, vous devez avoir au moins 18 ans et
                    disposer de la capacité juridique pour conclure des contrats.</li>
                <li><strong>Compte utilisateur</strong> : Vous devez créer un compte pour accéder à certaines
                    fonctionnalités. Vous êtes responsable de la confidentialité de vos identifiants (email et mot de
                    passe) et de toutes les activités effectuées sous votre compte.</li>
                <li><strong>Rôles</strong> : La Plateforme distingue les utilisateurs standards (clients) et les
                    administrateurs. Les administrateurs ont des droits supplémentaires pour gérer les données des
                    utilisateurs et des réservations.</li>
            </ul>

            <h2>3. Utilisation de la Plateforme</h2>
            <ul>
                <li><strong>Réservations</strong> : Vous pouvez réserver des voyages en sélectionnant un type de billet,
                    une destination, et une date. Toute réservation est soumise à disponibilité et à confirmation.</li>
                <li><strong>Paiements</strong> : Les paiements sont effectués via des moyens sécurisés. Vous acceptez de
                    fournir des informations de paiement exactes et de payer les montants indiqués lors de la
                    réservation.</li>
                <li><strong>Annulations</strong> : Les réservations peuvent être annulées selon les conditions
                    spécifiées dans votre confirmation de réservation. Des frais d’annulation peuvent s’appliquer.</li>
                <li><strong>Factures</strong> : Les factures sont générées automatiquement pour chaque réservation et
                    peuvent être téléchargées depuis la Plateforme.</li>
            </ul>

            <h2>4. Obligations de l'Utilisateur</h2>
            <p>Vous vous engagez à :</p>
            <ul>
                <li>Fournir des informations exactes et à jour lors de la création de votre compte et de vos
                    réservations.</li>
                <li>Ne pas utiliser la Plateforme à des fins illégales ou non autorisées.</li>
                <li>Ne pas tenter d’accéder à des données ou fonctionnalités pour lesquelles vous n’avez pas
                    d’autorisation (par exemple, les fonctionnalités réservées aux administrateurs).</li>
            </ul>

            <h2>5. Responsabilité</h2>
            <ul>
                <li><strong>Responsabilité de l’utilisateur</strong> : Vous êtes responsable de vos actions sur la
                    Plateforme, y compris des pertes ou dommages causés par une utilisation non conforme.</li>
                <li><strong>Responsabilité de la Plateforme</strong> : Nous nous efforçons de garantir un service
                    fiable, mais la Plateforme est fournie "telle quelle". Nous ne garantissons pas un accès
                    ininterrompu ou sans erreur. Nous ne sommes pas responsables des pertes indirectes, telles que la
                    perte de données ou de profits.</li>
            </ul>

            <h2>6. Suspension et Résiliation</h2>
            <p>Nous nous réservons le droit de suspendre ou de résilier votre compte en cas de :</p>
            <ul>
                <li>Violation des présentes Conditions d’Utilisation.</li>
                <li>Activité frauduleuse ou illégale.</li>
                <li>Demande des autorités compétentes.</li>
            </ul>

            <h2>7. Propriété Intellectuelle</h2>
            <p>Tous les contenus de la Plateforme (textes, logos, images, code) sont protégés par des droits d’auteur et
                appartiennent à <b>AGENCE-VOYAGE</b> ou à ses partenaires. Vous n’êtes pas autorisé à copier,
                modifier ou distribuer ces contenus sans autorisation préalable.</p>

            <h2>8. Modifications des Conditions</h2>
            <p>Nous pouvons modifier ces Conditions d’Utilisation à tout moment. Les modifications seront publiées sur
                cette page avec une nouvelle date de mise à jour. En continuant à utiliser la Plateforme après ces
                modifications, vous acceptez les nouvelles conditions.</p>

            <h2>9. Droit Applicable et Juridiction</h2>
            <p>Les présentes Conditions d’Utilisation sont régies par le droit du TOGO. Tout litige sera soumis à la
                compétence exclusive des tribunaux de Lomé, TOGO.</p>

            <h2>10. Contact</h2>
            <p>Pour toute question concernant ces Conditions d’Utilisation, veuillez nous contacter à :</p>
            <ul>
                <li>Email : oualoumidjeupisne@gmail.com</li>
                <li>Adresse : Adidogomé-Logoté</li>
            </ul>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
</body>

</html>