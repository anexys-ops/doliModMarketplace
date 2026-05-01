# Configuration Menu - Version & Git Information

## 🎯 Objectif

Intégrer les numéros de versioning et infos Git dans le menu de configuration pour assurer la traçabilité et faciliter le débogage et le suivi des modifications.

## ✅ Implémentation

### 1. **VersionManager Class**
- **Fichier:** `class/VersionManager.class.php`
- **Responsabilité:** Gestion centralisée de toutes les infos de version
- **Fonctionnalités:**
  - Récupération version module (1.2.0)
  - Lecture infos Git (branche, commit, auteur)
  - Récupération build info (OS, PHP, date)
  - Notes de version

### 2. **About Tab in Advanced Config**
- **Fichier:** `admin/advanced_config.php`
- **Accès:** Administration → MarketPlace_BDC → Configuration → About
- **Affichage:**
  ```
  📦 Version Information
    - Module Version: 1.2.0
    - Release Date: 2026-05-01
    - PHP Required: 8.1+
    - Dolibarr Required: 17.0+
  
  🔗 Git Information
    - Repository: https://github.com/anexys-ops/doliModMarketplace
    - Branch: main
    - Commit: ce6b16c
    - Commit Date: 2026-05-01 15:38:40
    - Author: Fahd OUBENAISSA
  
  ⚙️ Build Information
    - Build Date: 2026-05-01 15:38:40
    - Server OS: macOS
    - Server Info: Darwin...
  
  ✨ Features (Liste)
  📞 Support & Resources
  ```

### 3. **Automatic Git Hook**
- **Fichier:** `.git/hooks/post-commit`
- **Déclenchement:** Automatiquement après chaque `git commit`
- **Actions:**
  - Capture du commit SHA
  - Identification de la branche
  - Récupération date/auteur
  - Génération du fichier `.version-build`

### 4. **Manual Version Update Script**
- **Fichier:** `update_version.sh`
- **Usage:** `./update_version.sh [patch|minor|major]`
- **Actions:**
  - Met à jour `VersionManager.class.php`
  - Génère une nouvelle entrée `CHANGELOG.md`
  - Met à jour `README.md`
  - Crée un tag Git
  - Stage les fichiers pour commit

## 🔄 Workflow de Mise à Jour

### Pour chaque modification:

```bash
# 1. Effectuer les changements
# (éditer code, tester, etc.)

# 2. Committer
git add .
git commit -m "Fix: Description du changement"

# ✅ Le Git hook s'exécute AUTOMATIQUEMENT
#    - Capture version metadata
#    - Génère .version-build
#    - Affiche confirmation

# 3. Pour une release, bumper la version
./update_version.sh patch  # ou minor/major

# 4. Pusher
git push origin main --tags
```

## 📊 Structure des Infos

### Dans l'interface Dolibarr:

```
┌─────────────────────────────────────────┐
│ About Tab (ℹ️)                           │
├─────────────────────────────────────────┤
│ 📦 Version Information                  │
│   • Module Version: 1.2.0               │
│   • Release Date: 2026-05-01            │
│   • PHP Required: 8.1+                  │
│   • Dolibarr Required: 17.0+            │
├─────────────────────────────────────────┤
│ 🔗 Git Information                      │
│   • Repository: https://...             │
│   • Branch: main                        │
│   • Commit: ce6b16c                     │
│   • Commit Date: 2026-05-01 15:38:40   │
│   • Author: Fahd OUBENAISSA             │
├─────────────────────────────────────────┤
│ ⚙️ Build Information                    │
│   • Build Date: 2026-05-01 15:38:40    │
│   • Server OS: macOS                    │
│   • Server Info: Darwin...              │
├─────────────────────────────────────────┤
│ ✨ Features & Support                  │
└─────────────────────────────────────────┘
```

### Dans le fichier `.version-build`:

```
VERSION=1.2.0
BUILD_DATE=2026-05-01 15:38:40
GIT_BRANCH=main
GIT_COMMIT=ce6b16c
GIT_COMMIT_FULL=ce6b16ca21af29a11586fe71050f1f559a1dc540
GIT_COMMIT_DATE=2026-05-01 15:38:40 +0200
GIT_COMMIT_AUTHOR=Fahd OUBENAISSA
```

## 🎯 Cas d'Utilisation

### 1. **Support & Debugging**
Quand un user signale un bug:
- Demander la version affichée dans "About"
- Vérifier le commit exact
- Retrouver facilement dans Git

### 2. **Déploiement**
Avant de déployer:
- Vérifier que la version est à jour
- Confirmer la branche correcte
- Valider l'auteur et la date

### 3. **Traçabilité**
Pour chaque modification:
- Version automatiquement trackée
- Git info capturée
- Historique dans CHANGELOG

### 4. **Maintenance**
Pour les équipes:
- Voir qui a fait le dernier changement
- Savoir quand c'était
- Identifier la branche de travail

## 🚀 Prochaines Étapes

1. **Déployer sur le serveur:**
   ```bash
   git pull origin main
   ```

2. **Activer le module et accéder à:**
   - Administration → MarketPlace_BDC → Configuration → About

3. **Pour tester le versioning:**
   ```bash
   ./update_version.sh patch  # Change v1.2.0 → v1.2.1
   git commit -m "Release v1.2.1"
   git push origin main --tags
   ```

4. **Vérifier dans l'interface:**
   - Voir "Module Version: 1.2.1"
   - Voir nouveau commit affichié
   - Voir la nouvelle date

## 📝 Notes

- ✅ **Automatique:** Git hook s'exécute après chaque commit
- ✅ **Intégré:** S'intègre parfaitement dans Dolibarr via VersionManager
- ✅ **Scriptable:** `update_version.sh` pour version bumping
- ✅ **Traçable:** Tous les changements trackés dans Git
- ✅ **Documenté:** VERSION_MANAGEMENT.md pour le guide complet

---

**Fichiers modifiés/créés:**
- `class/VersionManager.class.php` - Classe de gestion de version
- `admin/advanced_config.php` - Tab About ajouté
- `.git/hooks/post-commit` - Git hook automatique
- `update_version.sh` - Script de version bumping
- `VERSION_MANAGEMENT.md` - Guide complet
- `.version-build` - Metadata automatiquement généré

**Status:** ✅ Production Ready
