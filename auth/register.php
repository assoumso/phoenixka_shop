<?php
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard/');
}

$error = '';
$plan = sanitize($_GET['plan'] ?? 'decouverte');
$refCode = sanitize($_GET['ref'] ?? $_POST['ref'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $storeName = sanitize($_POST['store_name'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($storeName)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        $result = registerUser([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ]);

        if ($result['success']) {
            // Trouver le plan
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM plans WHERE slug = ?");
            $stmt->execute([$plan]);
            $planRow = $stmt->fetch();
            $planId = $planRow ? $planRow['id'] : 1;

            // Créer la boutique
            $newStoreId = createStore([
                'user_id' => $result['user_id'],
                'name' => $storeName,
                'phone' => $phone,
                'whatsapp' => $phone,
                'primary_color' => sanitize($_POST['primary_color'] ?? '#D4A520'),
                'plan_id' => $planId
            ]);

            // Handle Referral Bonus (250 FCFA upon store activation)
            if (!empty($refCode)) {
                try {
                    $stmtUsers = $db->query("SELECT id, email FROM users");
                    $allUsers = $stmtUsers->fetchAll();
                    $referrerId = null;
                    foreach ($allUsers as $u) {
                        $calcCode = 'PHX-' . strtoupper(substr(md5($u['id'] . $u['email']), 0, 6));
                        if ($calcCode === strtoupper($refCode)) {
                            $referrerId = $u['id'];
                            break;
                        }
                    }
                    if ($referrerId && $referrerId != $result['user_id']) {
                        $db->prepare("INSERT INTO referrals (referrer_id, referred_user_id, referred_store_id, referral_code, bonus_amount, status) VALUES (?, ?, ?, ?, 250, 'pending')")
                           ->execute([$referrerId, $result['user_id'], $newStoreId, strtoupper($refCode)]);
                        
                        // Check if the store is active right away, trigger reward
                        $stmtCheckActive = $db->prepare("SELECT is_active FROM stores WHERE id = ?");
                        $stmtCheckActive->execute([$newStoreId]);
                        $stRow = $stmtCheckActive->fetch();
                        if ($stRow && !empty($stRow['is_active'])) {
                            triggerReferralReward($newStoreId);
                        }
                    }
                } catch (Exception $e) {
                    // Ignore referral errors during store creation
                }
            }

            redirect(SITE_URL . '/dashboard/');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer ma boutique — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;background:var(--bg-primary);position:relative;overflow:hidden}
        .auth-page::before{content:'';position:absolute;top:-30%;right:-20%;width:600px;height:600px;background:radial-gradient(circle,rgba(212,165,32,0.06) 0%,transparent 70%);border-radius:50%}
        .auth-card{width:100%;max-width:500px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:44px 40px;position:relative;z-index:1}
        .auth-logo{text-align:center;margin-bottom:28px}
        .auth-logo img{height:56px;margin:0 auto 10px;filter:drop-shadow(0 0 12px var(--gold-glow))}
        .auth-logo h1{font-family:'Playfair Display',serif;font-size:1.4rem;background:var(--gold-text-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .auth-logo p{color:var(--text-muted);font-size:0.85rem;margin-top:4px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .auth-footer{text-align:center;margin-top:20px;font-size:0.9rem;color:var(--text-secondary)}
        .auth-footer a{color:var(--gold);font-weight:600}
        .slug-preview{font-size:0.8rem;color:var(--gold);margin-top:4px;font-family:monospace}
        .color-row{display:flex;align-items:center;gap:12px}
        .color-row input[type="color"]{width:48px;height:36px;border:1px solid var(--border-color);border-radius:var(--radius-sm);background:var(--bg-surface);cursor:pointer;padding:2px}
        .color-row span{color:var(--text-muted);font-size:0.85rem}
        .section-label{font-size:0.8rem;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:1px;margin:24px 0 12px;padding-top:16px;border-top:1px solid var(--border-color)}
        @media(max-width:500px){.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
            <h1>Créer ma boutique</h1>
            <p>Rejoignez PhoenixKA et vendez en ligne en 5 minutes</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

            <div class="section-label" style="margin-top:0;border-top:none">👤 Vos informations</div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Prénom *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Votre prénom" value="<?= htmlspecialchars($firstName ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Nom *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Votre nom" value="<?= htmlspecialchars($lastName ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Téléphone / WhatsApp</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+237 6XX XXX XXX" value="<?= htmlspecialchars($phone ?? '') ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 caractères" required>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirmer *</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Confirmer" required>
                </div>
            </div>

            <div class="section-label">🏪 Votre boutique</div>

            <div class="form-group">
                <label for="store-name">Nom de votre boutique *</label>
                <input type="text" id="store-name" name="store_name" class="form-control" placeholder="Ex: Mode Élégante" value="<?= htmlspecialchars($storeName ?? '') ?>" required>
                <div class="slug-preview" id="slug-preview">phoenixka.shop/ma-boutique</div>
            </div>
            <div class="form-group">
                <label>Couleur de votre boutique</label>
                <div class="color-row">
                    <input type="color" name="primary_color" value="#D4A520">
                    <span>Choisissez la couleur principale de votre boutique</span>
                </div>
            <div class="form-group" style="margin-top:16px">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-weight:400;font-size:0.85rem;color:var(--text-secondary)">
                    <input type="checkbox" name="accept_terms" required checked style="margin-top:3px;accent-color:var(--gold)">
                    <span>J'accepte les termes du <a href="<?= SITE_URL ?>/terms" target="_blank" style="color:var(--gold);text-decoration:underline">Contrat Partenaire Marchand 🇨🇮</a> (5% de commission par vente, décaissements validés sous 24h, préfinancement Sponsoring à 50%).</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:12px">🚀 Créer ma boutique</button>
        </form>

        <div class="auth-footer">
            <p>Déjà un compte ? <a href="<?= SITE_URL ?>/auth/login">Se connecter</a></p>
            <p style="margin-top:10px"><a href="<?= SITE_URL ?>" style="color:var(--text-muted);font-weight:400">← Retour à l'accueil</a></p>
        </div>
    </div>
</div>
<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>
