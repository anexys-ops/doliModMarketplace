# ✅ Intégration Template Dolibarr - Product Tab

## 🎯 Ce qui a changé

Le fichier `product_tab.php` est maintenant **correctement intégré** au template Dolibarr. C'est plus qu'une page standalone - c'est un **véritable onglet Dolibarr**.

### Comment ça fonctionne

1. **Déclaration dans modMarketPlace_BDC.class.php:**
```php
$this->tabs = array(
    'product:+marketplaces:Marketplaces:marketplace_bdc@marketplace_bdc:1:/custom/marketplace_bdc/marketplace/product_tab.php?id=__ID__',
);
```

2. **Dolibarr ajoute l'onglet** sur chaque fiche produit automatiquement ✅

3. **Le fichier product_tab.php:**
   - N'a pas de tags HTML/HEAD/BODY
   - Utilise le contexte global Dolibarr (`$object`, `$db`, `$user`, `$conf`)
   - Affiche seulement le contenu du tab
   - Respecte le template Dolibarr

### Structure du fichier

```php
<?php
// 1. Pas de require main.inc.php
// Dolibarr charge déjà tout

// 2. Récupère le produit depuis le contexte Dolibarr
$product_id = isset($object->id) ? $object->id : ...
$product = $object;

// 3. Affiche seulement le contenu du tab
// Pas de <html>, <head>, <body>
// Pas de structure page complète

// 4. CSS intégré avec scope .marketplace-tab
// 5. JavaScript pour les actions

?>
<div class="marketplace-tab">
    <!-- Contenu spécifique de l'onglet -->
</div>
```

### Avantages

✅ **Template unifié** - Respecte le design Dolibarr  
✅ **Contexte global** - Accès direct au produit, user, permissions  
✅ **Sans duplication** - Pas de structure HTML redondante  
✅ **Performance** - Pas de page entière à charger  
✅ **Intégration native** - Onglet ajouté automatiquement  

---

## 📋 Fichiers à déployer

### 1. `core/modules/modMarketPlace_BDC.class.php`
**Chemin:** `/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php`

Fichier de déclaration du module avec:
- Déclaration de l'onglet produit
- Menus principaux (Dashboard, Orders, Config)
- Droits d'accès
- Configuration du module

### 2. `marketplace/product_tab.php`
**Chemin:** `/custom/marketplace_bdc/marketplace/product_tab.php`

Contenu de l'onglet:
- Affiche infos produit (SKU, Stock, Prix)
- Table avec toutes les offres marketplaces
- Édition en modal
- Actions: Edit, Sync, Delete
- Form pour ajouter offres

---

## 🚀 Déploiement

### Option A: Via Git (RECOMMANDÉ)
```bash
ssh root@dlbp150r58.edicloud.app -p 150
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
git pull origin main
```

### Option B: Via SCP
```bash
# Fichier module
sshpass -p 'A@WIN15tRaT0r' scp -P 150 \
  core/modules/modMarketPlace_BDC.class.php \
  root@dlbp150r58.edicloud.app:/var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/

# Fichier tab
sshpass -p 'A@WIN15tRaT0r' scp -P 150 \
  marketplace/product_tab.php \
  root@dlbp150r58.edicloud.app:/var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/
```

---

## ✅ Après déploiement

### 1. Vérifiez les fichiers
```bash
ls -la /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php
ls -la /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php
```

### 2. Activez le module
1. Dolibarr Admin → Modules → Chercher "MarketPlace_BDC"
2. Cliquez "Activer"
3. Le module se charge et enregistre l'onglet

### 3. Allez sur une fiche produit
1. Menu → Products → Selectionner un produit (ex: ID 155)
2. Vous verrez les onglets en haut:
   - Informations
   - Documents
   - **Marketplaces** ← NOUVEL ONGLET! 🎉
   - History
   - ...

### 4. Cliquez sur l'onglet "Marketplaces"
Vous verrez:
- Infos produit (SKU, Stock, Prix)
- Tableau avec les offres marketplaces
- Boutons d'action
- Form pour ajouter

---

## 📊 Visualisation

### Avant

```
Fiche Produit
├─ Informations
├─ Documents
├─ History
└─ ...

❌ Pas d'onglet Marketplaces
```

### Après

```
Fiche Produit
├─ Informations
├─ Documents
├─ ✅ Marketplaces ← NOUVEL ONGLET!
│  ├─ Infos produit
│  ├─ Tableau offres
│  ├─ Édition modal
│  └─ Actions (Edit, Sync, Delete)
├─ History
└─ ...
```

---

## 🔍 Dépannage

### L'onglet n'apparaît pas?

1. **Vérifiez le module est activé:**
   ```bash
   Dolibarr Admin → Modules → Chercher "MarketPlace"
   ```

2. **Vérifiez hard refresh:**
   - Ctrl+F5 (ou Cmd+Shift+R)
   - Clearez cache browser

3. **Vérifiez les fichiers:**
   ```bash
   php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php
   php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php
   ```

4. **Vérifiez les permissions:**
   ```bash
   chmod 644 /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php
   chmod 644 /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php
   ```

5. **Vérifiez les logs:**
   ```bash
   tail -50 /var/www/dolibarr/htdocs/dolibarr.log
   tail -50 /var/log/apache2/error.log
   ```

### Le tab affiche une erreur?

1. Vérifiez que les tables SQL existent:
   ```bash
   mysql -u dolibarr -p dolibarr
   > SHOW TABLES LIKE 'llx_modmkp%';
   ```

2. Vérifiez que vous avez les droits:
   ```
   Dolibarr Admin → Users & Permissions
   Cherchez votre utilisateur
   Vérifiez "MarketPlace Module" permissions
   ```

3. Vérifiez les logs PHP:
   ```bash
   tail -50 /var/log/apache2/error.log | grep marketplace
   ```

---

## 📝 Notes Techniques

### Contexte Dolibarr global
Le fichier `product_tab.php` a accès à:
- `$object` - Le produit (Product class instance)
- `$db` - Database connection
- `$user` - Utilisateur actuel
- `$conf` - Configuration Dolibarr
- `$langs` - Traductions

### CSS et JS
- CSS scoped avec `.marketplace-tab` prefix
- JS utilise des IDs unique: `editModal`, `editForm`, etc
- Pas de conflits avec Dolibarr CSS/JS

### Sécurité
- ✅ Vérification permissions Dolibarr
- ✅ Sanitization des entrées
- ✅ Protection SQL injection via `$db->escape()`
- ✅ Validation des données

---

## ✨ Résumé

| Point | Status |
|-------|--------|
| **Template Dolibarr** | ✅ Intégré |
| **Onglet automatique** | ✅ Déclaré |
| **CSS/JS scoped** | ✅ Isolé |
| **Contexte global** | ✅ Utilisé |
| **Pas HTML dupliqué** | ✅ Clean |
| **Permissions** | ✅ Contrôlées |
| **Prêt au déploiement** | ✅ OUI |

---

**Status:** ✅ SOLUTION COMPLÈTE  
**Date:** 2026-05-01  
**Version:** 1.0.1 - Template Integration  
**GitHub:** https://github.com/anexys-ops/doliModMarketplace
