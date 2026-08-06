<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
// Check if admin
if (($user['role'] ?? '') !== 'admin' && ($user['is_admin'] ?? 0) != 1 && ($user['email'] ?? '') !== 'admin@phoenixka.shop') {
    die("<div style='padding:50px;text-align:center;font-family:sans-serif;background:#111;color:#fff'><h2>⛔ Accès Restreint Administrateur</h2><p>Seul l'administrateur de PHOENIXKA peut accéder à cet espace.</p><a href='" . SITE_URL . "/dashboard/' style='color:#EAB308'>Retour au tableau de bord</a></div>");
}

$db = getDB();
$success = '';
$error = '';

// Handle Global Platform Settings Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_platform_settings'])) {
    $commissionRate = floatval($_POST['commission_rate'] ?? 5.0);
    $payoutFeeRate = floatval($_POST['payout_fee_rate'] ?? 3.5);
    $supportWhatsapp = sanitize($_POST['support_whatsapp'] ?? '+225 0141591150');
    $platformName = sanitize($_POST['platform_name'] ?? 'PhoenixKA Shop');
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;

    savePlatformSetting('commission_rate', $commissionRate);
    savePlatformSetting('payout_fee_rate', $payoutFeeRate);
    savePlatformSetting('support_whatsapp', $supportWhatsapp);
    savePlatformSetting('platform_name', $platformName);
    savePlatformSetting('maintenance_mode', $maintenanceMode);

    $success = "Paramètres généraux de la plateforme mis à jour avec succès !";
}

// Fetch current settings
$currentCommission = getPlatformSetting('commission_rate', 5.0);
$currentFee = getPlatformSetting('payout_fee_rate', 3.5);
$currentWhatsapp = getPlatformSetting('support_whatsapp', '+225 0141591150');
$currentName = getPlatformSetting('platform_name', 'PhoenixKA Shop');
$currentMaintenance = getPlatformSetting('maintenance_mode', 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Configuration Plateforme | PhoenixKA</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .settings-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; padding: 28px; margin-bottom: 24px; }
        .settings-title { font-size: 1.2rem; font-weight: 700; color: var(--gold); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'admin_settings'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>⚙️ Configuration Globale de la Plateforme</h2>
                <p>Ajustez les commissions, frais de retrait et paramètres globaux de PhoenixKA</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost btn-sm">← Mon Dashboard</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="save_platform_settings" value="1">

                <!-- COMMISSIONS & FRAIS -->
                <div class="settings-card">
                    <div class="settings-title">💰 Tarification &amp; Commissions Plateforme</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                        <div class="form-group">
                            <label>Taux de Commission Plateforme (%) *</label>
                            <input type="number" step="0.1" min="0" max="50" name="commission_rate" class="form-control" value="<?= floatval($currentCommission) ?>" required>
                            <small style="color:var(--text-muted)">Pourcentage prélevé sur chaque vente réussie (Par défaut : 5%).</small>
                        </div>

                        <div class="form-group">
                            <label>Frais de Décaissement / Retrait (%) *</label>
                            <input type="number" step="0.1" min="0" max="20" name="payout_fee_rate" class="form-control" value="<?= floatval($currentFee) ?>" required>
                            <small style="color:var(--text-muted)">Frais de transfert Mobile Money déduits au retrait (Par défaut : 3.5%).</small>
                        </div>
                    </div>
                </div>

                <!-- COORDONNÉES & SUPPORT -->
                <div class="settings-card">
                    <div class="settings-title">📞 Support &amp; Identité de la Plateforme</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                        <div class="form-group">
                            <label>Nom Officiel de la Plateforme *</label>
                            <input type="text" name="platform_name" class="form-control" value="<?= htmlspecialchars($currentName) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Numéro WhatsApp Support Officiel *</label>
                            <input type="text" name="support_whatsapp" class="form-control" value="<?= htmlspecialchars($currentWhatsapp) ?>" required>
                            <small style="color:var(--text-muted)">Format international (ex: +225 0141591150).</small>
                        </div>
                    </div>
                </div>

                <!-- MODE MAINTENANCE -->
                <div class="settings-card">
                    <div class="settings-title">🔒 Contrôle Système</div>
                    <div style="display:flex;align-items:center;justify-content:space-between;background:rgba(15, 23, 42, 0.6);padding:16px;border-radius:12px;border:1px solid rgba(255,255,255,0.05)">
                        <div>
                            <div style="font-weight:700;color:var(--text-primary)">Mode Maintenance Plateforme</div>
                            <div style="font-size:0.85rem;color:var(--text-muted)">Activez ce mode temporairement lors d'opérations de maintenance technique majeures.</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($currentMaintenance) ? 'checked' : '' ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div style="text-align:right">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Enregistrer la Configuration Plateforme</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
