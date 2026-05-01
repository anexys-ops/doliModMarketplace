# 🚀 DEPLOYMENT GUIDE - MarketPlace Module

## Quick Deploy (3 Steps)

### Option 1: Automated Script (Linux/Mac)

```bash
# 1. SSH to server
ssh root@dlbp150r58.edicloud.app -p 150

# 2. Download and run deploy script
cd /var/www/dolibarr/htdocs/custom
wget https://raw.githubusercontent.com/anexys-ops/doliModMarketplace/main/deploy.sh
bash deploy.sh

# Done! The module is deployed
```

### Option 2: Manual Commands

```bash
# 1. SSH to server
ssh root@dlbp150r58.edicloud.app -p 150

# 2. Go to custom directory
cd /var/www/dolibarr/htdocs/custom

# 3. Remove old directory (if exists)
rm -rf marketplace_bdc

# 4. Clone from GitHub
git clone https://github.com/anexys-ops/doliModMarketplace.git marketplace_bdc

# 5. Set permissions
cd marketplace_bdc
chmod -R 755 .
chown -R www-data:www-data .

# 6. Verify
ls -la
```

### Option 3: Git Pull (If already deployed)

```bash
ssh root@dlbp150r58.edicloud.app -p 150
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
git pull origin main
git checkout main
```

---

## After Deployment

### 1. Activate Module in Dolibarr

1. Login to Dolibarr as **Admin**
2. Go: **Admin → Modules & Applications → Modules**
3. Search: `MarketPlace_BDC`
4. Click: **✅ Activate**

Wait ~5 seconds for initialization...

### 2. Verify Activation

Check that:
- ✅ Module shows as **"ENABLED"** (green)
- ✅ Menu appears on left sidebar: **"MarketPlace_BDC"**
- ✅ Sub-menus: Dashboard, Orders, Configuration

### 3. Test Product Tab

1. Go: **Products → Select any product**
2. Look for tabs at top: **Informations | Documents | Marketplaces | ...**
3. Click: **Marketplaces** tab
4. You should see:
   - Product info (SKU, Stock, Price)
   - Marketplace offers table (empty or with data)
   - Add offer form

### 4. Test Configuration

1. Go: **Menu → MarketPlace_BDC → Configuration**
2. You should see:
   - Marketplace tiles/cards
   - Status (Active/Inactive)
   - Edit buttons

---

## Troubleshooting

### Module doesn't appear in Modules list?

```bash
# Check file exists
php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php

# Check permissions
ls -la /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/

# Check logs
tail -50 /var/www/dolibarr/htdocs/dolibarr.log
```

### Tab doesn't appear on product card?

```bash
# Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)

# Check in Dolibarr database
mysql -u dolibarr -p dolibarr
SELECT name, value FROM llx_const WHERE name LIKE '%marketplace%' LIMIT 10;
```

### Getting blank page error?

```bash
# Check PHP syntax
php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php

# Check Apache logs
tail -50 /var/log/apache2/error.log

# Check Dolibarr logs
tail -50 /var/www/dolibarr/htdocs/dolibarr.log
```

### Database tables not created?

```bash
# Check tables
mysql -u dolibarr -p dolibarr
SHOW TABLES LIKE 'llx_modmkp%';

# If missing, run SQL manually
mysql -u dolibarr -p dolibarr < /var/www/dolibarr/htdocs/custom/marketplace_bdc/sql/llx_modmkp_tables.sql
mysql -u dolibarr -p dolibarr < /var/www/dolibarr/htdocs/custom/marketplace_bdc/sql/llx_modmkp_orders.sql
```

---

## Verification Checklist

After deployment, verify:

- [ ] Files copied to `/var/www/dolibarr/htdocs/custom/marketplace_bdc/`
- [ ] Permissions set: `755` for dirs, `644` for files, owner `www-data`
- [ ] Module appears in **Admin → Modules**
- [ ] Module is **Activated** (green checkmark)
- [ ] **MarketPlace_BDC** menu appears on left sidebar
- [ ] **Marketplaces** tab appears on product cards
- [ ] **Configuration** page loads with tiles
- [ ] Database tables created: `llx_modmkp_*`
- [ ] No PHP errors in `/var/www/dolibarr/htdocs/dolibarr.log`

---

## Quick Command Reference

```bash
# SSH
ssh root@dlbp150r58.edicloud.app -p 150

# Clone
cd /var/www/dolibarr/htdocs/custom
git clone https://github.com/anexys-ops/doliModMarketplace.git marketplace_bdc

# Permissions
cd marketplace_bdc
chmod -R 755 .
chown -R www-data:www-data .

# Logs
tail -f /var/www/dolibarr/htdocs/dolibarr.log
tail -f /var/log/apache2/error.log

# Database
mysql -u dolibarr -p dolibarr
SHOW TABLES LIKE 'llx_modmkp%';
SELECT * FROM llx_modmkp_marketplace LIMIT 5;

# PHP Check
php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/core/modules/modMarketPlace_BDC.class.php
php -l /var/www/dolibarr/htdocs/custom/marketplace_bdc/marketplace/product_tab.php

# Pull updates
cd /var/www/dolibarr/htdocs/custom/marketplace_bdc
git pull origin main
```

---

## Support & Documentation

For more information:
- **README.md** - Overview and features
- **TEMPLATE_INTEGRATION.md** - Technical architecture
- **CONTRIBUTING.md** - Development guide
- **GitHub** - https://github.com/anexys-ops/doliModMarketplace

---

## Expected Result

After successful deployment and activation:

1. ✅ Module active in Dolibarr
2. ✅ New menu: **MarketPlace_BDC**
3. ✅ New tab on products: **Marketplaces**
4. ✅ Configuration page with marketplace tiles
5. ✅ Can add/edit/sync marketplace offers
6. ✅ Dashboard and Orders pages available

---

**Status:** Ready to Deploy
**Version:** 1.0.0
**Last Updated:** 2026-05-01
