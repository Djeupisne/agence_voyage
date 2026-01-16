<?php
require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - Agence de Voyage</title>
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

    .privacy-card {
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

    .privacy-card:hover {
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
        .privacy-card {
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
        <div class="privacy-card">
            <h1><i class="fas fa-shield-alt"></i> Politique de Confidentialité</h1>
            <p><strong>Date de dernière mise à jour : 01 mai 2025</strong></p>

            <p>[Nom de votre entreprise/organisation] (ci-après "nous", "notre", ou "la Plateforme") s’engage à protéger
                la confidentialité de vos données personnelles. Cette Politique de Confidentialité explique comment nous
                collectons, utilisons, stockons et protégeons vos informations lorsque vous utilisez notre plateforme de
                gestion de réservations de voyages.</p>

            <h2>1. Collecte des Données Personnelles</h2>
            <p>Nous collectons les types de données suivants :</p>
            <ul>
                <li><strong>Données d’identification</strong> : Nom, prénom, adresse email, mot de passe (crypté).</li>
                <li><strong>Données de réservation</strong> : Type de billet, nombre de places, date de voyage,
                    destination.</li>
                <li><strong>Données de paiement</strong> : Informations de paiement (traitées par des prestataires tiers
                    sécurisés, nous ne stockons pas les données de carte bancaire).</li>
                <li><strong>Données d’historique</strong> : Historique des actions effectuées sur la Plateforme
                    (réservations, annulations, modifications).</li>
                <li><strong>Données techniques</strong> : Adresse IP, type de navigateur, données de connexion
                    (horodatage).</li>
            </ul>

            <h2>2. Base Légale et Finalités du Traitement</h2>
            <p>Nous traitons vos données personnelles sur les bases légales suivantes :</p>
            <ul>
                <li><strong>Exécution d’un contrat</strong> : Pour gérer vos réservations, paiements et factures.</li>
                <li><strong>Consentement</strong> : Lorsque vous créez un compte ou acceptez nos Conditions
                    d’Utilisation.</li>
                <li><strong>Intérêt légitime</strong> : Pour améliorer nos services, prévenir la fraude et assurer la
                    sécurité de la Plateforme.</li>
            </ul>
            <p>Les finalités du traitement sont :</p>
            <ul>
                <li>Fournir et gérer les services de réservation.</li>
                <li>Générer des factures et traiter les paiements.</li>
                <li>Permettre aux administrateurs de gérer les utilisateurs et les réservations.</li>
                <li>Assurer le suivi des actions via l’historique.</li>
                <li>Répondre à vos demandes de support.</li>
            </ul>

            <h2>3. Durée de Conservation</h2>
            <ul>
                <li>Les données des utilisateurs (nom, email, etc.) sont conservées tant que votre compte est actif. Si
                    votre compte est supprimé, les données sont marquées comme supprimées (suppression douce) et peuvent
                    être restaurées par un administrateur. Les données sont définitivement supprimées après 1 an
                    d’inactivité ou sur demande.</li>
                <li>Les données de réservation et d’historique sont conservées pendant 5 ans pour des raisons comptables
                    et légales, sauf demande explicite de suppression.</li>
                <li>Les factures sont conservées pendant 10 ans conformément aux obligations légales.</li>
            </ul>

            <h2>4. Partage des Données</h2>
            <p>Nous pouvons partager vos données avec :</p>
            <ul>
                <li><strong>Prestataires de paiement</strong> : Pour traiter vos paiements de manière sécurisée.</li>
                <li><strong>Autorités légales</strong> : Si requis par la loi ou pour protéger nos droits.</li>
            </ul>
            <p>Nous ne vendons ni ne louons vos données personnelles à des tiers à des fins commerciales.</p>

            <h2>5. Sécurité des Données</h2>
            <p>Nous mettons en place des mesures techniques et organisationnelles pour protéger vos données, telles que
                :</p>
            <ul>
                <li>Cryptage des mots de passe.</li>
                <li>Accès restreint aux données pour les administrateurs uniquement.</li>
                <li>Hébergement sécurisé de la base de données.</li>
            </ul>
            <p>Cependant, aucun système n’est infaillible. En cas de violation de données, nous vous informerons dans
                les 72 heures, conformément au RGPD.</p>

            <h2>6. Vos Droits</h2>
            <p>Conformément au RGPD, vous disposez des droits suivants :</p>
            <ul>
                <li><strong>Droit d’accès</strong> : Vous pouvez demander une copie de vos données personnelles.</li>
                <li><strong>Droit de rectification</strong> : Vous pouvez demander la correction de données inexactes.
                </li>
                <li><strong>Droit à l’effacement</strong> : Vous pouvez demander la suppression définitive de vos
                    données (sous réserve des obligations légales).</li>
                <li><strong>Droit à la limitation</strong> : Vous pouvez demander une limitation du traitement de vos
                    données.</li>
                <li><strong>Droit à la portabilité</strong> : Vous pouvez demander à recevoir vos données dans un format
                    structuré.</li>
                <li><strong>Droit d’opposition</strong> : Vous pouvez vous opposer au traitement de vos données pour des
                    raisons légitimes.</li>
            </ul>
            <p>Pour exercer ces droits, contactez-nous à oualoumidjeupisne@gmail.com. Nous répondrons dans un délai de 30
                jours.</p>

            <h2>7. Cookies et Technologies Similaires</h2>
            <p>La Plateforme utilise des cookies pour :</p>
            <ul>
                <li>Maintenir votre session active (cookies de session).</li>
                <li>Améliorer l’expérience utilisateur (cookies fonctionnels).</li>
            </ul>
            <p>Vous pouvez gérer vos préférences de cookies via les paramètres de votre navigateur.</p>

            <h2>8. Transferts Internationaux</h2>
            <p>Si vos données sont transférées en dehors de l’Union Européenne, nous nous assurons que le pays
                destinataire offre un niveau de protection adéquat ou que des garanties appropriées (comme des clauses
                contractuelles types) sont en place.</p>

            <h2>9. Modifications de la Politique</h2>
            <p>Nous pouvons mettre à jour cette Politique de Confidentialité. Toute modification sera publiée sur cette
                page avec une nouvelle date de mise à jour. Nous vous encourageons à consulter cette page régulièrement.
            </p>

            <h2>10. Contact et Réclamations</h2>
            <p>Pour toute question ou réclamation concernant vos données personnelles, contactez-nous à :</p>
            <ul>
                <li>Email : oualoumidjeupisne@gmail.com</li>
                <li>Adresse : Adidogomé-Logoté</li>
            </ul>
            <p>Si vous estimez que vos droits ne sont pas respectés, vous pouvez déposer une réclamation auprès de
                l’autorité de protection des données de votre pays (par exemple, vous pouvez contacter l'IPDCP via : <br>
- Site officiel : IPDCP <br>
- Téléphone : (+228) 22 20 08 53<br>
- Email : contact@ipdcp.tg<br>
- Adresse : Lomé, Togo
).</p>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
</body>

</html>