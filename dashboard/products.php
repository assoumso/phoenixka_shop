<?php
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();

$user = getCurrentUser();
$store = getCurrentStore();

if (!$store) { redirect(SITE_URL . '/dashboard/'); }

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $name = sanitize($_POST['name'] ?? '');
    $description = $_POST['description'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $comparePrice = floatval($_POST['compare_price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    $productType = sanitize($_POST['product_type'] ?? 'physical');
    $digitalLink = sanitize($_POST['digital_link'] ?? '');
    $licenseKeys = trim($_POST['license_keys'] ?? '');
    $digitalFile = null;

    // Handle digital file upload if uploaded
    if (!empty($_FILES['digital_file']['tmp_name'])) {
        $uploadDir = dirname(__DIR__) . '/uploads/digital/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext = pathinfo($_FILES['digital_file']['name'], PATHINFO_EXTENSION);
        $fileName = 'digital_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['digital_file']['tmp_name'], $uploadDir . $fileName)) {
            $digitalFile = 'digital/' . $fileName;
        }
    }

    if (empty($name) || $price <= 0) {
        $error = 'Le nom et le prix sont obligatoires.';
    } else {
        $productId = createProduct([
            'store_id' => $store['id'],
            'category_id' => $categoryId ?: null,
            'product_type' => $productType,
            'digital_file' => $digitalFile,
            'digital_link' => $digitalLink,
            'license_keys' => $licenseKeys,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'compare_price' => $comparePrice ?: null,
            'stock' => $productType === 'physical' ? $stock : 9999,
            'is_featured' => $isFeatured
        ]);

        // Handle image upload
        if (!empty($_FILES['product_image']['tmp_name'])) {
            $upload = uploadImage($_FILES['product_image'], 'products');
            if ($upload['success']) {
                addProductImage($productId, $upload['path'], true);
            }
        }

        $success = 'Produit ajouté avec succès !';
        $action = 'list';
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $db = getDB();
    $db->prepare("UPDATE products SET is_active = 0 WHERE id = ? AND store_id = ?")->execute([intval($_GET['delete']), $store['id']]);
    $success = 'Produit supprimé.';
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $db = getDB();
    $db->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ? AND store_id = ?")->execute([intval($_GET['toggle']), $store['id']]);
    redirect(SITE_URL . '/dashboard/products');
}

$products = getStoreProducts($store['id']);
$categories = getStoreCategories($store['id']);

// Include sidebar active state
$currentPage = 'products';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png">
    <style>
        .product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px}
        .product-card-dash{background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-md);overflow:hidden;transition:var(--transition)}
        .product-card-dash:hover{border-color:var(--border-gold);box-shadow:var(--shadow-gold)}
        .product-card-dash .img-wrap{height:180px;background:var(--bg-elevated);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
        .product-card-dash .img-wrap img{width:100%;height:100%;object-fit:cover}
        .product-card-dash .img-placeholder{font-size:3rem;opacity:.3}
        .product-card-dash .card-body{padding:16px}
        .product-card-dash .card-body h4{font-size:.95rem;margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .product-card-dash .price-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
        .product-card-dash .current-price{font-weight:700;color:var(--gold);font-size:1rem}
        .product-card-dash .old-price{text-decoration:line-through;color:var(--text-muted);font-size:.85rem}
        .product-card-dash .meta{font-size:.8rem;color:var(--text-muted);display:flex;justify-content:space-between}
        .product-card-dash .actions{display:flex;gap:8px;padding:12px 16px;border-top:1px solid var(--border-color)}
        .product-card-dash .actions a,.product-card-dash .actions button{flex:1;padding:6px;text-align:center;border-radius:var(--radius-sm);font-size:.8rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none}
        .btn-edit{background:rgba(59,130,246,.1);color:var(--info)}
        .btn-edit:hover{background:rgba(59,130,246,.2)}
        .btn-delete{background:rgba(239,68,68,.1);color:var(--danger)}
        .btn-delete:hover{background:rgba(239,68,68,.2)}
        .featured-badge{position:absolute;top:8px;right:8px;background:var(--gold);color:#000;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:50px}
        .stock-badge{position:absolute;top:8px;left:8px;font-size:.7rem;font-weight:600;padding:3px 10px;border-radius:50px}
        .stock-in{background:rgba(34,197,94,.15);color:var(--success)}
        .stock-out{background:rgba(239,68,68,.15);color:var(--danger)}
        .add-form{max-width:680px;margin:0 auto}
        .img-upload-zone{border:2px dashed var(--border-color);border-radius:var(--radius-md);padding:30px;text-align:center;cursor:pointer;transition:var(--transition);background:var(--bg-surface)}
        .img-upload-zone:hover{border-color:var(--gold);background:rgba(212,165,32,.03)}
        .img-upload-zone input{display:none}
        .img-upload-zone .icon{font-size:2rem;margin-bottom:8px}
        .img-upload-zone p{color:var(--text-muted);font-size:.9rem}
        .preview-img{max-width:200px;max-height:200px;border-radius:var(--radius-sm);margin-top:12px;display:none}

        /* WIZARD PRODUCT CREATION (MATCHING SCREENSHOT) */
        .wizard-container{max-width:640px;margin:0 auto}
        .wizard-step-title{font-size:1.5rem;font-weight:700;margin-bottom:24px;color:var(--text-primary)}
        .product-type-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:24px}
        @media(max-width:640px){.product-type-grid{grid-template-columns:repeat(2, 1fr)}}
        .type-card-select{position:relative;background:var(--bg-card);border:2px solid var(--border-color);border-radius:18px;padding:16px 14px;cursor:pointer;transition:all 0.2s cubic-bezier(0.4, 0, 0.2, 1);display:flex;flex-direction:column;gap:12px;user-select:none}
        .type-card-select:hover{border-color:#EAB308;transform:translateY(-2px);box-shadow:0 8px 24px rgba(234, 179, 8, 0.12)}
        .type-card-select.active{border-color:#EAB308;background:rgba(234, 179, 8, 0.05);box-shadow:0 0 0 1px #EAB308}
        .type-card-header{display:flex;justify-content:space-between;align-items:center}
        .type-icon-badge{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.35rem}
        .type-check-circle{width:24px;height:24px;border-radius:50%;border:2px solid var(--border-color);display:flex;align-items:center;justify-content:center;font-size:0.75rem;color:transparent;transition:all 0.2s ease}
        .type-card-select.active .type-check-circle{background:#EAB308;border-color:#EAB308;color:#000;font-weight:800}
        .type-badge-nouveau{position:absolute;top:-8px;right:10px;background:#DC2626;color:#FFF;font-size:0.65rem;font-weight:800;padding:2px 8px;border-radius:50px;letter-spacing:0.3px}
        .type-card-title{font-size:0.95rem;font-weight:700;color:var(--text-primary)}
        
        .type-summary-box{background:var(--bg-surface);border-radius:20px;padding:24px;margin-bottom:24px;border:1px solid var(--border-color)}
        .type-summary-box h3{font-size:1.4rem;font-weight:800;margin-bottom:8px;color:var(--text-primary)}
        .type-summary-box p{color:var(--text-muted);font-size:0.92rem;margin-bottom:20px;line-height:1.5}
        .summary-features-list{display:flex;flex-direction:column;gap:10px}
        .summary-feature-item{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.03);padding:12px 16px;border-radius:12px;font-size:0.9rem;font-weight:500;color:var(--text-primary)}
        .summary-feature-item .feat-icon{width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-size:1rem}
        .btn-yellow-continue{width:100%;padding:16px;border-radius:14px;background:#EAB308;color:#000;font-weight:800;font-size:1.05rem;border:none;cursor:pointer;transition:all 0.2s ease;text-align:center;box-shadow:0 4px 15px rgba(234, 179, 8, 0.2)}
        .btn-yellow-continue:hover{background:#FACC15;transform:translateY(-1px);box-shadow:0 8px 25px rgba(234, 179, 8, 0.35)}
        .wizard-footer-help{text-align:center;margin-top:14px;font-size:0.85rem;color:var(--text-muted)}
        .wizard-footer-help a{color:#3B82F6;text-decoration:underline}
    </style>
</head>
<body>
<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <?php 
    $currentPage = 'products'; 
    include dirname(__DIR__) . '/includes/sidebar.php'; 
    ?>

    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="topbar-left">
                <h2><?= $action === 'add' ? '➕ Ajouter un produit' : '🛍️ Produits' ?></h2>
                <p><?= $action === 'add' ? 'Remplissez les informations du produit' : count($products) . ' produit(s) dans votre boutique' ?></p>
            </div>
            <div class="topbar-right">
                <?php if ($action !== 'add'): ?>
                    <a href="?action=add" class="btn btn-primary btn-sm">➕ Ajouter un produit</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/dashboard/products" class="btn btn-ghost btn-sm">← Retour aux produits</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/auth/logout" class="btn btn-ghost btn-sm" style="color:var(--danger)">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <?php if ($action === 'add'): ?>
            <!-- ADD PRODUCT FORM WITH 2-STEP WIZARD (EXACT SCREENSHOT MATCH) -->
            <div class="dash-card">
                <div class="dash-card-body" style="padding:32px 24px">
                    <form method="POST" enctype="multipart/form-data" class="add-form" id="productAddForm">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="product_type" id="selectedProductType" value="digital">

                        <!-- STEP 1: SELECT PRODUCT TYPE -->
                        <div id="wizardStep1" class="wizard-container">
                            <h2 class="wizard-step-title">Quel type de produit désirez-vous créer ?</h2>

                            <div class="product-type-grid">
                                <!-- 1. Fichiers -->
                                <div class="type-card-select active" data-type="digital" onclick="selectTypeCard(this)">
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#EAB308;color:#000">📄</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Fichiers</div>
                                </div>

                                <!-- 2. Formations -->
                                <div class="type-card-select" data-type="course" onclick="selectTypeCard(this)">
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#3B82F6">🎓</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Formations</div>
                                </div>

                                <!-- 3. Licences (Nouveau) -->
                                <div class="type-card-select" data-type="license" onclick="selectTypeCard(this)">
                                    <span class="type-badge-nouveau">Nouveau</span>
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#8B5CF6">🔑</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Licences</div>
                                </div>

                                <!-- 4. Bundles (Nouveau) -->
                                <div class="type-card-select" data-type="bundle" onclick="selectTypeCard(this)">
                                    <span class="type-badge-nouveau">Nouveau</span>
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#10B981">🥞</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Bundles</div>
                                </div>

                                <!-- 5. Coaching (Nouveau) -->
                                <div class="type-card-select" data-type="coaching" onclick="selectTypeCard(this)">
                                    <span class="type-badge-nouveau">Nouveau</span>
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#14B8A6">🖥️</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Coaching</div>
                                </div>

                                <!-- 6. Services -->
                                <div class="type-card-select" data-type="services" onclick="selectTypeCard(this)">
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#475569">💼</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Services</div>
                                </div>

                                <!-- 7. Communauté (Nouveau) -->
                                <div class="type-card-select" data-type="community" onclick="selectTypeCard(this)">
                                    <span class="type-badge-nouveau">Nouveau</span>
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#EF4444">👥</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Communauté</div>
                                </div>

                                <!-- 8. Produit Physique -->
                                <div class="type-card-select" data-type="physical" onclick="selectTypeCard(this)">
                                    <div class="type-card-header">
                                        <div class="type-icon-badge" style="background:#F97316">📦</div>
                                        <div class="type-check-circle">✓</div>
                                    </div>
                                    <div class="type-card-title">Produit Physique</div>
                                </div>
                            </div>

                            <!-- DYNAMIC SUMMARY BOX -->
                            <div class="type-summary-box">
                                <h3 id="summaryTitle">Fichiers</h3>
                                <p id="summaryDesc">E-books, templates, fichiers audio : vos clients téléchargent instantanément après achat.</p>
                                <div class="summary-features-list" id="summaryFeatures">
                                    <div class="summary-feature-item"><div class="feat-icon">⚡</div> <span>Livraison automatique</span></div>
                                    <div class="summary-feature-item"><div class="feat-icon">📄</div> <span>Tous formats acceptés (PDF, ZIP, MP3, etc.)</span></div>
                                    <div class="summary-feature-item"><div class="feat-icon">🛡️</div> <span>Protection anti-piratage intégrée</span></div>
                                </div>
                            </div>

                            <!-- CONTINUER BUTTON -->
                            <button type="button" class="btn-yellow-continue" onclick="goToStep2()">Continuer</button>
                            <div class="wizard-footer-help">
                                Besoin d'aide pour choisir ? <a href="#">Consultez notre guide</a>
                            </div>
                        </div>

                        <!-- STEP 2: PRODUCT DETAILS FORM -->
                        <div id="wizardStep2" style="display:none">
                            <button type="button" onclick="goToStep1()" class="btn btn-ghost btn-sm" style="margin-bottom:20px;color:var(--gold);font-weight:600">
                                ← Modifier le type (<span id="selectedTypeNameDisplay">Fichiers</span>)
                            </button>

                            <!-- Dynamic Digital Fields -->
                            <div id="digitalFields" style="display:none;background:rgba(0, 180, 216, 0.05);padding:18px;border-radius:14px;border:1px solid rgba(0, 180, 216, 0.3);margin-bottom:20px">
                                <div class="form-group">
                                    <label style="color:#00B4D8;font-weight:700">📄 Fichier Numérique (PDF, ZIP, EPUB, MP3, etc.)</label>
                                    <input type="file" name="digital_file" class="form-control">
                                </div>
                                <div class="form-group" style="margin-bottom:0">
                                    <label style="color:#00B4D8;font-weight:700">🔗 OU Lien d'accès direct (Drive, Dropbox, Notion, Canva, Telegram)</label>
                                    <input type="url" name="digital_link" class="form-control" placeholder="https://drive.google.com/file/d/...">
                                </div>
                            </div>

                            <!-- Dynamic License Fields -->
                            <div id="licenseFields" style="display:none;background:rgba(234, 179, 8, 0.05);padding:18px;border-radius:14px;border:1px solid rgba(234, 179, 8, 0.3);margin-bottom:20px">
                                <div class="form-group" style="margin-bottom:0">
                                    <label style="color:#EAB308;font-weight:700">🔑 Clés de Licence (1 clé par ligne)</label>
                                    <textarea name="license_keys" class="form-control" rows="4" placeholder="KEY-9021-X9A2&#10;KEY-9021-X9A3&#10;KEY-9021-X9A4"></textarea>
                                    <small style="color:var(--text-muted);display:block;margin-top:6px">Chaque acheteur recevra une clé unique lors du paiement.</small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="name">Nom du produit *</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Ex: e-Book Stratégie Vente WhatsApp" required>
                            </div>

                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-control" placeholder="Décrivez votre produit..."></textarea>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div class="form-group">
                                    <label for="price">Prix (<?= DEFAULT_CURRENCY ?>) *</label>
                                    <input type="number" id="price" name="price" class="form-control" placeholder="0" min="0" step="100" required>
                                </div>
                                <div class="form-group">
                                    <label for="compare_price">Ancien prix (barré)</label>
                                    <input type="number" id="compare_price" name="compare_price" class="form-control" placeholder="Optionnel" min="0" step="100">
                                </div>
                            </div>

                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div class="form-group" id="stockGroup">
                                    <label for="stock">Stock disponible</label>
                                    <input type="number" id="stock" name="stock" class="form-control" value="0" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="category_id">Catégorie</label>
                                    <select id="category_id" name="category_id" class="form-control">
                                        <option value="">Sans catégorie</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Photo du produit</label>
                                <div class="img-upload-zone" onclick="document.getElementById('product_image').click()">
                                    <div class="icon">📷</div>
                                    <p>Cliquez pour ajouter une photo</p>
                                    <input type="file" id="product_image" name="product_image" accept="image/*">
                                    <img class="preview-img" id="imagePreview">
                                </div>
                            </div>

                            <div class="form-group">
                                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                    <input type="checkbox" name="is_featured" value="1"> Mettre en avant (Coup de cœur)
                                </label>
                            </div>

                            <button type="submit" class="btn-yellow-continue" style="margin-top:10px">✓ Publier et Ajouter le produit</button>
                        </div>
                    </form>
                </div>
            </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" placeholder="Décrivez votre produit..."></textarea>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                            <div class="form-group">
                                <label for="price">Prix (<?= DEFAULT_CURRENCY ?>) *</label>
                                <input type="number" id="price" name="price" class="form-control" placeholder="0" min="0" step="100" required>
                            </div>
                            <div class="form-group">
                                <label for="compare_price">Ancien prix (barré)</label>
                                <input type="number" id="compare_price" name="compare_price" class="form-control" placeholder="Optionnel" min="0" step="100">
                            </div>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                            <div class="form-group">
                                <label for="stock">Stock disponible</label>
                                <input type="number" id="stock" name="stock" class="form-control" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label for="category_id">Catégorie</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">Sans catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Photo du produit</label>
                            <div class="img-upload-zone" onclick="document.getElementById('product_image').click()">
                                <div class="icon">📷</div>
                                <p>Cliquez pour ajouter une photo</p>
                                <input type="file" id="product_image" name="product_image" accept="image/*">
                                <img class="preview-img" id="imagePreview">
                            </div>
                        </div>

                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="is_featured" value="1"> Mettre en avant (Coup de cœur)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">✓ Ajouter le produit</button>
                    </form>
                </div>
            </div>

            <?php else: ?>
            <!-- PRODUCT LIST -->
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="icon">🛍️</div>
                    <h3>Aucun produit</h3>
                    <p>Ajoutez votre premier produit pour commencer à vendre.</p>
                    <a href="?action=add" class="btn btn-primary">➕ Ajouter un produit</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                <?php foreach ($products as $p): ?>
                    <div class="product-card-dash">
                        <div class="img-wrap">
                            <?php if ($p['primary_image']): ?>
                                <img src="<?= UPLOADS_URL ?>/<?= $p['primary_image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <?php else: ?>
                                <span class="img-placeholder">📷</span>
                            <?php endif; ?>
                            <?php if ($p['is_featured']): ?><span class="featured-badge">⭐ Coup de cœur</span><?php endif; ?>
                            <span class="stock-badge <?= $p['stock'] > 0 ? 'stock-in' : 'stock-out' ?>">
                                <?= $p['stock'] > 0 ? 'Stock: ' . $p['stock'] : 'Rupture' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h4><?= htmlspecialchars($p['name']) ?></h4>
                            <div class="price-row">
                                <span class="current-price"><?= formatPrice($p['price']) ?></span>
                                <?php if ($p['compare_price']): ?>
                                    <span class="old-price"><?= formatPrice($p['compare_price']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="meta">
                                <span><?= $p['sales_count'] ?> vente(s)</span>
                                <span><?= $p['views_count'] ?> vue(s)</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-edit">✏️ Modifier</a>
                            <a href="?delete=<?= $p['id'] ?>" class="btn-delete" onclick="return confirm('Supprimer ce produit ?')">🗑️ Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
const typeInfoDict = {
    digital: {
        title: "Fichiers",
        desc: "E-books, templates, fichiers audio : vos clients téléchargent instantanément après achat.",
        features: [
            { icon: "⚡", text: "Livraison automatique" },
            { icon: "📄", text: "Tous formats acceptés (PDF, ZIP, MP3, etc.)" },
            { icon: "🛡️", text: "Protection anti-piratage intégrée" }
        ]
    },
    course: {
        title: "Formations",
        desc: "Cours en ligne, formations vidéo et contenus éducatifs accessibles 24h/24.",
        features: [
            { icon: "🎓", text: "Accès immédiat et sécurisé au contenu" },
            { icon: "🎬", text: "Streaming vidéo et support de cours" },
            { icon: "📱", text: "Consultation sur mobile et ordinateur" }
        ]
    },
    license: {
        title: "Licences",
        desc: "Clés d'activation, numéros de série et codes de logiciels livrés automatiquement.",
        features: [
            { icon: "🔑", text: "Distribution automatique de clés uniques" },
            { icon: "⚡", text: "Remise instantanée au client après règlement" },
            { icon: "🛡️", text: "Gestion automatique du stock de clés" }
        ]
    },
    bundle: {
        title: "Bundles",
        desc: "Regroupez plusieurs produits numériques ou physiques dans un pack à prix avantageux.",
        features: [
            { icon: "🎁", text: "Offres groupées à forte conversion" },
            { icon: "💰", text: "Augmentation de la valeur moyenne du panier" },
            { icon: "⚡", text: "Téléchargements ou expédition en 1 clic" }
        ]
    },
    coaching: {
        title: "Coaching",
        desc: "Séances d'accompagnement individuel, créneaux de conseils et visioconférences.",
        features: [
            { icon: "📅", text: "Prise de rendez-vous automatique" },
            { icon: "💬", text: "Envoi automatique du lien de visioconférence" },
            { icon: "⏱️", text: "Rappels automatiques par e-mail et WhatsApp" }
        ]
    },
    services: {
        title: "Services",
        desc: "Prestations sur mesure (Design, Rédaction, Marketing, Développement).",
        features: [
            { icon: "📝", text: "Formulaire de brief personnalisé à la commande" },
            { icon: "⏱️", text: "Délais de livraison clairement définis" },
            { icon: "💬", text: "Échanges directs avec le client via WhatsApp" }
        ]
    },
    community: {
        title: "Communauté",
        desc: "Accès VIP à un groupe privé Telegram, WhatsApp, Discord ou réseau d'abonnés.",
        features: [
            { icon: "👥", text: "Génération automatique de liens d'invitation uniques" },
            { icon: "🔒", text: "Accès réservé exclusivement aux clients payants" },
            { icon: "💬", text: "Fidélisation et opportunités de réabonnement" }
        ]
    },
    physical: {
        title: "Produits Physiques",
        desc: "Vêtements, accessoires, produits cosmétiques et articles physiques livrés par colis.",
        features: [
            { icon: "🚚", text: "Gestion des zones et tarifs de livraison" },
            { icon: "📦", text: "Suivi des stocks, variantes et déclinaisons" },
            { icon: "💵", text: "Paiement direct Wave, Mobile Money ou Cash" }
        ]
    }
};

function selectTypeCard(cardEl) {
    document.querySelectorAll('.type-card-select').forEach(c => c.classList.remove('active'));
    cardEl.classList.add('active');
    
    const typeKey = cardEl.dataset.type;
    document.getElementById('selectedProductType').value = typeKey;
    
    const info = typeInfoDict[typeKey] || typeInfoDict.digital;
    document.getElementById('summaryTitle').textContent = info.title;
    document.getElementById('summaryDesc').textContent = info.desc;
    
    const featsList = document.getElementById('summaryFeatures');
    featsList.innerHTML = info.features.map(f => `
        <div class="summary-feature-item">
            <div class="feat-icon">${f.icon}</div>
            <span>${f.text}</span>
        </div>
    `).join('');
}

function goToStep2() {
    const selectedType = document.getElementById('selectedProductType').value;
    const info = typeInfoDict[selectedType] || typeInfoDict.digital;
    
    document.getElementById('selectedTypeNameDisplay').textContent = info.title;
    document.getElementById('wizardStep1').style.display = 'none';
    document.getElementById('wizardStep2').style.display = 'block';
    
    // Toggle specific type fields in Step 2
    const digital = document.getElementById('digitalFields');
    const license = document.getElementById('licenseFields');
    const stockGroup = document.getElementById('stockGroup');

    if (digital) digital.style.display = (selectedType === 'digital' || selectedType === 'course' || selectedType === 'bundle' || selectedType === 'coaching' || selectedType === 'community') ? 'block' : 'none';
    if (license) license.style.display = (selectedType === 'license') ? 'block' : 'none';
    if (stockGroup) stockGroup.style.display = (selectedType === 'physical') ? 'block' : 'none';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep1() {
    document.getElementById('wizardStep2').style.display = 'none';
    document.getElementById('wizardStep1').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('product_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>

