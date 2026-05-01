# 📋 Guide de Test des Flux API - Marketplaces Luxgreen

**⚠️ IMPORTANT**: Ce fichier contient les credentials de test. À supprimer après les tests.

## 📁 Fichier des Credentials
- **Fichier**: `.env.test.credentials`
- **Contient**: Toutes les clés API, secrets et endpoints
- **À SUPPRIMER** après validation des flux

---

## 🔄 Flux de Test - Ordre Recommandé

### 1️⃣ CDISCOUNT - Authentification & Octopia

#### Étape 1: Générer un token d'accès
```bash
curl -X POST https://api.cdiscount.com/api/1.0/auth/GenerateToken \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=LuxGreenApiCdiscount&client_secret=YlXszv0hpB86bwZSXkyHYvL7RX3s0fIa"
```
**Résultat**: Token d'accès pour les appels suivants

#### Étape 2: Récupérer les offres existantes
```bash
curl -X GET https://api.cdiscount.com/api/1.0/seller/v2/offers \
  -H "Authorization: Bearer {TOKEN_FROM_STEP_1}"
```
**But**: Récupérer les `sellerExternalReference` pour le matching

#### Étape 3: Créer un package Octopia (Type "Update")
```bash
curl -X POST https://api.cdiscount.com/api/1.0/seller/v2/offer-packages \
  -H "Authorization: Bearer {TOKEN_FROM_STEP_1}" \
  -H "Content-Type: application/json" \
  -d '{
    "packageType": "Update"
  }'
```
**Résultat**: `packageId` dans la réponse (ou header `content-location`)
**Format**: `/seller/v2/offer-packages/{packageId}`

#### Étape 4: Ajouter les offer-requests au package
```bash
curl -X POST https://api.cdiscount.com/api/1.0/seller/v2/offer-packages/{packageId}/offer-requests \
  -H "Authorization: Bearer {TOKEN_FROM_STEP_1}" \
  -H "Content-Type: application/json" \
  -d '[
    {
      "sellerExternalReference": "SKU_123",
      "price": {
        "price": 99.99
      },
      "quantity": 50
    },
    {
      "sellerExternalReference": "SKU_456",
      "price": {
        "price": 149.99
      },
      "quantity": 30
    }
  ]'
```
**Notes**:
- Max 100 offres par upload
- `sellerExternalReference` doit correspondre aux offres existantes
- `price.price` = prix en EUR

#### Étape 5: Passer le package en état "Ready"
```bash
curl -X PATCH https://api.cdiscount.com/api/1.0/seller/v2/offer-packages/{packageId} \
  -H "Authorization: Bearer {TOKEN_FROM_STEP_1}" \
  -H "Content-Type: application/json" \
  -d '{
    "state": "Ready"
  }'
```
**But**: Déclenche l'intégration des offres

#### Étape 6: Vérifier le statut du package
```bash
curl -X GET https://api.cdiscount.com/api/1.0/seller/v2/offer-packages/{packageId} \
  -H "Authorization: Bearer {TOKEN_FROM_STEP_1}"
```
**Statuts possibles**: 
- `WaitingForCompletion` (valide 6h)
- `Completed`
- `Failed`

---

### 2️⃣ MIRAKL ADEO (Leroy Merlin / Brico Dépôt)

#### Étape 1: Lister les produits disponibles
```bash
curl -X GET https://adeo-marketplace.mirakl.net/api/products \
  -H "Authorization: d93a0347-3645-41ff-98d0-8837017a1bfa"
```

#### Étape 2: Créer/Mettre à jour une offre
```bash
curl -X POST https://adeo-marketplace.mirakl.net/api/offers/import \
  -H "Authorization: d93a0347-3645-41ff-98d0-8837017a1bfa" \
  -H "Content-Type: application/json" \
  -d '{
    "offersCreateUpdate": [
      {
        "sku": "ADEO_SKU_001",
        "product-id": "12345",
        "price": 89.99,
        "quantity": 100,
        "state": "ACTIVE"
      }
    ]
  }'
```

#### Étape 3: Récupérer les offres importées
```bash
curl -X GET https://adeo-marketplace.mirakl.net/api/offers \
  -H "Authorization: d93a0347-3645-41ff-98d0-8837017a1bfa"
```

---

### 3️⃣ AMAZON SP-API (EU)

#### Étape 1: Authentification LWA (Login with Amazon)
```bash
curl -X POST https://api.amazon.com/auth/o2/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&client_id=amzn1.application-oa2-client.9d11c3172c03474090f53b3f127d8759&client_secret={SECRET_MANQUANT}&scope=sellingpartnerapi::orders:read"
```
**Note**: Le `client_secret` pour Amazon doit être configuré dans la console SP-API

#### Étape 2: Lister les commandes
```bash
curl -X GET https://sellingpartnerapi-eu.amazon.com/orders/v0/orders \
  -H "x-amz-access-token: {AMAZON_LWA_TOKEN}" \
  -H "x-amzn-requestid: {REQUEST_ID}"
```

#### Étape 3: Vérifier l'inventaire
```bash
curl -X GET https://sellingpartnerapi-eu.amazon.com/fba/inventory/v1/summaries \
  -H "x-amz-access-token: {AMAZON_LWA_TOKEN}"
```

---

## ✅ Matching Dolibarr → Cdiscount

Pour le matching automatique des produits:

```
Dolibarr Product
├─ barcode (EAN)
└─ ref (reference interne)
          ↓
Cdiscount Offer
├─ product.gtin (EAN)
└─ sellerExternalReference (ref à synchroniser)
```

**Mapping recommandé**:
- Si `dolibarr.product.barcode` = `cdiscount.offer.product.gtin` → **Match trouvé**
- Utiliser `dolibarr.product.ref` comme `sellerExternalReference` pour Cdiscount

---

## 🛠️ Checklist de Test

- [ ] **Cdiscount Auth**: Token généré avec succès
- [ ] **Cdiscount Offers**: Offres existantes récupérées
- [ ] **Octopia Package Create**: Package créé avec packageId
- [ ] **Octopia Offers Add**: Offer-requests ajoutées (< 100)
- [ ] **Octopia Package Ready**: Statut passé à "Ready"
- [ ] **Octopia Status Check**: Statut vérifiable
- [ ] **Mirakl Auth**: API Key validée
- [ ] **Mirakl Products**: Produits listés
- [ ] **Mirakl Offers**: Offres créées/mises à jour
- [ ] **Amazon Auth**: LWA Token obtenu
- [ ] **Amazon Orders**: Commandes listées
- [ ] **Amazon Inventory**: Stock FBA accessible
- [ ] **Matching EAN**: Produits matché Dolibarr ↔ Cdiscount

---

## ⏱️ Délais Importants

| Marketplace | Délai | Notes |
|-------------|-------|-------|
| **Cdiscount Octopia** | 6h | Packages en `WaitingForCompletion` expirent après 6h |
| **Mirakl ADEO** | Immédiat | Synchronisation en temps réel |
| **Amazon** | 15-30min | Délai de propagation de l'inventaire |

---

## 🗑️ Nettoyage Après les Tests

```bash
# Supprimer le fichier des credentials de test
rm /Users/admin/Documents/ModuleMarketPlace_dolibarr/.env.test.credentials

# Supprimer ce guide de test
rm /Users/admin/Documents/ModuleMarketPlace_dolibarr/TEST_GUIDE.md
```

---

## 📌 Références

- **Cdiscount Octopia v2**: `/seller/v2/offer-packages`
- **Mirakl ADEO**: `https://adeo-marketplace.mirakl.net/api` ✅ (URL correcte)
- **Amazon SP-API EU**: `https://sellingpartnerapi-eu.amazon.com`

---

**Créé le**: 2026-05-01  
**À SUPPRIMER APRÈS**: Tests complétés
