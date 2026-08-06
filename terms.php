<?php
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat Partenaire Marchand & Conditions de Service — PhoenixKA Shop</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png" type="image/png">
    <style>
        .terms-page {
            padding: 60px 0 100px;
            background: var(--bg-dark);
            min-height: 100vh;
        }
        .terms-card {
            background: var(--bg-card);
            border: 1px solid var(--border-gold);
            border-radius: var(--radius-lg);
            padding: 48px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
            max-width: 900px;
            margin: 0 auto;
        }
        .terms-header {
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 32px;
            margin-bottom: 40px;
        }
        .terms-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--gold-light);
            margin-bottom: 12px;
        }
        .terms-meta {
            display: inline-flex;
            gap: 16px;
            background: rgba(212, 165, 32, 0.1);
            border: 1px solid var(--border-gold);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.82rem;
            color: var(--gold);
        }
        .clause-box {
            background: rgba(26, 26, 30, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 28px;
            transition: var(--transition);
        }
        .clause-box.highlight-clause {
            border-color: rgba(212, 165, 32, 0.5);
            background: linear-gradient(145deg, rgba(26, 26, 30, 0.95), rgba(45, 35, 12, 0.4));
            box-shadow: 0 4px 20px rgba(212, 165, 32, 0.15);
        }
        .clause-box h3 {
            color: var(--gold);
            font-size: 1.2rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .clause-box p, .clause-box ul {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.7;
        }
        .clause-box ul {
            padding-left: 20px;
            margin-top: 10px;
        }
        .clause-box ul li {
            margin-bottom: 8px;
        }
        .clause-badge {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .terms-actions {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" style="position:relative">
    <div class="container">
        <a href="<?= SITE_URL ?>" class="navbar-logo">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
            <span>PhoenixKA</span>
        </a>
        <div class="navbar-actions">
            <a href="<?= SITE_URL ?>" class="btn btn-ghost">← Retour à l'accueil</a>
            <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary">Créer ma boutique</a>
        </div>
    </div>
</nav>

<div class="terms-page">
    <div class="container">
        <div class="terms-card">
            <div class="terms-header">
                <h1>CONTRAT PARTENAIRE MARCHAND</h1>
                <p style="color:var(--text-secondary)">Conditions Générales de Service &amp; Engagements Contractuels</p>
                <div class="terms-meta" style="margin-top:16px">
                    <span><strong>Société :</strong> PhoenixKA Shop</span>
                    <span><strong>Version :</strong> 2.4 (Mise à jour 2026)</span>
                </div>
            </div>

            <!-- PREAMBULE -->
            <div class="clause-box">
                <h3>📜 PRÉAMBULE</h3>
                <p>Le présent contrat régit les relations contractuelles entre la plateforme <strong>PhoenixKA Shop</strong> (société 100% Ivoirienne 🇨🇮, ci-après désignée "l'Éditeur") et tout marchand indépendant ou entreprise (ci-après désigné "le Marchand") utilisant les services d'hébergement, d'affichage et de gestion des commandes en ligne.</p>
            </div>

            <!-- CLAUSE 1: COMMISSION DE 5% -->
            <div class="clause-box highlight-clause">
                <h3>
                    <span>💰 ARTICLE 1 : FRAIS ET COMMISSIONS SUR VENTES (5%)</span>
                    <span class="clause-badge">CLAUSE ESSENTIELLE</span>
                </h3>
                <p>En contrepartie des services technologiques, de la sécurisation des paiements et de l'hébergement de la boutique :</p>
                <ul>
                    <li><strong>Commission contractuelle de 5% :</strong> PhoenixKA applique une commission fixe de <strong>5% sur chaque vente</strong> réalisée et validée via les outils de la plateforme.</li>
                    <li><strong>Prélèvement à la source :</strong> La commission de 5% est automatiquement déduite du montant brut de chaque transaction lors de son encaissement.</li>
                    <li><strong>Frais d'activation :</strong> La création de boutique est gratuite. L'activation définitive de la boutique s'effectue moyennant le paiement unique des frais du pack choisi (à partir de 2 000 FCFA pour le pack Découverte).</li>
                </ul>
            </div>

            <!-- CLAUSE 2: DECAISSEMENTS SOUS 24H -->
            <div class="clause-box highlight-clause">
                <h3>
                    <span>⚡ ARTICLE 2 : DÉCAISSEMENTS ET RETRAIT DES FONDS (24H MAX)</span>
                    <span class="clause-badge">ENGAGEMENT DE SERVICE</span>
                </h3>
                <p>PhoenixKA s'engage à garantir un accès fluide et rapide au chiffre d'affaires accumulé par le Marchand :</p>
                <ul>
                    <li><strong>Validation et traitement en 24h :</strong> Toute demande de décaissement (retrait de solde) effectuée par le Marchand depuis son tableau de bord est <strong>validée et exécutée dans un délai strict de vingt-quatre (24) heures maximum</strong> suivant la soumission.</li>
                    <li><strong>Modes de versement :</strong> Les fonds décaissés sont versés directement sur le compte Mobile Money (Wave, Orange Money, MTN Money) ou bancaire enregistré par le Marchand.</li>
                </ul>
            </div>

            <!-- CLAUSE 3: SPONSORING & PREFINANCEMENT 50% -->
            <div class="clause-box highlight-clause">
                <h3>
                    <span>🚀 ARTICLE 3 : SERVICE DE SPONSORING &amp; PRÉFINANCEMENT PUBLICITAIRE (CO-FINANCEMENT 50%)</span>
                    <span class="clause-badge">SERVICE EXCLUSIF</span>
                </h3>
                <p>Afin d'accélérer les ventes du Marchand, PhoenixKA met à disposition une offre de co-financement publicitaire :</p>
                <ul>
                    <li><strong>Co-financement à 50% / 50% :</strong> Lors de la souscription à un pack Sponsoring (Meta / TikTok Ads), le Marchand ne règle que <strong>50% du montant du pack</strong>. La société PhoenixKA <strong>préfinance les 50% restants</strong> pour lancer immédiatement la campagne publicitaire.</li>
                    <li><strong>Durée du Pack de Démarrage :</strong> La campagne publicitaire associée au Pack Pub Découverte (budget pub de 10 000 FCFA) est programmée pour une diffusion continue sur une <strong>durée de 5 jours</strong>.</li>
                    <li><strong>Modalités de remboursement :</strong> Les 50% préfinancés par PhoenixKA sont automatiquement prélevés et récupérés lors du décaissement des ventes générées par la boutique du Marchand.</li>
                </ul>
            </div>

            <!-- CLAUSE 3: ENGAGEMENT DE L'ÉDITEUR -->
            <div class="clause-box">
                <h3>🛠️ ARTICLE 3 : ENGAGEMENTS ET GARANTIES DE PHOENIXKA</h3>
                <p>PhoenixKA s'engage à :</p>
                <ul>
                    <li>Assurer la disponibilité continue de la boutique du Marchand 24h/24 et 7j/7.</li>
                    <li>Sécuriser la transmission des données de paiement selon les normes en vigueur.</li>
                    <li>Mettre à disposition du Marchand un tableau de bord lisible traçant chaque commande et chaque commission.</li>
                </ul>
            </div>

            <!-- CLAUSE 4: OBLIGATIONS DU MARCHAND -->
            <div class="clause-box">
                <h3>📦 ARTICLE 4 : OBLIGATIONS DU MARCHAND</h3>
                <p>Le Marchand s'engage contractuellement à :</p>
                <ul>
                    <li>Ne proposer à la vente que des produits conformes aux lois en vigueur et non contrefaits.</li>
                    <li>Livrer les produits commandés dans les délais annoncés aux clients finaux.</li>
                    <li>Respecter l'image et l'intégrité de la plateforme PhoenixKA Shop.</li>
                </ul>
            </div>

            <!-- CLAUSE 5: RÉSILIATION -->
            <div class="clause-box">
                <h3>🚪 ARTICLE 5 : DUREE ET RESILIATION DU CONTRAT</h3>
                <p>Le présent contrat prend effet dès la création du compte marchand pour une durée indéterminée. Le Marchand conserve le droit de fermer sa boutique et de résilier son engagement à tout moment, sans pénalité, sous réserve d'avoir honoré les commandes en cours.</p>
            </div>

            <div class="terms-actions">
                <div style="font-size:0.85rem;color:var(--text-muted)">
                    En vous inscrivant sur PhoenixKA Shop, vous reconnaissez avoir lu et accepté l'intégralité de ces clauses contractuelles.
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline btn-sm">🖨️ Imprimer le contrat</button>
                    <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary btn-sm">J'accepte &amp; Je crée ma boutique →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container text-center" style="padding:20px 0">
        <p style="color:var(--text-muted);font-size:0.85rem">© <?= date('Y') ?> PhoenixKA Shop — Tous droits réservés.</p>
    </div>
</footer>

</body>
</html>

