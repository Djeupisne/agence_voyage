<?php
session_start();
require_once 'database.php';
require_once 'email_config.php';

// Charger PHPMailer au niveau global
require 'vendor/PHPMailer/src/Exception.php';
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';

// Déclarations "use" au niveau global
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $email = htmlspecialchars($_POST['email']);
        $password = $_POST['password'];

        try {
            $stmt = $db->query("SELECT * FROM utilisateurs WHERE email = ?", [$email], 's');
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {
                $error = "Aucun utilisateur trouvé pour l'email : $email";
            } else {
                $password_field = isset($user['mot_de_passe']) ? 'mot_de_passe' : (isset($user['password']) ? 'password' : null);
                if ($password_field) {
                    if (isset($user['deleted']) && $user['deleted'] == 1) {
                        $error = "Votre compte a été supprimé. Veuillez contacter l'administrateur pour le restaurer.";
                    } elseif (password_verify($password, $user[$password_field])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['nom'] = $user['nom'];
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $error = "Mot de passe incorrect pour l'email : $email";
                    }
                } else {
                    $error = "Aucune colonne de mot de passe trouvée (ni 'mot_de_passe' ni 'password') pour l'email : $email";
                }
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la connexion : " . $e->getMessage();
        }
    } elseif (isset($_POST['reset_request'])) {
        $email = htmlspecialchars($_POST['reset_email']);

        if (empty($email)) {
            $error = "Veuillez entrer une adresse email.";
        } else {
            try {
                $stmt = $db->query("SELECT id FROM utilisateurs WHERE email = ?", [$email], 's');
                $user = $stmt->get_result()->fetch_assoc();

                if ($user) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Supprimer les anciens tokens pour cet email
                    $db->query("DELETE FROM password_resets WHERE email = ?", [$email], 's');

                    // Insérer le nouveau token
                    $stmt = $db->query(
                        "INSERT INTO password_resets (email, token, expiry) VALUES (?, ?, ?)",
                        [$email, $token, $expiry],
                        'sss'
                    );
                    if ($stmt) {
                        // Envoyer l'email de réinitialisation
                        $mail = new PHPMailer(true);
                        try {
                            // Configuration SMTP
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = EMAIL_USERNAME;
                            $mail->Password = EMAIL_PASSWORD;
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            // Activer le débogage SMTP pour diagnostiquer les problèmes
                            $mail->SMTPDebug = 2;
                            $mail->Debugoutput = function($str, $level) {
                                file_put_contents('smtp_debug.log', "[$level] $str\n", FILE_APPEND);
                            };

                            // Destinataire et expéditeur
                            $mail->setFrom(EMAIL_USERNAME, 'Agence de Voyage'); // Utiliser l'email réel
                            $mail->addAddress($email);

                            // Contenu de l'email
                            $reset_link = "http://localhost/agence_voyage/reset_password.php?token=" . $token;
                            $mail->isHTML(true);
                            $mail->Subject = 'Réinitialisation de votre mot de passe - Agence de Voyage';
                            $mail->Body = "<h2>Réinitialisation de mot de passe</h2>
                                           <p>Bonjour,</p>
                                           <p>Vous avez demandé une réinitialisation de mot de passe pour votre compte sur Agence de Voyage.</p>
                                           <p>Cliquez sur le lien suivant pour réinitialiser votre mot de passe (valide 1 heure) :</p>
                                           <p><a href='$reset_link'>Réinitialiser mon mot de passe</a></p>
                                           <p>Si vous n'avez pas fait cette demande, veuillez ignorer cet email.</p>
                                           <p>Cordialement,<br>L'équipe Agence de Voyage</p>";
                            $mail->AltBody = "Réinitialisation de mot de passe\n\nBonjour,\n\nVous avez demandé une réinitialisation de mot de passe pour votre compte sur Agence de Voyage.\n\nCliquez sur le lien suivant pour réinitialiser votre mot de passe (valide 1 heure) :\n$reset_link\n\nSi vous n'avez pas fait cette demande, veuillez ignorer cet email.\n\nCordialement,\nL'équipe Agence de Voyage";

                            if ($mail->send()) {
                                $success = "Un lien de réinitialisation a été envoyé à $email. Vérifiez votre boîte de réception (y compris les spams).";
                            } else {
                                $error = "L'email n'a pas été envoyé avec succès, bien que l'opération soit marquée comme réussie. Vérifiez smtp_debug.log pour plus de détails.";
                            }
                        } catch (Exception $e) {
                            $error = "Échec de l'envoi de l'email de réinitialisation : " . $mail->ErrorInfo . ". Vérifiez smtp_debug.log pour plus de détails.";
                        }
                    } else {
                        $error = "Erreur lors de l'enregistrement du token dans la base de données.";
                    }
                } else {
                    $error = "Aucun compte trouvé avec cet email.";
                }
            } catch (Exception $e) {
                $error = "Erreur lors de la demande de réinitialisation : " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Agence de Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #f0f4f8, #d9e4f5);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        font-family: 'Arial', sans-serif;
        animation: gradientAnimation 10s ease infinite;
    }

    @keyframes gradientAnimation {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .content {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px 0;
    }

    .login-container {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        max-width: 450px;
        width: 100%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-container:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    .login-header {
        color: #1a3c6d;
        text-align: center;
        margin-bottom: 1.5rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .form-label {
        font-weight: 500;
        color: #1a3c6d;
        margin-bottom: 0.5rem;
    }

    .form-control {
        border-radius: 10px;
        border: 2px solid #e9ecef;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .form-control:focus {
        border-color: #1a3c6d;
        box-shadow: 0 0 10px rgba(26, 60, 109, 0.2);
        outline: none;
    }

    .btn-primary {
        background: linear-gradient(45deg, #1a3c6d, #2a5ca0);
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        border-radius: 10px;
        width: 100%;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(45deg, #153e75, #1f4680);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(26, 60, 109, 0.4);
    }

    .alert {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border: none;
        animation: fadeIn 0.5s ease-in;
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .forgot-password,
    .reset-request,
    .register-link {
        text-align: center;
        margin-top: 1rem;
    }

    .reset-form {
        display: none;
        margin-top: 1.5rem;
    }

    .toggle-form {
        cursor: pointer;
        color: #1a3c6d;
        text-decoration: underline;
        transition: color 0.3s ease;
    }

    .toggle-form:hover {
        color: #153e75;
    }

    .register-link a {
        color: #1a3c6d;
        text-decoration: underline;
        transition: color 0.3s ease;
    }

    .register-link a:hover {
        color: #153e75;
    }

    footer {
        margin-top: auto;
        text-align: center;
        padding: 1rem 0;
        color: #666;
        background: rgba(248, 249, 250, 0.9);
        backdrop-filter: blur(5px);
    }
    </style>
</head>

<body>
    <div class="content">
        <div class="login-container">
            <h2 class="login-header"><i class="fas fa-sign-in-alt"></i> Connexion</h2>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Formulaire de connexion -->
            <form method="POST" id="loginForm" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="email" class="form-label">Email <i class="fas fa-envelope"></i></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe <i class="fas fa-lock"></i></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Veuillez entrer votre mot de passe.</div>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Se connecter</button>
            </form>

            <div class="forgot-password">
                <span class="toggle-form" onclick="toggleForm()">Mot de passe oublié ?</span>
            </div>

            <!-- Formulaire de demande de réinitialisation -->
            <form method="POST" id="resetForm" class="reset-form needs-validation" novalidate>
                <div class="mb-4">
                    <label for="reset_email" class="form-label">Entrez votre email <i
                            class="fas fa-envelope"></i></label>
                    <input type="email" name="reset_email" id="reset_email" class="form-control" required>
                    <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                </div>
                <button type="submit" name="reset_request" class="btn btn-primary">Envoyer le lien de
                    réinitialisation</button>
                <div class="reset-request">
                    <span class="toggle-form" onclick="toggleForm()">Retour à la connexion</span>
                </div>
            </form>

            <p class="register-link">Pas de compte ? <a href="register.php">S'inscrire</a></p>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleForm() {
        const loginForm = document.getElementById('loginForm');
        const resetForm = document.getElementById('resetForm');
        if (loginForm.style.display === 'none') {
            loginForm.style.display = 'block';
            resetForm.style.display = 'none';
        } else {
            loginForm.style.display = 'none';
            resetForm.style.display = 'block';
        }
    }

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