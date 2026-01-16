<?php
require_once 'header.php';
require_once 'database.php';

$db = new Database();
$result = $db->query("SELECT * FROM voyages")->get_result();
$voyages = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="jumbotron text-center bg-primary text-white py-5 mb-5">
    <h1 class="display-4">Bienvenue chez Agence de Voyage</h1>
    <p class="lead">Découvrez des destinations de rêve et réservez votre prochain voyage dès aujourd'hui !</p>
    <?php if (!isset($_SESSION['user_id'])): ?>
    <a href="login.php" class="btn btn-light btn-lg mt-3" style="color: green;">Commencez maintenant</a>
    <?php endif; ?>
</div>

<h2 class="text-center mb-4">Nos Destinations</h2>
<div class="row">
    <?php foreach ($voyages as $voyage): ?>
    <div class="col-md-4 mb-4">
        <div class="card">
            <img src="images/<?php echo htmlspecialchars($voyage['image']); ?>" class="card-img-top"
                alt="<?php echo htmlspecialchars($voyage['destination']); ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($voyage['destination']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($voyage['description']); ?></p>
                <p class="card-text"><strong>Prix :</strong> <?php echo htmlspecialchars($voyage['prix']); ?> €</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="reservation.php?voyage_id=<?php echo $voyage['id']; ?>" class="btn btn-primary">Réserver</a>
                <?php else: ?>
                <a href="login.php" class="btn btn-primary">Connectez-vous pour réserver</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
$db->close();
require_once 'footer.php';
?>