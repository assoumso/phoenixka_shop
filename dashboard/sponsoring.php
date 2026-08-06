<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$db = getDB();
$error = '';
$success = '';

// Check if sponsoring table exists, if not create seamlessly
try {
    $db->exec("CREATE TABLE IF NOT EXISTS sponsoring_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        product_id INT NULL,
        pack_name VARCHAR(100) NOT NULL,
        merchant_amount DECIMAL(10,2) NOT NULL,
        phoenix_amount DECIMAL(10,2) NOT NULL,
        total_budget DECIMAL(10,2) NOT NULL,
        platform_target VARCHAR(100) DEFAULT 'Meta & TikTok Ads',
        status ENUM('pending', 'approved', 'active', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Table already exists or silent fallback
}

// Process Campaign Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sponsoring'])) {
    $productId = !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
    $packType = sanitize($_POST['pack_type'] ?? 'starter');
    $platform = sanitize($_POST['platform_target'] ?? 'Meta & TikTok Ads');
    $notes = sanitize($_POST['notes'] ?? '');

    $packs = [
        'starter' => ['name' => 'Pack Pub Découverte', 'merchant' => 5000, 'phoenix' => 5000, 'total' => 10000],
        'booster' => ['name' => 'Pack Pub Booster (Populaire)', 'merchant' => 12500, 'phoenix' => 12500, 'total' => 25000],
        'pro'     => ['name' => 'Pack Pub Pro VIP', 'merchant' => 25000, 'phoenix' => 25000, 'total' => 50000]
    ];

    if (!isset($packs[$packType])) {
        $error = 'Pack de sponsoring invalide.';
    } else {
        $pack = $packs[$packType];
        $stmt = $db->prepare("INSERT INTO sponsoring_requests (store_id, product_id, pack_name, merchant_amount, phoenix_amount, total_budget, platform_target, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $store['id'],
            $productId,
            $pack['name'],
            $pack['merchant'],
            $pack['phoenix'],
            $pack['total'],
            $platform,
            $notes
        ]);

        $success = '🚀 Demande de Sponsoring enregistrée avec succès ! Notre équipe média valide votre visuel et lance la campagne sous 24h.';
    }
}

// Fetch merchant products for dropdown
$productsStmt = $db->prepare("SELECT id, name, price FROM products WHERE store_id = ? AND is_active = 1 ORDER BY name ASC");
$productsStmt->execute([$store['id']]);
$products = $productsStmt->fetchAll();

// Fetch previous sponsoring requests
$requests = [];
try {
    $reqStmt = $db->prepare("SELECT s.*, p.name as product_name FROM sponsoring_requests s LEFT JOIN products p ON s.product_id = p.id WHERE s.store_id = ? ORDER BY s.created_at DESC");
    $reqStmt->execute([$store['id']]);
    $requests = $reqStmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sponsoring &amp; Préfinancement 50% — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .sponsoring-hero-card {
            background: linear-gradient(135deg, rgba(234, 179, 8, 0.15), rgba(15, 23, 42, 0.95)), var(--bg-card);
            border: 1px solid rgba(234, 179, 8, 0.4);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .sponsoring-hero-card::after {
            content: "50%";
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 10rem;
            font-weight: 900;
            color: rgba(234, 179, 8, 0.05);
            pointer-events: none;
        }

        .sponsoring-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(234, 179, 8, 0.2);
            color: var(--gold);
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 12px;
            border: 1px solid rgba(234, 179, 8, 0.4);
        }

        .packs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .pack-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .pack-card:hover {
            border-color: var(--gold);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .pack-card.popular {
            border-color: var(--gold);
            background: linear-gradient(180deg, rgba(234, 179, 8, 0.08), var(--bg-card));
        }

        .popular-tag {
            position: absolute;
            top: -12px;
            right: 20px;
            background: var(--gold);
            color: #000;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 3px 12px;
            border-radius: 50px;
            text-transform: uppercase;
        }

        .pack-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .pack-price-box {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 16px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .user-pay {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--gold);
        }

        .phoenix-pay {
            font-size: 0.82rem;
            color: var(--success);
            margin-top: 4px;
        }

        .pack-features {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            font-size: 0.86rem;
            color: var(--text-secondary);
        }

        .pack-features li {
            padding: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-pending { background: rgba(234, 179, 8, 0.15); color: var(--gold); }
        .status-approved { background: rgba(59, 130, 246, 0.15); color: #3B82F6; }
        .status-active { background: rgba(34, 197, 94, 0.15); color: var(--success); }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'sponsoring'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>🚀 Sponsoring &amp; Préfinancement Publicitaire 50%</h2>
                <p>Propulsez vos ventes avec des campagnes sponsorisées co-financées par PhoenixKA</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <!-- HERO HEADER -->
            <div class="sponsoring-hero-card">
                <div class="sponsoring-badge">🤝 PROGRAMME EXCLUSIF CO-FINANCEMENT MARCHAND</div>
                <h3 style="font-size:1.5rem;font-weight:800;color:var(--text-primary);margin-bottom:8px">
                    Ne payez que <span style="color:var(--gold)">50% de votre budget publicitaire</span>
                </h3>
                <p style="color:var(--text-secondary);font-size:0.92rem;max-width:800px;line-height:1.6">
                    PhoenixKA préfinance immédiatement les 50% restants de votre campagne sur Meta (Facebook &amp; Instagram) et TikTok Ads. Les 50% avancés par la plateforme sont automatiquement déduits des gains générés par vos futures ventes au moment du décaissement.
                </p>
            </div>

            <!-- PACKS DISPLAY -->
            <div class="packs-grid">
                <!-- PACK STARTER -->
                <div class="pack-card">
                    <div class="pack-title">Pack Découverte</div>
                    <div class="pack-price-box">
                        <div>Budget Pub Total : <strong style="color:#FFF">10 000 FCFA</strong></div>
                        <div class="user-pay">Vous payez : 5 000 FCFA</div>
                        <div class="phoenix-pay">✓ PhoenixKA avance : 5 000 FCFA</div>
                    </div>
                    <ul class="pack-features">
                        <li><span>⏱️</span> Diffusion sur 5 jours</li>
                        <li><span>🎯</span> Facebook &amp; Instagram Ads</li>
                        <li><span>📊</span> 1 500 à 3 500 prospects touchés</li>
                        <li><span>🎨</span> Création du visuel offerte</li>
                    </ul>
                    <button type="button" onclick="selectPack('starter', 'Pack Découverte', 5000)" class="btn btn-outline btn-block" style="margin-top:auto">Choisir ce Pack</button>
                </div>

                <!-- PACK BOOSTER -->
                <div class="pack-card popular">
                    <div class="popular-tag">Plus Populaire ⭐</div>
                    <div class="pack-title" style="color:var(--gold)">Pack Booster</div>
                    <div class="pack-price-box">
                        <div>Budget Pub Total : <strong style="color:#FFF">25 000 FCFA</strong></div>
                        <div class="user-pay">Vous payez : 12 500 FCFA</div>
                        <div class="phoenix-pay">✓ PhoenixKA avance : 12 500 FCFA</div>
                    </div>
                    <ul class="pack-features">
                        <li><span>⏱️</span> Diffusion sur 7 à 10 jours</li>
                        <li><span>🚀</span> Meta &amp; TikTok Ads Multi-canal</li>
                        <li><span>📊</span> 5 000 à 12 000 prospects qualifiés</li>
                        <li><span>🎥</span> Vidéo publicitaire dynamique incluse</li>
                    </ul>
                    <button type="button" onclick="selectPack('booster', 'Pack Booster', 12500)" class="btn btn-primary btn-block" style="margin-top:auto">Profiter du Booster 50%</button>
                </div>

                <!-- PACK PRO VIP -->
                <div class="pack-card">
                    <div class="pack-title">Pack Pro VIP</div>
                    <div class="pack-price-box">
                        <div>Budget Pub Total : <strong style="color:#FFF">50 000 FCFA</strong></div>
                        <div class="user-pay">Vous payez : 25 000 FCFA</div>
                        <div class="phoenix-pay">✓ PhoenixKA avance : 25 000 FCFA</div>
                    </div>
                    <ul class="pack-features">
                        <li><span>👑</span> Domination Multi-réseaux complète</li>
                        <li><span>📈</span> Optimization quotidienne par Media Buyer</li>
                        <li><span>📊</span> 15 000 à 35 000 acheteurs potentiels</li>
                        <li><span>⚡</span> Support &amp; retargeting prioritaire</li>
                    </ul>
                    <button type="button" onclick="selectPack('pro', 'Pack Pro VIP', 25000)" class="btn btn-outline btn-block" style="margin-top:auto">Lancer le Pack VIP</button>
                </div>
            </div>

            <!-- FORMULAR LAUNCH -->
            <div class="dash-card" id="sponsoring-form-card" style="margin-bottom:30px">
                <div class="dash-card-header">
                    <h3>📢 Lancer une Campagne Sponsorisée</h3>
                </div>
                <div class="dash-card-body">
                    <form method="POST">
                        <input type="hidden" name="submit_sponsoring" value="1">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                            <div class="form-group">
                                <label for="pack_type">Choix du Pack Sponsoring *</label>
                                <select name="pack_type" id="pack_type" class="form-control" required onchange="updateSummary()">
                                    <option value="starter">Pack Découverte — Budget 10 000 FCFA (Vous payez 5 000 FCFA)</option>
                                    <option value="booster" selected>Pack Booster — Budget 25 000 FCFA (Vous payez 12 500 FCFA)</option>
                                    <option value="pro">Pack Pro VIP — Budget 50 000 FCFA (Vous payez 25 000 FCFA)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="product_id">Produit à promouvoir (Optionnel)</label>
                                <select name="product_id" id="product_id" class="form-control">
                                    <option value="">-- Toute la boutique / Aucun en particulier --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= formatPrice($p['price']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                            <div class="form-group">
                                <label for="platform_target">Canaux de diffusion</label>
                                <select name="platform_target" id="platform_target" class="form-control">
                                    <option value="Meta Ads (Facebook & Instagram)">Meta Ads (Facebook &amp; Instagram)</option>
                                    <option value="TikTok Ads">TikTok Ads</option>
                                    <option value="Meta & TikTok Ads (Multi-canal)" selected>Meta &amp; TikTok Ads (Multi-canal)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="notes">Objectif ou consigne particulière</label>
                                <input type="text" name="notes" id="notes" class="form-control" placeholder="Ex: cibler les femmes d'Abidjan intéressées par les robes...">
                            </div>
                        </div>

                        <div style="background:rgba(234, 179, 8, 0.08);border:1px solid rgba(234, 179, 8, 0.2);border-radius:12px;padding:16px;margin:16px 0;display:flex;justify-content:space-between;align-items:center">
                            <div>
                                <div style="font-weight:700;color:var(--text-primary)" id="summary-pack-title">Pack Booster (25 000 FCFA)</div>
                                <div style="font-size:0.85rem;color:var(--text-muted)">Avance PhoenixKA : <span id="summary-phoenix" style="color:var(--success);font-weight:700">12 500 FCFA</span> (déduits ultérieurement)</div>
                            </div>
                            <div style="text-align:right">
                                <div style="font-size:0.8rem;color:var(--text-muted)">Montant dû par le marchand :</div>
                                <div style="font-size:1.3rem;font-weight:800;color:var(--gold)" id="summary-merchant">12 500 FCFA</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">🚀 Valider &amp; Lancer la Campagne Sponsoring (50%)</button>
                    </form>
                </div>
            </div>

            <!-- TABLE OF HISTORIC SPONSORING REQUESTS -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <h3>📋 Historique de vos campagnes de Sponsoring</h3>
                </div>
                <div class="dash-card-body" style="padding:0">
                    <?php if (empty($requests)): ?>
                        <div class="empty-state">
                            <div class="icon">🚀</div>
                            <h3>Aucune campagne lancée</h3>
                            <p>Choisissez un pack ci-dessus pour lancer votre première campagne sponsorisée co-financée.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Pack</th>
                                        <th>Produit Promu</th>
                                        <th>Canaux</th>
                                        <th>Votre Part</th>
                                        <th>Part PhoenixKA</th>
                                        <th>Budget Total</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $r): ?>
                                        <tr>
                                            <td style="font-size:0.85rem;color:var(--text-muted)"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                            <td style="font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($r['pack_name']) ?></td>
                                            <td><?= htmlspecialchars($r['product_name'] ?: 'Toute la boutique') ?></td>
                                            <td style="font-size:0.85rem"><?= htmlspecialchars($r['platform_target']) ?></td>
                                            <td style="font-weight:700;color:var(--gold)"><?= formatPrice($r['merchant_amount']) ?></td>
                                            <td style="font-weight:700;color:var(--success)"><?= formatPrice($r['phoenix_amount']) ?></td>
                                            <td style="font-weight:800"><?= formatPrice($r['total_budget']) ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <span class="status-badge status-pending">⏳ Validation en cours</span>
                                                <?php elseif ($r['status'] === 'approved' || $r['status'] === 'active'): ?>
                                                    <span class="status-badge status-active">🟢 Campagne Active</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-approved"><?= ucfirst($r['status']) ?></span>
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
function selectPack(type, name, price) {
    const select = document.getElementById('pack_type');
    if (select) {
        select.value = type;
        updateSummary();
        document.getElementById('sponsoring-form-card').scrollIntoView({ behavior: 'smooth' });
    }
}

function updateSummary() {
    const select = document.getElementById('pack_type');
    const pack = select.value;
    const titleEl = document.getElementById('summary-pack-title');
    const phoenixEl = document.getElementById('summary-phoenix');
    const merchantEl = document.getElementById('summary-merchant');

    if (pack === 'starter') {
        titleEl.textContent = 'Pack Découverte (Budget : 10 000 FCFA)';
        phoenixEl.textContent = '5 000 FCFA';
        merchantEl.textContent = '5 000 FCFA';
    } else if (pack === 'pro') {
        titleEl.textContent = 'Pack Pro VIP (Budget : 50 000 FCFA)';
        phoenixEl.textContent = '25 000 FCFA';
        merchantEl.textContent = '25 000 FCFA';
    } else {
        titleEl.textContent = 'Pack Booster (Budget : 25 000 FCFA)';
        phoenixEl.textContent = '12 500 FCFA';
        merchantEl.textContent = '12 500 FCFA';
    }
}
</script>
</body>
</html>
