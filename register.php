<?php
session_start();
require_once 'database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$db = new Database();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        // Vérifier si l'email existe déjà (actif ou supprimé)
        $stmt = $db->query("SELECT * FROM utilisateurs WHERE email = ?", [$email], 's');
        $existing_user = $stmt->get_result()->fetch_assoc();

        if ($existing_user) {
            $error = "Cet email est déjà utilisé. Veuillez utiliser un autre email.";
        } else {
            $result = $db->query(
                "INSERT INTO utilisateurs (nom, email, password, role) VALUES (?, ?, ?, 'utilisateur')",
                [$nom, $email, $password],
                'sss' // nom, email et mot_de_passe sont des chaînes (s)
            );

            if ($result) {
                $success = "Inscription réussie ! Vous pouvez maintenant vous <a href='login.php'>connecter</a>.";
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
    } catch (Exception $e) {
        $error = "Erreur lors de l'inscription : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Agence de Voyage</title>
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
        padding: 20px 0;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 2.5rem;
        max-width: 500px;
        width: 100%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .register-card:hover {
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

    .btn-custom {
        padding: 0.75rem 1.5rem;
        font-size: 1.1rem;
        border-radius: 10px;
        width: 100%;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: linear-gradient(45deg, #1a3c6d, #2a5ca0);
        border: none;
        color: #fff;
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
    }

    .alert-success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        animation: fadeIn 0.5s ease-in;
    }

    .alert-danger {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .text-center a {
        color: #1a3c6d;
        text-decoration: underline;
        transition: color 0.3s ease;
    }

    .text-center a:hover {
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
        <div class="register-card">
            <h2><i class="fas fa-user-plus"></i> Inscription</h2>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="nom" class="form-label">Nom <i class="fas fa-user"></i></label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                    <div class="invalid-feedback">Veuillez entrer votre nom.</div>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Email <i class="fas fa-envelope"></i></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe <i class="fas fa-lock"></i></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">Veuillez entrer un mot de passe.</div>
                </div>
                <button type="submit" class="btn btn-primary btn-custom mb-3">S'inscrire</button>
                <p class="text-center">Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </form>
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