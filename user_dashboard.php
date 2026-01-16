<?php
session_start();
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Vérifier l'existence de la colonne 'deleted'
$has_deleted_column = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM reservations LIKE 'deleted'");
    $has_deleted_column = $stmt->get_result()->num_rows > 0;
} catch (Exception $e) {
    $error .= "Erreur lors de la vérification de 'deleted' : " . $e->getMessage() . "<br>";
}

// Récupérer les réservations de l'utilisateur
try {
    $query = "SELECT r.*, v.destination AS voyage_destination, v.prix, p.payment_status, p.statut as payment_approval_status 
              FROM reservations r 
              JOIN voyages v ON r.id_voyage = v.id 
              LEFT JOIN paiements p ON r.id = p.reservation_id 
              WHERE r.id_utilisateur = ?" . ($has_deleted_column ? " AND r.deleted = 0" : "");
    $stmt = $db->query($query, [$user_id], 'i');
    $reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des réservations : " . $e->getMessage() . "<br>";
    $reservations = [];
}

$db->close();
?>

<div class="container py-4">
    <h2 class="text-center mb-4 text-primary fw-bold">Tableau de Bord Utilisateur</h2>
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Bouton Réserver un Voyage -->
    <div class="mb-4">
        <a href="reserve.php" class="btn btn-primary">Réserver un Voyage</a>
    </div>

    <!-- Réservations en Attente -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-warning text-white">
            <h3 class="mb-0">Réservations en Attente</h3>
        </div>
        <div class="card-body">
            <?php 
            $pending_reservations = array_filter($reservations, fn($r) => $r['statut'] === 'En attente');
            if (empty($pending_reservations)): ?>
            <p class="text-muted">Aucune réservation en attente.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-warning">
                        <tr>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Places</th>
                            <th>Prix Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_voyage']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['nombre_places']); ?></td>
                            <td><?php echo number_format($reservation['nombre_places'] * $reservation['prix'], 2); ?> €
                            </td>
                            <td><span class="badge bg-warning text-dark">En attente</span></td>
                            <td>
                                <form method="POST" action="cancel_reservation.php">
                                    <input type="hidden" name="reservation_id"
                                        value="<?php echo $reservation['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Voulez-vous annuler cette réservation ?')">Annuler</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Réservations Approuvées -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Réservations Approuvées</h3>
        </div>
        <div class="card-body">
            <?php 
            $approved_reservations = array_filter($reservations, fn($r) => $r['statut'] === 'Approuvé');
            if (empty($approved_reservations)): ?>
            <p class="text-muted">Aucune réservation approuvée.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Places</th>
                            <th>Prix Total</th>
                            <th>Statut Paiement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approved_reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_voyage']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['nombre_places']); ?></td>
                            <td><?php echo number_format($reservation['nombre_places'] * $reservation['prix'], 2); ?> €
                            </td>
                            <td>
                                <?php if (isset($reservation['payment_status'])): ?>
                                <?php if ($reservation['payment_status'] === 'completed'): ?>
                                <span class="badge bg-success">Payé</span>
                                <?php elseif ($reservation['payment_status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">En attente de paiement</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Échoué</span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="badge bg-secondary">Non initié</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($reservation['payment_status']) && $reservation['payment_status'] === 'pending'): ?>
                                <form action="payment.php" method="POST">
                                    <input type="hidden" name="reservation_id"
                                        value="<?php echo $reservation['id']; ?>">
                                    <input type="hidden" name="amount"
                                        value="<?php echo ($reservation['nombre_places'] * $reservation['prix']) * 100; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="bi bi-credit-card"></i> Payer maintenant
                                    </button>
                                </form>
                                <?php elseif (isset($reservation['payment_status']) && $reservation['payment_status'] === 'completed'): ?>
                                <span class="text-success">Paiement complété</span>
                                <?php else: ?>
                                <span class="text-muted">En attente d'approbation</span>
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

    <!-- Réservations Refusées/Annulées -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0">Réservations Refusées/Annulées</h3>
        </div>
        <div class="card-body">
            <?php 
            $canceled_reservations = array_filter($reservations, fn($r) => $r['statut'] === 'Refusé' || $r['statut'] === 'Annulé');
            if (empty($canceled_reservations)): ?>
            <p class="text-muted">Aucune réservation refusée ou annulée.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-danger">
                        <tr>
                            <th>Destination</th>
                            <th>Date</th>
                            <th>Places</th>
                            <th>Prix Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($canceled_reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['date_voyage']); ?></td>
                            <td><?php echo htmlspecialchars($reservation['nombre_places']); ?></td>
                            <td><?php echo number_format($reservation['nombre_places'] * $reservation['prix'], 2); ?> €
                            </td>
                            <td><span
                                    class="badge bg-danger"><?php echo htmlspecialchars($reservation['statut']); ?></span>
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