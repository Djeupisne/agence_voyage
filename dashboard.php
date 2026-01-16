<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$stmt = $db->query("SELECT r.*, v.destination, v.prix FROM reservations r JOIN voyages v ON r.id_voyage = v.id WHERE r.id_utilisateur = ?", [$_SESSION['user_id']], 'i');
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SESSION['role'] == 'admin') {
    $stmt = $db->query("SELECT r.*, v.destination, v.prix, u.nom FROM reservations r JOIN voyages v ON r.id_voyage = v.id JOIN utilisateurs u ON r.id_utilisateur = u.id");
    $all_reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0f4f8, #d9e4f5);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Arial', sans-serif;
        }
        .content {
            flex: 1;
            padding: 20px 0;
        }
        .dashboard-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        h2 {
            color: #1a3c6d;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        h3.card-title {
            color: #1a3c6d;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #1a3c6d;
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #153e75;
        }
        .table {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background-color: #1a3c6d;
            color: #ffffff;
        }
        .table td, .table th {
            padding: 0.75rem;
            vertical-align: middle;
        }
        p {
            color: #555;
            text-align: center;
            margin: 1rem 0;
        }
        a {
            text-decoration: none;
        }
        footer {
            margin-top: auto;
            text-align: center;
            padding: 1rem 0;
            color: #666;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="container">
            <h2>Tableau de bord</h2>
            <div class="dashboard-card">
                <h3 class="card-title">Vos réservations</h3>
                <a href="reservation.php" class="btn btn-primary mb-3">Réserver un Voyage</a>
                <?php if (empty($reservations)): ?>
                    <p>Aucune réservation pour le moment.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Destination</th>
                                <th>Prix Total</th>
                                <th>Type de Billet</th>
                                <th>Nombre de Places</th>
                                <th>Date de Voyage</th>
                                <th>Date de Réservation</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reservation['destination']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['prix'] * $reservation['nombre_places']); ?> €</td>
                                <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['nombre_places']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_voyage']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['date_reservation']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['statut']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['role'] == 'admin'): ?>
            <div class="dashboard-card">
                <h3 class="card-title">Administration</h3>
                <p><a href="send_users_email.php" class="btn btn-primary">Envoyer la liste des utilisateurs par email</a></p>
                <p><a href="admin_manage.php" class="btn btn-primary">Gérer les Réservations, Utilisateurs et Factures</a></p>
            </div>
            <div class="dashboard-card">
                <h3 class="card-title">Toutes les réservations</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Destination</th>
                            <th>Prix Total</th>
                            <th>Type de Billet</th>
                            <th>Nombre de Places</th>
                            <th>Date de Voyage</th>
                            <th>Date de Réservation</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['nom']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['destination']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['prix'] * $reservation['nombre_places']); ?> €</td>
                            <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['nombre_places']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_voyage']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_reservation']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['statut']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $db->close();
    require_once 'footer.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>