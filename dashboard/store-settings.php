<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$success = '';
$error = '';

$isAdmin = (($user['role'] ?? '') === 'admin' || ($user['is_admin'] ?? 0) == 1 || ($user['email'] ?? '') === 'admin@phoenixka.shop');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $name = sanitize($_POST['name'] ?? $store['name']);
    $description = sanitize($_POST['description'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $whatsapp = sanitize($_POST['whatsapp'] ?? '');
    $waveNumber = sanitize($_POST['wave_number'] ?? '');
    $waveNumberSandbox = sanitize($_POST['wave_number_sandbox'] ?? '');
    $waveNumberLive = sanitize($_POST['wave_number_live'] ?? '');

    // Merchants are strictly in Live mode, Admin can choose
    $paymentEnv = $isAdmin ? (in_array($_POST['payment_environment'] ?? '', ['sandbox', 'live']) ? $_POST['payment_environment'] : ($store['payment_environment'] ?? 'live')) : 'live';
    $payoutMode = in_array($_POST['payout_mode'] ?? '', ['auto', 'on_demand']) ? $_POST['payout_mode'] : 'auto';
    $waveLink = sanitize($_POST['wave_link'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $country = sanitize($_POST['country'] ?? '');
    $primaryColor = sanitize($_POST['primary_color'] ?? '#D4A520');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Handle logo upload
    $logoPath = $store['logo'];
    if (!empty($_FILES['logo']['tmp_name'])) {
        $upload = uploadImage($_FILES['logo'], 'logos');
        if ($upload['success']) { $logoPath = $upload['path']; }
        else { $error = $upload['message']; }
    }

    // Handle cover upload
    $coverPath = $store['cover_image'];
    if (!empty($_FILES['cover_image']['tmp_name'])) {
        $upload = uploadImage($_FILES['cover_image'], 'covers');
        if ($upload['success']) { $coverPath = $upload['path']; }
        else { $error = $upload['message']; }
    }

    if (!$error) {
        $db->prepare("UPDATE stores SET name=?, description=?, phone=?, whatsapp=?, wave_number=?, wave_number_sandbox=?, wave_number_live=?, payment_environment=?, payout_mode=?, wave_link=?, city=?, country=?, primary_color=?, is_active=?, logo=?, cover_image=? WHERE id=?")
           ->execute([$name, $description, $phone, $whatsapp, $waveNumber, $waveNumberSandbox, $waveNumberLive, $paymentEnv, $payoutMode, $waveLink, $city, $country, $primaryColor, $isActive, $logoPath, $coverPath, $store['id']]);
        
        if ($isAdmin) {
            $platformPayments = [
                'wave' => isset($_POST['payment_wave']) ? 1 : 0,
                'orange_money' => isset($_POST['payment_orange']) ? 1 : 0,
                'mobile_money' => isset($_POST['payment_momo']) ? 1 : 0,
                'cash_on_delivery' => isset($_POST['payment_cash']) ? 1 : 0,
            ];
            setPlatformSetting('available_payment_methods', $platformPayments, 'json');
        }

        $success = 'Paramètres enregistrés avec succès !';
        $store = getCurrentStore(); // Refresh
    }
}

$enabledPayments = getEnabledPaymentMethods();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres boutique — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}
        .logo-preview{width:80px;height:80px;border-radius:50%;border:2px solid var(--border-gold);overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--bg-surface);margin-bottom:12px}
        .logo-preview img{width:100%;height:100%;object-fit:cover}
        .toggle-switch{display:flex;align-items:center;gap:12px;cursor:pointer}
        .toggle-switch input{display:none}
        .toggle-track{width:48px;height:26px;border-radius:50px;background:var(--bg-elevated);border:1px solid var(--border-color);position:relative;transition:var(--transition)}
        .toggle-track::after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:var(--text-muted);transition:var(--transition)}
        .toggle-switch input:checked+.toggle-track{background:var(--gold);border-color:var(--gold)}
        .toggle-switch input:checked+.toggle-track::after{transform:translateX(22px);background:#000}
        @media(max-width:768px){.settings-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'store-settings'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>⚙️ Paramètres de la boutique</h2>
                <p>Personnalisez votre boutique en ligne</p>
            </div>
            <div class="topbar-right">
                <a href="<?= getStoreUrl($store) ?>" target="_blank" class="btn btn-ghost btn-sm">👁 Voir ma boutique</a>
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="settings-grid">
                    <!-- Left Column -->
                    <div>
                        <div class="dash-card">
                            <div class="dash-card-header"><h3>🏪 Informations</h3></div>
                            <div class="dash-card-body">
                                <div class="form-group">
                                    <label>Nom de la boutique</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($store['name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Lien de votre boutique</label>
                                    <input type="text" class="form-control" value="<?= getStoreUrl($store) ?>" readonly style="opacity:.7">
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Décrivez votre boutique..."><?= htmlspecialchars($store['description']) ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($store['phone']) ?>" placeholder="+237 6XX XXX XXX">
                                </div>
                                <div class="form-group">
                                    <label>WhatsApp</label>
                                    <input type="tel" name="whatsapp" class="form-control" value="<?= htmlspecialchars($store['whatsapp']) ?>" placeholder="+237 6XX XXX XXX">
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <div class="form-group">
                                        <label>Ville</label>
                                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($store['city']) ?>" placeholder="Abidjan">
                                    </div>
                                    <div class="form-group">
                                        <label>Pays</label>
                                        <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($store['country']) ?>" placeholder="Côte d'Ivoire">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- NUMEROS DE PAIEMENT & ENVIRONNEMENT -->
                        <div class="dash-card" style="border:1px solid rgba(0, 180, 216, 0.4);background:linear-gradient(145deg, rgba(26,26,30,0.95), rgba(10, 40, 50, 0.3));margin-top:20px">
                            <div class="dash-card-header" style="border-bottom:1px solid rgba(0, 180, 216, 0.2)">
                                <h3 style="color:#00B4D8">📱 Numéro de Paiement Mobile Money</h3>
                            </div>
                            <div class="dash-card-body">
                                <?php if ($isAdmin): ?>
                                    <!-- ADMIN ONLY: ENVIRONMENT SELECTOR -->
                                    <div class="form-group" style="background:rgba(255,255,255,0.03);padding:14px;border-radius:12px;border:1px solid var(--border-color)">
                                        <label style="color:var(--gold);font-weight:700">⚡ Environnement (Réservé Administrateur)</label>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px">
                                            <label style="padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                                <input type="radio" name="payment_environment" value="sandbox" <?= ($store['payment_environment'] ?? 'live') === 'sandbox' ? 'checked' : '' ?>>
                                                <div>
                                                    <strong style="color:#FACC15">🧪 Démonstration (Sandbox)</strong>
                                                    <div style="font-size:0.75rem;color:var(--text-muted)">Utilisé pour les tests & démos</div>
                                                </div>
                                            </label>
                                            <label style="padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                                <input type="radio" name="payment_environment" value="live" <?= ($store['payment_environment'] ?? 'live') === 'live' ? 'checked' : '' ?>>
                                                <div>
                                                    <strong style="color:#22C55E">🚀 Production (Live)</strong>
                                                    <div style="font-size:0.75rem;color:var(--text-muted)">Transactions réelles active</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label style="color:#FFF">Numéro de Démonstration (Sandbox)</label>
                                        <input type="tel" name="wave_number_sandbox" class="form-control" value="<?= htmlspecialchars($store['wave_number_sandbox'] ?? $store['wave_number'] ?? '') ?>" placeholder="Ex: +225 0100000000 (Pour tests)" style="border-color:rgba(234, 179, 8, 0.4)">
                                    </div>
                                <?php else: ?>
                                    <!-- MERCHANT VIEW: SINGLE PRODUCTION MODE -->
                                    <div style="background:rgba(34, 197, 94, 0.08);padding:14px;border-radius:12px;border:1px solid rgba(34, 197, 94, 0.3);margin-bottom:16px;display:flex;align-items:center;gap:12px">
                                        <span style="font-size:1.5rem">🚀</span>
                                        <div>
                                            <strong style="color:#22C55E">Mode Actif : Production (Live)</strong>
                                            <div style="font-size:0.8rem;color:var(--text-muted)">Vos transactions sont directement dirigées en environnement réel.</div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php 
                                $allMobileDisabled = empty($enabledPayments['wave']) && empty($enabledPayments['orange_money']) && empty($enabledPayments['mobile_money']);
                                if ($allMobileDisabled): 
                                ?>
                                    <div style="background:rgba(239,68,68,0.1);padding:12px;border-radius:10px;border:1px solid rgba(239,68,68,0.3);margin-bottom:14px;color:#EF4444;font-weight:700;font-size:0.85rem;display:flex;align-items:center;gap:8px">
                                        <span>🔒</span> <span>Les paiements Mobile Money (Wave, Orange, MTN) sont actuellement verrouillés par l'administrateur.</span>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <label style="color:#FFF">Numéro Mobile Money (Wave / Orange / MTN) *</label>
                                    <input type="tel" name="wave_number_live" class="form-control" value="<?= htmlspecialchars($store['wave_number_live'] ?? $store['wave_number'] ?? '') ?>" placeholder="Ex: +225 0141591150" <?= $allMobileDisabled ? 'disabled' : 'required' ?> style="border-color:rgba(34, 197, 94, 0.4)">
                                    <small style="color:var(--text-muted)">Numéro sur lequel vous recevrez les règlements de vos clients.</small>
                                </div>

                                <div class="form-group" style="margin-bottom:0">
                                    <label style="color:#FFF">Lien de paiement Wave Pay 1-Clic <?= empty($enabledPayments['wave']) ? '<span style="color:#EF4444;font-size:0.8rem">(🔒 Verrouillé par l\'Admin)</span>' : '(Optionnel)' ?></label>
                                    <input type="url" name="wave_link" class="form-control" value="<?= htmlspecialchars($store['wave_link'] ?? '') ?>" placeholder="Ex: https://pay.wave.com/m/M_..." <?= empty($enabledPayments['wave']) ? 'disabled' : '' ?> style="border-color:rgba(0, 180, 216, 0.4)">
                                </div>
                            </div>
                        </div>

                        <?php if ($isAdmin): ?>
                        <!-- ADMIN ONLY: GLOBAL AVAILABLE PAYMENT METHODS CONTROL -->
                        <div class="dash-card" style="border:1px solid rgba(212, 165, 32, 0.5);background:linear-gradient(145deg, rgba(26,26,30,0.95), rgba(40, 30, 0, 0.3));margin-top:20px">
                            <div class="dash-card-header" style="border-bottom:1px solid rgba(212, 165, 32, 0.2)">
                                <h3 style="color:var(--gold)">👑 Moyens de Paiement Disponibles sur la Plateforme (ADMIN)</h3>
                            </div>
                            <div class="dash-card-body">
                                <p style="color:var(--text-muted);font-size:0.85rem;margin-bottom:14px">
                                    Cochez les moyens de paiement autorisés sur l'ensemble de la plateforme PHENIXKA. Seuls les moyens cochés apparaîtront sur les boutiques de tous les marchands.
                                </p>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                                    <label style="padding:12px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                        <input type="checkbox" name="payment_wave" value="1" <?= !empty($enabledPayments['wave']) ? 'checked' : '' ?> style="accent-color:var(--gold);width:18px;height:18px">
                                        <img src="<?= ASSETS_URL ?>/images/payments/wave.png" width="28" height="28" alt="Wave" style="border-radius:6px;object-fit:cover">
                                        <div>
                                            <strong style="color:#00B4D8;font-size:0.9rem">Wave Direct</strong>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">Paiement Wave 1-Clic</div>
                                        </div>
                                    </label>

                                    <label style="padding:12px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                        <input type="checkbox" name="payment_orange" value="1" <?= !empty($enabledPayments['orange_money']) ? 'checked' : '' ?> style="accent-color:var(--gold);width:18px;height:18px">
                                        <img src="<?= ASSETS_URL ?>/images/payments/orange_money.png" width="28" height="28" alt="Orange" style="object-fit:contain">
                                        <div>
                                            <strong style="color:#FF7900;font-size:0.9rem">Orange Money</strong>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">Paiement Orange Money</div>
                                        </div>
                                    </label>

                                    <label style="padding:12px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                        <input type="checkbox" name="payment_momo" value="1" <?= !empty($enabledPayments['mobile_money']) ? 'checked' : '' ?> style="accent-color:var(--gold);width:18px;height:18px">
                                        <img src="<?= ASSETS_URL ?>/images/payments/mtn_momo.svg" width="22" height="22" alt="MTN">
                                        <img src="<?= ASSETS_URL ?>/images/payments/moov_money.svg" width="22" height="22" alt="Moov">
                                        <div>
                                            <strong style="color:#FFCC00;font-size:0.9rem">MTN / Moov Money</strong>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">Mobile Money général</div>
                                        </div>
                                    </label>

                                    <label style="padding:12px;border:1px solid var(--border-color);border-radius:10px;cursor:pointer;display:flex;align-items:center;gap:10px;background:rgba(0,0,0,0.2)">
                                        <input type="checkbox" name="payment_cash" value="1" <?= !empty($enabledPayments['cash_on_delivery']) ? 'checked' : '' ?> style="accent-color:var(--gold);width:18px;height:18px">
                                        <img src="<?= ASSETS_URL ?>/images/payments/cash.png" width="28" height="28" alt="Cash" style="border-radius:6px;object-fit:cover">
                                        <div>
                                            <strong style="color:#4ADE80;font-size:0.9rem">Paiement à la Livraison</strong>
                                            <div style="font-size:0.75rem;color:var(--text-muted)">Règlement en espèces</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- RECEPTION DES PAIEMENTS (MODE DE DECAISSEMENT) -->
                        <div class="dash-card" style="border:1px solid rgba(234, 179, 8, 0.4);background:linear-gradient(145deg, rgba(26,26,30,0.95), rgba(40, 30, 10, 0.3));margin-top:20px">
                            <div class="dash-card-header" style="border-bottom:1px solid rgba(234, 179, 8, 0.2)">
                                <h3 style="color:#EAB308">💳 Mode de Réception des Paiements</h3>
                            </div>
                            <div class="dash-card-body">
                                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:16px">
                                    Choisissez comment vous désirez encaisser vos revenus sur PHENIXKA :
                                </p>

                                <div style="display:flex;flex-direction:column;gap:14px">
                                    <label style="padding:16px;border:2px solid <?= ($store['payout_mode'] ?? 'auto') === 'auto' ? '#EAB308' : 'var(--border-color)' ?>;border-radius:14px;cursor:pointer;background:rgba(255,255,255,0.02);display:flex;gap:14px">
                                        <input type="radio" name="payout_mode" value="auto" <?= ($store['payout_mode'] ?? 'auto') === 'auto' ? 'checked' : '' ?> style="margin-top:4px">
                                        <div>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <strong style="font-size:1rem;color:var(--text-primary)">⚡ Option 1 : Paiement Automatique (Recommandé)</strong>
                                            </div>
                                            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:6px;line-height:1.4">
                                                Le paiement est encaissé par PHENIXKA. Les commissions et frais d'opérateurs sont déduits automatiquement. Le montant net restant génère un décaissement automatique vers votre compte Mobile Money dès validation par l'administrateur.
                                            </p>
                                        </div>
                                    </label>

                                    <label style="padding:16px;border:2px solid <?= ($store['payout_mode'] ?? '') === 'on_demand' ? '#EAB308' : 'var(--border-color)' ?>;border-radius:14px;cursor:pointer;background:rgba(255,255,255,0.02);display:flex;gap:14px">
                                        <input type="radio" name="payout_mode" value="on_demand" <?= ($store['payout_mode'] ?? '') === 'on_demand' ? 'checked' : '' ?> style="margin-top:4px">
                                        <div>
                                            <div style="display:flex;align-items:center;gap:8px">
                                                <strong style="font-size:1rem;color:var(--text-primary)">💰 Option 2 : Paiement Sur Demande (Portefeuille Virtuel)</strong>
                                            </div>
                                            <p style="font-size:0.85rem;color:var(--text-muted);margin-top:6px;line-height:1.4">
                                                Vos ventes s'accumulent dans votre portefeuille virtuel PHENIXKA. Aucun transfert automatique n'est effectué : vous demandez un retrait quand vous le désirez (délai de 24h max après validation).
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <div class="dash-card">
                            <div class="dash-card-header"><h3>🎨 Apparence</h3></div>
                            <div class="dash-card-body">
                                <div class="form-group">
                                    <label>Logo de la boutique</label>
                                    <div class="logo-preview">
                                        <?php if ($store['logo']): ?>
                                            <img src="<?= UPLOADS_URL ?>/<?= $store['logo'] ?>" alt="Logo">
                                        <?php else: ?>
                                            <span style="font-size:2rem">🏪</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="logo" accept="image/*" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>🖼️ Bannière Rectangulaire de Couverture</label>
                                    <div class="cover-preview" style="width:100%;height:130px;border-radius:12px;border:1px solid var(--border-gold);overflow:hidden;background:#0F172A;display:flex;align-items:center;justify-content:center;margin-bottom:10px;position:relative">
                                        <?php if (!empty($store['cover_image'])): ?>
                                            <img src="<?= UPLOADS_URL ?>/<?= $store['cover_image'] ?>" alt="Bannière" style="width:100%;height:100%;object-fit:cover">
                                        <?php else: ?>
                                            <div style="text-align:center;color:var(--text-muted);font-size:0.85rem">
                                                <span style="font-size:1.8rem;display:block;margin-bottom:4px">🌌</span>
                                                Aucune bannière (Cliquez ci-dessous pour ajouter une image rectangulaire)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="cover_image" accept="image/*" class="form-control">
                                    <small style="color:var(--text-muted);font-size:0.75rem;margin-top:4px;display:block">
                                        Format recommandé : Rectangulaire HD (ex: 1200 × 400 pixels)
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Couleur principale</label>
                                    <div style="display:flex;align-items:center;gap:12px">
                                        <input type="color" name="primary_color" value="<?= $store['primary_color'] ?>" style="width:50px;height:40px;border:1px solid var(--border-color);border-radius:var(--radius-sm);background:var(--bg-surface);cursor:pointer;padding:2px">
                                        <span style="color:var(--text-muted);font-size:.85rem"><?= $store['primary_color'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dash-card">
                            <div class="dash-card-header"><h3>🔧 Statut</h3></div>
                            <div class="dash-card-body">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="is_active" value="1" <?= $store['is_active'] ? 'checked' : '' ?>>
                                    <span class="toggle-track"></span>
                                    <span>Boutique <?= $store['is_active'] ? 'en ligne' : 'hors ligne' ?></span>
                                </label>
                                <p style="color:var(--text-muted);font-size:.8rem;margin-top:8px">
                                    Activez votre boutique pour que vos clients puissent voir vos produits et passer commande.
                                </p>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">💾 Enregistrer les modifications</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>

