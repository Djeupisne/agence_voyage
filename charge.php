<?php
session_start();
require_once 'database.php';

if (!isset($_POST['reservation_id']) || !isset($_POST['amount'])) {
    header("Location: reservation.php?error=Paramètres manquants.");
    exit;
}

$db = new Database();
$reservation_id = intval($_POST['reservation_id']);
$amount = floatval($_POST['amount']) / 100; // Montant en euros

// Vérifier si la réservation est approuvée et en attente de paiement
try {
    $stmt = $db->query("SELECT r.statut, p.payment_status FROM reservations r LEFT JOIN paiements p ON r.id = p.reservation_id WHERE r.id = ?", [$reservation_id], 'i');
    $result = $stmt->get_result()->fetch_assoc();
    if (!$result || $result['statut'] !== 'Approuvé' || $result['payment_status'] !== 'pending') {
        header("Location: reservation.php?error=Action non autorisée.");
        exit;
    }
} catch (Exception $e) {
    header("Location: reservation.php?error=Erreur lors de la vérification : " . $e->getMessage());
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de Paiement - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    .payment-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .payment-card:hover {
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

    .form-control {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 0.75rem;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        border-color: #1a3c6d;
        box-shadow: 0 0 5px rgba(26, 60, 109, 0.3);
    }

    .btn-custom {
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        border-radius: 8px;
        width: 100%;
    }

    .btn-success {
        background-color: #28a745;
        border: none;
        transition: background-color 0.3s ease;
    }

    .btn-success:hover {
        background-color: #218838;
    }

    .btn-secondary {
        background-color: #6c757d;
        border: none;
        transition: background-color 0.3s ease;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    .text-highlight {
        color: #1a3c6d;
        font-weight: bold;
    }

    .invalid-feedback {
        font-size: 0.875rem;
        color: #dc3545;
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
            <div class="payment-card">
                <h2><i class="fas fa-credit-card"></i> Formulaire de Paiement</h2>
                <div class="mb-4 text-center">
                    <h4>Réservation ID: <span class="text-highlight"><?php echo $reservation_id; ?></span></h4>
                    <p><strong>Montant à payer:</strong> <span
                            class="text-highlight"><?php echo number_format($amount, 2); ?> EUR</span></p>
                </div>
                <form method="POST" action="process_payment.php" class="needs-validation" novalidate>
                    <input type="hidden" name="reservation_id" value="<?php echo $reservation_id; ?>">
                    <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                    <div class="row g-3">
                        <div class="form-group col-12">
                            <label for="card_number" class="form-label">Numéro de carte <i
                                    class="fas fa-credit-card"></i></label>
                            <input type="text" class="form-control" id="card_number" name="card_number"
                                placeholder="1234 5678 9012 3456" required pattern="\d{4}\s?\d{4}\s?\d{4}\s?\d{4}">
                            <div class="invalid-feedback">Veuillez entrer un numéro de carte valide (16 chiffres).</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="expiry_date" class="form-label">Date d'expiration <i
                                    class="fas fa-calendar-alt"></i></label>
                            <input type="text" class="form-control" id="expiry_date" name="expiry_date"
                                placeholder="MM/AA" required pattern="(0[1-9]|1[0-2])/(2[5-9]|[3-9][0-9])">
                            <div class="invalid-feedback">Veuillez entrer une date valide (MM/AA, ex. 05/25).</div>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cvv" class="form-label">CVV <i class="fas fa-lock"></i></label>
                            <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123" required
                                pattern="\d{3,4}">
                            <div class="invalid-feedback">Veuillez entrer un CVV valide (3 ou 4 chiffres).</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-custom mt-4"><i class="fas fa-check"></i> Confirmer
                        le Paiement</button>
                    <a href="reservation.php" class="btn btn-secondary btn-custom mt-3"><i class="fas fa-times"></i>
                        Annuler</a>
                </form>
            </div>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validation Bootstrap
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>

</html>

<?php
$db->close();
?>