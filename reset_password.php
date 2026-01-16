<?php
session_start();
require_once 'database.php';

$success = '';
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = new Database();

    try {
        $stmt = $db->query("SELECT email, expiry FROM password_resets WHERE token = ?", [$token], 's');
        $reset = $stmt->get_result()->fetch_assoc();

        if ($reset && new DateTime() <= new DateTime($reset['expiry'])) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'];
                $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];

                if (empty($nouveau_mot_de_passe) || empty($confirmation_mot_de_passe)) {
                    $error = "Veuillez remplir tous les champs.";
                } elseif ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
                    $error = "Les mots de passe ne correspondent pas.";
                } elseif (strlen($nouveau_mot_de_passe) < 6) {
                    $error = "Le mot de passe doit contenir au moins 6 caractères.";
                } else {
                    $password_field = 'password'; // Ajustez selon votre table (mot_de_passe ou password)
                    $hashed_password = password_hash($nouveau_mot_de_passe, PASSWORD_DEFAULT);
                    $db->query("UPDATE utilisateurs SET $password_field = ? WHERE email = ?", [$hashed_password, $reset['email']], 'ss');

                    // Supprimer le token après utilisation
                    $db->query("DELETE FROM password_resets WHERE token = ?", [$token], 's');

                    // Récupérer les informations de l'utilisateur pour restaurer la session
                    $stmt = $db->query("SELECT id, nom, role FROM utilisateurs WHERE email = ?", [$reset['email']], 's');
                    $user = $stmt->get_result()->fetch_assoc();
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['role'] = $user['role'];

                    $success = "Mot de passe réinitialisé avec succès. Vous êtes connecté.";
                    header("Refresh: 2; url=dashboard.php");
                }
            }
        } else {
            $error = "Lien de réinitialisation invalide ou expiré.";
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la réinitialisation : " . $e->getMessage();
    }
    $db->close();
} else {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du Mot de Passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #d3e0ea 100%);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        font-family: 'Arial', sans-serif;
    }

    .reset-container {
        background: #ffffff;
        padding: 2rem 3rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }

    .reset-header {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #1a3c6d;
    }

    .form-label {
        font-weight: 500;
        color: #333;
    }

    .form-control {
        border-radius: 5px;
        border: 1px solid #ced4da;
        transition: border-color 0.3s ease;
    }

    .form-control:focus {
        border-color: #1a3c6d;
        box-shadow: none;
    }

    .btn-primary {
        background-color: #1a3c6d;
        border: none;
        padding: 0.75rem;
        transition: background-color 0.3s ease;
    }

    .btn-primary:hover {
        background-color: #153e75;
    }

    .alert {
        margin-top: 1rem;
        border-radius: 5px;
    }
    </style>
</head>

<body>
    <div class="reset-container">
        <h2 class="reset-header">Réinitialiser le Mot de Passe</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="nouveau_mot_de_passe" class="form-label">Nouveau Mot de Passe</label>
                <input type="password" name="nouveau_mot_de_passe" id="nouveau_mot_de_passe" class="form-control"
                    required>
            </div>
            <div class="mb-3">
                <label for="confirmation_mot_de_passe" class="form-label">Confirmer le Mot de Passe</label>
                <input type="password" name="confirmation_mot_de_passe" id="confirmation_mot_de_passe"
                    class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Réinitialiser</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>