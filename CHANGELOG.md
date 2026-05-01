# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-05-01

### ✅ Phase 1 - Infrastructure
- **Added** Module Dolibarr core declaration with 4 permission levels
- **Added** 6 database tables (marketplace, offer, order, item, synclog, stock_alert)
- **Added** Base classes: Marketplace, MarketplaceOffer, MarketplaceOrder
- **Added** Abstract API class with 4 marketplace implementations
- **Added** Credential encryption via Dolibarr native system

### ✅ Phase 2 - Product Management
- **Added** "Marketplaces" tab on product cards
- **Added** Offer editor (price, stock, SKU, promotions)
- **Added** 1-click sync to marketplace
- **Added** Real-time sync status indicator (OK/ERR/Pending)

### ✅ Phase 3 - Dashboard & Orders
- **Added** Dashboard with global marketplace overview
- **Added** Order management system (received → validated → integrated)
- **Added** Order import from marketplaces
- **Added** Stats tiles (total offers, low stock, sync errors, orders)
- **Added** Filterable orders list with integration status
- **Added** Order detail view with line items

### ✅ Configuration Enhancements
- **Added** MarketplaceConfigManager for JSON-based configuration
- **Added** Modular marketplace configs in `modules/configs/`
- **Added** UI tiles + dropdown for 15-20+ marketplace support
- **Added** Dynamic form fields from JSON config
- **Added** Import/Export action definitions per marketplace
- **Added** Menu items (Dashboard, Orders, Configuration)

### 📁 Files Created
- **Core Classes**: 4 main classes + 1 manager
- **API Implementations**: 4 complete marketplace integrations
- **UI Pages**: 5 new pages (dashboard, orders, setup, product_tab, etc)
- **JSON Configs**: 4 marketplace configs (extensible)
- **Database**: 6 tables with proper indexing
- **Documentation**: 6 comprehensive guides

### 🐛 Known Issues
- Marketplace tab not showing initially - see ONGLET_TROUBLESHOOTING.md for fix

## Upcoming

### Phase 4 - Automation [Planned]
- [ ] Cron job for automated sync
- [ ] Webhook support for order reception
- [ ] Email notifications
- [ ] Auto-retry on failed syncs
- [ ] Scheduled stock verification

### Phase 5 - Advanced [Planned]
- [ ] Dynamic pricing algorithm
- [ ] Inventory forecasting
- [ ] Multi-currency support
- [ ] Advanced reporting
- [ ] API rate limiting
- [ ] Bulk import/export

## Branch Status

- `main` - v1.0.0 stable ✅
- `develop` - Ready for Phase 4
- `feature/phase4-automation` - Ready to start

## Compatibility

- **Dolibarr**: 17.x, 18.x, 19.x
- **PHP**: 8.1+
- **MySQL**: 5.7+
- **APIs**: Mirakl, Octopia, SP-API, WooCommerce REST v3

## Installation

See README.md for installation instructions.

## Contributors

- Module created: 2026-05-01
- Lead: BigDataConsulting
- Status: Active development

---

**Version:** 1.0.0  
**Last Updated:** 2026-05-01  
**Maintainer:** BDC Team
