<?php
require_once 'header.php';

// Inclure PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = '';
$error = '';
$nom = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = htmlspecialchars($_POST['nom'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    // Validation des champs
    if (empty($nom) || empty($email) || empty($message)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email est invalide.";
    } else {
        // Envoi de l'email avec PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'oualoumidjeupisne@gmail.com';
            $mail->Password = 'xwxckisfvffzhsox';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($email, $nom);
            $mail->addAddress('oualoumidjeupisne@gmail.com');
            $mail->addReplyTo($email, $nom);

            $mail->isHTML(false);
            $mail->Subject = "Nouveau message depuis la page Contact - $nom";
            $mail->Body = "Nom : $nom\nEmail : $email\n\nMessage :\n$message";

            $mail->send();
            $success = "Votre message a été envoyé avec succès à oualoumidjeupisne@gmail.com.";
        } catch (Exception $e) {
            $error = "Une erreur s'est produite lors de l'envoi de l'email : {$mail->ErrorInfo}. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactez-nous - Agence de Voyage</title>
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

    .contact-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        max-width: 600px;
        width: 100%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .contact-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
    }

    h2 {
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

    textarea.form-control {
        resize: vertical;
        min-height: 120px;
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

    .btn-success {
        background: linear-gradient(45deg, #28a745, #218838);
        border: none;
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        border-radius: 10px;
        color: #fff;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-success:hover {
        background: linear-gradient(45deg, #218838, #1e7e34);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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
        <div class="contact-card">
            <h2><i class="fas fa-envelope"></i> Contactez-nous</h2>
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="nom" class="form-label">Nom <i class="fas fa-user"></i></label>
                    <input type="text" class="form-control" id="nom" name="nom"
                        value="<?php echo htmlspecialchars($nom); ?>" required>
                    <div class="invalid-feedback">Veuillez entrer votre nom.</div>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Email <i class="fas fa-envelope"></i></label>
                    <input type="email" class="form-control" id="email" name="email"
                        value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                </div>
                <div class="mb-4">
                    <label for="message" class="form-label">Message <i class="fas fa-comment"></i></label>
                    <textarea class="form-control" id="message" name="message" rows="5"
                        required><?php echo htmlspecialchars($message); ?></textarea>
                    <div class="invalid-feedback">Veuillez entrer un message.</div>
                </div>
                <button type="submit" class="btn btn-primary mb-3">Envoyer</button>
            </form>
            <?php if ($success): ?>
            <p class="text-center">Vous pouvez également nous contacter directement via WhatsApp :</p>
            <a href="https://wa.me/22893360150?text=Nom:%20<?php echo urlencode($nom); ?>%20Email:%20<?php echo urlencode($email); ?>%20Message:%20<?php echo urlencode($message); ?>"
                target="_blank" class="btn btn-success">
                <i class="fab fa-whatsapp"></i> Envoyer sur WhatsApp
            </a>
            <?php endif; ?>
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