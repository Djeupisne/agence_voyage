<?php
require_once 'header.php';
require_once 'database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$success = '';
$error = '';

// Vérifier si la colonne 'deleted' existe dans les tables
$tables_to_check = ['reservations', 'utilisateurs', 'historique', 'factures', 'notifications', 'paiements'];
$has_deleted_column = [];

foreach ($tables_to_check as $table) {
    $has_deleted_column[$table] = false;
    try {
        $stmt = $db->query("SHOW COLUMNS FROM $table LIKE 'deleted'");
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $has_deleted_column[$table] = true;
        }
    } catch (Exception $e) {
        $error .= "Erreur lors de la vérification de la colonne 'deleted' dans $table : " . $e->getMessage() . "<br>";
    }
}

// Vérifier si la colonne 'payment_status' existe dans la table paiements
$has_payment_status_column = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM paiements LIKE 'payment_status'");
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $has_payment_status_column = true;
    }
} catch (Exception $e) {
    $error .= "Erreur lors de la vérification de la colonne 'payment_status' dans paiements : " . $e->getMessage() . "<br>";
}

// Gérer les actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Modifier une réservation
    if ($action == 'edit_reservation') {
        $reservation_id = intval($_POST['reservation_id']);
        $type_billet = htmlspecialchars($_POST['type_billet']);
        $nombre_places = intval($_POST['nombre_places']);
        $date_voyage = htmlspecialchars($_POST['date_voyage']);

        // Validation : Vérifier si la date de voyage est >= à la date du jour
        $current_date = new DateTime('2025-05-05'); // Date actuelle (05/05/2025)
        $submitted_date = new DateTime($date_voyage);

        if ($submitted_date < $current_date) {
            $error = "La date de voyage ne peut pas être antérieure à la date actuelle (05/05/2025).";
        } else {
            $result = $db->query(
                "UPDATE reservations SET type_billet = ?, nombre_places = ?, date_voyage = ? WHERE id = ?",
                [$type_billet, $nombre_places, $date_voyage, $reservation_id],
                'sssi'
            );

            if ($result) {
                $db->query(
                    "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                    ['Modification Réservation', $_SESSION['user_id'], $reservation_id, "Admin a modifié la réservation"],
                    'siis'
                );
                $success = "Réservation modifiée avec succès.";
            } else {
                $error = "Erreur lors de la modification de la réservation.";
            }
        }
    }

    // Approuver ou refuser une réservation
    if ($action == 'manage_reservation') {
        $reservation_id = intval($_POST['reservation_id']);
        $status = $_POST['status']; // 'approve' ou 'refuse'

        // Ajouter id_utilisateur dans la sélection
        $stmt = $db->query(
            "SELECT id_utilisateur, created_at, statut FROM reservations WHERE id = ? AND statut = 'En attente'" . ($has_deleted_column['reservations'] ? " AND deleted = 0" : ""),
            [$reservation_id],
            'i'
        );
        $reservation = $stmt->get_result()->fetch_assoc();

        if ($reservation && isset($reservation['id_utilisateur'])) {
            // Vérifier si plus de 5 minutes se sont écoulées
            $created_at = strtotime($reservation['created_at']);
            $current_time = time();
            $elapsed_time = ($current_time - $created_at) / 60; // Temps écoulé en minutes

            if ($elapsed_time < 5) {
                $error = "Cette réservation ne peut pas encore être traitée. Elle est encore dans la période d'annulation de 5 minutes.";
            } else {
                $new_status = ($status === 'approve') ? 'Approuvé' : 'Refusé';
                $utilisateur_id = $reservation['id_utilisateur'];

                // Mettre à jour le statut de la réservation
                $result1 = $db->query("UPDATE reservations SET statut = ? WHERE id = ?", [$new_status, $reservation_id], 'si');
                // Mettre à jour le statut du paiement
                $update_payment_query = "UPDATE paiements SET statut = ?" . ($has_payment_status_column ? ", payment_status = ?" : "") . " WHERE reservation_id = ?";
                $update_payment_params = ($has_payment_status_column) ? [$new_status, ($status === 'approve' ? 'pending' : 'failed'), $reservation_id] : [$new_status, $reservation_id];
                $update_payment_types = ($has_payment_status_column) ? 'ssi' : 'si';
                $result2 = $db->query($update_payment_query, $update_payment_params, $update_payment_types);

                if ($result1 && $result2) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Mise à jour Réservation', $_SESSION['user_id'], $reservation_id, "Admin a " . ($status === 'approve' ? 'approuvé' : 'refusé') . " la réservation"],
                        'siis'
                    );

                    if ($status === 'approve' && $utilisateur_id) {
                        $message = "Votre réservation (ID: $reservation_id) a été approuvée par l'administrateur. Veuillez procéder au paiement.";
                        $db->query(
                            "INSERT INTO notifications (utilisateur_id, message, date_notification" . ($has_deleted_column['notifications'] ? ", deleted" : "") . ") VALUES (?, ?, NOW()" . ($has_deleted_column['notifications'] ? ", 0" : "") . ")",
                            [$utilisateur_id, $message],
                            'is'
                        );
                    }

                    $success = "Réservation " . ($status === 'approve' ? 'approuvée' : 'refusée') . " avec succès.";
                } else {
                    $error = "Erreur lors de la gestion de la réservation.";
                }
            }
        } else {
            $error = "Réservation non trouvée, déjà traitée ou annulée.";
        }
    }

    // Supprimer une réservation
    if ($action == 'delete_reservation') {
        $reservation_id = intval($_POST['reservation_id']);

        $query = "SELECT " . ($has_deleted_column['reservations'] ? "deleted" : "1") . " FROM reservations WHERE id = ?";
        $stmt = $db->query($query, [$reservation_id], 'i');
        $reservation = $stmt->get_result()->fetch_assoc();

        if (!$reservation) {
            $error = "Réservation non trouvée.";
        } else {
            $is_deleted = $has_deleted_column['reservations'] ? ($reservation['deleted'] == 1) : false;
            if ($is_deleted) {
                $error = "Réservation déjà supprimée.";
            } else {
                if ($has_deleted_column['reservations']) {
                    $result1 = $db->query("UPDATE reservations SET deleted = 1 WHERE id = ?", [$reservation_id], 'i');
                } else {
                    $result1 = $db->query("DELETE FROM reservations WHERE id = ?", [$reservation_id], 'i');
                }
                $update_payment_query = "UPDATE paiements SET statut = 'Refusé'" . ($has_payment_status_column ? ", payment_status = 'failed'" : "") . " WHERE reservation_id = ?";
                $result2 = $db->query($update_payment_query, [$reservation_id], 'i');

                if ($result1 && $result2) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Suppression Réservation', $_SESSION['user_id'], $reservation_id, "Admin a marqué la réservation comme supprimée"],
                        'siis'
                    );
                    $success = "Réservation marquée comme supprimée avec succès.";
                } else {
                    $error = "Erreur lors de la suppression de la réservation.";
                }
            }
        }
    }

    // Restaurer une réservation
    if ($action == 'restore_reservation') {
        $reservation_id = intval($_POST['reservation_id']);

        $query = "SELECT statut" . ($has_deleted_column['reservations'] ? ", deleted" : "") . " FROM reservations WHERE id = ?";
        $stmt = $db->query($query, [$reservation_id], 'i');
        $reservation = $stmt->get_result()->fetch_assoc();

        if (!$reservation) {
            $error = "Réservation non trouvée.";
        } else {
            $current_status = trim($reservation['statut']);
            $is_deleted = $has_deleted_column['reservations'] ? ($reservation['deleted'] == 1) : false;

            if ($current_status == 'Approuvé' && !$is_deleted) {
                $error = "Réservation déjà approuvée et non supprimée.";
            } else {
                $stmt_payment = $db->query("SELECT statut" . ($has_payment_status_column ? ", payment_status" : "") . " FROM paiements WHERE reservation_id = ?", [$reservation_id], 'i');
                $payment = $stmt_payment->get_result()->fetch_assoc();

                if (!$payment) {
                    $error = "Erreur : Paiement non trouvé.";
                } else {
                    $query = "UPDATE reservations SET statut = 'Approuvé'" . ($has_deleted_column['reservations'] ? ", deleted = 0" : "") . " WHERE id = ?";
                    $result1 = $db->query($query, [$reservation_id], 'i');
                    $update_payment_query = "UPDATE paiements SET statut = 'Approuvé'" . ($has_payment_status_column ? ", payment_status = 'pending'" : "") . " WHERE reservation_id = ?";
                    $result2 = $db->query($update_payment_query, [$reservation_id], 'i');

                    if ($result1 && $result2) {
                        $db->query(
                            "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                            ['Restauration Réservation', $_SESSION['user_id'], $reservation_id, "Admin a restauré la réservation et le paiement"],
                            'siis'
                        );
                        $success = "Réservation restaurée avec succès.";
                    } else {
                        $error = "Erreur lors de la restauration de la réservation.";
                    }
                }
            }
        }
    }

    // Supprimer un utilisateur
    if ($action == 'delete_user') {
        $user_id = intval($_POST['user_id']);
        $query = "SELECT " . ($has_deleted_column['utilisateurs'] ? "deleted" : "1") . " FROM utilisateurs WHERE id = ? AND role != 'admin'";
        $stmt = $db->query($query, [$user_id], 'i');
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = "Utilisateur non trouvé ou rôle non autorisé.";
        } else {
            $is_deleted = $has_deleted_column['utilisateurs'] ? ($user['deleted'] == 1) : false;
            if ($is_deleted) {
                $error = "Utilisateur déjà supprimé.";
            } else {
                if ($has_deleted_column['utilisateurs']) {
                    $result = $db->query("UPDATE utilisateurs SET deleted = 1 WHERE id = ? AND role != 'admin'", [$user_id], 'i');
                } else {
                    $result = $db->query("DELETE FROM utilisateurs WHERE id = ? AND role != 'admin'", [$user_id], 'i');
                }

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Suppression Utilisateur', $_SESSION['user_id'], "Admin a marqué l'utilisateur ID: $user_id comme supprimé"],
                        'sis'
                    );
                    $success = "Utilisateur marqué comme supprimé avec succès.";
                } else {
                    $error = "Erreur lors de la suppression de l'utilisateur.";
                }
            }
        }
    }

    // Restaurer un utilisateur
    if ($action == 'restore_user') {
        $user_id = intval($_POST['user_id']);
        $query = "SELECT " . ($has_deleted_column['utilisateurs'] ? "deleted" : "1") . " FROM utilisateurs WHERE id = ? AND role != 'admin'";
        $stmt = $db->query($query, [$user_id], 'i');
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            $error = "Utilisateur non trouvé ou rôle non autorisé.";
        } else {
            $is_deleted = $has_deleted_column['utilisateurs'] ? ($user['deleted'] == 1) : false;
            if (!$is_deleted) {
                $error = "Utilisateur non supprimé.";
            } else {
                $result = $db->query("UPDATE utilisateurs SET deleted = 0 WHERE id = ? AND role != 'admin'", [$user_id], 'i');

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Restauration Utilisateur', $_SESSION['user_id'], "Admin a restauré l'utilisateur ID: $user_id"],
                        'sis'
                    );
                    $success = "Utilisateur restauré avec succès.";
                } else {
                    $error = "Erreur lors de la restauration de l'utilisateur.";
                }
            }
        }
    }

    // Supprimer un historique
    if ($action == 'delete_history') {
        $history_id = intval($_POST['history_id']);
        $query = "SELECT " . ($has_deleted_column['historique'] ? "deleted" : "1") . " FROM historique WHERE id = ?";
        $stmt = $db->query($query, [$history_id], 'i');
        $history = $stmt->get_result()->fetch_assoc();

        if (!$history) {
            $error = "Entrée d'historique non trouvée.";
        } else {
            $is_deleted = $has_deleted_column['historique'] ? ($history['deleted'] == 1) : false;
            if ($is_deleted) {
                $error = "Entrée d'historique déjà supprimée.";
            } else {
                if ($has_deleted_column['historique']) {
                    $result = $db->query("UPDATE historique SET deleted = 1 WHERE id = ?", [$history_id], 'i');
                } else {
                    $result = $db->query("DELETE FROM historique WHERE id = ?", [$history_id], 'i');
                }

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Suppression Historique', $_SESSION['user_id'], "Admin a marqué un historique ID: $history_id comme supprimé"],
                        'sis'
                    );
                    $success = "Entrée d'historique marquée comme supprimée avec succès.";
                } else {
                    $error = "Erreur lors de la suppression de l'entrée d'historique.";
                }
            }
        }
    }

    // Restaurer un historique
    if ($action == 'restore_history') {
        $history_id = intval($_POST['history_id']);
        $query = "SELECT " . ($has_deleted_column['historique'] ? "deleted" : "1") . " FROM historique WHERE id = ?";
        $stmt = $db->query($query, [$history_id], 'i');
        $history = $stmt->get_result()->fetch_assoc();

        if (!$history) {
            $error = "Entrée d'historique non trouvée.";
        } else {
            $is_deleted = $has_deleted_column['historique'] ? ($history['deleted'] == 1) : false;
            if (!$is_deleted) {
                $error = "Entrée d'historique non supprimée.";
            } else {
                $result = $db->query("UPDATE historique SET deleted = 0 WHERE id = ?", [$history_id], 'i');

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Restauration Historique', $_SESSION['user_id'], "Admin a restauré un historique ID: $history_id"],
                        'sis'
                    );
                    $success = "Entrée d'historique restaurée avec succès.";
                } else {
                    $error = "Erreur lors de la restauration de l'entrée d'historique.";
                }
            }
        }
    }

    // Générer une facture
    if ($action == 'generate_invoice') {
        $reservation_id = intval($_POST['reservation_id']);
        $stmt = $db->query("SELECT r.*, v.destination AS voyage_destination, v.prix, u.nom AS utilisateur_nom FROM reservations r JOIN voyages v ON r.id_voyage = v.id JOIN utilisateurs u ON r.id_utilisateur = u.id WHERE r.id = ?", [$reservation_id], 'i');
        $reservation = $stmt->get_result()->fetch_assoc();

        if ($reservation) {
            // Vérifier si le paiement est complet avant de générer une facture
            $stmt_payment = $db->query("SELECT " . ($has_payment_status_column ? "payment_status" : "statut") . " FROM paiements WHERE reservation_id = ?", [$reservation_id], 'i');
            $payment = $stmt_payment->get_result()->fetch_assoc();

            if ($payment) {
                $payment_status = $has_payment_status_column ? $payment['payment_status'] : ($payment['statut'] == 'Approuvé' ? 'pending' : 'failed');
                if ($payment_status !== 'completed') {
                    $error = "La facture ne peut être générée que si le paiement est complet.";
                } else {
                    $montant = $reservation['nombre_places'] * $reservation['prix'];
                    $date_generation = date('Y-m-d H:i:s');
                    $fichier_path = "invoices/facture_{$reservation_id}.txt";

                    $content = "Facture pour la réservation ID: {$reservation_id}\n";
                    $content .= "Client: {$reservation['utilisateur_nom']}\n";
                    $content .= "Destination: {$reservation['voyage_destination']}\n";
                    $content .= "Type de Billet: {$reservation['type_billet']}\n";
                    $content .= "Nombre de Places: {$reservation['nombre_places']}\n";
                    $content .= "Date de Voyage: {$reservation['date_voyage']}\n";
                    $content .= "Montant: {$montant} EUR\n";
                    $content .= "Généré le: {$date_generation}\n";

                    if (!file_exists('invoices')) {
                        mkdir('invoices', 0777, true);
                    }

                    file_put_contents($fichier_path, $content);

                    $result = $db->query(
                        "INSERT INTO factures (reservation_id, montant, date_generation, fichier_path" . ($has_deleted_column['factures'] ? ", deleted" : "") . ") VALUES (?, ?, ?, ?" . ($has_deleted_column['factures'] ? ", 0" : "") . ")",
                        [$reservation_id, $montant, $date_generation, $fichier_path],
                        'idss'
                    );

                    if ($result) {
                        $db->query(
                            "INSERT INTO historique (action, utilisateur_id, reservation_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                            ['Génération Facture', $_SESSION['user_id'], $reservation_id, "Admin a généré une facture"],
                            'siis'
                        );
                        $success = "Facture générée avec succès.";
                    } else {
                        $error = "Erreur lors de la génération de la facture.";
                    }
                }
            } else {
                $error = "Erreur : Paiement non trouvé pour cette réservation.";
            }
        } else {
            $error = "Réservation non trouvée.";
        }
    }

    // Supprimer une facture
    if ($action == 'delete_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        $query = "SELECT fichier_path" . ($has_deleted_column['factures'] ? ", deleted" : "") . " FROM factures WHERE id = ?";
        $stmt = $db->query($query, [$invoice_id], 'i');
        $invoice = $stmt->get_result()->fetch_assoc();

        if (!$invoice) {
            $error = "Facture non trouvée.";
        } else {
            $is_deleted = $has_deleted_column['factures'] ? ($invoice['deleted'] == 1) : false;
            if ($is_deleted) {
                $error = "Facture déjà supprimée.";
            } else {
                if ($has_deleted_column['factures']) {
                    $result = $db->query("UPDATE factures SET deleted = 1 WHERE id = ?", [$invoice_id], 'i');
                } else {
                    if (file_exists($invoice['fichier_path'])) {
                        unlink($invoice['fichier_path']);
                    }
                    $result = $db->query("DELETE FROM factures WHERE id = ?", [$invoice_id], 'i');
                }

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Suppression Facture', $_SESSION['user_id'], "Admin a marqué une facture ID: $invoice_id comme supprimée"],
                        'sis'
                    );
                    $success = "Facture marquée comme supprimée avec succès.";
                } else {
                    $error = "Erreur lors de la suppression de la facture.";
                }
            }
        }
    }

    // Restaurer une facture
    if ($action == 'restore_invoice') {
        $invoice_id = intval($_POST['invoice_id']);
        $query = "SELECT " . ($has_deleted_column['factures'] ? "deleted" : "1") . " FROM factures WHERE id = ?";
        $stmt = $db->query($query, [$invoice_id], 'i');
        $invoice = $stmt->get_result()->fetch_assoc();

        if (!$invoice) {
            $error = "Facture non trouvée.";
        } else {
            $is_deleted = $has_deleted_column['factures'] ? ($invoice['deleted'] == 1) : false;
            if (!$is_deleted) {
                $error = "Facture non supprimée.";
            } else {
                $result = $db->query("UPDATE factures SET deleted = 0 WHERE id = ?", [$invoice_id], 'i');

                if ($result) {
                    $db->query(
                        "INSERT INTO historique (action, utilisateur_id, date_action, details" . ($has_deleted_column['historique'] ? ", deleted" : "") . ") VALUES (?, ?, NOW(), ?" . ($has_deleted_column['historique'] ? ", 0" : "") . ")",
                        ['Restauration Facture', $_SESSION['user_id'], "Admin a restauré une facture ID: $invoice_id"],
                        'sis'
                    );
                    $success = "Facture restaurée avec succès.";
                } else {
                    $error = "Erreur lors de la restauration de la facture.";
                }
            }
        }
    }
}

// Récupérer les données après traitement des actions
// Réservations en attente (seulement celles qui ont dépassé 5 minutes et ne sont pas annulées)
try {
    $query = "SELECT r.*, v.destination AS voyage_destination, u.nom AS utilisateur_nom, r.created_at 
              FROM reservations r 
              JOIN voyages v ON r.id_voyage = v.id 
              JOIN utilisateurs u ON r.id_utilisateur = u.id 
              WHERE r.statut = 'En attente' 
              AND TIMESTAMPDIFF(MINUTE, r.created_at, NOW()) >= 5" . 
              ($has_deleted_column['reservations'] ? " AND r.deleted = 0" : "");
    $stmt = $db->query($query);
    $pending_reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des réservations en attente : " . $e->getMessage() . "<br>";
    $pending_reservations = [];
}

// Réservations approuvées
try {
    $query = "SELECT r.*, v.destination AS voyage_destination, v.prix, u.nom AS utilisateur_nom, p.statut AS payment_statut" . ($has_payment_status_column ? ", p.payment_status" : "") . "
              FROM reservations r 
              JOIN voyages v ON r.id_voyage = v.id 
              JOIN utilisateurs u ON r.id_utilisateur = u.id 
              LEFT JOIN paiements p ON r.id = p.reservation_id 
              WHERE r.statut = 'Approuvé'" . ($has_deleted_column['reservations'] ? " AND r.deleted = 0" : "");
    $stmt = $db->query($query);
    $approved_reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des réservations approuvées : " . $e->getMessage() . "<br>";
    $approved_reservations = [];
}

// Réservations refusées ou annulées ou supprimées
try {
    $query = "SELECT r.*, v.destination AS voyage_destination, u.nom AS utilisateur_nom 
              FROM reservations r 
              JOIN voyages v ON r.id_voyage = v.id 
              JOIN utilisateurs u ON r.id_utilisateur = u.id 
              WHERE r.statut IN ('Refusé', 'Annulé')" . 
              ($has_deleted_column['reservations'] ? " OR r.deleted = 1" : "");
    $stmt = $db->query($query);
    $canceled_or_deleted_reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des réservations refusées ou annulées : " . $e->getMessage() . "<br>";
    $canceled_or_deleted_reservations = [];
}

// Utilisateurs actifs
try {
    $query = "SELECT * FROM utilisateurs WHERE role != 'admin'" . ($has_deleted_column['utilisateurs'] ? " AND deleted = 0" : "");
    $stmt = $db->query($query);
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des utilisateurs : " . $e->getMessage() . "<br>";
    $users = [];
}

// Utilisateurs supprimés
try {
    $query = "SELECT * FROM utilisateurs WHERE role != 'admin'" . ($has_deleted_column['utilisateurs'] ? " AND deleted = 1" : " AND 1=0");
    $stmt = $db->query($query);
    $deleted_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des utilisateurs supprimés : " . $e->getMessage() . "<br>";
    $deleted_users = [];
}

// Historique actif
try {
    $query = "SELECT h.*, u.nom AS utilisateur_nom 
              FROM historique h 
              JOIN utilisateurs u ON h.utilisateur_id = u.id" . 
              ($has_deleted_column['historique'] ? " WHERE h.deleted = 0" : "");
    $stmt = $db->query($query);
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération de l'historique : " . $e->getMessage() . "<br>";
    $history = [];
}

// Historique supprimé
try {
    $query = "SELECT h.*, u.nom AS utilisateur_nom 
              FROM historique h 
              JOIN utilisateurs u ON h.utilisateur_id = u.id" . 
              ($has_deleted_column['historique'] ? " WHERE h.deleted = 1" : " WHERE 1=0");
    $stmt = $db->query($query);
    $deleted_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération de l'historique supprimé : " . $e->getMessage() . "<br>";
    $deleted_history = [];
}

// Factures actives
try {
    $query = "SELECT f.*, r.id AS reservation_id 
              FROM factures f 
              JOIN reservations r ON f.reservation_id = r.id" . 
              ($has_deleted_column['factures'] ? " WHERE f.deleted = 0" : "");
    $stmt = $db->query($query);
    $invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des factures : " . $e->getMessage() . "<br>";
    $invoices = [];
}

// Factures supprimées
try {
    $query = "SELECT f.*, r.id AS reservation_id 
              FROM factures f 
              JOIN reservations r ON f.reservation_id = r.id" . 
              ($has_deleted_column['factures'] ? " WHERE f.deleted = 1" : " WHERE 1=0");
    $stmt = $db->query($query);
    $deleted_invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error .= "Erreur lors de la récupération des factures supprimées : " . $e->getMessage() . "<br>";
    $deleted_invoices = [];
}

$db->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Administrative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
    body {
        background-color: #f5f7fa;
        font-family: 'Roboto', sans-serif;
    }

    .admin-container {
        max-width: 95%;
        margin: 2rem auto;
        padding: 2rem;
        background-color: #ffffff;
        border: 1px solid #e0e4e8;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
    }

    .section-title {
        color: #1a3c6d;
        font-weight: 600;
        border-bottom: 2px solid #e0e4e8;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .table-responsive {
        margin-bottom: 2rem;
    }

    .btn-custom {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        border-radius: 6px;
        transition: background-color 0.3s ease, transform 0.1s ease;
    }

    .btn-success:hover {
        background-color: #218838;
        transform: translateY(-1px);
    }

    .btn-danger:hover {
        background-color: #c82333;
        transform: translateY(-1px);
    }

    .btn-primary:hover {
        background-color: #0056b3;
        transform: translateY(-1px);
    }

    .action-group {
        display: inline-flex;
        gap: 0.5rem;
    }

    .text-muted {
        font-style: italic;
    }
    </style>
</head>

<body>
    <div class="admin-container">
        <h2 class="text-center mb-4 text-primary"><i class="bi bi-gear"></i> Gestion Administrative</h2>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Gestion des Réservations en Attente -->
        <h3 class="section-title"><i class="bi bi-clock"></i> Réservations en Attente</h3>
        <?php if (empty($pending_reservations)): ?>
        <p class="text-muted">Aucune réservation en attente trouvée.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Destination</th>
                        <th>Type de Billet</th>
                        <th>Places</th>
                        <th>Date de Voyage</th>
                        <th>Date de Réservation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_reservations as $reservation): ?>
                    <tr>
                        <td><?php echo $reservation['id']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['utilisateur_nom']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                        <td><?php echo $reservation['nombre_places']; ?></td>
                        <td><?php echo $reservation['date_voyage']; ?></td>
                        <td><?php echo $reservation['created_at']; ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="manage_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <input type="hidden" name="status" value="approve">
                                <button type="submit" class="btn btn-success btn-custom"
                                    onclick="return confirm('Voulez-vous approuver cette réservation ?')"><i
                                        class="bi bi-check"></i> Approuver</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="manage_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <input type="hidden" name="status" value="refuse">
                                <button type="submit" class="btn btn-danger btn-custom"
                                    onclick="return confirm('Voulez-vous refuser cette réservation ?')"><i
                                        class="bi bi-x"></i> Refuser</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Réservations Approuvées -->
        <h3 class="section-title"><i class="bi bi-check-circle"></i> Réservations Approuvées</h3>
        <?php if (empty($approved_reservations)): ?>
        <p class="text-muted">Aucune réservation approuvée trouvée.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Destination</th>
                        <th>Type de Billet</th>
                        <th>Places</th>
                        <th>Date de Voyage</th>
                        <th>Date de Réservation</th>
                        <th>Statut du Paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($approved_reservations as $reservation): ?>
                    <tr>
                        <td><?php echo $reservation['id']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['utilisateur_nom']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                        <td><?php echo $reservation['nombre_places']; ?></td>
                        <td><?php echo $reservation['date_voyage']; ?></td>
                        <td><?php echo $reservation['created_at']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['payment_status'] ?? ($has_payment_status_column ? 'N/A' : $reservation['payment_statut'] ?? 'pending')); ?>
                        </td>
                        <td class="action-group">
                            <button type="button" class="btn btn-primary btn-custom" data-bs-toggle="modal"
                                data-bs-target="#editReservationModal<?php echo $reservation['id']; ?>"><i
                                    class="bi bi-pencil"></i> Modifier</button>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment marquer cette réservation comme supprimée ?')"><i
                                        class="bi bi-trash"></i> Supprimer</button>
                            </form>
                            <?php if (isset($reservation['payment_status']) && $reservation['payment_status'] === 'pending'): ?>
                            <span class="text-muted">Paiement en attente de l'utilisateur</span>
                            <?php elseif (!$has_payment_status_column && isset($reservation['payment_statut']) && $reservation['payment_statut'] === 'Approuvé'): ?>
                            <span class="text-muted">Paiement en attente de l'utilisateur</span>
                            <?php endif; ?>
                            <?php
                                    $is_payment_completed = ($has_payment_status_column && isset($reservation['payment_status']) && $reservation['payment_status'] === 'completed') ||
                                                          (!$has_payment_status_column && isset($reservation['payment_statut']) && $reservation['payment_statut'] === 'Approuvé');
                                    if ($is_payment_completed): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="generate_invoice">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom"><i
                                        class="bi bi-file-earmark-text"></i> Générer une facture</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Modal pour modifier la réservation -->
                    <div class="modal fade" id="editReservationModal<?php echo $reservation['id']; ?>" tabindex="-1"
                        aria-labelledby="editReservationModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editReservationModalLabel">Modifier la Réservation ID:
                                        <?php echo $reservation['id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="edit_reservation">
                                        <input type="hidden" name="reservation_id"
                                            value="<?php echo $reservation['id']; ?>">
                                        <div class="mb-3">
                                            <label for="type_billet" class="form-label">Type de Billet</label>
                                            <select name="type_billet" class="form-select" required>
                                                <option value="Standard"
                                                    <?php if ($reservation['type_billet'] == 'Standard') echo 'selected'; ?>>
                                                    Standard</option>
                                                <option value="Premium"
                                                    <?php if ($reservation['type_billet'] == 'Premium') echo 'selected'; ?>>
                                                    Premium</option>
                                                <option value="VIP"
                                                    <?php if ($reservation['type_billet'] == 'VIP') echo 'selected'; ?>>
                                                    VIP</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nombre_places" class="form-label">Nombre de Places</label>
                                            <input type="number" name="nombre_places" class="form-control"
                                                value="<?php echo $reservation['nombre_places']; ?>" min="1" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="date_voyage" class="form-label">Date de Voyage</label>
                                            <input type="date" name="date_voyage" class="form-control"
                                                value="<?php echo $reservation['date_voyage']; ?>"
                                                min="<?php echo date('Y-m-d', strtotime('2025-05-05')); ?>" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-custom">Enregistrer</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Réservations Refusées ou Annulées ou Supprimées -->
        <h3 class="section-title"><i class="bi bi-x-circle"></i> Réservations Refusées ou Annulées</h3>
        <?php if (empty($canceled_or_deleted_reservations)): ?>
        <p class="text-muted">Aucune réservation refusée ou annulée trouvée.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Destination</th>
                        <th>Type de Billet</th>
                        <th>Places</th>
                        <th>Date de Voyage</th>
                        <th>Date de Réservation</th>
                        <th>Statut</th>
                        <?php if ($has_deleted_column['reservations']): ?>
                        <th>Supprimée</th>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($canceled_or_deleted_reservations as $reservation): ?>
                    <tr>
                        <td><?php echo $reservation['id']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['utilisateur_nom']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['voyage_destination']); ?></td>
                        <td><?php echo htmlspecialchars($reservation['type_billet']); ?></td>
                        <td><?php echo $reservation['nombre_places']; ?></td>
                        <td><?php echo $reservation['date_voyage']; ?></td>
                        <td><?php echo $reservation['created_at']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['statut'] ?: 'Non défini'); ?></td>
                        <?php if ($has_deleted_column['reservations']): ?>
                        <td><?php echo ($reservation['deleted'] == 1) ? 'Oui' : 'Non'; ?></td>
                        <?php endif; ?>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="restore_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment restaurer cette réservation et le paiement associé ?')"><i
                                        class="bi bi-arrow-counterclockwise"></i> Restaurer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Utilisateurs Actifs -->
        <h3 class="section-title"><i class="bi bi-people"></i> Utilisateurs Actifs</h3>
        <?php if (empty($users)): ?>
        <p class="text-muted">Aucun utilisateur actif trouvé.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['nom']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment marquer cet utilisateur comme supprimé ?')"><i
                                        class="bi bi-trash"></i> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Utilisateurs Supprimés -->
        <h3 class="section-title"><i class="bi bi-people"></i> Utilisateurs Supprimés</h3>
        <?php if (empty($deleted_users)): ?>
        <p class="text-muted">Aucun utilisateur supprimé trouvé.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['nom']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="restore_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment restaurer cet utilisateur ?')"><i
                                        class="bi bi-arrow-counterclockwise"></i> Restaurer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion de l'Historique Actif -->
        <h3 class="section-title"><i class="bi bi-clock-history"></i> Historique des Actions</h3>
        <?php if (empty($history)): ?>
        <p class="text-muted">Aucun historique actif trouvé.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Utilisateur</th>
                        <th>Réservation ID</th>
                        <th>Date</th>
                        <th>Détails</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                    <tr>
                        <td><?php echo $entry['id']; ?></td>
                        <td><?php echo htmlspecialchars($entry['action']); ?></td>
                        <td><?php echo htmlspecialchars($entry['utilisateur_nom']); ?></td>
                        <td><?php echo $entry['reservation_id'] ?: '-'; ?></td>
                        <td><?php echo $entry['date_action']; ?></td>
                        <td><?php echo htmlspecialchars($entry['details']); ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_history">
                                <input type="hidden" name="history_id" value="<?php echo $entry['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment marquer cette entrée d\'historique comme supprimée ?')"><i
                                        class="bi bi-trash"></i> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion de l'Historique Supprimé -->
        <h3 class="section-title"><i class="bi bi-clock-history"></i> Historique Supprimé</h3>
        <?php if (empty($deleted_history)): ?>
        <p class="text-muted">Aucun historique supprimé trouvé.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Action</th>
                        <th>Utilisateur</th>
                        <th>Réservation ID</th>
                        <th>Date</th>
                        <th>Détails</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_history as $entry): ?>
                    <tr>
                        <td><?php echo $entry['id']; ?></td>
                        <td><?php echo htmlspecialchars($entry['action']); ?></td>
                        <td><?php echo htmlspecialchars($entry['utilisateur_nom']); ?></td>
                        <td><?php echo $entry['reservation_id'] ?: '-'; ?></td>
                        <td><?php echo $entry['date_action']; ?></td>
                        <td><?php echo htmlspecialchars($entry['details']); ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="restore_history">
                                <input type="hidden" name="history_id" value="<?php echo $entry['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment restaurer cette entrée d\'historique ?')"><i
                                        class="bi bi-arrow-counterclockwise"></i> Restaurer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Factures Actives -->
        <h3 class="section-title"><i class="bi bi-receipt"></i> Gérer les Factures</h3>
        <?php if (empty($pending_reservations) && empty($approved_reservations) && empty($canceled_or_deleted_reservations)): ?>
        <p class="text-muted">Aucune réservation disponible pour générer une facture.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Réservation ID</th>
                        <th>Montant</th>
                        <th>Date de Génération</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($approved_reservations, $pending_reservations, $canceled_or_deleted_reservations) as $reservation): ?>
                    <?php
                            $payment_status = $has_payment_status_column ? ($reservation['payment_status'] ?? 'pending') : ($reservation['payment_statut'] ?? 'pending');
                            $is_payment_completed = $payment_status === 'completed' || (!$has_payment_status_column && $payment_status === 'Approuvé');
                            if ($is_payment_completed): ?>
                    <tr>
                        <td colspan="5">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="generate_invoice">
                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom">Générer une facture pour
                                    Réservation ID: <?php echo $reservation['id']; ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo $invoice['id']; ?></td>
                        <td><?php echo $invoice['reservation_id']; ?></td>
                        <td><?php echo $invoice['montant']; ?> EUR</td>
                        <td><?php echo $invoice['date_generation']; ?></td>
                        <td class="action-group">
                            <a href="<?php echo $invoice['fichier_path']; ?>" class="btn btn-primary btn-custom"
                                download><i class="bi bi-download"></i> Télécharger</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_invoice">
                                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment marquer cette facture comme supprimée ?')"><i
                                        class="bi bi-trash"></i> Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Gestion des Factures Supprimées -->
        <h3 class="section-title"><i class="bi bi-receipt"></i> Factures Supprimées</h3>
        <?php if (empty($deleted_invoices)): ?>
        <p class="text-muted">Aucune facture supprimée trouvée.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Réservation ID</th>
                        <th>Montant</th>
                        <th>Date de Génération</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_invoices as $invoice): ?>
                    <tr>
                        <td><?php echo $invoice['id']; ?></td>
                        <td><?php echo $invoice['reservation_id']; ?></td>
                        <td><?php echo $invoice['montant']; ?> EUR</td>
                        <td><?php echo $invoice['date_generation']; ?></td>
                        <td class="action-group">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="restore_invoice">
                                <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                <button type="submit" class="btn btn-success btn-custom"
                                    onclick="return confirm('Voulez-vous vraiment restaurer cette facture ?')"><i
                                        class="bi bi-arrow-counterclockwise"></i> Restaurer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'footer.php'; ?>
</body>

</html>