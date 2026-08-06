<?php
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $user = loginUser($email, $password);
        if ($user) {
            redirect(SITE_URL . '/dashboard/');
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .auth-page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;background:var(--bg-primary);position:relative;overflow:hidden}
        .auth-page::before{content:'';position:absolute;top:-30%;right:-20%;width:600px;height:600px;background:radial-gradient(circle,rgba(212,165,32,0.06) 0%,transparent 70%);border-radius:50%}
        .auth-card{width:100%;max-width:440px;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-lg);padding:48px 40px;position:relative;z-index:1}
        .auth-logo{text-align:center;margin-bottom:32px}
        .auth-logo img{height:60px;margin:0 auto 12px;filter:drop-shadow(0 0 12px var(--gold-glow))}
        .auth-logo h1{font-family:'Playfair Display',serif;font-size:1.5rem;background:var(--gold-text-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .auth-logo p{color:var(--text-muted);font-size:0.9rem;margin-top:4px}
        .auth-divider{text-align:center;margin:24px 0;color:var(--text-muted);font-size:0.85rem}
        .auth-footer{text-align:center;margin-top:24px;font-size:0.9rem;color:var(--text-secondary)}
        .auth-footer a{color:var(--gold);font-weight:600}
        .password-field{position:relative}
        .password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem}
    </style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
            <h1>PhoenixKA Shop</h1>
            <p>Connectez-vous à votre compte</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Compte créé avec succès ! Connectez-vous.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label for="email">Adresse email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Votre mot de passe" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">👁</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px">Se connecter</button>
        </form>

        <div class="auth-footer">
            <p>Pas encore de compte ? <a href="<?= SITE_URL ?>/auth/register">Créer ma boutique</a></p>
            <p style="margin-top:12px"><a href="<?= SITE_URL ?>" style="color:var(--text-muted);font-weight:400">← Retour à l'accueil</a></p>
        </div>
    </div>
</div>
<script>
function togglePassword(){
    const p=document.getElementById('password');
    p.type=p.type==='password'?'text':'password';
}
</script>
</body>
</html>
