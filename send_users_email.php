<?php
require_once 'header.php';
require_once 'database.php';
require_once 'email_config.php';

// Vérifier que l'utilisateur est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Charger PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier que les fichiers PHPMailer existent
$exception_file = 'vendor/PHPMailer/src/Exception.php';
$phpmailer_file = 'vendor/PHPMailer/src/PHPMailer.php';
$smtp_file = 'vendor/PHPMailer/src/SMTP.php';

if (!file_exists($exception_file) || !file_exists($phpmailer_file) || !file_exists($smtp_file)) {
    die("Erreur : Les fichiers PHPMailer sont manquants. Veuillez vérifier que le dossier vendor/PHPMailer/src contient les fichiers nécessaires.");
}

require $exception_file;
require $phpmailer_file;
require $smtp_file;

// Récupérer tous les utilisateurs
$db = new Database();
$result = $db->query("SELECT * FROM utilisateurs")->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Construire le corps de l'email
$email_body = "<h2>Liste des utilisateurs de l'Agence de Voyage</h2>";
$email_body .= "<table border='1' cellpadding='5'>";
$email_body .= "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th></tr>";
foreach ($users as $user) {
    $email_body .= "<tr>";
    $email_body .= "<td>" . htmlspecialchars($user['id']) . "</td>";
    $email_body .= "<td>" . htmlspecialchars($user['nom']) . "</td>";
    $email_body .= "<td>" . htmlspecialchars($user['email']) . "</td>";
    $email_body .= "<td>" . htmlspecialchars($user['role']) . "</td>";
    $email_body .= "</tr>";
}
$email_body .= "</table>";

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mail = new PHPMailer(true);
    try {
        // Activer le débogage SMTP pour diagnostiquer les problèmes
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            file_put_contents('smtp_debug.log', "[$level] $str\n", FILE_APPEND);
        };

        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Destinataire et expéditeur
        $mail->setFrom('oualoumidjeupisne@gmail.com', 'Agence de Voyage');
        $mail->addAddress('oualoumidjeupisne@gmail.com');

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Liste des utilisateurs - Agence de Voyage';
        $mail->Body = $email_body;
        $mail->AltBody = strip_tags($email_body);

        $mail->send();
        $success = "Email envoyé avec succès !";
    } catch (Exception $e) {
        $error = "Échec d'envoi, vérifiez votre connexion internet !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envoyer la liste des utilisateurs - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

    .dashboard-card {
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

    .dashboard-card:hover {
        transform: translateY(-5px);
    }

    h2 {
        color: #1a3c6d;
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }

    .btn-primary {
        background-color: #1a3c6d;
        border: none;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        transition: background-color 0.3s ease;
        width: 100%;
    }

    .btn-primary:hover {
        background-color: #153e75;
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

    p {
        color: #555;
        text-align: center;
        margin: 1rem 0;
    }

    strong {
        color: #1a3c6d;
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
            <h2>Envoyer la liste des utilisateurs par email</h2>
            <div class="dashboard-card">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <p>Cliquez sur le bouton ci-dessous pour envoyer la liste de tous les utilisateurs à
                    <strong>oualoumidjeupisne@gmail.com</strong>.</p>
                <form method="POST">
                    <button type="submit" class="btn btn-primary">Envoyer l'email</button>
                </form>
            </div>
        </div>
    </div>
    <?php require_once 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>