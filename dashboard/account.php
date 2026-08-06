<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');

    // Password change
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, password_hash=? WHERE id=?")
               ->execute([$firstName, $lastName, $phone, $hash, $user['id']]);
            $success = 'Compte et mot de passe mis à jour !';
        }
    } else {
        $db->prepare("UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?")
           ->execute([$firstName, $lastName, $phone, $user['id']]);
        $success = 'Informations mises à jour !';
    }

    $_SESSION['user_name'] = $firstName . ' ' . $lastName;
    $user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'account'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>👤 Mon compte</h2>
                <p>Gérez vos informations personnelles</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                    <div class="dash-card">
                        <div class="dash-card-header"><h3>Informations personnelles</h3></div>
                        <div class="dash-card-body">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                <div class="form-group">
                                    <label>Prénom</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Nom</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly style="opacity:.7">
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="dash-card">
                        <div class="dash-card-header"><h3>🔒 Changer le mot de passe</h3></div>
                        <div class="dash-card-body">
                            <div class="form-group">
                                <label>Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                            </div>
                            <div class="form-group">
                                <label>Confirmer le mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirmer">
                            </div>
                            <p style="color:var(--text-muted);font-size:.8rem">Minimum 6 caractères</p>
                        </div>
                    </div>
                </div>
                <div style="margin-top:20px">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Enregistrer</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>

