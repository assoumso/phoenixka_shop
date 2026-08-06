<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'PhoenixKA Shop — Créez votre boutique en ligne en 5 minutes';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= SITE_DESCRIPTION ?>">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css">
    <link rel="icon" href="<?= ASSETS_URL ?>/images/logo.png" type="image/png">
</head>
<body>

<!-- BANNIÈRE PROMOTIONNELLE SUPÉRIEURE -->
<div class="top-promo-bar" id="topPromoBar">
    <div class="container top-promo-container">
        <div class="top-promo-content">
            <span class="promo-tag-flash">🇨🇮 100% IVOIRIEN · OFFRE SPÉCIALE</span>
            <span class="promo-pitch-text">
                Créez et activez votre boutique professionnelle dès <strong>2 000 FCFA à l'activation</strong> (au lieu de <span style="text-decoration:line-through;opacity:0.7">5 000 FCFA</span>) !
            </span>
            <span class="promo-timer">⚡ Commission réduite à 5% · Offre limitée</span>
        </div>
        <div class="top-promo-actions">
            <a href="<?= SITE_URL ?>/auth/register?plan=decouverte&promo=2000" class="btn btn-promo">Activer ma boutique →</a>
            <button class="top-promo-close" onclick="document.getElementById('topPromoBar').style.display='none'" title="Fermer">✕</button>
        </div>
    </div>
</div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container">
        <a href="<?= SITE_URL ?>" class="navbar-logo">
            <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
            <span>PhoenixKA <small style="font-size:0.65rem;color:var(--gold-light);background:rgba(212,165,32,0.15);padding:2px 6px;border-radius:4px;border:1px solid var(--border-gold)">🇨🇮 CI</small></span>
        </a>
        <ul class="navbar-menu" id="navMenu">
            <li><a href="#comment">Comment ça marche</a></li>
            <li><a href="#fonctionnalites">Fonctionnalités</a></li>
            <li><a href="#sponsoring">Sponsoring Pub 🚀</a></li>
            <li><a href="#tarifs">Tarifs</a></li>
            <li><a href="#faq">FAQ</a></li>
        </ul>
        <div class="navbar-actions">
            <a href="<?= SITE_URL ?>/auth/login" class="btn btn-ghost">Connexion</a>
            <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary">Créer ma boutique</a>
            <button class="mobile-toggle" id="mobileToggle">☰</button>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-content">
            <div class="hero-badge fade-in">
                <span class="promo-hero-pill">🇨🇮 100% Ivoirien · 5% Commission</span>
                <span class="stars">4,9★</span>
                <span>— noté par nos marchands partenaires</span>
            </div>
            <h1 class="fade-in">
                Créez votre <span class="highlight">boutique en ligne</span> en 5 minutes, avec ton téléphone.
            </h1>
            <p class="fade-in fade-in-delay-1">
                Ajoutez vos produits, partagez votre lien, et c'est tout. <strong>Vos clients commandent seuls</strong>, 
                même quand tu dors, paient par mobile money ou à la livraison, et <strong>tu retires ton argent instantanément sur ton téléphone</strong>.
            </p>
            <div class="hero-buttons fade-in fade-in-delay-2">
                <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary btn-lg">🚀 Créer ma boutique gratuitement</a>
                <a href="#comment" class="btn btn-outline btn-lg">Voir comment ça marche</a>
            </div>
        </div>
        
        <!-- PHONE MOCKUP ANIMATION -->
        <div class="hero-visual fade-in fade-in-delay-1">
            <div class="phone-mockup">
                <div class="phone-camera"></div>
                <div class="phone-screen">
                    <div class="fake-store-header">
                        <div class="back-btn">&lt;</div>
                        <div class="fake-store-name">Robe wax bordeaux « Léna »<br><span style="color:var(--gold);font-size:0.75rem">22 500 FCFA</span></div>
                    </div>
                    <div class="fake-product-card">
                        <div class="fake-product-img" style="background:#e8dfeb;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">👗</div>
                        <div class="fake-product-info">
                            <strong>Robe wax bordeaux « Léna »</strong>
                            <span>Taille M · Couleur bordeaux</span>
                            <span class="price" style="color:var(--info);font-weight:700">22 500 FCFA</span>
                        </div>
                    </div>
                    
                    <div class="fake-form">
                        <div class="fake-form-title">VOS COORDONNÉES</div>
                        <div class="fake-input">Mariama Kouyaté</div>
                        <div class="fake-input">+221 77 456 78 90</div>
                        <div class="fake-input">Plateau, Dakar</div>
                        <div class="fake-form-title" style="margin-top:12px">DATE DE LIVRAISON</div>
                        <div style="display:flex;gap:8px;margin-bottom:16px">
                            <div class="fake-date active">Auj</div>
                            <div class="fake-date">Demain</div>
                        </div>
                        <div class="fake-btn">Commander — 22 500 FCFA</div>
                    </div>
                </div>

                <!-- Floating Badges -->
                <div class="floating-badge badge-1">
                    <div class="badge-icon" style="background:rgba(59,130,246,0.2);border:1px solid rgba(59,130,246,0.4)">🛍️</div>
                    <div class="badge-text">
                        <strong>Nouvelle commande</strong>
                        <span>2 articles — il y a 1 min</span>
                    </div>
                </div>
                <div class="floating-badge badge-2">
                    <div class="badge-icon" style="background:rgba(245,158,11,0.2);border:1px solid rgba(245,158,11,0.4)">💰</div>
                    <div class="badge-text">
                        <strong>Paiement Mobile reçu</strong>
                        <span>15 000 FCFA — Awa D.</span>
                    </div>
                </div>
                <div class="floating-badge badge-3">
                    <div class="badge-icon" style="background:rgba(34,197,94,0.2);border:1px solid rgba(34,197,94,0.4)">🛵</div>
                    <div class="badge-text">
                        <strong>Commande envoyée</strong>
                        <span>en 1 clic, sur WhatsApp</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COMPARISON SECTION (LE CHANGEMENT) -->
<section class="section section-dark comparison-section-wrapper" id="comment">
    <div class="container">
        <!-- HEADER EN-TÊTE ULTRA MISE EN VALEUR -->
        <div class="comparison-hero-box fade-in">
            <div class="comparison-pill-tag">
                <span class="glow-dot"></span>
                <span>LE CHANGEMENT RADICAL POUR VOTRE BUSINESS</span>
            </div>
            <h2 class="comparison-main-title">
                Vos clients <span class="highlight-gold-gradient">paient comme ils veulent</span>, 24h/24 &amp; 7j/7.
            </h2>
            <div class="comparison-pitch-card">
                <div class="pitch-icon">💡</div>
                <div class="pitch-text">
                    <strong>Si vous vendez déjà sur WhatsApp ou Instagram, vous connaissez les galères.</strong>
                    <p>Répondre 50 fois aux mêmes questions, envoyer les photos une par une, louper des ventes la nuit... <span>PhoenixKA résout tout en un seul lien.</span></p>
                </div>
            </div>
        </div>

        <!-- GRILLE COMPARATIVE DYNAMIQUE ET PREMIUM -->
        <div class="comparison-grid-enhanced">
            <!-- CARTE 1: SANS PHOENIXKA (AVANT) -->
            <div class="comparison-card before-card fade-in">
                <div class="card-header-badge badge-danger-glow">
                    <span>😮‍💨 AUJOURD'HUI · SANS PHOENIXKA</span>
                </div>
                <ul class="comparison-list">
                    <li>
                        <span class="item-icon icon-danger">❌</span>
                        <div><strong>Perte de temps :</strong> Vous répondez aux mêmes questions toute la journée</div>
                    </li>
                    <li>
                        <span class="item-icon icon-danger">❌</span>
                        <div><strong>Galerie manuelle :</strong> Vous envoyez les photos produit une par une par message</div>
                    </li>
                    <li>
                        <span class="item-icon icon-danger">❌</span>
                        <div><strong>Ventes perdues :</strong> Impossible de répondre quand vous dormez ou êtes occupé</div>
                    </li>
                    <li>
                        <span class="item-icon icon-danger">❌</span>
                        <div><strong>Gestion pénible :</strong> Vous notez les commandes à la main dans un cahier</div>
                    </li>
                    <li>
                        <span class="item-icon icon-danger">❌</span>
                        <div><strong>Pas d'analyse :</strong> Impossible de savoir exactement combien vous avez gagné</div>
                    </li>
                </ul>
            </div>

            <!-- BADGE VS FLOTTANT AU CENTRE -->
            <div class="comparison-vs-badge">
                <span>VS</span>
            </div>

            <!-- CARTE 2: AVEC PHOENIXKA (APRÈS) -->
            <div class="comparison-card after-card fade-in fade-in-delay-1">
                <div class="card-header-badge badge-gold-glow">
                    <span>🚀 DEMAIN · AVEC PHOENIXKA</span>
                </div>
                <ul class="comparison-list">
                    <li>
                        <span class="item-icon icon-success">✅</span>
                        <div><strong>Boutique 24/7 :</strong> Vos produits, prix et photos sont disponibles en ligne</div>
                    </li>
                    <li>
                        <span class="item-icon icon-success">✅</span>
                        <div><strong>1 seul lien :</strong> À partager partout (WhatsApp, Instagram, Facebook, TikTok)</div>
                    </li>
                    <li>
                        <span class="item-icon icon-success">✅</span>
                        <div><strong>Ventes automatiques :</strong> Les clients commandent 24h/24, même à 3h du matin</div>
                    </li>
                    <li>
                        <span class="item-icon icon-success">✅</span>
                        <div><strong>Zéro oubli :</strong> Les commandes sont enregistrées et envoyées sur WhatsApp</div>
                    </li>
                    <li>
                        <span class="item-icon icon-success">✅</span>
                        <div><strong>Tableau de bord :</strong> Suivez vos ventes et encaissements en temps réel</div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- FOOTER CALLOUT EN BAS DE LA SECTION -->
        <div class="comparison-bottom-cta fade-in fade-in-delay-2">
            <div class="cta-inner">
                <span class="sparkle-icon">💳</span>
                <span>Paiement par <strong>Wave, Orange Money, MTN Money</strong> ou à la livraison.</span>
                <a href="#tarifs" class="btn btn-primary btn-sm">Activer ma boutique dès 2 000 FCFA →</a>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <span class="label">C'est simple</span>
            <h2>Comment ça marche ?</h2>
            <p>Pas besoin d'un développeur. Pas besoin d'un ordinateur.</p>
        </div>
        <div class="steps-grid">
            <div class="step-card fade-in">
                <div class="step-number">1</div>
                <h3>Créez votre boutique</h3>
                <p>Donnez le nom de votre business, ajoutez vos produits avec description, photo et prix.</p>
                <ul class="step-features">
                    <li>Nom + logo de votre boutique</li>
                    <li>Photos et prix de vos produits</li>
                    <li>Couleur aux couleurs de votre marque</li>
                </ul>
            </div>
            <div class="step-card fade-in fade-in-delay-1">
                <div class="step-number">2</div>
                <h3>Activez et partagez</h3>
                <p>Activez votre boutique et partagez votre lien unique sur WhatsApp, Instagram ou Facebook.</p>
                <ul class="step-features">
                    <li>Activation en un clic</li>
                    <li>Lien unique prêt à partager</li>
                    <li>Les commandes arrivent directement</li>
                </ul>
            </div>
            <div class="step-card fade-in fade-in-delay-2">
                <div class="step-number">3</div>
                <h3>Recevez vos paiements</h3>
                <p>Vos clients paient par mobile money ou à la livraison. L'argent arrive sur votre solde.</p>
                <ul class="step-features">
                    <li>Wave, Orange Money, MTN Money</li>
                    <li>Paiement à la livraison possible</li>
                    <li>Suivi en temps réel des encaissements</li>
                </ul>
            </div>
            <div class="step-card fade-in fade-in-delay-3 fast-payout-card">
                <div class="step-number">4</div>
                <h3>Décaissement rapide (24h)</h3>
                <p>Faites votre demande de retrait à tout moment et recevez vos fonds sous 24h max.</p>
                <ul class="step-features">
                    <li>Demande de retrait en 1 clic</li>
                    <li>Réception sous 24h après la demande</li>
                    <li>Virement Mobile Money ou Banque</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="section section-dark" id="fonctionnalites">
    <div class="container">
        <div class="section-header fade-in">
            <span class="label">Tout est inclus</span>
            <h2>Tout ce qu'il vous faut. Rien de compliqué.</h2>
            <p>Chaque fonctionnalité existe pour vous faire gagner du temps ou de l'argent.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card fade-in">
                <div class="feature-icon">🌐</div>
                <h3>Votre boutique, accessible partout</h3>
                <p>Un lien unique que vous partagez sur WhatsApp, Instagram, TikTok ou Facebook. Vos clients commandent sans application.</p>
            </div>
            <div class="feature-card fade-in fade-in-delay-1">
                <div class="feature-icon">💳</div>
                <h3>Paiement mobile intégré</h3>
                <p>Wave, Orange Money, MTN Money — vos clients paient comme ils le souhaitent. L'argent arrive directement chez vous.</p>
            </div>
            <div class="feature-card fade-in fade-in-delay-2">
                <div class="feature-icon">🚚</div>
                <h3>Livraisons simplifiées</h3>
                <p>Envoyez chaque commande à votre livreur en 1 clic sur WhatsApp, avec toutes les infos nécessaires.</p>
            </div>
            <div class="feature-card fade-in">
                <div class="feature-icon">⭐</div>
                <h3>Avis clients</h3>
                <p>Collectez les avis de vos clients et affichez-les sur vos produits pour augmenter la confiance et vos ventes.</p>
            </div>
            <div class="feature-card fade-in fade-in-delay-1">
                <div class="feature-icon">📊</div>
                <h3>Tableau de bord complet</h3>
                <p>Suivez vos ventes du jour, de la semaine et du mois. Gérez vos commandes, stocks et clients en un coup d'œil.</p>
            </div>
            <div class="feature-card fade-in fade-in-delay-2">
                <div class="feature-icon">📱</div>
                <h3>Tout depuis votre téléphone</h3>
                <p>Pas besoin d'ordinateur ni de développeur. Créez et gérez tout depuis votre smartphone.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <span class="label">Témoignages</span>
            <h2>Nos marchands en parlent mieux</h2>
        </div>
        <div class="testimonials-track fade-in">
            <div class="testimonial-card">
                <div class="quote">"Avant PhoenixKA, je prenais les commandes sur WhatsApp et je perdais la moitié. Maintenant mes clients commandent sur mon lien et je vois tout en temps réel."</div>
                <div class="author">
                    <div class="author-avatar">AS</div>
                    <div class="author-info"><h4>Aminata S.</h4><p>Cuisine maison, Douala</p></div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote">"J'ai partagé mon lien sur Instagram un vendredi soir. Le lendemain matin j'avais 12 commandes. PhoenixKA a changé ma façon de vendre."</div>
                <div class="author">
                    <div class="author-avatar">FK</div>
                    <div class="author-info"><h4>Fatou K.</h4><p>Mode & Accessoires, Yaoundé</p></div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote">"Le paiement mobile à la commande, c'est révolutionnaire. Fini les retards et les impayés. Mes clients paient avant même la préparation."</div>
                <div class="author">
                    <div class="author-avatar">MN</div>
                    <div class="author-info"><h4>Marie N.</h4><p>Cosmétiques naturels, Dakar</p></div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="quote">"J'ai configuré ma boutique en 10 minutes depuis mon téléphone. Le site est beau et mes clients ont confiance pour commander."</div>
                <div class="author">
                    <div class="author-avatar">KB</div>
                    <div class="author-info"><h4>Khadija B.</h4><p>Artisanat & Déco, Abidjan</p></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="section section-dark">
    <div class="container">
        <div class="stats-bar fade-in">
            <div class="stat-item">
                <div class="number" data-count="1500">0</div>
                <div class="label">boutiques créées</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="8">0</div>
                <div class="label">pays couverts</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="25000">0</div>
                <div class="label">commandes traitées</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="98">0</div>
                <div class="label">% de satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- RUBRIQUE SPONSORING & PRÉFINANCEMENT PUBLICITAIRE (NOUVEAUTÉ) -->
<section class="section section-dark sponsoring-section-wrapper" id="sponsoring">
    <div class="container">
        <div class="sponsoring-hero-box fade-in text-center">
            <div class="sponsoring-badge-pill">
                <span class="sparkle">🚀 NOUVEAUTÉ EXCLUSIVE MARCHANDS</span>
            </div>
            <h2 class="sponsoring-title">
                Boostez vos ventes avec notre <span class="highlight-gold-gradient">Sponsoring Co-financé à 50%</span>
            </h2>
            <p class="sponsoring-intro">
                Des solutions souples et ultra-performantes pour vous aider à vendre rapidement tous vos produits. Nous créons et gérons vos campagnes publicitaires (Facebook, Instagram, TikTok) avec une formule de <strong>préfinancement unique à 50%</strong> !
            </p>
        </div>

        <!-- CONTEXT CARD DE CO-FINANCEMENT 50/50 -->
        <div class="sponsoring-mechanism-card fade-in">
            <div class="mechanism-step">
                <div class="mechanism-icon">💳</div>
                <h4>1. Vous payez seulement 50%</h4>
                <p>Vous ne déboursez que 50% du montant du pack publicitaire choisi.</p>
            </div>
            <div class="mechanism-arrow">🤝</div>
            <div class="mechanism-step highlight-step">
                <div class="mechanism-icon">🎁</div>
                <h4>2. PhoenixKA préfinance 50%</h4>
                <p>La société avance les 50% restants pour lancer immédiatement votre campagne pub.</p>
            </div>
            <div class="mechanism-arrow">🔄</div>
            <div class="mechanism-step">
                <div class="mechanism-icon">💰</div>
                <h4>3. Déduit lors du décaissement</h4>
                <p>Les 50% préfinancés sont simplement récupérés lors du décaissement de vos ventes.</p>
            </div>
        </div>

        <!-- GRILLE DE PACKS SPONSORING -->
        <div class="sponsoring-packs-grid">
            <div class="sponsoring-pack-card fade-in">
                <div class="pack-badge">SPONSORING STARTER</div>
                <h3>Pack Pub Découverte</h3>
                <div class="pack-price">
                    <span class="user-part">Vous payez : <strong>5 000 FCFA</strong></span>
                    <span class="company-part">PhoenixKA préfinance : 5 000 FCFA</span>
                </div>
                <div class="pack-value">Budget pub : <strong>10 000 FCFA</strong> · <span style="color:var(--gold-light);font-weight:700">⏱️ Campagne sur 5 jours</span></div>
                <ul class="pack-features">
                    <li><span class="icon">✓</span> <strong>Durée de campagne : 5 jours de diffusion</strong></li>
                    <li><span class="icon">✓</span> Campagne ciblée Facebook &amp; Instagram</li>
                    <li><span class="icon">✓</span> Rédaction de l'annonce + visuel pro</li>
                    <li><span class="icon">✓</span> Estimation : 1 500 à 3 500 vues d'acheteurs</li>
                    <li><span class="icon">✓</span> Préfinancement de 50% déduit au décaissement</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register" class="btn btn-outline btn-block">Profiter du Sponsoring 50%</a>
            </div>

            <div class="sponsoring-pack-card popular-sponsoring fade-in fade-in-delay-1">
                <div class="pack-badge badge-gold">POPULAIRE · POPULAR BOOST</div>
                <h3>Pack Pub Booster</h3>
                <div class="pack-price">
                    <span class="user-part">Vous payez : <strong>12 500 FCFA</strong></span>
                    <span class="company-part">PhoenixKA préfinance : 12 500 FCFA</span>
                </div>
                <div class="pack-value">Budget pub total : <strong>25 000 FCFA</strong></div>
                <ul class="pack-features">
                    <li><span class="icon">✓</span> Campagne ciblée Facebook, Instagram &amp; TikTok</li>
                    <li><span class="icon">✓</span> Création vidéo/visuel haute conversion</li>
                    <li><span class="icon">✓</span> Estimation : 5 000 à 12 000 prospects qualifiés</li>
                    <li><span class="icon">✓</span> Préfinancement de 50% déduit au décaissement</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary btn-block">Profiter du Sponsoring Booster</a>
            </div>

            <div class="sponsoring-pack-card fade-in fade-in-delay-2">
                <div class="pack-badge">PACK VENTE EXPRESS</div>
                <h3>Pack Pub Pro VIP</h3>
                <div class="pack-price">
                    <span class="user-part">Vous payez : <strong>25 000 FCFA</strong></span>
                    <span class="company-part">PhoenixKA préfinance : 25 000 FCFA</span>
                </div>
                <div class="pack-value">Budget pub total : <strong>50 000 FCFA</strong></div>
                <ul class="pack-features">
                    <li><span class="icon">✓</span> Domination Multi-réseaux (Meta &amp; TikTok Ads)</li>
                    <li><span class="icon">✓</span> Optimisation quotidienne par nos experts pub</li>
                    <li><span class="icon">✓</span> Estimation : 15 000 à 35 000 acheteurs ciblés</li>
                    <li><span class="icon">✓</span> Préfinancement de 50% déduit au décaissement</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register" class="btn btn-outline btn-block">Profiter du Pack VIP</a>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="section" id="tarifs">
    <div class="container">
        <div class="section-header fade-in">
            <span class="label">Packs & Activation</span>
            <h2>Activez votre boutique selon votre pack</h2>
            <p>Frais d'activation uniques par boutique. Choisissez le pack adapté à vos besoins.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card fade-in promo-highlight-card">
                <div class="card-promo-badge">PROMO ACTIVATION -60%</div>
                <h3>Pack Découverte</h3>
                <div class="pricing-price">
                    <span class="old-price">5 000</span>
                    <span class="amount">2 000</span>
                    <span class="currency">FCFA</span>
                    <span class="period">/ activation</span>
                </div>
                <p class="tagline">Activation idéale pour tester et lancer rapidement sa première boutique.</p>
                <ul class="pricing-features">
                    <li><span class="icon">✓</span> Jusqu'à 10 produits en ligne</li>
                    <li><span class="icon">✓</span> Paiement mobile money ou à la livraison</li>
                    <li><span class="icon">✓</span> Livraison simplifiée en 1 clic</li>
                    <li><span class="icon">✓</span> Tableau de bord des ventes</li>
                    <li><span class="icon">✓</span> 5% de commission sur les ventes</li>
                    <li class="disabled"><span class="icon">—</span> Produits illimités</li>
                    <li class="disabled"><span class="icon">—</span> Codes promo</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register?plan=decouverte&promo=2000" class="btn btn-outline btn-block" style="border-color:#EF4444;color:#EF4444">Activer ma boutique — 2 000 FCFA</a>
            </div>
            <div class="pricing-card popular fade-in fade-in-delay-1">
                <h3>Pack Business</h3>
                <div class="pricing-price">
                    <span class="amount">4 900</span>
                    <span class="currency">FCFA</span>
                    <span class="period">/ activation</span>
                </div>
                <p class="tagline">La solution complète sans limite de produits pour vendre efficacement.</p>
                <ul class="pricing-features">
                    <li><span class="icon">✓</span> Produits illimités</li>
                    <li><span class="icon">✓</span> Paiement mobile money ou à la livraison</li>
                    <li><span class="icon">✓</span> Notifications WhatsApp automatiques</li>
                    <li><span class="icon">✓</span> Codes promo pour fidéliser</li>
                    <li><span class="icon">✓</span> Tableau de bord avancé</li>
                    <li><span class="icon">✓</span> 5% de commission sur les ventes</li>
                    <li class="disabled"><span class="icon">—</span> 0% de commission</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register?plan=business" class="btn btn-primary btn-block">Activer pack Business — 4 900 FCFA</a>
            </div>
            <div class="pricing-card fade-in fade-in-delay-2">
                <h3>Pack Pro</h3>
                <div class="pricing-price">
                    <span class="amount">9 900</span>
                    <span class="currency">FCFA</span>
                    <span class="period">/ activation</span>
                </div>
                <p class="tagline">Pour une image de marque premium et zéro commission sur vos ventes.</p>
                <ul class="pricing-features">
                    <li><span class="icon">✓</span> Tout ce qu'offre Business</li>
                    <li><span class="icon">✓</span> 0% de commission sur tous les paiements</li>
                    <li><span class="icon">✓</span> Domaine personnalisé (votresite.com)</li>
                    <li><span class="icon">✓</span> Marque PhoenixKA masquée</li>
                    <li><span class="icon">✓</span> Export commandes en Excel</li>
                    <li><span class="icon">✓</span> Support prioritaire WhatsApp</li>
                </ul>
                <a href="<?= SITE_URL ?>/auth/register?plan=pro" class="btn btn-outline btn-block">Activer pack Pro — 9 900 FCFA</a>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section section-dark" id="faq">
    <div class="container">
        <div class="section-header fade-in">
            <span class="label">FAQ</span>
            <h2>On répond à tout</h2>
        </div>
        <div class="faq-list fade-in">
            <div class="faq-item">
                <button class="faq-question">Je n'y connais rien en technologie, c'est pour moi ? <span class="icon">+</span></button>
                <div class="faq-answer"><p>Oui ! PhoenixKA est fait exactement pour vous. Si vous savez envoyer une photo sur WhatsApp, vous savez créer votre boutique. Notre équipe vous accompagne gratuitement jusqu'à votre première vente.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Combien ça coûte d'activer ma boutique ? <span class="icon">+</span></button>
                <div class="faq-answer"><p>La création de votre boutique est gratuite. Vous ne payez que les frais d'activation selon le pack choisi quand vous êtes prêt à lancer votre boutique, à partir de 2 000 FCFA seulement en promotion (Pack Découverte à 2 000 FCFA, Business à 4 900 FCFA, Pro à 9 900 FCFA).</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Comment mes clients paient ? <span class="icon">+</span></button>
                <div class="faq-answer"><p>Vos clients peuvent payer par Wave, Orange Money, MTN Money, ou à la livraison. L'argent arrive directement sur votre téléphone.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Est-ce que je peux vendre depuis mon téléphone ? <span class="icon">+</span></button>
                <div class="faq-answer"><p>Absolument ! PhoenixKA est conçu pour fonctionner parfaitement sur téléphone. Vous pouvez créer, gérer et suivre toute votre boutique depuis votre smartphone.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-question">Puis-je avoir plusieurs boutiques ? <span class="icon">+</span></button>
                <div class="faq-answer"><p>Oui, notre plateforme multi-boutique vous permet de créer et gérer plusieurs boutiques depuis un seul compte. Chaque boutique a son propre lien, ses produits et son tableau de bord.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container fade-in">
        <h2>Prêt à vendre vos produits en ligne ?</h2>
        <p>Votre boutique prête en 5 minutes. Vos clients commandent et paient directement en ligne.</p>
        <div class="cta-input-group">
            <input type="text" placeholder="Le nom de votre business..." id="ctaBusinessName">
            <a href="<?= SITE_URL ?>/auth/register" class="btn btn-primary" id="ctaBtn">Créer ma boutique →</a>
        </div>
        <p class="cta-note">✓ Boutique gratuite à créer · ✓ Pas besoin d'ordinateur · ✓ Annulation à tout moment</p>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="logo">
                    <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
                    <span>PhoenixKA Shop</span>
                </div>
                <p>Des solutions intelligentes pour booster votre réussite. Votre partenaire technologique de confiance.</p>
            </div>
            <div class="footer-links">
                <div>
                    <h4>Plateforme</h4>
                    <ul>
                        <li><a href="#comment">Comment ça marche</a></li>
                        <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                        <li><a href="#tarifs">Tarifs</a></li>
                        <li><a href="#faq">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Centre d'aide</a></li>
                        <li><a href="#">Nous contacter</a></li>
                        <li><a href="#">WhatsApp</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Légal</h4>
                    <ul>
                        <li><a href="<?= SITE_URL ?>/terms">Contrat Marchand &amp; CGU</a></li>
                        <li><a href="<?= SITE_URL ?>/terms">Confidentialité</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> PhoenixKA Shop — LePhoenixKA. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<script src="<?= ASSETS_URL ?>/js/main.js"></script>
</body>
</html>

