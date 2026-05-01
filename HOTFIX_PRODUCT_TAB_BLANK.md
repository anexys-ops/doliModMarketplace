# 🔧 URGENT FIX - Product Tab Blank Page

## Problème

URL: `https://dlbp150r58.edicloud.app/custom/marketplace_bdc/marketplace/product_tab.php?id=108`

Affiche une page blanche. Cause: Manque d'import de la classe `Product`.

## Solution Appliquée

**Fichier corrigé:** `marketplace/product_tab.php`

### Changements:

1. ✅ Ajout import classe Product:
```php
if (!class_exists('Product')) {
    require_once DOL_DOCUMENT_ROOT . '/products/class/product.class.php';
}
```

2. ✅ Vérification de l'objet avant usage:
```php
if (!isset($object) || !is_object($object) || $object->id != $product_id) {
```

3. ✅ Gestion des exceptions:
```php
try {
    $product = new Product($db);
    if ($product->fetch($product_id) <= 0) {
        echo '<!-- DEBUG: Product not found -->';
        return;
    }
} catch (Exception $e) {
    echo '<!-- DEBUG: Product fetch error... -->';
    return;
}
```

4. ✅ Debug comments pour troubleshooting

## 📋 Déploiement Manual

### Via SCP (SSH):

```bash
# Depuis ta machine locale:
scp -P 150 marketplace/product_tab.php \
    root@dlbp150r58.edicloud.app:/var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php

# Ou via Git (si déployé):
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc/
git pull origin main
```

### Alternative: Git Deploy (si disponible):

Sur le serveur:
```bash
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
git pull origin main
# Vérifier les permissions:
chown -R www-data:www-data /var/www/dolibarr/htdocs/custom/marketplace_bdc
```

## ✅ Vérification

Après déploiement, tester:

```bash
# Vérifier le fichier est correct:
ssh -p 150 root@dlbp150r58.edicloud.app \
    "grep -n 'Product class' /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php"

# Vérifier la syntaxe PHP:
ssh -p 150 root@dlbp150r58.edicloud.app \
    "php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php"
```

Ensuite visiter:
```
https://dlbp150r58.edicloud.app/custom/marketplace_bdc/marketplace/product_tab.php?id=108
```

## 🔍 Si ça reste blanc:

### Debug Steps:

1. **Vérifier les logs Apache:**
```bash
ssh -p 150 root@dlbp150r58.edicloud.app \
    "tail -50 /var/log/apache2/error.log"
```

2. **Vérifier les logs Dolibarr:**
```bash
ssh -p 150 root@dlbp150r58.edicloud.app \
    "tail -50 /var/www/dolibarr/htdocs/documents/dolibarr.log"
```

3. **Vérifier les permissions:**
```bash
ssh -p 150 root@dlbp150r58.edicloud.app \
    "ls -la /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php"
```

4. **Tester PHP direct:**
```bash
ssh -p 150 root@dlbp150r58.edicloud.app \
    "php -r \"require('/var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php');\" 2>&1 | head -10"
```

## 📝 Commit Info

- **Commit:** 0afcd7c
- **Date:** 2026-05-01 20:23:58
- **Message:** Fix: Add Product class import and error handling in product_tab.php
- **Fichier:** marketplace/product_tab.php

## 🎯 Prochaines Étapes

1. Déployer le fichier sur le serveur
2. Tester l'URL
3. Si tout ok, continuer avec Phase 4 tickets
4. Si problème persiste, check les logs

---

**Status:** 🟡 Ready to deploy  
**Priority:** 🔴 HIGH  
**Urgency:** NOW
