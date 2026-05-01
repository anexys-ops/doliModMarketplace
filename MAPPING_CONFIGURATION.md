# 🔗 Field Mapping Configuration

## ✨ Nouvelle Fonctionnalité

Onglet complet pour mapper les champs entre Dolibarr et les Marketplaces, avec **support complet des Extrafields**.

### 3 Entités Supportées

#### 1. **📦 Produits (Products)**
Champs standards:
- `ref` → SKU/Reference
- `label` → Product Label
- `description` → Description
- `price` → Price HT
- `price_ttc` → Price TTC
- `tva_tx` → VAT Rate
- `weight` → Weight
- `quantity` → Stock/Quantity
- `status` → Status (0/1)
- `barcode` → Barcode
- `cost_price` → Cost Price
- `fk_product_category` → Category ID

**+ Tous les Extrafields du produit**

#### 2. **📁 Catégories (Categories)**
Champs standards:
- `label` → Category Label
- `description` → Description
- `rowid` → Category ID

**+ Tous les Extrafields de catégorie**

#### 3. **📋 Commandes (Orders)**
Champs standards:
- `ref` → Order Reference
- `ref_client` → Client Reference
- `socname` → Company Name
- `firstname` → First Name
- `lastname` → Last Name
- `address` → Address
- `zip` → ZIP Code
- `city` → City
- `country` → Country
- `email` → Email
- `phone` → Phone
- `total_ht` → Total HT
- `total_ttc` → Total TTC
- `total_tva` → Total VAT
- `date` → Order Date
- `date_livraison` → Delivery Date

**+ Tous les Extrafields de commande**

---

## 🎯 Fonctionnalités

### 1. **Mapping Standard Fields**
```
Dolibarr Field          Marketplace Field       Required
─────────────────────────────────────────────────────────
ref                 →   sku_marketplace         ☑
label               →   product_name            ☑
price               →   marketplace_price       ☐
description         →   product_description     ☐
```

### 2. **Mapping Extrafields**
- Détecte automatiquement tous les Extrafields actifs
- Affiche le type (text, int, select, etc)
- Badge "EXTRA" pour identifier les champs personnalisés
- Même processus que les champs standards

### 3. **Champs Requis**
- Checkbox "Required" pour chaque champ
- Validation lors de l'envoi vers le marketplace
- Empêche l'envoi si un champ requis est vide

### 4. **3 Onglets Distincts**
- **Onglet Produits:** 12 champs standard + extrafields produit
- **Onglet Catégories:** 3 champs standard + extrafields catégorie
- **Onglet Commandes:** 15 champs standard + extrafields commande

---

## 📁 Fichiers Créés

### 1. **class/MappingManager.class.php**
Classe pour gérer les mappings:

```php
// Sauvegarder un mapping
$manager->saveMapping($marketplace_id, 'product', $config);

// Récupérer un mapping
$mapping = $manager->getMapping($marketplace_id, 'product');

// Sauvegarder un champ
$manager->saveFieldMapping($mapping_id, 'ref', 'sku_marketplace');

// Obtenir champs standards
$fields = $manager->getStandardFields('product');

// Obtenir extrafields
$extras = $manager->getExtrafields('product');
```

### 2. **sql/llx_modmkp_mapping.sql**
Trois nouvelles tables:

**llx_modmkp_mapping**
```
rowid                  INT
fk_marketplace         INT
entity_type            VARCHAR(50)  - product|category|order
config                 JSON
date_created           DATETIME
date_updated           DATETIME
```

**llx_modmkp_mapping_fields**
```
rowid                  INT
fk_mapping             INT
dolibarr_field         VARCHAR(100)
marketplace_field      VARCHAR(100)
is_extrafield          TINYINT(1)   - 0=standard, 1=extra
is_required            TINYINT(1)   - 0=optional, 1=required
transformation         VARCHAR(255) - optional transformation
date_created           DATETIME
```

**llx_modmkp_mapping_history**
```
rowid                  INT
fk_mapping             INT
action                 VARCHAR(50)  - created|updated|deleted
old_value              JSON
new_value              JSON
user_id                INT
date_created           DATETIME
```

### 3. **admin/mapping_config.php**
Interface complète de mapping:

**3 Onglets:**
1. Produits (Standard + Extras)
2. Catégories (Standard + Extras)
3. Commandes (Standard + Extras)

**Chaque onglet affiche:**
- Tableau avec colonnes: Champ Dolibarr | Marketplace Field | Required | Type
- Pour chaque champ: 
  - Input text pour le champ marketplace
  - Checkbox "Required"
  - Badge type/source (standard/extra)
- Bouton "Save Mappings"

---

## 💻 Utilisation

### Étape 1: Accéder à la Config
```
Admin → Configuration → [Marketplace] → Mapping
```

### Étape 2: Sélectionner Entité
```
Onglet 1: 📦 Products
Onglet 2: 📁 Categories
Onglet 3: 📋 Orders
```

### Étape 3: Mapper les Champs
Pour chaque champ:
1. Regarder le label Dolibarr
2. Entrer le nom du champ marketplace
3. Cocher "Required" si obligatoire
4. Cliquer "Save Mappings"

### Exemple: Produit

```
Dolibarr Field          Marketplace Field
─────────────────────────────────────────
ref                 →   sku                     ✓ Required
label               →   title                   ✓ Required
description         →   description
price               →   price_ht
price_ttc           →   price_ttc
weight              →   weight_kg
status              →   active
barcode             →   barcode
[custom_field_1]    →   vendor_sku              ✓ Required (Extrafield)
[color]             →   variant_color           (Extrafield)
```

---

## 🔄 Intégration avec le Sync

Lors du sync d'un produit:

1. **Récupérer les mappings**
   ```php
   $mappings = $mapper->getFieldMappings($mapping_id);
   ```

2. **Pour chaque champ du mapping**
   ```php
   $dolibarr_value = $product->{$field->dolibarr_field};
   $marketplace_payload[$field->marketplace_field] = $dolibarr_value;
   ```

3. **Appliquer transformations (optionnel)**
   ```php
   if ($field->transformation) {
       $marketplace_payload[$field->marketplace_field] = 
           applyTransformation($dolibarr_value, $field->transformation);
   }
   ```

4. **Valider champs requis**
   ```php
   if ($field->is_required && empty($marketplace_payload[$field->marketplace_field])) {
       throw new Exception("Required field missing: " . $field->marketplace_field);
   }
   ```

5. **Envoyer au marketplace**
   ```php
   $api->syncProduct($marketplace_payload);
   ```

---

## 🎯 Extrafields - Détails

### Détection Automatique
```sql
SELECT attrname, label, type 
FROM llx_extrafields 
WHERE elementtype = 'product' AND active = 1
```

### Types d'Extrafields Supportés
- `string` - Texte
- `int` - Nombre entier
- `double` - Nombre décimal
- `date` - Date
- `datetime` - Date/Heure
- `checkbox` - Booléen
- `select` - Liste sélection
- `text` - Texte long
- `link` - Lien
- `multiselect` - Sélection multiple

### Exemple: Extrafield Couleur
```
Dolibarr:
  Field Name: color
  Label: Couleur
  Type: select
  Values: Rouge, Bleu, Vert

Mapping:
  Dolibarr Field: color (EXTRA)
  Marketplace Field: variant_color
  Required: ☑
```

Lors du sync:
```
$product->color = "Rouge"
→ marketplace_payload['variant_color'] = "Rouge"
```

---

## 📊 Tables de Mapping

### Avant Deploy
```
Aucune table
```

### Après Deploy
```
llx_modmkp_mapping (1 row par marketplace/entity)
llx_modmkp_mapping_fields (N rows, 1 par field mappé)
llx_modmkp_mapping_history (audit trail)
```

### Exemple de Contenu

**llx_modmkp_mapping:**
```
rowid  marketplace  entity_type  config
1      1           product      {}
2      1           category     {}
3      1           order        {}
```

**llx_modmkp_mapping_fields:**
```
rowid  mapping  dolibarr_field  marketplace_field  required
1      1        ref            sku                1
2      1        label          title              1
3      1        price          price_ht           0
4      1        weight         weight_kg          0
5      1        color          variant_color      1
```

---

## ✅ Checklist

- [ ] Tables SQL créées
- [ ] Classe MappingManager implémentée
- [ ] Interface mapping_config.php accessible
- [ ] Mappage Produits complété
- [ ] Mappage Catégories complété
- [ ] Mappage Commandes complété
- [ ] Tests DEV avec données test
- [ ] Tests PROD avec données réelles
- [ ] Vérification des Extrafields
- [ ] Logs des syncs vérifés

---

## 🚀 Déploiement

Fichiers à déployer:
- ✅ `class/MappingManager.class.php`
- ✅ `sql/llx_modmkp_mapping.sql`
- ✅ `admin/mapping_config.php`

Menu à ajouter:
- ✅ Configuration → Mapping (dans setup.php)

---

**Status:** ✅ PRÊT AU DÉPLOIEMENT  
**Version:** 1.2.0 (Mapping Features)  
**Date:** 2026-05-01
