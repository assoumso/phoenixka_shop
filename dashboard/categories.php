<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();
if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$error = '';
$success = '';

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name'] ?? '');
    if (empty($name)) {
        $error = 'Le nom de la catégorie est obligatoire.';
    } else {
        $db = getDB();
        $slug = uniqueSlug('categories', generateSlug($name));
        $db->prepare("INSERT INTO categories (store_id, name, slug, description) VALUES (?, ?, ?, ?)")
           ->execute([$store['id'], $name, $slug, sanitize($_POST['description'] ?? '')]);
        $success = 'Catégorie ajoutée !';
    }
}

// Delete category
if (isset($_GET['delete'])) {
    $db = getDB();
    $db->prepare("DELETE FROM categories WHERE id = ? AND store_id = ?")->execute([intval($_GET['delete']), $store['id']]);
    $success = 'Catégorie supprimée.';
}

$categories = getStoreCategories($store['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
</head>
<body>
<div class="dashboard-layout">
    <?php 
    $currentPage = 'categories'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2>📁 Catégories</h2>
                <p>Organisez vos produits par catégories</p>
            </div>
            <div class="topbar-right">
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <!-- Add Form -->
                <div class="dash-card">
                    <div class="dash-card-header"><h3>➕ Nouvelle catégorie</h3></div>
                    <div class="dash-card-body">
                        <form method="POST">
                            <input type="hidden" name="add_category" value="1">
                            <div class="form-group">
                                <label for="name">Nom *</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Ex: Robes, Chaussures..." required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" placeholder="Description optionnelle..." rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Ajouter</button>
                        </form>
                    </div>
                </div>

                <!-- List -->
                <div class="dash-card">
                    <div class="dash-card-header"><h3>Vos catégories (<?= count($categories) ?>)</h3></div>
                    <div class="dash-card-body">
                        <?php if (empty($categories)): ?>
                            <div class="empty-state" style="padding:20px">
                                <p>Aucune catégorie créée.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--border-color)">
                                    <div>
                                        <div style="font-weight:600;font-size:.95rem"><?= htmlspecialchars($cat['name']) ?></div>
                                        <div style="font-size:.8rem;color:var(--text-muted)"><?= $cat['product_count'] ?> produit(s)</div>
                                    </div>
                                    <a href="?delete=<?= $cat['id'] ?>" onclick="return confirm('Supprimer cette catégorie ?')" style="color:var(--danger);font-size:.85rem">🗑️ Supprimer</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>

