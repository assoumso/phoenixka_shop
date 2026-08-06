<?php
require_once __DIR__ . '/includes/functions.php';

$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    die("Jeton de téléchargement invalide ou manquant.");
}

$db = getDB();
$stmt = $db->prepare("
    SELECT oi.*, p.name as product_name, p.product_type, p.digital_file, p.digital_link, o.payment_status, o.status as order_status 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    JOIN orders o ON oi.order_id = o.id 
    WHERE oi.download_token = ?
");
$stmt->execute([$token]);
$item = $stmt->fetch();

if (!$item) {
    die("Fichier introuvable ou lien expiré.");
}

// Vérifier si le paiement est valide (paid, confirmed, delivered ou cash_on_delivery pour démo)
if (!in_array($item['payment_status'], ['paid', 'confirmed', 'completed']) && !in_array($item['order_status'], ['confirmed', 'delivered'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Paiement en attente</title><link rel="stylesheet" href="' . ASSETS_URL . '/css/style.css"></head><body style="background:#0F172A;color:#FFF;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;text-align:center"><div><div style="font-size:3.5rem;margin-bottom:16px">⏳</div><h2>Paiement en cours de validation</h2><p style="color:#94A3B8;margin:12px 0 24px">Votre téléchargement sera accessible dès que le marchand aura confirmé votre paiement.</p><a href="' . SITE_URL . '" class="btn btn-primary">Retour à la boutique</a></div></body></html>';
    exit;
}

// Mettre à jour le compteur de téléchargements
$db->prepare("UPDATE order_items SET download_count = download_count + 1 WHERE id = ?")->execute([$item['id']]);

// Cas 1 : Lien externe (Drive, Dropbox, Notion, etc.)
if (!empty($item['digital_link'])) {
    header("Location: " . $item['digital_link']);
    exit;
}

// Cas 2 : Fichier stocké sur le serveur
if (!empty($item['digital_file'])) {
    $filePath = __DIR__ . '/uploads/' . $item['digital_file'];
    
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/uploads/digital/' . $item['digital_file'];
    }

    if (file_exists($filePath)) {
        $filename = basename($filePath);
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $downloadName = generateSlug($item['product_name']) . '.' . $ext;

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}

die("Le fichier n'est pas disponible pour le moment. Veuillez contacter le support de la boutique.");
