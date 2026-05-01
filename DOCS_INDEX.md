# 📚 Documentation Index - Version Management System

## Quick Navigation

### 🎯 Getting Started

**Nouvelle? Commencez ici:**

1. [README.md](./README.md) - Vue d'ensemble du module
2. [VERSIONING_COMPLETE.md](./VERSIONING_COMPLETE.md) - Summary rapide

### 📖 Documentation Complète

#### Version Management Guides

- **[VERSION_MANAGEMENT.md](./VERSION_MANAGEMENT.md)** ⭐ 
  - Guide complet du système de versioning
  - Structure des versions (MAJOR.MINOR.PATCH)
  - Workflows complets
  - Checklist de release
  - Git hooks et scripts

- **[CONFIG_VERSION_INTEGRATION.md](./CONFIG_VERSION_INTEGRATION.md)** ⭐
  - Intégration dans le menu Dolibarr
  - Onglet "About" détails
  - Cas d'usage
  - Workflow quotidien

- **[VERSIONING_COMPLETE.md](./VERSIONING_COMPLETE.md)**
  - Summary de l'implémentation
  - Fichiers concernés
  - Status et prochaines étapes

#### Testing & Verification

- **[TEST_VERSION_SYSTEM.md](./TEST_VERSION_SYSTEM.md)** 🧪
  - Guide de test complet
  - Checklist de vérification
  - Troubleshooting
  - Test success criteria

### 🚀 Accès dans Dolibarr

```
Administration
  → MarketPlace_BDC
    → Configuration (Advanced Config)
      → Onglet "About" ℹ️
        ├─ Version Info
        ├─ Git Information
        ├─ Build Information
        └─ Features & Support
```

### 🛠️ Tools & Scripts

#### Version Management Script

```bash
# Voir la version actuelle
grep "const VERSION = " class/VersionManager.class.php

# Bumper les versions
./update_version.sh patch    # 1.2.0 → 1.2.1
./update_version.sh minor    # 1.2.0 → 1.3.0
./update_version.sh major    # 1.2.0 → 2.0.0
```

#### Git Commands

```bash
# Voir les commits récents
git log --oneline -10

# Voir tous les tags
git tag -l

# Voir les tags avec dates
git tag -l --format='%(refname:short) %(creatordate:short)'

# Créer un tag manuel
git tag -a v1.2.0 -m "Release version 1.2.0"

# Voir le fichier de build
cat .version-build
```

### 📊 Key Files

#### Core Implementation

| File | Purpose |
|------|---------|
| `class/VersionManager.class.php` | Classe principale de gestion de version |
| `admin/advanced_config.php` | UI avec onglet About |
| `.git/hooks/post-commit` | Git hook automatique |
| `update_version.sh` | Script de version bumping |

#### Documentation

| File | Purpose |
|------|---------|
| `VERSION_MANAGEMENT.md` | Guide complet versioning |
| `CONFIG_VERSION_INTEGRATION.md` | Docs intégration menu |
| `VERSIONING_COMPLETE.md` | Summary implémentation |
| `TEST_VERSION_SYSTEM.md` | Guide de test |
| `.version-build` | Metadata auto-généré |

### 🔄 Workflow Quotidien

**À chaque modification:**

```bash
# 1. Faire les changements
# (éditer code, tester, etc.)

# 2. Committer
git add .
git commit -m "Fix: Description"

# ✅ Git hook s'exécute automatiquement
#    Capture et met à jour .version-build

# 3. Pusher
git push origin main

# 4. Voir dans Dolibarr
# Admin → Configuration → About
# → Affiche la nouvelle version/commit
```

**Pour une release:**

```bash
# 1. Bumper la version
./update_version.sh minor

# 2. Valider les changements
git diff --cached

# 3. Committer
git commit -m "Release v1.3.0"

# 4. Pusher (avec tags)
git push origin main --tags
```

### 🎯 Cas d'Usage

#### 🐛 Debugging
- User signale un bug
- Check: Admin → Configuration → About
- Voir version exacte + commit
- Retrouver change dans Git

#### 📦 Déploiement
- Vérifier version avant deploy
- Confirmer branche correcte
- Valider auteur et date
- Deploy en confiance

#### 👥 Maintenance d'Équipe
- Qui a modifié → Auteur
- Quand → Date/heure
- Quelle branche → Branch
- Quel changement → Commit SHA

#### 📌 Version Management
- Bumper version avec script
- Git tags automatiques
- CHANGELOG auto-généré
- README auto-updaté

### 📝 Infos Affichées dans About

**📦 Version Information**
- Module Version: `1.2.0`
- Release Date: `2026-05-01`
- PHP Required: `8.1+`
- PHP Current: `(détecté)`
- Dolibarr Required: `17.0+`

**🔗 Git Information**
- Repository: `https://github.com/anexys-ops/doliModMarketplace`
- Branch: `main`
- Commit: `9814fca` (court) / `9814fca3e7...` (long)
- Commit Date: `2026-05-01 15:41:04 +0200`
- Commit Author: `Fahd OUBENAISSA`
- Tags: `v1.2.0, v1.3.0, ...`

**⚙️ Build Information**
- Build Date: `2026-05-01 15:41:04`
- Server OS: `(auto-detected)`
- Server Info: `(auto-detected)`

**✨ Features**
- Liste des fonctionnalités

**📞 Support & Resources**
- Links vers GitHub, Linear, contact

### 🚀 Prochaines Étapes

1. **Déployer sur serveur:**
   ```bash
   cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
   git pull origin main
   ```

2. **Activer le module Dolibarr**

3. **Tester le système:**
   - Voir VERSION_MANAGEMENT.md → Testing section
   - Suivre les tests dans TEST_VERSION_SYSTEM.md

4. **Utiliser en production:**
   - Suivi des versions automatique
   - Git history complète
   - Traçabilité à 100%

### ✅ Checklist Implémentation

- [x] Classe VersionManager créée
- [x] UI About tab implémentée
- [x] Git hook configuré
- [x] update_version.sh script créé
- [x] Documentation complète
- [x] Tests documentés
- [x] GitHub pushé
- [x] Production ready

### 📞 Support

**Questions?** Consultez:

1. Le guide adapté à votre besoin:
   - VERSION_MANAGEMENT.md - Pour comprendre le système
   - CONFIG_VERSION_INTEGRATION.md - Pour l'interface Dolibarr
   - TEST_VERSION_SYSTEM.md - Pour tester

2. Les examples pratiques dans les guides

3. Le troubleshooting section

### 🔗 Ressources Externes

- **GitHub Repository:** https://github.com/anexys-ops/doliModMarketplace
- **Linear Board:** https://linear.app/anexys/project/dolibarr-modulemarketplace-bdc-f79cfe2c5ebb
- **Dolibarr Docs:** https://wiki.dolibarr.org/

---

**Version:** 1.2.0  
**Last Updated:** 2026-05-01  
**Status:** ✅ Production Ready
