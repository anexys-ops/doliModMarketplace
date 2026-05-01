# Contributing to ModuleMarketPlace

## Branches principales

### `main` (production)
- Code stable testé
- Chaque commit = une version releasable
- Protégé: PR review required

### `develop` (développement)
- Intégration des features
- Base pour nouvelles branches feature
- Branche de travail principale

### `feature/*` (features individuelles)
- `feature/phase4-automation` - Phase 4 (crons, webhooks)
- `feature/amazon-improvements` - Améliorations Amazon
- Format: `feature/DESCRIPTION-courte`

### `fix/*` (bugfixes)
- Format: `fix/description-du-bug`
- Basé sur `develop`

### `docs/*` (documentation)
- Format: `docs/description`
- Mise à jour docs uniquement

## Workflow

```
main (stable) ← PR review ← develop ← feature/description
                                        ↓
                                    feature branch
                                    (travail en cours)
```

## Créer une feature

```bash
# 1. Mettre à jour develop
git checkout develop
git pull origin develop

# 2. Créer branche feature
git checkout -b feature/ma-feature

# 3. Travailler...
# 4. Commit réguliers
git add .
git commit -m "[FEATURE] Description courte et claire"

# 5. Push
git push origin feature/ma-feature

# 6. Créer PR sur GitHub
gh pr create --base develop --title "Ma feature" \
  --body "Description complète de la feature"
```

## Commit messages

Format:
```
[TYPE] Description courte (< 50 char)

Description plus longue si besoin
- Point 1
- Point 2

Closes #123 (si bug fix lié à issue)
```

Types:
- `[FEATURE]` - Nouvelle fonctionnalité
- `[FIX]` - Correction de bug
- `[REFACTOR]` - Restructuration code
- `[DOCS]` - Documentation
- `[TEST]` - Tests
- `[PERF]` - Performance

## Exemple de feature complète

```bash
# 1. Créer branche
git checkout develop
git pull origin develop
git checkout -b feature/import-orders

# 2. Développer
# Faire vos changements...

# 3. Commits réguliers
git add src/class/MarketplaceOrder.class.php
git commit -m "[FEATURE] Add order import functionality"

git add src/marketplace/orders.php
git commit -m "[FEATURE] Add order management interface"

# 4. Tester localement
# npm test / phpunit / etc.

# 5. Push
git push origin feature/import-orders

# 6. PR sur develop
gh pr create --base develop \
  --title "[FEATURE] Order import system" \
  --body "Implements automated order import from marketplaces"

# 7. Après review + merge sur develop
# Phase de test/QA...
# Puis PR vers main quand stable
```

## Phase suivante: Phase 4 Automation

### Tâches
- [ ] Cron job pour sync automatique
- [ ] Webhooks pour réception commandes
- [ ] Email notifications
- [ ] Auto-retry on error

### Branche
```bash
git checkout -b feature/phase4-automation
# Ou: git checkout feature/phase4-automation si existe
```

## Règles

1. **Ne jamais push directement sur `main`** - Toujours PR
2. **Rebase avant PR** - `git rebase develop` pour historique propre
3. **Tests avant PR** - Lancer tests localement
4. **Commits atomiques** - Un commit = une logique
5. **Messages clairs** - Type + description

## Hotfixes (urgents)

```bash
git checkout -b fix/bug-critique
# Fix le bug...
git commit -m "[FIX] Corriger bug critique"
git push origin fix/bug-critique
# PR vers main PUIS develop
```

## Release (stable → main)

```bash
# Sur develop (testé et validé)
git checkout main
git pull origin main
git merge develop --no-ff -m "Release v1.0.1"
git tag -a v1.0.1 -m "Version 1.0.1"
git push origin main
git push origin v1.0.1
```

---

**Questions?** Voir README.md ou fichiers `.md` dans le repo.
