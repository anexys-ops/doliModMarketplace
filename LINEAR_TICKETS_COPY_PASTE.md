# Linear Tickets - Copy/Paste Templates

## 🎯 COPY-PASTE DANS LINEAR

---

## ✅ COMPLETED TICKETS (Marquer comme "Done")

### Ticket 1: Phase 1 - Infrastructure & Core Module

**Title:**
```
Phase 1: Infrastructure & Core Module
```

**Description:**
```
Core module setup and infrastructure implementation.

## Deliverables
- Core module declaration (modMarketPlace_BDC.class.php)
- 6 database tables (marketplace, offer, order, items, synclog, stock_alert)
- Core classes (Marketplace, MarketplaceOffer, MarketplaceOrder)
- Abstract API interface + 4 implementations
  - Mirakl (ADEO)
  - Octopia (Cdiscount)
  - Amazon SP-API
  - WooCommerce REST API v3
- Credential encryption
- Module rights & permissions

## Status
✅ Complete - 11 commits to GitHub

## GitHub
https://github.com/anexys-ops/doliModMarketplace/commits/main
```

**Priority:** High  
**Status:** Done  
**Label:** Infrastructure, Phase 1

---

### Ticket 2: Phase 2 - Product Tab Integration

**Title:**
```
Phase 2: Product Tab Integration
```

**Description:**
```
Native Dolibarr product tab with marketplace offer management.

## Features
- Marketplace tab on product cards ("Marketplaces")
- Offer listing table with columns:
  - Marketplace | SKU | Price | Stock | Modifier | Description | Status | Last Sync
- Offer editing in modal
- 1-click sync to marketplace
- Add/Delete offer functionality
- Real-time status indicators (ok/error/pending)

## Status
✅ Complete - Integrated as native Dolibarr template tab

## GitHub
https://github.com/anexys-ops/doliModMarketplace/commits/main
```

**Priority:** High  
**Status:** Done  
**Label:** Phase 2, UI

---

### Ticket 3: Phase 3 - Dashboard & Orders Management

**Title:**
```
Phase 3: Dashboard & Orders Management
```

**Description:**
```
Complete dashboard and order management system.

## Features
- Global marketplace dashboard
  - Statistics cards (Total offers, Low stock alerts, Sync errors, Orders)
  - Marketplace filter
  - Active offers table
- Order management system
  - Status flow: received → validated → integrated
  - Order import from marketplaces
  - Order detail view with line items
  - Integration status tracking
  - Filterable orders list
  - Order statistics

## Status
✅ Complete

## GitHub
https://github.com/anexys-ops/doliModMarketplace/commits/main
```

**Priority:** High  
**Status:** Done  
**Label:** Phase 3, Orders

---

### Ticket 4: Phase 4 - Advanced Configuration

**Title:**
```
Phase 4: Advanced Configuration
```

**Description:**
```
Advanced features for configuration and monitoring.

## Features
1. Endpoint Selection
   - Checkboxes for each marketplace
   - Select operations: Sync Offers | Import Orders | Send Promos | Update Descriptions

2. Cron Parameterization
   - 4 cron types: sync_offers, sync_stock, fetch_orders, fetch_returns
   - Frequency options: hourly, daily, weekly, monthly
   - Specific hour selection (0-23)
   - Day of week/month selection
   - Enable/Disable per task
   - Execution tracking

3. Connection Tests (DEV/PROD)
   - Test DEV (non-destructive)
   - Test PROD (real connection)
   - Results logged automatically

4. Logs & Monitoring
   - New page: Admin → Tools → Logs
   - Filters: Marketplace, Type, Status, Date
   - Stats cards (Total, OK, Errors, Warnings)
   - Export CSV
   - Purge old logs (7/30 days)
   - Configurable retention

## Status
✅ Complete

## GitHub
https://github.com/anexys-ops/doliModMarketplace/commits/main
```

**Priority:** High  
**Status:** Done  
**Label:** Phase 4, Configuration

---

### Ticket 5: Phase 5 - Field Mapping & Extrafields

**Title:**
```
Phase 5: Field Mapping & Extrafields Support
```

**Description:**
```
Complete field mapping system with Extrafields support.

## Features
1. Product Mapping
   - 12 standard fields (ref, label, price, weight, etc)
   - Auto-detection of Extrafields
   - Required/Optional marking

2. Category Mapping
   - 3 standard fields
   - Extrafields support
   - Mapping interface

3. Order Mapping
   - 15 standard fields (ref, client, address, email, etc)
   - Extrafields support
   - Complete mapping

4. UI
   - 3-tab interface (Products/Categories/Orders)
   - Table display of fields
   - Input for mapping
   - Type badges
   - Required checkboxes

## Technical
- Automatic Extrafields detection from llx_extrafields
- Supports all extrafield types (string, int, select, date, etc)
- Mapping history & audit trail
- 3 new tables created

## Status
✅ Complete

## GitHub
https://github.com/anexys-ops/doliModMarketplace/commits/main
```

**Priority:** High  
**Status:** Done  
**Label:** Phase 5, Mapping

---

## 🔄 IN PROGRESS

### Ticket 6: Server Deployment - Git Clone & Setup

**Title:**
```
Server Deployment - Git Clone & Setup
```

**Description:**
```
Deploy module to production server from GitHub.

## Actions
1. SSH to server: dlbp150r58.edicloud.app:150
2. Clone repository:
   cd /var/www/dolibarr/htdocs/custom
   git clone https://github.com/anexys-ops/doliModMarketplace.git marketplace_bdc

3. Set permissions:
   cd marketplace_bdc
   chmod -R 755 .
   chown -R www-data:www-data .

4. Verify:
   - Check file structure
   - Verify PHP syntax
   - Database ready (tables will be created on module activation)

## Subtasks
- [ ] Git clone completed
- [ ] Permissions set correctly
- [ ] File structure verified
- [ ] Ready for module activation

## Status
🔄 In Progress

## Timeline
Started: 2026-05-01 15:20 UTC+2
Expected: 2026-05-01 15:30 UTC+2 (2-5 min clone time)

## GitHub
https://github.com/anexys-ops/doliModMarketplace
```

**Priority:** Critical  
**Status:** In Progress  
**Assignee:** DevOps  
**Estimate:** 0.5 hours  
**Label:** Deployment, Critical

---

## ⏳ TO DO

### Ticket 7: Activate Module in Dolibarr Admin

**Title:**
```
Activate Module in Dolibarr Admin
```

**Description:**
```
Activate the MarketPlace_BDC module in Dolibarr.

## Steps
1. Login to Dolibarr as Admin
2. Go: Admin → Modules & Applications → Modules
3. Search: "MarketPlace_BDC"
4. Click: ✅ Activate
5. Wait for initialization (~2-3 seconds)

## Verification Checklist
- [ ] Module shows "ENABLED" (green)
- [ ] Menu "MarketPlace_BDC" appears (left sidebar)
- [ ] Sub-menus: Dashboard, Orders, Configuration visible
- [ ] On product card: "Marketplaces" tab appears
- [ ] Database tables created (check logs)
- [ ] No PHP errors in logs

## Expected Behavior
After activation, you should see:
- Left menu with marketplace options
- Product tab on any product card
- Configuration page with marketplace setup
- Dashboard with empty stats (normal)

## Troubleshooting
- Hard refresh: Ctrl+F5 or Cmd+Shift+R
- Check logs: tail -50 /var/www/dolibarr/htdocs/dolibarr.log
- Check module file: php -l core/modules/modMarketPlace_BDC.class.php

## Status
⏳ To Do

## Next Steps
Once activated → Run connection tests → Configure endpoints → Enable cron

## GitHub
https://github.com/anexys-ops/doliModMarketplace
```

**Priority:** Critical  
**Status:** To Do  
**Assignee:** Admin User  
**Estimate:** 0.25 hours  
**Label:** Deployment, Critical

---

### Ticket 8: Phase 6 - Testing & Validation

**Title:**
```
Phase 6: Testing & Validation
```

**Description:**
```
Comprehensive testing of all module features.

## Test Cases

### Connection Tests
- [ ] Test DEV connection (ADEO/Mirakl)
- [ ] Test PROD connection (ADEO/Mirakl)
- [ ] Test DEV connection (Cdiscount/Octopia)
- [ ] Test PROD connection (Cdiscount/Octopia)
- [ ] Logs recorded for each test

### Configuration
- [ ] Endpoint selection works
- [ ] Cron job creation/edit works
- [ ] Cron frequency options work
- [ ] Enable/Disable toggles work

### Product Management
- [ ] Add product offer works
- [ ] Edit product offer works
- [ ] Delete product offer works
- [ ] Marketplace tab displays correctly
- [ ] 1-click sync works

### Orders
- [ ] Order import works
- [ ] Order status changes work
- [ ] Order detail view works
- [ ] Order list filters work

### Logging
- [ ] Logs are recorded
- [ ] Log filters work
- [ ] Export CSV works
- [ ] Purge logs works

### Field Mapping
- [ ] Products mapping interface loads
- [ ] Categories mapping interface loads
- [ ] Orders mapping interface loads
- [ ] Extrafields are detected
- [ ] Mapping save works

### Performance
- [ ] Dashboard loads in < 2 seconds
- [ ] Log page loads in < 3 seconds
- [ ] No console errors
- [ ] No memory leaks

## Status
🔲 To Do

## Timeline
Est. 3-4 hours

## Assigned to
QA Team

## GitHub
https://github.com/anexys-ops/doliModMarketplace
```

**Priority:** High  
**Status:** To Do  
**Team:** QA  
**Estimate:** 4 hours  
**Label:** Phase 6, QA, Testing

---

### Ticket 9: Phase 7 - Documentation

**Title:**
```
Phase 7: Documentation Finalization
```

**Description:**
```
Complete documentation for the module.

## Documentation to Create

### User Guides
- [ ] README.md updates
- [ ] Quick-start guide (15 min setup)
- [ ] Administrator manual (config, crons, logs)
- [ ] End-user manual (product management, sync)

### Technical Docs
- [ ] Troubleshooting guide
- [ ] API documentation
- [ ] Database schema documentation
- [ ] Field mapping guide

### Optional
- [ ] Video tutorial (setup)
- [ ] Video tutorial (configuration)
- [ ] FAQ
- [ ] Known issues & workarounds

## Documentation Files in Repo
- README.md
- ADVANCED_CONFIG.md
- MAPPING_CONFIGURATION.md
- DEPLOY.md
- + 7 other guides already created

## Status
🔲 To Do

## Timeline
Est. 2-3 hours

## GitHub
https://github.com/anexys-ops/doliModMarketplace
```

**Priority:** Medium  
**Status:** To Do  
**Team:** Documentation  
**Estimate:** 3 hours  
**Label:** Phase 7, Documentation

---

### Ticket 10: Phase 8 - Support & Maintenance

**Title:**
```
Phase 8: Support & Maintenance Setup
```

**Description:**
```
Setup for production support and maintenance.

## To Setup
- [ ] Production deployment procedures
- [ ] Backup & restore guides
- [ ] Update procedures
- [ ] Security hardening
- [ ] Performance optimization
- [ ] Monitoring setup
- [ ] Support process

## Ongoing Tasks
- Monitor logs for errors
- Respond to user issues
- Release updates
- Security patches

## Status
🔲 To Do (Ongoing)

## GitHub
https://github.com/anexys-ops/doliModMarketplace
```

**Priority:** Medium  
**Status:** To Do  
**Team:** Support  
**Estimate:** Ongoing  
**Label:** Phase 8, Support, Maintenance

---

## 📋 LABELS À CRÉER DANS LINEAR

```
Infrastructure
UI
Backend
Frontend
QA
Testing
Configuration
Deployment
Maintenance
Documentation
Critical
High
Medium
Low
Phase 1
Phase 2
Phase 3
Phase 4
Phase 5
Phase 6
Phase 7
Phase 8
Orders
Mapping
Cron
Logging
```

---

**Version:** 1.0  
**Last Updated:** 2026-05-01 15:30 UTC+2  
**GitHub:** https://github.com/anexys-ops/doliModMarketplace
