<?php
require_once 'config.php';
session_start();
// Stocker temporairement l'ID de la réservation en cours avant déconnexion
if (isset($_SESSION['current_reservation_id'])) {
    $_SESSION['temp_reservation_id'] = $_SESSION['current_reservation_id'];
}
session_destroy();
header("Location: login.php");
exit;
?>