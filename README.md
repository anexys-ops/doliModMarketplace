# ModuleMarketPlace - Dolibarr Marketplace Manager

**Version 1.0** | Gérer les offres produits sur les marketplaces (ADEO, Cdiscount, Amazon, WooCommerce)

## 🚀 Features

### Phase 1 ✅ - Infrastructure
- Module Dolibarr complet (6 tables, 4 classes)
- 4 APIs implémentées (Mirakl, Octopia, SP-API, REST)
- Chiffrement des credentials
- Permissions granulaires (read/write/sync/admin)

### Phase 2 ✅ - Product Tab
- Onglet "Marketplaces" sur fiches produits
- Édition des offres (prix, stock, promos)
- Sync 1-clic
- Statut sync en temps réel

### Phase 3 ✅ - Dashboard & Orders
- Vue globale des offres par marketplace
- Gestion des commandes reçues
- Statuts: received → validated → integrated
- Stats et filtres

### Phase 4 🔲 - Automation (À faire)
- Cron sync automatique
- Webhooks pour les commandes
- Import stock depuis Dolibarr

## 📦 Structure

```
marketplace_bdc/
├── class/                    # Classes métier
│   ├── marketplace.class.php
│   ├── marketplaceoffer.class.php
│   ├── marketplaceorder.class.php
│   ├── MarketplaceConfigManager.class.php
│   └── api/                  # 4 implémentations
├── admin/                    # Pages admin
│   └── setup.php            # Configuration (UI tiles + JSON)
├── marketplace/             # Pages métier
│   ├── dashboard.php        # Vue globale
│   ├── product_tab.php      # Onglet produit
│   └── orders.php           # Gestion commandes
├── modules/
│   └── configs/             # JSON configs par marketplace
├── sql/                      # Migrations SQL
└── core/modules/
    └── modMarketPlace_BDC.class.php
```

## 🛠️ Installation

1. **SSH sur serveur:**
   ```bash
   ssh root@dlbp150r58.edicloud.app -p 150
   ```

2. **Cloner/sync depuis Git:**
   ```bash
   cd /var/www/dolibarr/htdocs/custom/
   git clone <REPO_URL> marketplace_bdc
   ```

3. **Activer module:**
   - Dolibarr Admin → Modules → Chercher "MarketPlace_BDC" → Activer

4. **Configurer:**
   - Admin → MarketPlace Module Setup
   - Remplir credentials pour chaque marketplace

## 📚 Documentation

- [Phase 3 Summary](./PHASE3_SUMMARY.md) - Fonctionnalités complètes
- [Testing Guide](./TESTING_GUIDE.md) - Comment tester
- [JSON Config Architecture](./JSON_CONFIG_ARCHITECTURE.md) - Architecture config
- [Onglet Troubleshooting](./ONGLET_TROUBLESHOOTING.md) - Fix onglet non visible

## 🔐 Configuration

### Marketplaces supportées
- **ADEO** (Mirakl) - adeo
- **Cdiscount** (Octopia) - cdiscount
- **Amazon SP-API** - amazon
- **WooCommerce REST** - woocommerce

### Credentials
Stockés chiffrés en base de données. Voir `modules/configs/*.json` pour endpoints.

## 📊 Database

6 tables créées:
- `llx_modmkp_marketplace` - Configuration
- `llx_modmkp_offer` - Offres produit
- `llx_modmkp_order` - Commandes
- `llx_modmkp_order_item` - Lignes commandes
- `llx_modmkp_synclog` - Logs sync
- `llx_modmkp_stock_alert` - Alertes stock

## 🧪 Testing

```bash
# Diagnostic page
https://YOUR_DOLIBARR/custom/marketplace_bdc/diagnose_tab.php

# Reload tabs
https://YOUR_DOLIBARR/custom/marketplace_bdc/reload_tabs.php
```

## 🔗 URLs

| Page | URL |
|------|-----|
| Dashboard | `/custom/marketplace_bdc/marketplace/dashboard.php` |
| Orders | `/custom/marketplace_bdc/marketplace/orders.php` |
| Setup | `/custom/marketplace_bdc/admin/setup.php` |

## 📝 Git Branches

- `main` - Production stable
- `develop` - Développement actif
- `feature/phase4-automation` - Phase 4 (à faire)
- `feature/*` - Features individuelles

## 🐛 Known Issues

- Onglet Marketplace pas visible → Voir [ONGLET_TROUBLESHOOTING.md](./ONGLET_TROUBLESHOOTING.md)

## 🚀 Roadmap

- [x] Phase 1: Infrastructure ✅
- [x] Phase 2: Product Tab ✅
- [x] Phase 3: Dashboard & Orders ✅
- [ ] Phase 4: Automation & Crons
- [ ] Phase 5: Advanced features

## 👨‍💻 Development

### Setup local

```bash
git clone <REPO_URL>
cd marketplace_bdc
# Voir documentation de chaque fichier pour détails
```

### Commit message format

```
[FEATURE] Description courte
[FIX] Description du fix
[DOCS] Mise à jour documentation
[REFACTOR] Refactorisation
```

### PRs

Chaque feature doit avoir une branche + PR pour review.

## 📞 Support

- Voir les fichiers `.md` pour troubleshooting
- Dashboard accessible à tous les utilisateurs avec droit "read"
- Configuration réservée aux admins

## 📄 License

Dolibarr Module - Same as Dolibarr (GPLv3)

---

**Created:** 2026-05-01  
**Module Version:** 1.0.0  
**Dolibarr:** 17+  
**PHP:** 8.1+
