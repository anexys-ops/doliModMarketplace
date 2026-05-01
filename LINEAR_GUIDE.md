# Instructions Linear - Création des Tickets

## Accès Linear
https://linear.app/anexys/project/dolibarr-modulemarketplace-bdc-f79cfe2c5ebb

---

## TICKETS À CRÉER

### ✅ COMPLETED (Marquer comme Done)

#### Ticket 1: Phase 1 - Infrastructure & Core Module
- **Title:** Phase 1: Infrastructure & Core Module
- **Description:** Core module setup, database tables, classes, API implementations
- **Status:** Done ✅
- **Priority:** High
- **Team:** Backend
- **Label:** Phase 1, Infrastructure

#### Ticket 2: Phase 2 - Product Tab Integration
- **Title:** Phase 2: Product Tab Integration
- **Description:** Native Dolibarr product tab with offers management
- **Status:** Done ✅
- **Priority:** High
- **Team:** Frontend/Backend
- **Label:** Phase 2, UI

#### Ticket 3: Phase 3 - Dashboard & Orders
- **Title:** Phase 3: Dashboard & Orders Management
- **Description:** Dashboard, order management, import system
- **Status:** Done ✅
- **Priority:** High
- **Team:** Backend
- **Label:** Phase 3, Orders

#### Ticket 4: Phase 4 - Advanced Configuration
- **Title:** Phase 4: Advanced Configuration
- **Description:** Endpoints, Cron jobs, Connection tests, Logs management
- **Status:** Done ✅
- **Priority:** High
- **Team:** Backend
- **Label:** Phase 4, Configuration

#### Ticket 5: Phase 5 - Field Mapping & Extrafields
- **Title:** Phase 5: Field Mapping & Extrafields Support
- **Description:** Complete field mapping for Products/Categories/Orders with Extrafields
- **Status:** Done ✅
- **Priority:** High
- **Team:** Backend
- **Label:** Phase 5, Mapping

---

### 🔄 IN PROGRESS

#### Ticket 6: Server Deployment
- **Title:** Server Deployment - Git Clone & Setup
- **Description:** Clone repository to /var/www/dolibarr/htdocs/custom/marketplace_bdc/, set permissions, verify installation
- **Status:** In Progress 🔄
- **Priority:** Critical
- **Assignee:** DevOps
- **Label:** Deployment, Critical
- **Estimate:** 0.5 hours

**Subtasks:**
- [ ] Git clone completed
- [ ] Permissions set (755, www-data)
- [ ] File integrity verified
- [ ] Database structure ready

---

### ⏳ TO DO

#### Ticket 7: Dolibarr Module Activation
- **Title:** Activate Module in Dolibarr Admin
- **Description:** Admin → Modules → Search "MarketPlace_BDC" → Activate
- **Status:** To Do ⏳
- **Priority:** Critical
- **Assignee:** Admin User
- **Label:** Deployment, Critical
- **Estimate:** 0.25 hours

**Subtasks:**
- [ ] Module appears in list
- [ ] Activation successful
- [ ] Menus visible (Dashboard, Orders, Config)
- [ ] Product tab visible
- [ ] Database tables created

---

#### Ticket 8: Phase 6 - Testing & Validation
- **Title:** Phase 6: Testing & Validation
- **Description:** Complete testing of all features in DEV and PROD
- **Status:** To Do ⏳
- **Priority:** High
- **Team:** QA
- **Label:** Phase 6, QA, Testing
- **Estimate:** 4 hours

**Subtasks:**
- [ ] Connection test DEV
- [ ] Connection test PROD
- [ ] Endpoint selection works
- [ ] Cron job execution test
- [ ] Log generation verified
- [ ] Field mapping works
- [ ] Product sync test
- [ ] Order import test
- [ ] UI/UX validation
- [ ] Performance testing

---

#### Ticket 9: Phase 7 - Documentation
- **Title:** Phase 7: Documentation Finalization
- **Description:** Complete documentation for all features
- **Status:** To Do ⏳
- **Priority:** Medium
- **Team:** Documentation
- **Label:** Phase 7, Documentation
- **Estimate:** 3 hours

**Subtasks:**
- [ ] Update README
- [ ] Quick-start guide
- [ ] User manual (Admin)
- [ ] User manual (End User)
- [ ] Troubleshooting guide
- [ ] API documentation
- [ ] Database schema docs
- [ ] Video tutorials (optional)

---

#### Ticket 10: Phase 8 - Support & Maintenance
- **Title:** Phase 8: Support & Maintenance Setup
- **Description:** Production support procedures, monitoring, updates
- **Status:** To Do ⏳
- **Priority:** Medium
- **Team:** Support
- **Label:** Phase 8, Support, Maintenance
- **Estimate:** Ongoing

**Subtasks:**
- [ ] Production deployment guide
- [ ] Backup & restore procedures
- [ ] Update procedures
- [ ] Security hardening
- [ ] Performance tuning
- [ ] Monitoring setup
- [ ] Support process

---

## LABELS À CRÉER

```
Infrastructure, UI, Backend, Frontend, QA, Testing
Configuration, Deployment, Maintenance, Documentation
Phase 1, Phase 2, Phase 3, Phase 4, Phase 5, Phase 6, Phase 7, Phase 8
Critical, High, Medium, Low
```

---

## CYCLE D'ÉTAT (Status Workflow)

```
Backlog → Todo → In Progress → In Review → Done
```

---

## SPRINTS SUGGÉRÉS

**Sprint 1 (Current):**
- Tickets 1-5: ✅ Completed
- Ticket 6: 🔄 In Progress (Deployment)

**Sprint 2 (Next):**
- Ticket 7: ⏳ Activation
- Ticket 8: 🔲 Testing
- Ticket 9: 🔲 Documentation

**Sprint 3:**
- Ticket 10: 🔲 Support & Maintenance
- Bug fixes & optimizations

---

## ÉQUIPES

```
Backend:      Development of core features
Frontend:     UI/UX implementation
QA:           Testing & validation
DevOps:       Deployment & infrastructure
Documentation: User guides & technical docs
Support:      Customer support & maintenance
```

---

## AUTOMATISATION SUGGESTIONS

**GitHub Integration:**
- Auto-sync commits to tickets
- Auto-link issues to tickets

**Slack Integration:**
- Notify on ticket status changes
- Daily standups

---

## POINTS DE REPÈRE (Milestones)

- **Milestone 1:** Module Development ✅ (Done)
- **Milestone 2:** Deployment ⏳ (In Progress)
- **Milestone 3:** Testing & Release (Next)
- **Milestone 4:** Production Support (Ongoing)

---

## CONTACTS

| Rôle | Contact | Email |
|------|---------|-------|
| Project Lead | Fahd OUBENAISSA | fahd@anexys.fr |
| Backend Lead | - | - |
| Frontend Lead | - | - |
| QA Lead | - | - |
| DevOps Lead | - | - |

---

## TEMPLATES DE TICKETS

### Template Standard
```
Title: [Phase X] Feature Name
Description: Clear description
Status: To Do/In Progress/Done
Priority: High/Medium/Low
Team: Backend/Frontend/QA/DevOps
Estimate: X hours
Labels: Phase X, Feature Type
Subtasks: [ ] Task 1, [ ] Task 2
```

---

**Last Updated:** 2026-05-01 15:30 UTC+2  
**Document Version:** 1.0
