# Version Management Guide

## Overview

La gestion des versions du module MarketPlace_BDC est automatisée via plusieurs mécanismes pour assurer la traçabilité complète des modifications.

## Version Structure

**Format:** `MAJOR.MINOR.PATCH`

- **MAJOR**: Changements majeurs, API breaking, ou rearchitecturing
- **MINOR**: Nouvelles fonctionnalités, améliorations
- **PATCH**: Corrections de bugs

**Exemple:** `1.2.0` = Version stable avec améliorations mineures

## Components de Version

### 1. **VersionManager.class.php**

Classe centrale qui gère toutes les informations de version:

```php
const VERSION = '1.2.0';
const VERSION_DATE = '2026-05-01';
const MINIMUM_PHP = '8.1';
const MINIMUM_DOLIBARR = '17.0';
```

**Méthodes principales:**

- `getVersionInfo()` - Infos complètes de version
- `getGitInfo()` - Infos Git (branche, commit, auteur)
- `getBuildInfo()` - Infos de build (OS, PHP, date)
- `getVersionString()` - String formatée pour affichage
- `getReleaseNotes()` - Notes de version par release

### 2. **Configuration UI**

L'onglet **"About"** dans `admin/advanced_config.php` affiche:

- ✅ Numéro de version
- ✅ Date de release
- ✅ Infos Git (branche, commit, auteur)
- ✅ Informations de build
- ✅ Liste des features
- ✅ Liens support (GitHub, Linear)

**Accès:** Administration → MarketPlace_BDC → Configuration → About

## Processus de Mise à Jour

### Mise à Jour Automatique (Git Hook)

**Fichier:** `.git/hooks/post-commit`

Exécuté **après chaque commit** pour:
1. Capturer le commit SHA
2. Identifier la branche
3. Récupérer la date et l'auteur
4. Créer un fichier `.version-build`

**Pas d'action requise** - Automatique !

### Mise à Jour Manuelle

**Script:** `update_version.sh`

#### Usage:

```bash
# Augmenter la version patch (1.2.0 → 1.2.1)
./update_version.sh patch

# Augmenter la version minor (1.2.0 → 1.3.0)
./update_version.sh minor

# Augmenter la version major (1.2.0 → 2.0.0)
./update_version.sh major
```

#### Actions du script:

1. ✅ Met à jour `VersionManager.class.php`
2. ✅ Met à jour `CHANGELOG.md`
3. ✅ Met à jour `README.md`
4. ✅ Crée un tag Git (`v1.2.0`)
5. ✅ Stage les fichiers pour commit
6. ✅ Affiche les instructions pour push

#### Exemple complet:

```bash
$ ./update_version.sh minor

=== MarketPlace Module Version Manager ===

Current Version: 1.2.0
New Version: 1.3.0
Release Date: 2026-05-01

✅ Updated VersionManager.class.php
✅ Updated CHANGELOG.md
✅ Updated README.md

=== Git Operations ===

✅ Git tag created: v1.3.0
✅ Files staged for commit

=== Ready for Commit ===

Branch: develop
Files staged: 3

Next steps:
  1. Review changes: git diff --cached
  2. Commit: git commit -m "Release v1.3.0"
  3. Push: git push origin --all && git push origin --tags
```

## Fichiers Impliqués

### Toujours mis à jour:

1. **`class/VersionManager.class.php`**
   - Constantes VERSION et VERSION_DATE
   - Infos de build

2. **`CHANGELOG.md`**
   - Nouvelle section avec version/date
   - Points clés (Added, Fixed, Changed)

3. **`README.md`**
   - Badge de version
   - Notes importantes

### Automatiquement suivis par Git:

4. **`.version-build`** (généré)
   - Metadata de build
   - Info Git automatique

5. **Tags Git**
   - Créés lors du versioning (v1.2.0, v1.3.0, etc.)

## Checklist avant Chaque Release

- [ ] Tous les changements sont validés et testés
- [ ] `CHANGELOG.md` est complété
- [ ] Code review effectuée
- [ ] Tests passent (si CI configurée)
- [ ] Version bump exécuté: `./update_version.sh [type]`
- [ ] Git changes vérifiés: `git diff --cached`
- [ ] Commit créé: `git commit -m "Release v[version]"`
- [ ] Push effectué: `git push origin --all --tags`

## Consultation des Versions

### Via interface Dolibarr:

1. Aller à: **Administration → MarketPlace_BDC → Configuration → About**
2. Voir version, Git info, build metadata

### Via CLI:

```bash
# Voir la version actuelle
grep "const VERSION = " class/VersionManager.class.php

# Voir tous les tags Git
git tag -l

# Voir le dernier commit
git log -1 --oneline

# Voir l'historique complet
git log --oneline
```

### Via fichier:

```bash
cat .version-build
```

## Exemple Workflow Complet

### Scenario: Corrections de bugs mineurs

```bash
# 1. Effectuer les modifications
# (éditer files, tester, valider)

# 2. Vérifier les changements
git status
git diff

# 3. Committer les changements
git add .
git commit -m "Fix: Corrections bugs marketplace sync"

# 4. Bumper la version (patch)
./update_version.sh patch

# 5. Vérifier les changements de version
git diff --cached

# 6. Valider et pusher
git commit -m "Release v1.2.1"
git push origin --all --tags

# 7. Vérifier sur Dolibarr
# Aller à: Admin → Configuration → About
# → Voir v1.2.1 avec infos Git
```

### Scenario: Nouvelles fonctionnalités

```bash
# 1. Créer une branche feature
git checkout -b feature/new-marketplace-api

# 2. Développer et committer
git add .
git commit -m "Add: Support for new marketplace API"

# 3. Merger dans develop
git checkout develop
git merge feature/new-marketplace-api

# 4. Merger dans main avec version bump
git checkout main
git merge develop
./update_version.sh minor

# 5. Pusher
git commit -m "Release v1.3.0"
git push origin main develop --tags
```

## Lecture et Interprétation des Infos

### Dans l'interface About:

```
Module Version: 1.2.1 [develop]
Git Commit: a1b2c3d (7 caractères)
Commit Date: 2026-05-01 14:30:45 +0200
Branch: develop
Author: fahd

Cela signifie:
✅ Version 1.2.1 est déployée
✅ Basée sur le commit a1b2c3d
✅ Modifiée le 2026-05-01 à 14:30:45
✅ Sur la branche develop
✅ Dernier changement par fahd
```

## Support

Pour toute question sur le versioning:

- 📖 Consulter ce guide
- 🔗 Voir le GitHub: https://github.com/anexys-ops/doliModMarketplace
- 📋 Linear board: https://linear.app/anexys/project/dolibarr-modulemarketplace-bdc-f79cfe2c5ebb

---

**Dernier update:** 2026-05-01  
**Version:** 1.2.0  
**Auteur:** Fahd / Anexys  
