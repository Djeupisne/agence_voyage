<?php
session_start();
require_once 'database.php';

if (!isset($_POST['reservation_id']) || !isset($_POST['amount'])) {
    header("Location: reservation.php?error=Paramètres manquants.");
    exit;
}

$db = new Database();
$reservation_id = intval($_POST['reservation_id']);
$amount = floatval($_POST['amount']);

// Simuler un paiement réussi (vous pouvez ajouter une validation des champs ici)
$payment_success = true;

if ($payment_success) {
    try {
        $db->query("UPDATE paiements SET payment_status = 'completed', statut = 'Approuvé' WHERE reservation_id = ?", [$reservation_id], 'i');
        header("Location: reservation.php?success=1&reservation_id=" . $reservation_id);
    } catch (Exception $e) {
        header("Location: reservation.php?error=Erreur lors de la mise à jour : " . $e->getMessage());
    }
} else {
    header("Location: reservation.php?error=Échec du paiement.");
}

$db->close();
?>