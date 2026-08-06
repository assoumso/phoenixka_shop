<?php
/**
 * Shared PhoenixKA Dashboard Sidebar Component
 * Expects $currentPage (string), $store (array), and optional $stats (array)
 */
$currentPage = $currentPage ?? 'dashboard';
$pendingCount = isset($stats['pending_orders']) ? intval($stats['pending_orders']) : 0;
?>
<aside class="sidebar" id="sidebar">
    <a href="<?= SITE_URL ?>/dashboard/" class="sidebar-logo">
        <img src="<?= ASSETS_URL ?>/images/logo.png" alt="PhoenixKA">
        <span>PhoenixKA</span>
    </a>
    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Principal</div>
            
            <!-- Tableau de bord -->
            <a href="<?= SITE_URL ?>/dashboard/" class="sidebar-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Tableau de bord</span>
            </a>

            <!-- Commandes -->
            <a href="<?= SITE_URL ?>/dashboard/orders" class="sidebar-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
                <span>Commandes</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge-count"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>

            <!-- Produits -->
            <a href="<?= SITE_URL ?>/dashboard/products" class="sidebar-link <?= $currentPage === 'products' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                    <line x1="12" y1="14" x2="12" y2="18"/>
                    <polyline points="10 16 12 14 14 16"/>
                </svg>
                <span>Produits</span>
            </a>

            <!-- Catégories -->
            <a href="<?= SITE_URL ?>/dashboard/categories" class="sidebar-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                    <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                </svg>
                <span>Catégories</span>
            </a>

            <!-- Clients -->
            <a href="<?= SITE_URL ?>/dashboard/customers" class="sidebar-link <?= $currentPage === 'customers' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span>Clients</span>
            </a>

            <!-- Portefeuille & Décaissements -->
            <a href="<?= SITE_URL ?>/dashboard/wallet" class="sidebar-link <?= $currentPage === 'wallet' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M14.8 9A2 2 0 0 0 13 8h-2a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-2a2 2 0 0 1-1.8-1"/>
                    <line x1="12" y1="6" x2="12" y2="18"/>
                    <polyline points="17 7 14 10 17 10"/>
                </svg>
                <span>Portefeuille &amp; Retraits</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Marketing</div>

            <!-- Sponsoring & Préfinancement -->
            <a href="<?= SITE_URL ?>/dashboard/sponsoring" class="sidebar-link <?= $currentPage === 'sponsoring' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                    <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-3.05 11a22.35 22.35 0 0 1-3.95 2z"/>
                </svg>
                <span>Sponsoring 50%</span>
                <span class="badge-count" style="background:linear-gradient(135deg,#FFD700,#FFA500);color:#000;font-weight:800;font-size:0.65rem">50%</span>
            </a>

            <!-- Programme d'Affiliation -->
            <a href="<?= SITE_URL ?>/dashboard/affiliation" class="sidebar-link <?= $currentPage === 'affiliation' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Affiliation &amp; Bonus</span>
                <span class="badge-count" style="background:rgba(34,197,94,0.2);color:#22C55E;font-weight:800;font-size:0.65rem">🎁 250</span>
            </a>
            
            <!-- Codes promo -->
            <a href="<?= SITE_URL ?>/dashboard/promos" class="sidebar-link <?= $currentPage === 'promos' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                <span>Codes promo</span>
            </a>

            <!-- Avis clients -->
            <a href="<?= SITE_URL ?>/dashboard/reviews" class="sidebar-link <?= $currentPage === 'reviews' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Avis clients</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Paramètres</div>

            <!-- Ma boutique -->
            <a href="<?= SITE_URL ?>/dashboard/store-settings" class="sidebar-link <?= $currentPage === 'store-settings' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/>
                    <line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/>
                    <line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/>
                    <line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/>
                    <line x1="17" y1="16" x2="23" y2="16"/>
                </svg>
                <span>Ma boutique</span>
            </a>

            <!-- Mon compte -->
            <a href="<?= SITE_URL ?>/dashboard/account" class="sidebar-link <?= $currentPage === 'account' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>Mon compte</span>
            </a>
        </div>

        <?php 
        $userSession = getCurrentUser();
        if (($userSession['role'] ?? '') === 'admin' || ($userSession['is_admin'] ?? 0) == 1 || ($userSession['email'] ?? '') === 'admin@phoenixka.shop'): 
        ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title" style="color:var(--gold)">ADMINISTRATION</div>
            
            <!-- Décaissements & Gateways -->
            <a href="<?= SITE_URL ?>/admin/payouts" class="sidebar-link <?= $currentPage === 'admin_payouts' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
                <span>Décaissements &amp; Gateways</span>
            </a>

            <!-- Gestion des Boutiques -->
            <a href="<?= SITE_URL ?>/admin/stores" class="sidebar-link <?= $currentPage === 'admin_stores' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span>Gestion Boutiques</span>
            </a>

            <!-- Sponsoring & Pub 50% -->
            <a href="<?= SITE_URL ?>/admin/sponsoring" class="sidebar-link <?= $currentPage === 'admin_sponsoring' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/>
                    <path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-3.05 11a22.35 22.35 0 0 1-3.95 2z"/>
                </svg>
                <span>Sponsoring &amp; Pub 50%</span>
            </a>

            <!-- Configuration Plateforme -->
            <a href="<?= SITE_URL ?>/admin/settings" class="sidebar-link <?= $currentPage === 'admin_settings' ? 'active' : '' ?>">
                <svg class="sidebar-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <span>Configuration Plateforme</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>

    <?php if (!empty($store)): ?>
    <div class="sidebar-store-info">
        <div class="store-name"><?= htmlspecialchars($store['name']) ?></div>
        <div class="store-status">
            <span class="dot <?= !empty($store['is_active']) ? 'online' : 'offline' ?>"></span>
            <span style="color:var(--text-muted)"><?= !empty($store['is_active']) ? 'En ligne' : 'Hors ligne' ?></span>
        </div>
    </div>
    <?php endif; ?>
</aside>
