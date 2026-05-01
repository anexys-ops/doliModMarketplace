# 🔧 CRITICAL FIX - Module Init() Method Signature

## 🔴 Erreur Serveur

```
Fatal error occurred: Declaration of modMarketPlace_BDC::init() must be compatible 
with DolibarrModules::init($options = '') in 
/var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php 
on line 61
```

## ✅ Problème Identifié

La méthode `init()` dans `modMarketPlace_BDC` avait la mauvaise signature:

**AVANT (❌ Incorrect):**
```php
public function init()  // ❌ Pas de paramètres
{
    // code
}
```

**APRÈS (✅ Correct):**
```php
public function init($options = '')  // ✅ Paramètre optionnel comme parent
{
    // code
    return 1;  // ✅ Retour requis
}
```

## 📝 Changements Appliqués

**Fichier:** `core/modules/modMarketPlace_BDC.class.php`

1. ✅ Ajout du paramètre `$options = ''` à la signature
2. ✅ Ajout du `return 1;` à la fin de la méthode

## 🚀 Déploiement

### Option 1: Via Git (Recommandé)

Sur le serveur:
```bash
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
git pull origin main
```

### Option 2: Via SCP

```bash
scp -P 150 core/modules/modMarketPlace_BDC.class.php \
    root@dlbp150r58.edicloud.app:/var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php
```

### Option 3: Via script

```bash
./deploy_quick.sh core/modules/modMarketPlace_BDC.class.php
```

## ✅ Vérification

Après déploiement:

```bash
# 1. Vérifier le fichier
ssh -p 150 root@dlbp150r58.edicloud.app \
    "php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php"

# Doit afficher: No syntax errors detected
```

## 🧪 Test du Module

1. **Aller à:** Administration → Setup → Modules
2. **Chercher:** "MarketPlace_BDC"
3. **Doit voir:** ✅ Module activable (pas d'erreur)
4. **Cliquer:** Activer le module
5. **Vérifier:** ✅ Module actif

## 📊 Fichiers Affectés

- ✅ `core/modules/modMarketPlace_BDC.class.php` (FIXÉ)

## 💾 Commit Info

- **Commit:** 54fd2b3
- **Date:** 2026-05-01 20:29:47
- **Message:** Fix: Correct init() method signature

## 🎯 Status

✅ **Fix appliqué et testé localement**  
⏳ **Attente: Déploiement sur serveur**

Après déploiement, l'erreur disparaîtra et le module s'activera correctement! 🎉

---

**Priority:** 🔴 CRITICAL  
**Urgency:** NOW  
**Status:** Ready to deploy
