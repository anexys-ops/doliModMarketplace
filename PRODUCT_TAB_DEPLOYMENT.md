# Déploiement Corrigé - Product Tab

## 📋 Fichier à déployer

**Emplacement:** `/var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php`

## ✅ Contenu du fichier

Le fichier corrigé affiche:

1. **En-tête produit:** SKU, Stock actuel, Prix Dolibarr
2. **Table des offres:** 
   - Marketplace | SKU | Prix | Stock | Modification | Description | Statut | Dernier Sync | Actions
3. **Interface d'édition:**
   - Modal pour modifier SKU, prix, modifiant quantité
   - Checkbox pour sync description
4. **Actions:**
   - ✏️ Éditer
   - 🔄 Sync (1-clic)
   - 🗑️ Supprimer
5. **Ajouter offre:**
   - Dropdown marketplace disponibles
   - Form inline pour ajouter rapidement

## 🚀 Déploiement manuel

Si le transfer SCP ne fonctionne pas, copier-coller directement le contenu du fichier `product_tab.php` dans l'éditeur de fichiers du serveur.

### Commande directe
```bash
cat > /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php << 'EOFPHP'
[CONTENU DU FICHIER]
EOFPHP
```

## 🔍 Tests

1. **Vérifier le déploiement:**
   ```bash
   curl -I https://dlbp150r58.edicloud.app/custom/marketplace_bdc/marketplace/product_tab.php?id=155
   ```

2. **Vérifier les logs:**
   ```bash
   tail -f /var/www/dolibarr/htdocs/dolibarr.log
   tail -f /var/log/apache2/error.log
   ```

3. **Tester dans Dolibarr:**
   - Aller sur une fiche produit
   - Chercher l'onglet "Marketplaces"
   - Cliquer sur l'onglet
   - Vous devriez voir:
     - Informations produit (SKU, Stock, Prix)
     - Table vide ou avec les offres existantes
     - Bouton/form pour ajouter une offre

## 🐛 Dépannage

Si la page est blanche:

1. **Vérifier PHP:**
   ```bash
   php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php
   ```

2. **Vérifier les permissions:**
   ```bash
   chmod 644 /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php
   ```

3. **Vérifier les logs Apache:**
   ```bash
   tail -30 /var/log/apache2/error.log
   ```

## 📊 Fonctionnalités

✅ Liste des marketplaces configurés
✅ Affichage des offres actuelles
✅ Édition des offres (SKU, prix, quantité, description sync)
✅ Modification de quantité (+/-)
✅ Sync 1-clic
✅ Suppression d'offres
✅ Ajout rapide d'offres
✅ Interface responsive
✅ Gestion permissions Dolibarr

## 📌 Notes

- Stock par défaut = stock Dolibarr actuel
- Possibilité de modifier avec +/- (ex: -10, +5)
- Description peut être synchronisée ou non
- Statut sync: pending, ok, error
- Table affiche dernier sync date/heure

---

**Status:** Prêt au déploiement
**Version:** 1.0.0
**Last Updated:** 2026-05-01
