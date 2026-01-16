<?php
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

// Vérifier si une réservation est spécifiée et appartient à l'utilisateur
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservation_id']) && isset($_POST['amount'])) {
    $reservation_id = intval($_POST['reservation_id']);
    $amount = intval($_POST['amount']) / 100; // Convertir en euros
    
    // Vérifier que la réservation est approuvée et appartient à l'utilisateur
    $stmt = $db->query(
        "SELECT r.*, v.prix, p.payment_status 
         FROM reservations r 
         JOIN voyages v ON r.id_voyage = v.id 
         LEFT JOIN paiements p ON r.id = p.reservation_id 
         WHERE r.id = ? AND r.id_utilisateur = ? AND r.statut = 'Approuvé' AND (p.payment_status = 'pending' OR p.payment_status IS NULL)",
        [$reservation_id, $user_id],
        'ii'
    );
    
    $reservation = $stmt->get_result()->fetch_assoc();
    
    if (!$reservation) {
        $error = "Réservation non trouvée, non approuvée ou déjà payée.";
    } else {
        // Simuler le processus de paiement (dans un vrai système, vous utiliseriez une API de paiement)
        $payment_success = true; // Simuler un paiement réussi
        
        if ($payment_success) {
            // Mettre à jour le statut du paiement
            $result = $db->query(
                "UPDATE paiements SET payment_status = 'completed', statut = 'Payé', date_paiement = NOW() WHERE reservation_id = ?",
                [$reservation_id],
                'i'
            );
            
            if ($result) {
                $success = "Paiement effectué avec succès pour la réservation #$reservation_id.";
                
                // Ajouter à l'historique
                $db->query(
                    "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details) VALUES (?, ?, ?, NOW(), ?)",
                    ['Paiement Réservation', $user_id, $reservation_id, "Utilisateur a effectué le paiement pour la réservation"],
                    'siis'
                );
                
                // Envoyer une notification
                $message = "Votre paiement pour la réservation #$reservation_id a été accepté.";
                $db->query(
                    "INSERT INTO notifications (utilisateur_id, message, date_notification) VALUES (?, ?, NOW())",
                    [$user_id, $message],
                    'is'
                );
                
                // Rediriger vers le tableau de bord après 3 secondes
                header("Refresh: 3; url=user_dashboard.php");
            } else {
                $error = "Erreur lors de la mise à jour du statut de paiement.";
            }
        } else {
            $error = "Le paiement a échoué. Veuillez réessayer.";
            
            // Mettre à jour le statut du paiement comme échoué
            $db->query(
                "UPDATE paiements SET payment_status = 'failed', statut = 'Échoué' WHERE reservation_id = ?",
                [$reservation_id],
                'i'
            );
        }
    }
} else {
    $error = "Paramètres de paiement manquants ou invalides.";
}

$db->close();
?>

<div class="container py-4">
    <h2 class="text-center mb-4">Paiement</h2>

    <?php if ($success): ?>
    <div class="alert alert-success text-center">
        <?php echo htmlspecialchars($success); ?>
        <p>Vous allez être redirigé vers votre tableau de bord...</p>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger text-center">
        <?php echo htmlspecialchars($error); ?>
        <p><a href="user_dashboard.php" class="btn btn-primary mt-3">Retour au tableau de bord</a></p>
    </div>
    <?php endif; ?>

    <?php if (!$success && !$error): ?>
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Procéder au paiement</h3>
        </div>
        <div class="card-body">
            <form id="payment-form" method="POST">
                <input type="hidden" name="reservation_id"
                    value="<?php echo htmlspecialchars($_POST['reservation_id'] ?? ''); ?>">
                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">

                <div class="mb-3">
                    <label class="form-label">Montant à payer</label>
                    <div class="form-control"><?php echo number_format(($amount ?? 0), 2); ?> €</div>
                </div>

                <div class="mb-3">
                    <label for="card-number" class="form-label">Numéro de carte</label>
                    <input type="text" class="form-control" id="card-number" placeholder="4242 4242 4242 4242" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="card-expiry" class="form-label">Date d'expiration</label>
                        <input type="text" class="form-control" id="card-expiry" placeholder="MM/AA" required>
                    </div>
                    <div class="col-md-6">
                        <label for="card-cvc" class="form-label">CVC</label>
                        <input type="text" class="form-control" id="card-cvc" placeholder="123" required>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">Payer maintenant</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>