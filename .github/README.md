# GitHub Actions - MarketPlace Module

Automated CI/CD workflows pour le module MarketPlace Dolibarr.

## 📋 Workflows Disponibles

### 1. **PHP Syntax Check** (`php-lint.yml`)
- **Trigger:** Push/PR sur `main` et `develop` (fichiers `.php`)
- **Actions:**
  - ✅ Vérifie la syntaxe PHP de tous les fichiers
  - ✅ Optionnel: Exécute PHP-CS-Fixer
  - ✅ Commente les PR en cas d'erreur

**Status Badge:**
```markdown
![PHP Lint](https://github.com/anexys-ops/doliModMarketplace/workflows/PHP%20Syntax%20Check/badge.svg)
```

### 2. **Auto-Versioning & Release** (`auto-version.yml`)
- **Trigger:** Push sur `main` (code changes)
- **Actions:**
  - 🔍 Détecte le type de bump (major/minor/patch) depuis le message de commit
  - 📝 Met à jour automatiquement:
    - `VersionManager.class.php` (VERSION et VERSION_DATE)
    - `README.md` (version badge)
    - `CHANGELOG.md` (nouvelle entrée)
  - 🏷️ Crée un Git tag (v1.2.0)
  - 📦 Crée une GitHub Release avec notes

**Commit Messages Reconnus:**
```
- "Release v..." ou "bump: major" → Major version bump
- "Feature" ou "Feat:" ou "bump: minor" → Minor version bump
- "Fix:" ou "Hotfix" ou "bump: patch" → Patch version bump
- Autres → Pas de bump
```

**Exemple:**
```bash
git commit -m "Feat: Add webhook support"  # Bumpe v1.2.0 → v1.3.0
git commit -m "Fix: Product tab blank page"  # Bumpe v1.2.0 → v1.2.1
git commit -m "Release v2.0.0"  # Bumpe v1.2.0 → v2.0.0
```

### 3. **Module Tests & Validation** (`validate.yml`)
- **Trigger:** Push/PR sur `main` et `develop`
- **Actions:**
  - ✅ Vérifie la structure du module
  - ✅ Valide tous les fichiers PHP
  - ✅ Valide les schemas SQL
  - ✅ Vérifie les fichiers JSON
  - ✅ Teste la classe VersionManager
  - ✅ Valide la documentation

**Checks:**
```
✅ Fichiers requis présents
✅ PHP syntaxe valide
✅ SQL schemas valides
✅ JSON configs valides
✅ VersionManager opérationnel
✅ Documentation complète
```

### 4. **Deploy to Server** (`deploy.yml`)
- **Trigger:** GitHub Release créée
- **Actions:**
  - 📦 Prépare un package tar.gz
  - 📝 Génère un guide de déploiement
  - 📋 Crée une checklist de vérification

**Manuel Deployment:**
```bash
# 1. Télécharger depuis GitHub Releases
cd /tmp
wget https://github.com/anexys-ops/doliModMarketplace/releases/download/v1.2.0/marketplace_bdc_v1.2.0.tar.gz

# 2. Déployer
cd /var/www/dolibarr/htdocs/custom
tar -xzf /tmp/marketplace_bdc_v1.2.0.tar.gz
chown -R www-data:www-data marketplace_bdc/

# 3. Vérifier
https://dlbp150r58.edicloud.app/custom/marketplace_bdc/admin/setup.php
```

## 🚀 Utilisation

### Démarrer Automatiquement une Release

```bash
# 1. Faire des modifications
# 2. Committer avec message reconnu:
git commit -m "Feat: Add new marketplace support"

# 3. Pusher
git push origin main

# 🤖 GitHub Actions se déclenche automatiquement:
# - Detecte "Feat:" → bump minor
# - Met à jour les fichiers
# - Crée un tag
# - Crée une release
# - Génère le package
```

### Déclencher Manuellement un Deploy

Sur GitHub:
1. Aller à **Actions** → **Deploy to Server**
2. Cliquer **Run workflow**
3. Sélectionner la branche et l'environnement
4. Cliquer **Run**

### Voir l'Historique des Builds

GitHub → **Actions** → Sélectionner le workflow

## 📊 Statut des Workflows

| Workflow | Statut | Dernière Run |
|----------|--------|-------------|
| PHP Lint | ✅ | [Voir](https://github.com/anexys-ops/doliModMarketplace/actions/workflows/php-lint.yml) |
| Auto-Version | ✅ | [Voir](https://github.com/anexys-ops/doliModMarketplace/actions/workflows/auto-version.yml) |
| Validate | ✅ | [Voir](https://github.com/anexys-ops/doliModMarketplace/actions/workflows/validate.yml) |
| Deploy | ✅ | [Voir](https://github.com/anexys-ops/doliModMarketplace/actions/workflows/deploy.yml) |

## 🔧 Configuration

### Secrets Requis

GitHub → **Settings** → **Secrets and variables** → **Actions**

```yaml
# Optionnel (déjà configuré par défaut):
GITHUB_TOKEN  # Auto-fourni par GitHub
```

### Protection Branches

Pour la branche `main`:
1. **Settings** → **Branches** → **Branch protection rules**
2. Ajouter une règle pour `main`:
   - ✅ Require status checks to pass before merging
   - Sélectionner: `PHP Syntax Check`, `Module Tests & Validation`

## 📝 Exemples

### Exemple 1: Commit avec Auto-Versioning

```bash
# Votre changement
git add marketplace/product_tab.php
git commit -m "Fix: Add Product class import

- Fix blank page issue on product tab
- Add proper error handling
- Add debug comments"

git push origin main

# 🤖 GitHub Actions exécute:
# ✅ PHP Lint check
# ✅ Détecte "Fix:" → patch bump
# ✅ Met à jour v1.2.0 → v1.2.1
# ✅ Crée release v1.2.1
# ✅ Génère package marketplace_bdc_v1.2.1.tar.gz
```

### Exemple 2: Feature Release

```bash
# Nouvelle feature
git add class/MarketplaceWebhook.class.php
git commit -m "Feat: Implement webhook support for order updates

- Add webhook class
- Add webhook validation
- Add webhook tests"

git push origin main

# 🤖 GitHub Actions exécute:
# ✅ PHP Lint check
# ✅ Détecte "Feat:" → minor bump
# ✅ Met à jour v1.2.1 → v1.3.0
# ✅ Crée release v1.3.0
# ✅ Génère package marketplace_bdc_v1.3.0.tar.gz
```

### Exemple 3: Hotfix Critical

```bash
# Fix urgent
git add marketplace/product_tab.php
git commit -m "Hotfix: Critical security issue in API call

This fixes a potential SQL injection vulnerability."

git push origin main

# 🤖 GitHub Actions exécute:
# ✅ PHP Lint check
# ✅ Détecte "Hotfix:" → patch bump
# ✅ Met à jour v1.3.0 → v1.3.1
# ✅ Crée release v1.3.1 (marqée comme security patch)
```

## 🐛 Troubleshooting

### Workflow Failed

1. Aller à **Actions** → Cliquer sur le run échoué
2. Voir les logs détaillés
3. Corrections courantes:
   - PHP syntax error: Vérifier `php -l` localement
   - Missing dependencies: Vérifier les imports
   - JSON invalid: Valider avec `jq`

### Pas de Bump de Version

Vérifier que le commit message contient une des clés:
- "Release", "Feat:", "Fix:", "Hotfix", "bump:"

### Release Non Créée

Vérifier:
1. Le push est bien sur `main`
2. Les tests passent (`validate.yml`)
3. `GITHUB_TOKEN` est actif

## 📚 Ressources

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [GitHub Releases](https://github.com/anexys-ops/doliModMarketplace/releases)
- [Actions Marketplace](https://github.com/marketplace?type=actions)

---

**Dernière mise à jour:** 2026-05-01  
**Version:** 1.2.0  
**Status:** ✅ Production Ready
