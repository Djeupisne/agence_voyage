<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'header.php';
require_once 'database.php';

// Gérer le retour de paiement Stripe
if (isset($_GET['success']) && isset($_GET['reservation_id'])) {
    $db = new Database();
    $reservation_id = intval($_GET['reservation_id']);
    try {
        $db->query("UPDATE paiements SET payment_status = 'completed', statut = 'Approuvé' WHERE reservation_id = ?", [$reservation_id], 'i');
        $success = "Paiement effectué avec succès pour la réservation ID $reservation_id.";
    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour du paiement : " . $e->getMessage();
    }
    $db->close();
}

// Autoriser l'accès avec un ID de réservation via l'URL, même sans session
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['current_reservation_id']) ? $_SESSION['current_reservation_id'] : null);

if ($reservation_id && !isset($_SESSION['user_id'])) {
    // Si pas connecté, vérifier que l'utilisateur est le propriétaire de la réservation
    $db = new Database();
    $stmt = $db->query("SELECT id_utilisateur FROM reservations WHERE id = ?", [$reservation_id], 'i');
    $owner_id = $stmt->get_result()->fetch_assoc()['id_utilisateur'];
    if (!$owner_id) {
        header("Location: login.php");
        exit;
    }
    // Rediriger vers login pour se connecter avec le bon utilisateur
    $_SESSION['temp_reservation_id'] = $reservation_id;
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id']) && !$reservation_id) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$success = '';
$error = '';
$new_reservation = null;
$new_time_remaining = 0;

// Types de billets disponibles
$types_billets = ['Bussness', 'economique', ];

// Traitement de la nouvelle réservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['action'])) {
    $voyage_id = intval($_POST['voyage_id']);
    $type_billet = htmlspecialchars($_POST['type_billet']);
    $nombre_places = intval($_POST['nombre_places']);
    $date_voyage = htmlspecialchars($_POST['date_voyage']);
    $user_id = $_SESSION['user_id'];

    // Validation
    if (empty($voyage_id) || empty($type_billet) || $nombre_places <= 0 || empty($date_voyage)) {
        $error = "Veuillez remplir tous les champs correctement.";
    } else {
        // Validation : Vérifier si la date de voyage est >= à la date du jour
        $current_date = new DateTime('2025-05-05'); // Date actuelle (05/05/2025)
        $submitted_date = new DateTime($date_voyage);

        if ($submitted_date < $current_date) {
            $error = "La date de voyage ne peut pas être antérieure à la date actuelle (05/05/2025).";
        } else {
            try {
                $stmt = $db->query("SELECT prix FROM voyages WHERE id = ?", [$voyage_id], 'i');
                $voyage = $stmt->get_result()->fetch_assoc();

                if ($voyage) {
                    // Insérer la réservation avec statut 'En attente'
                    $stmt = $db->query(
                        "INSERT INTO reservations (id_utilisateur, id_voyage, type_billet, nombre_places, date_voyage, date_reservation, statut, created_at) VALUES (?, ?, ?, ?, ?, NOW(), 'En attente', NOW())",
                        [$user_id, $voyage_id, $type_billet, $nombre_places, $date_voyage],
                        'iisis'
                    );

                    // Récupérer l'ID de la réservation
                    $reservation_id = $db->get_insert_id();
                    $_SESSION['current_reservation_id'] = $reservation_id;

                    // Insérer le paiement avec statut 'En attente'
                    $montant = $voyage['prix'] * $nombre_places;
                    $stmt = $db->query(
                        "INSERT INTO paiements (reservation_id, montant, date_paiement, payment_status, statut) VALUES (?, ?, NOW(), 'pending', 'En attente')",
                        [$reservation_id, $montant],
                        'id'
                    );

                    // Ajouter une entrée dans l'historique
                    $stmt = $db->query(
                        "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details) VALUES (?, ?, ?, NOW(), ?)",
                        ['Nouvelle Réservation', $user_id, $reservation_id, "Type: $type_billet, Places: $nombre_places, Date: $date_voyage, Montant: $montant EUR, En attente d'approbation"],
                        'siis'
                    );

                    // Récupérer les détails de la nouvelle réservation pour l'affichage
                    $stmt = $db->query(
                        "SELECT r.*, v.destination AS voyage_destination, r.created_at 
                         FROM reservations r 
                         JOIN voyages v ON r.id_voyage = v.id 
                         WHERE r.id = ? AND r.id_utilisateur = ?",
                        [$reservation_id, $user_id],
                        'ii'
                    );
                    $new_reservation = $stmt->get_result()->fetch_assoc();
                    $created_at = strtotime($new_reservation['created_at']);
                    $current_time = time();
                    $elapsed_time = $current_time - $created_at;
                    $time_limit = 300; // 5 minutes en secondes
                    $new_time_remaining = max(0, $time_limit - $elapsed_time);
                } else {
                    $error = "Destination non trouvée.";
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la création de la réservation : " . $e->getMessage();
            }
        }
    }
}

// Gérer l'annulation de la nouvelle réservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancel_reservation') {
    $reservation_id = intval($_POST['reservation_id']);
    
    try {
        $stmt = $db->query(
            "SELECT created_at, statut, id_utilisateur FROM reservations WHERE id = ?",
            [$reservation_id],
            'i'
        );
        $reservation_check = $stmt->get_result()->fetch_assoc();

        if ($reservation_check && ($reservation_check['id_utilisateur'] == $_SESSION['user_id'] || isset($_SESSION['temp_reservation_id']) && $_SESSION['temp_reservation_id'] == $reservation_id)) {
            $created_at = strtotime($reservation_check['created_at']);
            $current_time = time();
            $elapsed_time = $current_time - $created_at;

            if ($elapsed_time <= 300) { // 5 minutes
                $stmt = $db->query(
                    "UPDATE reservations SET statut = 'Annulé' WHERE id = ?",
                    [$reservation_id],
                    'i'
                );
                $stmt = $db->query(
                    "UPDATE paiements SET statut = 'Annulé', payment_status = 'failed' WHERE reservation_id = ?",
                    [$reservation_id],
                    'i'
                );

                $stmt = $db->query(
                    "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details) VALUES (?, ?, ?, NOW(), ?)",
                    ['Annulation Réservation', $_SESSION['user_id'], $reservation_id, "Utilisateur a annulé la réservation"],
                    'siis'
                );

                // Notifier les admins
                $stmt = $db->query(
                    "SELECT id FROM utilisateurs WHERE role = 'admin'"
                );
                $admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                foreach ($admins as $admin) {
                    $message = "La réservation ID $reservation_id a été annulée par l'utilisateur.";
                    $db->query(
                        "INSERT INTO notifications (utilisateur_id, message, date_notification) VALUES (?, ?, NOW())",
                        [$admin['id'], $message],
                        'is'
                    );
                }

                $success = "Réservation annulée avec succès.";
                $new_reservation = null;
                unset($_SESSION['current_reservation_id']);
                unset($_SESSION['temp_reservation_id']);
            } else {
                $error = "Le délai de 5 minutes pour annuler la réservation est écoulé.";
            }
        } else {
            $error = "Réservation non trouvée ou vous n'êtes pas autorisé à l'annuler.";
        }
    } catch (Exception $e) {
        $error = "Erreur lors de l'annulation de la réservation : " . $e->getMessage();
    }
}

// Récupérer les réservations existantes de l'utilisateur (exclure les annulées)
try {
    $result = $db->query("SELECT * FROM voyages");
    $voyages = $result->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt = $db->query(
        "SELECT r.*, v.destination AS voyage_destination, v.prix, p.payment_status 
         FROM reservations r 
         JOIN voyages v ON r.id_voyage = v.id 
         LEFT JOIN paiements p ON r.id = p.reservation_id 
         WHERE r.id_utilisateur = ? 
         AND r.id != ? 
         AND r.statut != 'Annulé'",
        [$_SESSION['user_id'], $new_reservation['id'] ?? 0],
        'ii'
    );
    $user_reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Erreur lors du chargement des données : " . $e->getMessage();
    $voyages = [];
    $user_reservations = [];
}

// Récupérer les détails de la réservation en cours si spécifiée
if ($reservation_id && isset($_SESSION['user_id'])) {
    $stmt = $db->query(
        "SELECT r.*, v.destination AS voyage_destination, r.created_at 
         FROM reservations r 
         JOIN voyages v ON r.id_voyage = v.id 
         WHERE r.id = ? AND r.id_utilisateur = ? AND r.statut = 'En attente'",
        [$reservation_id, $_SESSION['user_id']],
        'ii'
    );
    $new_reservation = $stmt->get_result()->fetch_assoc();
    if ($new_reservation) {
        $created_at = strtotime($new_reservation['created_at']);
        $current_time = time();
        $elapsed_time = $current_time - $created_at;
        $time_limit = 300; // 5 minutes en secondes
        $new_time_remaining = max(0, $time_limit - $elapsed_time);
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver un Voyage - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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

    .reservation-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .reservation-card:hover {
        transform: translateY(-5px);
    }

    h2 {
        color: #1a3c6d;
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .form-label {
        font-weight: 500;
        color: #1a3c6d;
    }

    .btn-primary {
        background-color: #1a3c6d;
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #153e75;
    }

    .btn-danger {
        background-color: #dc3545;
        border: none;
        padding: 0.5rem 1rem;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }

    .btn-danger:hover {
        background-color: #c82333;
    }

    .btn-warning {
        background-color: #ffc107;
        border: none;
        padding: 0.5rem 1rem;
        font-weight: 500;
        color: #000;
        transition: background-color 0.3s ease;
    }

    .btn-warning:hover {
        background-color: #e0a800;
    }

    .alert {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .table {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
    }

    .table th {
        background-color: #1a3c6d;
        color: #ffffff;
        font-weight: 600;
    }

    .table td,
    .table th {
        padding: 0.75rem;
        vertical-align: middle;
    }

    .badge {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 5px;
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
            <h2>Réserver un Voyage</h2>
            <div class="reservation-card">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Formulaire de réservation -->
                <form method="POST">
                    <div class="mb-3">
                        <label for="voyage_id" class="form-label">Destination</label>
                        <select name="voyage_id" id="voyage_id" class="form-select" required>
                            <option value="">Sélectionnez une destination</option>
                            <?php foreach ($voyages as $voyage): ?>
                            <option value="<?php echo $voyage['id']; ?>">
                                <?php echo htmlspecialchars($voyage['destination']); ?>
                                (<?php echo $voyage['prix']; ?> EUR)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type_billet" class="form-label">Type de Billet</label>
                        <select name="type_billet" id="type_billet" class="form-select" required>
                            <?php foreach ($types_billets as $type): ?>
                            <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="nombre_places" class="form-label">Nombre de Places</label>
                        <input type="number" name="nombre_places" id="nombre_places" class="form-control" min="1"
                            required>
                    </div>
                    <div class="mb-3">
                        <label for="date_voyage" class="form-label">Date de Voyage</label>
                        <input type="date" name="date_voyage" id="date_voyage" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Réserver</button>
                </form>

                <!-- Confirmation de la nouvelle réservation -->
                <?php if ($new_reservation && $new_time_remaining > 0): ?>
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        Nouvelle Réservation ID: <?php echo $new_reservation['id']; ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Destination:</strong>
                            <?php echo htmlspecialchars($new_reservation['voyage_destination']); ?></p>
                        <p><strong>Type de Billet:</strong>
                            <?php echo htmlspecialchars($new_reservation['type_billet']); ?></p>
                        <p><strong>Nombre de Places:</strong> <?php echo $new_reservation['nombre_places']; ?></p>
                        <p><strong>Date de Voyage:</strong> <?php echo $new_reservation['date_voyage']; ?></p>
                        <p><strong>Date de Réservation:</strong> <?php echo $new_reservation['date_reservation']; ?></p>
                        <p><strong>Statut:</strong> <?php echo $new_reservation['statut']; ?></p>
                        <div id="new_timer" class="alert alert-warning">
                            Temps restant pour annuler : <span
                                id="new_time"><?php echo gmdate("i:s", $new_time_remaining); ?></span>
                        </div>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="cancel_reservation">
                            <input type="hidden" name="reservation_id" value="<?php echo $new_reservation['id']; ?>">
                            <button type="submit" class="btn btn-danger" id="new_cancelButton"
                                onclick="return confirm('Voulez-vous vraiment annuler cette réservation ?')">Annuler la
                                Réservation</button>
                        </form>
                    </div>
                </div>

                <script>
                let newTimeRemaining = <?php echo $new_time_remaining; ?>;
                const newTimerElement = document.getElementById('new_time');
                const newCancelButton = document.getElementById('new_cancelButton');

                function updateNewTimer() {
                    if (newTimeRemaining <= 0) {
                        newTimerElement.textContent = '00:00';
                        newCancelButton.disabled = true;
                        newCancelButton.textContent = 'Annulation Expirée';
                        // Rediriger après expiration
                        window.location.href = 'reservation.php';
                    } else {
                        const minutes = Math.floor(newTimeRemaining / 60);
                        const seconds = newTimeRemaining % 60;
                        newTimerElement.textContent =
                            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                        newTimeRemaining--;
                        setTimeout(updateNewTimer, 1000);
                    }
                }

                updateNewTimer();
                </script>
                <?php endif; ?>

                <!-- Liste des réservations existantes -->
                <h3 class="mt-5">Vos Réservations</h3>
                <?php if (empty($user_reservations)): ?>
                <p class="text-center">Aucune réservation active trouvée.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Destination</th>
                                <th>Type de Billet</th>
                                <th>Places</th>
                                <th>Date de Voyage</th>
                                <th>Date de Réservation</th>
                                <th>Statut</th>
                                <th>Statut Paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_reservations as $reservation): ?>
                            <tr>
                                <td><?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                                <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                                <td><?php echo $reservation['nombre_places']; ?></td>
                                <td><?php echo $reservation['date_voyage']; ?></td>
                                <td><?php echo $reservation['date_reservation']; ?></td>
                                <td><?php echo $reservation['statut']; ?></td>
                                <td>
                                    <?php if (isset($reservation['payment_status'])): ?>
                                    <?php if ($reservation['payment_status'] === 'completed'): ?>
                                    <span class="badge bg-success">Payé</span>
                                    <?php elseif ($reservation['payment_status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">En attente</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Non initié</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">Non initié</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reservation['statut'] === 'En attente'):
                                                $reservation_time = strtotime($reservation['date_reservation']);
                                                $current_time = time();
                                                $time_diff = ($current_time - $reservation_time) / 60;
                                                $remaining_seconds = max(0, (5 * 60) - ($current_time - $reservation_time));
                                                if ($time_diff > 5): ?>
                                    <a href="reservation.php?cancel=1&id=<?php echo $reservation['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Voulez-vous vraiment refuser cette réservation et le paiement associé ?')">Refuser</a>
                                    <?php else: ?>
                                    <span class="text-muted" data-reservation-id="<?php echo $reservation['id']; ?>"
                                        data-remaining-seconds="<?php echo $remaining_seconds; ?>">Refus impossible
                                        (temps restant:
                                        <span
                                            class="timer"><?php echo sprintf("%02d:%02d", floor($remaining_seconds / 60), $remaining_seconds % 60); ?></span>)</span>
                                    <?php endif; ?>
                                    <?php elseif ($reservation['statut'] === 'Approuvé' && isset($reservation['payment_status']) && $reservation['payment_status'] === 'pending'): ?>
                                    <form action="charge.php" method="POST">
                                        <input type="hidden" name="reservation_id"
                                            value="<?php echo $reservation['id']; ?>">
                                        <input type="hidden" name="amount"
                                            value="<?php echo ($reservation['nombre_places'] * $reservation['prix']) * 100; ?>">
                                        <button type="submit" class="btn btn-warning btn-sm"><i
                                                class="bi bi-credit-card"></i> Payer en ligne</button>
                                    </form>
                                    <?php elseif (isset($reservation['payment_status']) && $reservation['payment_status'] === 'completed'): ?>
                                    <span class="text-success">Paiement complété</span>
                                    <?php else: ?>
                                    <span class="text-muted">Aucune action disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const timers = document.querySelectorAll('.timer');
        timers.forEach(timer => {
            const parent = timer.closest('span');
            const remainingSeconds = parseInt(parent.getAttribute('data-remaining-seconds'));
            let timeLeft = remainingSeconds;

            const updateTimer = () => {
                if (timeLeft <= 0) {
                    const reservationId = parent.getAttribute('data-reservation-id');
                    parent.innerHTML =
                        `<a href="reservation.php?cancel=1&id=${reservationId}" class="btn btn-danger btn-sm" onclick="return confirm('Voulez-vous vraiment refuser cette réservation et le paiement associé ?')">Refuser</a>`;
                } else {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    timer.textContent =
                        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                }
            };
            updateTimer();
        });
    });
    </script>
</body>

</html>