<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$success = '';
$error = '';
$reservation_id = intval($_POST['reservation_id']);

try {
    $stmt = $db->query("SELECT id_utilisateur FROM reservations WHERE id = ? AND statut = 'Approuvé'", [$reservation_id], 'i');
    $reservation = $stmt->get_result()->fetch_assoc();

    if ($reservation && $reservation['id_utilisateur'] == $_SESSION['user_id']) {
        $admin_id = 1; // ID de l'admin (à ajuster selon votre système)
        $message = "Demande de paiement manuel pour la réservation ID: $reservation_id par l'utilisateur ID: {$_SESSION['user_id']}.";
        $db->query("INSERT INTO notifications (utilisateur_id, message, date_notification) VALUES (?, ?, NOW())", [$admin_id, $message], 'is');
        $success = "Demande de paiement manuel envoyée à l'admin avec succès.";
    } else {
        $error = "Réservation invalide ou non autorisée.";
    }
} catch (Exception $e) {
    $error = "Erreur : " . $e->getMessage();
}

$db->close();

header("Location: user_dashboard.php?success=" . urlencode($success) . "&error=" . urlencode($error));
exit;
?>