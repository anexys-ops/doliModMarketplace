# 🧪 Testing Version Management System

## Quick Test Guide

Voici comment tester le système de gestion de version complet.

---

## 📋 Checklist de Test

### Test 1: Voir les infos Version dans Dolibarr ✅

**Prérequis:** Module activé sur le serveur

1. Aller à: **Administration → MarketPlace_BDC → Configuration**
2. Cliquer sur l'onglet **"About"** (ℹ️)
3. Vérifier l'affichage:
   - ✅ Version: 1.2.0
   - ✅ Release Date: 2026-05-01
   - ✅ PHP info
   - ✅ Dolibarr info
   - ✅ Git branch: main
   - ✅ Git commit: 3f1c195
   - ✅ Commit author: Fahd OUBENAISSA
   - ✅ Commit date: 2026-05-01 15:40:13
   - ✅ Build info

### Test 2: Git Hook Automatique ✅

**But:** Vérifier que le hook s'exécute après chaque commit

```bash
cd /Users/admin/Documents/ModuleMarketPlace_dolibarr

# Faire une petite modification
echo "# Test comment" >> README.md

# Committer
git add README.md
git commit -m "Test: Verify git hook execution"

# Observer la sortie:
# 📝 Updating version metadata...
# ✅ Version: 1.2.0
# ✅ Branch: main
# ✅ Commit: <SHA>
# ✅ Last Updated: <DATE>
# 💾 Build info saved to .version-build

# Vérifier le fichier auto-généré
cat .version-build
# Doit afficher les dernières infos de build
```

**Résultat attendu:**
- Hook s'exécute automatiquement ✅
- `.version-build` est généré ✅
- Métadata à jour ✅

### Test 3: Version Bump Script ✅

**But:** Tester le script `update_version.sh`

```bash
cd /Users/admin/Documents/ModuleMarketPlace_dolibarr

# Voir la version actuelle
grep "const VERSION = " class/VersionManager.class.php
# const VERSION = '1.2.0';

# Bumper une version patch
./update_version.sh patch

# Observer la sortie:
# === MarketPlace Module Version Manager ===
# Current Version: 1.2.0
# New Version: 1.2.1
# Release Date: 2026-05-01
# 
# ✅ Updated VersionManager.class.php
# ✅ Updated CHANGELOG.md
# ✅ Updated README.md
# ✅ Git tag created: v1.2.1
# ✅ Files staged for commit

# Vérifier les changements
git diff --cached class/VersionManager.class.php
# Doit montrer: const VERSION = '1.2.1'

git diff --cached README.md
# Doit montrer la nouvelle version

# Valider les changements
git commit -m "Release v1.2.1"
git push origin main --tags

# Vérifier les tags
git tag -l
# Doit afficher: v1.2.0, v1.2.1
```

**Résultat attendu:**
- Version bumpée correctement ✅
- CHANGELOG mis à jour ✅
- README mis à jour ✅
- Git tag créé ✅
- Fichiers staged pour commit ✅

### Test 4: Git Info Récupération ✅

**But:** Vérifier que les infos Git sont correctement récupérées

```bash
# Vérifier la classe VersionManager
php -r "
require '/Users/admin/Documents/ModuleMarketPlace_dolibarr/class/VersionManager.class.php';
\$vm = new VersionManager('/Users/admin/Documents/ModuleMarketPlace_dolibarr');
\$info = \$vm->getVersionInfo();
echo 'Version: ' . \$info['version'] . \"\n\";

\$git = \$vm->getGitInfo();
echo 'Branch: ' . \$git['branch'] . \"\n\";
echo 'Commit: ' . \$git['commit_short'] . \"\n\";
echo 'Author: ' . \$git['commit_author'] . \"\n\";
"

# Résultat attendu:
# Version: 1.2.0
# Branch: main
# Commit: 3f1c195
# Author: Fahd OUBENAISSA
```

**Résultat attendu:**
- Version info correcte ✅
- Git branch détectée ✅
- Commit SHA récupéré ✅
- Auteur identifié ✅

### Test 5: Workflow Complet ✅

**But:** Tester un workflow complet de développement

```bash
cd /Users/admin/Documents/ModuleMarketPlace_dolibarr

# 1. Créer une branche feature
git checkout -b feature/test-version-system

# 2. Faire des changements
echo "// Test modification" >> admin/advanced_config.php

# 3. Committer
git add admin/advanced_config.php
git commit -m "Test: Feature development on version system"

# ✅ Git hook s'exécute
# ✅ .version-build mis à jour

# 4. Voir les infos
cat .version-build
# GIT_BRANCH=feature/test-version-system
# GIT_COMMIT=<nouveau SHA>

# 5. Merger et bumper
git checkout main
git merge feature/test-version-system
./update_version.sh minor

# 6. Pusher
git commit -m "Release v1.3.0"
git push origin main develop --tags

# 7. Vérifier la version
git log --oneline -3
git tag -l | tail -3
```

**Résultat attendu:**
- Tous les changements trackés ✅
- Version bumpée automatiquement ✅
- Branche correctement identifiée ✅
- Git tags créés ✅

---

## 🎯 Vérification Finale

### Checklist de vérification:

- [ ] About tab visible dans Dolibarr
- [ ] Version 1.2.0 affichée
- [ ] Git info correcte (branch, commit, author)
- [ ] Build date/time mis à jour
- [ ] `.version-build` auto-généré après chaque commit
- [ ] `update_version.sh` fonctionne correctement
- [ ] Git tags créés (v1.2.0, v1.2.1, etc.)
- [ ] CHANGELOG mis à jour automatiquement
- [ ] README version à jour
- [ ] Tous les changements poussés vers GitHub

### URLs de vérification:

1. **GitHub Repository:**
   - https://github.com/anexys-ops/doliModMarketplace
   - Vérifier commits et tags récents

2. **Dolibarr Interface:**
   - Admin → MarketPlace_BDC → Configuration → About
   - Voir version, git info, build metadata

3. **Linear Tickets:**
   - https://linear.app/anexys/project/dolibarr-modulemarketplace-bdc-f79cfe2c5ebb
   - Créer tickets pour suivi de version

---

## 🐛 Troubleshooting

### Problem: Git hook ne s'exécute pas

**Solution:**
```bash
# Vérifier les permissions
ls -la /Users/admin/Documents/ModuleMarketPlace_dolibarr/.git/hooks/post-commit
# Doit montrer: -rwxr-xr-x (executable)

# Si pas executable:
chmod +x /Users/admin/Documents/ModuleMarketPlace_dolibarr/.git/hooks/post-commit

# Tester le hook manuellement:
bash /Users/admin/Documents/ModuleMarketPlace_dolibarr/.git/hooks/post-commit
```

### Problem: Version ne s'affiche pas dans Dolibarr

**Solution:**
```bash
# Vérifier que VersionManager.class.php existe
ls -la /var/www/dolibarr/htdocs/custom/marketplace_bdc/class/VersionManager.class.php

# Vérifier la syntaxe PHP
php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/class/VersionManager.class.php

# Vérifier que advanced_config.php charge la classe
grep -n "VersionManager" /var/www/dolibarr/htdocs/custom/marketplace_bdc/admin/advanced_config.php
```

### Problem: update_version.sh ne marche pas

**Solution:**
```bash
# Vérifier les permissions
ls -la update_version.sh
# Doit montrer: -rwxr-xr-x

# Si pas executable:
chmod +x update_version.sh

# Tester manuellement:
bash update_version.sh patch
```

---

## ✅ Test Success Criteria

La implémentation est considérée réussie si:

1. ✅ About tab visible et accessible dans Dolibarr
2. ✅ Version 1.2.0 affichée correctement
3. ✅ Git branch, commit, author visibles
4. ✅ Build date/time auto-updaté
5. ✅ `.version-build` généré après chaque commit
6. ✅ `update_version.sh` bump les versions correctement
7. ✅ Git tags créés et poussés
8. ✅ CHANGELOG auto-généré
9. ✅ README version à jour
10. ✅ Tous les changements dans GitHub

---

## 📝 Notes

- Git hook s'exécute **automatiquement** après `git commit`
- Pas besoin d'interventions manuelles pour la capture de metadata
- Version peut être bumpée manuellement avec `./update_version.sh`
- Tous les fichiers sont stockés dans Git pour traçabilité

---

**Status:** ✅ Ready for testing  
**Version:** 1.2.0  
**Last Updated:** 2026-05-01
