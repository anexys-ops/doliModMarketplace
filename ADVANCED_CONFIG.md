# 🚀 Advanced Configuration Features

## ✨ Nouvelles Fonctionnalités

### 1. **Sélection des Endpoints** 🌐
Cochez les endpoints que vous voulez utiliser pour chaque marketplace:
- ✅ Sync Offers (Prix/Stock)
- ✅ Import Orders
- ✅ Send Promotions
- ✅ Update Descriptions

### 2. **Cron Paramétrable** ⏰
Configuration des tâches cron intégrées à Dolibarr:
- Fréquence: Hourly, Daily, Weekly, Monthly
- Heure spécifique (0-23)
- Jour de la semaine (pour weekly)
- Jour du mois (pour monthly)
- Status: Enable/Disable par tâche

### 3. **Test des Clés** 🧪
Test des connexions en DEV et PROD:
- **Test DEV**: Valide avec credentials DEV
- **Test PROD**: Valide avec credentials PROD
- Résultats sauvegardés en logs
- Historique des tests accessible

### 4. **Logs dans les Outils** 📊
Nouvelle page "Tools → Logs" qui affiche:
- ✅ Tous les événements de sync
- ✅ Tests de connexion
- ✅ Imports/Exports
- ✅ Erreurs et warnings
- ✅ Rétention configurable

---

## 📁 Fichiers Créés

### 1. **class/LogManager.class.php**
Classe pour gérer les logs:
- `log()` - Enregistrer un événement
- `getLogs()` - Récupérer avec filtres
- `getStats()` - Statistiques
- `purgeOldLogs()` - Nettoyage automatique
- `exportToCSV()` - Export

### 2. **sql/llx_modmkp_cron_and_logs.sql**
Nouvelles tables SQL:
- `llx_modmkp_synclog` - Logs des syncs
- `llx_modmkp_cron` - Configuration crons
- `llx_modmkp_config` - Configuration module

### 3. **admin/tools.php**
Page Logs & Monitoring:
```
Admin → MarketPlace → Tools (NEW!)
```
Features:
- ✅ Filtres: Marketplace, Type, Status, Date
- ✅ Statuts: OK, Error, Warning, Pending
- ✅ Stats cards (Total, OK, Errors, Warnings)
- ✅ Export CSV
- ✅ Purge logs (7/30 days)
- ✅ Pagination

### 4. **admin/advanced_config.php**
Configuration avancée avec 4 onglets:

#### Onglet 1: Endpoints 📍
- Tuiles pour chaque marketplace
- Checkboxes pour chaque endpoint:
  - Sync Offers
  - Import Orders
  - Send Promotions
  - Update Descriptions
- Bouton Save par marketplace

#### Onglet 2: Cron Jobs ⏰
- Tableau des crons
- Colonnes: Type, Description, Status, Frequency, Last Execution
- Bouton Edit pour chaque cron

#### Onglet 3: Tests 🧪
- Pour chaque marketplace:
  - Bouton "Test DEV"
  - Bouton "Test PROD"
- Résultat du test affiché (success/error)

#### Onglet 4: Settings 🔧
- Log Retention (jours)
- Enable Development Mode
- Auto-Retry Failed Syncs
- Retry Attempts

---

## 🔄 Architecture

```
Dolibarr Menu Structure:
│
├─ MarketPlace_BDC
│  ├─ Dashboard
│  ├─ Orders
│  ├─ Configuration (setup.php)
│  ├─ Advanced Config (advanced_config.php) ← NEW!
│  └─ Tools
│     └─ Logs (tools.php) ← NEW!
│
└─ Outils (Tools menu)
   ├─ Admin Tools
   ├─ Logs & Monitoring ← NOUVEAU!
   └─ ...
```

---

## 📊 Tables SQL

### llx_modmkp_synclog
```
rowid              INT
fk_marketplace     INT
fk_offer           INT
type               VARCHAR(50) - test|sync|import|export|error
status             VARCHAR(20) - ok|error|warning|pending
message            TEXT
date_created       DATETIME
```

### llx_modmkp_cron
```
rowid              INT
name               VARCHAR(100) - Unique name
description        TEXT
type               VARCHAR(50) - sync_offers|sync_stock|fetch_orders|...
enabled            TINYINT(1)
frequency          VARCHAR(50) - hourly|daily|weekly|monthly
hour               INT (0-23)
day_of_week        INT (0-6)
day_of_month       INT (1-31)
last_execution     DATETIME
next_execution     DATETIME
status             VARCHAR(20) - pending|running|completed|failed
```

### llx_modmkp_config
```
rowid              INT
key_name           VARCHAR(100) - Unique
value              TEXT
type               VARCHAR(50) - string|int|json|bool
description        TEXT
date_updated       DATETIME
```

---

## 🎯 Fonctionnalités Détaillées

### Tests (DEV vs PROD)

**Test DEV:**
- Utilise credentials DEV (si configurées différemment)
- Pas d'impact sur les vraies données
- Parfait pour valider l'intégration

**Test PROD:**
- Utilise credentials PROD réelles
- À faire avec prudence
- Peut créer des données test sur le marketplace

**Résultat:**
```
✓ Connection test successful
  - Marketplace: ADEO
  - API Type: Mirakl
  - Endpoint: https://api.mirakl.net/...
  - Last Tested: 2026-05-01 15:30:00
```

### Logs & Retention

**Filtres disponibles:**
- Marketplace
- Type (test, sync, import, export)
- Status (ok, error, warning)
- Date range (from/to)

**Actions:**
- 📊 Voir stats (cards)
- 📥 Export CSV
- 🗑️ Purge old (7 days / 30 days)
- 📄 Pagination

**Rétention:**
- Configurable en settings (par défaut 30 jours)
- Auto-purge basé sur la config
- Possible de purger manuellement

### Cron Jobs

**Tâches disponibles:**
1. `sync_offers` - Sync prix/stock automatique
2. `sync_stock` - Sync stock depuis Dolibarr
3. `fetch_orders` - Récupérer nouvelles commandes
4. `fetch_returns` - Récupérer retours/annulations

**Configuration:**
- Enable/Disable par tâche
- Fréquence: Hourly, Daily, Weekly, Monthly
- Heure: 0-23 (pour daily, weekly, monthly)
- Jour: 0-6 (pour weekly) ou 1-31 (pour monthly)

**Exemple:**
```
Sync Offers
├─ Enabled: ✅
├─ Frequency: Daily
├─ Hour: 02 (2 AM)
└─ Last Execution: 2026-05-01 02:15:00
```

---

## 🔌 Intégration Dolibarr Cron

Les crons seront exécutés par le système Dolibarr via:
```
/core/cron/cron_read_agenda.php
/core/cron/cron_read_suppliers.php
/core/cron/cron_order_scheduler.php
```

Notre système s'intègre comme un cron hook Dolibarr standard.

---

## 🧪 Exemple d'Utilisation

### Scénario 1: Configuration Initiale

1. **Admin → Advanced Config**
2. Onglet "Endpoints":
   - ☑ Sync Offers
   - ☑ Import Orders
   - ☐ Send Promotions
   - ☑ Update Descriptions
3. Onglet "Tests":
   - Cliquer "Test DEV" pour ADEO → ✓ Success
   - Cliquer "Test PROD" pour ADEO → ✓ Success
4. Onglet "Cron":
   - Edit "sync_offers"
   - Enable: ✅
   - Frequency: Daily
   - Hour: 02
   - Save
5. Voir logs dans "Tools → Logs"

### Scénario 2: Déboguer une Erreur

1. **Admin → Tools → Logs**
2. Filtrer par:
   - Marketplace: ADEO
   - Status: Error
   - Date: Last 24 hours
3. Voir message d'erreur
4. Cliquer "Export CSV" pour rapport
5. Corriger la config
6. Relancer le test

---

## 🚀 Déploiement

Tous les fichiers sont prêts:

```bash
git clone https://github.com/anexys-ops/doliModMarketplace.git
cd marketplace_bdc
```

Fichiers nouveau:
- ✅ `class/LogManager.class.php`
- ✅ `sql/llx_modmkp_cron_and_logs.sql`
- ✅ `admin/tools.php`
- ✅ `admin/advanced_config.php`

Menu items à ajouter:
- ✅ Admin → Tools (nouveau sous-menu)
- ✅ Admin → Advanced Config (nouveau sous-menu)

---

## 📋 Checklist Activation

- [ ] Fichiers déployés
- [ ] Tables SQL créées
- [ ] Menu Tools visible
- [ ] Menu Advanced Config visible
- [ ] Test DEV → Success
- [ ] Test PROD → Success
- [ ] Cron job activé
- [ ] Premier sync réussi
- [ ] Logs visibles dans Tools

---

## 📖 Documentation Existante

Consulter aussi:
- `README.md` - Vue d'ensemble
- `DEPLOY.md` - Installation
- `TEMPLATE_INTEGRATION.md` - Architecture
- `PROJECT_STATUS.md` - État général

---

**Status:** ✅ PRÊT AU DÉPLOIEMENT  
**Date:** 2026-05-01  
**Version:** 1.1.0 (Advanced Features)
