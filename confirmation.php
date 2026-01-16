<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$db->close();
?>

<h2 class="text-center mb-4">Confirmation</h2>
<div class="col-md-6 mx-auto">
    <p>Cette page est actuellement inactive. Veuillez utiliser reservation.php pour gérer vos réservations.</p>
</div>

<?php require_once 'footer.php'; ?>