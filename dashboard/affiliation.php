<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();
$error = '';
$success = '';

// Check and create referral tables seamlessly
try {
    $db->exec("CREATE TABLE IF NOT EXISTS referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        referrer_id INT NOT NULL,
        referred_user_id INT NOT NULL,
        referred_store_id INT NOT NULL,
        referral_code VARCHAR(50) NOT NULL,
        bonus_amount DECIMAL(10,2) DEFAULT 5000.00,
        status ENUM('pending', 'active', 'rewarded') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (referred_store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Table fallback
}

// Generate unique referral code for this merchant user if not present
$referralCode = 'PHX-' . strtoupper(substr(md5($user['id'] . $user['email']), 0, 6));
$referralLink = SITE_URL . '/auth/register?ref=' . $referralCode;

// Fetch referral statistics for this merchant
$referralStats = [
    'total_referred' => 0,
    'active_stores' => 0,
    'total_earned' => 0
];

$referredList = [];
try {
    $stmtRef = $db->prepare("
        SELECT r.*, u.first_name, u.last_name, u.email as referred_email, s.name as store_name, s.slug as store_slug, s.is_active as store_active, s.created_at as store_created
        FROM referrals r
        JOIN users u ON r.referred_user_id = u.id
        JOIN stores s ON r.referred_store_id = s.id
        WHERE r.referrer_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmtRef->execute([$user['id']]);
    $referredList = $stmtRef->fetchAll();

    foreach ($referredList as $rf) {
        $referralStats['total_referred']++;
        if ($rf['store_active']) $referralStats['active_stores']++;
        $referralStats['total_earned'] += floatval($rf['bonus_amount']);
    }
} catch (PDOException $e) {
    $referredList = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programme d'Affiliation &amp; Parrainage — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .affiliate-hero-card {
            background: linear-gradient(135deg, rgba(234, 179, 8, 0.15), rgba(15, 23, 42, 0.95)), var(--bg-card);
            border: 1px solid rgba(234, 179, 8, 0.4);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .affiliate-hero-card::after {
            content: "🤝";
            position: absolute;
            right: 10px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.08;
            pointer-events: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .share-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .link-input-group {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            margin-bottom: 18px;
        }

        .link-input-group input {
            flex: 1;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: var(--gold);
            font-family: monospace;
            font-weight: 700;
            padding: 12px 16px;
            border-radius: 10px;
        }

        .social-share-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-social {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.88rem;
            color: #fff;
            text-decoration: none;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .btn-social:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-whatsapp { background: #25D366; }
        .btn-facebook { background: #1877F2; }
        .btn-telegram { background: #229ED9; }
        .btn-copy { background: var(--gold); color: #000; cursor: pointer; border: none; font-weight: 800; }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'affiliation'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>🤝 Programme d'Affiliation &amp; Parrainage</h2>
                <p>Parrainez d'autres commerçants et gagnez des bonus crédités directement sur votre solde</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <!-- HERO CARD -->
            <div class="affiliate-hero-card">
                <span class="badge" style="background:rgba(234,179,8,0.2);color:var(--gold);margin-bottom:10px;display:inline-block">🎁 GAINS DE PARRAINAGE EXCLUSIFS</span>
                <h3 style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:8px">
                    Gagnez <span style="color:var(--gold)">250 FCFA</span> dès l'activation de chaque boutique filleule
                </h3>
                <p style="color:var(--text-secondary);font-size:0.92rem;max-width:800px;line-height:1.6">
                    Partagez votre lien ou votre code de parrainage avec des commerçants. Dès l'activation de la boutique d'un filleul par la plateforme, vous recevez automatiquement <strong style="color:var(--gold)">250 FCFA de prime</strong> crédités sur votre portefeuille !
                </p>
            </div>

            <!-- STATS CARDS -->
            <div class="stats-grid">
                <div class="stat-card" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Filleuls Inscrits</div>
                    <div style="font-size:1.8rem;font-weight:800;color:var(--gold);margin-top:6px"><?= $referralStats['total_referred'] ?> commerçant(s)</div>
                </div>

                <div class="stat-card" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Boutiques Actives</div>
                    <div style="font-size:1.8rem;font-weight:800;color:var(--success);margin-top:6px"><?= $referralStats['active_stores'] ?> boutique(s)</div>
                </div>

                <div class="stat-card" style="background:var(--bg-card);border:1px solid var(--border-color);border-radius:18px;padding:22px">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Cumul Gains d'Affiliation</div>
                    <div style="font-size:1.8rem;font-weight:800;color:var(--gold);margin-top:6px"><?= formatPrice($referralStats['total_earned']) ?></div>
                </div>
            </div>

            <!-- SHARE BOX -->
            <div class="share-box">
                <h3 style="font-size:1.15rem;font-weight:700;color:var(--text-primary);margin-bottom:6px">🔗 Votre Lien de Parrainage Personnalisé</h3>
                <p style="font-size:0.88rem;color:var(--text-muted)">Copiez votre lien et envoyez-le directement sur WhatsApp ou vos réseaux sociaux :</p>
                
                <div class="link-input-group">
                    <input type="text" id="referral-link" value="<?= htmlspecialchars($referralLink) ?>" readonly>
                    <button type="button" class="btn-social btn-copy" onclick="copyReferralLink()">📋 Copier le Lien</button>
                </div>

                <div style="display:flex;align-items:center;gap:16px;margin-top:16px;flex-wrap:wrap">
                    <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700">Code Parrainage : <span style="color:var(--gold);font-family:monospace;font-size:1rem"><?= $referralCode ?></span></div>
                    <div class="social-share-buttons">
                        <a href="https://api.whatsapp.com/send?text=<?= urlencode("Bonjour ! Crée ta boutique en ligne professionnelle sur PhoenixKA avec mon lien de parrainage et profite des meilleurs outils de vente : " . $referralLink) ?>" target="_blank" class="btn-social btn-whatsapp">
                            💬 WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($referralLink) ?>" target="_blank" class="btn-social btn-facebook">
                            📘 Facebook
                        </a>
                        <a href="https://t.me/share/url?url=<?= urlencode($referralLink) ?>&text=<?= urlencode("Rejoins PhoenixKA Shop !") ?>" target="_blank" class="btn-social btn-telegram">
                            ✈️ Telegram
                        </a>
                    </div>
                </div>
            </div>

            <!-- REFERRED MERCHANTS TABLE -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Vos Commerçants Parrainés</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($referredList)): ?>
                        <div class="empty-state">
                            <div class="icon">🤝</div>
                            <h3>Aucun commerçant parrainé</h3>
                            <p>Partagez votre lien de parrainage ci-dessus pour inviter des vendeurs et accumuler des commissions.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date d'inscription</th>
                                        <th>Nom du Commerçant</th>
                                        <th>Boutique Créée</th>
                                        <th>Bonus Gagné</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($referredList as $rf): ?>
                                        <tr>
                                            <td style="font-size:0.85rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($rf['created_at'])) ?></td>
                                            <td>
                                                <strong style="color:var(--text-primary)"><?= htmlspecialchars($rf['first_name'] . ' ' . $rf['last_name']) ?></strong><br>
                                                <small style="color:var(--text-muted)"><?= htmlspecialchars($rf['referred_email']) ?></small>
                                            </td>
                                            <td>
                                                <strong style="color:var(--gold)"><?= htmlspecialchars($rf['store_name']) ?></strong><br>
                                                <a href="<?= SITE_URL ?>/<?= htmlspecialchars($rf['store_slug']) ?>" target="_blank" style="font-size:0.8rem;color:var(--text-muted);text-decoration:underline">/<?= htmlspecialchars($rf['store_slug']) ?></a>
                                            </td>
                                            <td style="font-weight:800;color:var(--gold)">+ <?= formatPrice($rf['bonus_amount']) ?></td>
                                            <td>
                                                <?php if ($rf['store_active']): ?>
                                                    <span style="background:rgba(34,197,94,0.15);color:#22C55E;padding:3px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">🟢 Boutique Active</span>
                                                <?php else: ?>
                                                    <span style="background:rgba(234,179,8,0.15);color:#EAB308;padding:3px 10px;border-radius:50px;font-size:0.75rem;font-weight:700">⏳ En validation</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function copyReferralLink() {
    const linkInput = document.getElementById('referral-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(linkInput.value).then(() => {
        alert('📋 Lien de parrainage copié dans le presse-papier !');
    });
}
</script>
</body>
</html>
