# Plan de Testing — TPV Backend

> Documento de planificación que recoge el estado actual de los tests, los que están pendientes de refactorizar y los que faltan por crear.

---

## 1. Tests completados ✅ (verdes)

### Fase 1 — Shared VOs (41 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/Shared/Domain/ValueObject/EmailTest.php` | Unit VO | Creación, validación, equals |
| `tests/Unit/Shared/Domain/ValueObject/UuidTest.php` | Unit VO | Creación, generate, equals |
| `tests/Unit/Shared/Domain/ValueObject/DomainDateTimeTest.php` | Unit VO | Creación, now, format, inmutabilidad |
| `tests/Unit/Shared/Domain/ValueObject/MoneyTest.php` | Unit VO | Creación, suma, resta, formato, equals |

### Fase 2 — ProductModifier (50 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/ProductModifier/` (10 files) | Unit VO + Entity + Use Cases | 43 tests |
| `tests/Feature/ProductModifier/` (1 file) | Feature | 7 tests |

### Fase 3 — ProductVariant (45 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/ProductVariant/` (8 files) | Unit VO + Entity + Use Cases | 42 tests |
| `tests/Feature/ProductVariant/` (1 file) | Feature | 3 tests |

### Fase 4 — Cash (127 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/Cash/Domain/ValueObject/` (6 files) | Unit VOs | CashSessionStatus, DeviceId, MovementReasonCode, MovementType, TipSource, ZReportHash, ZReportNumber |
| `tests/Unit/Cash/Domain/Entity/` (4 files) | Unit Entities | CashSession, CashMovement, ZReport, Tip |
| `tests/Unit/Cash/Application/` (13 files) | Unit Use Cases | Open, ForceClose, CancelClosing, RegisterMovement, GetActive, ListMovements, GetZReport, StartClosing, GenerateZReport, Close, GetSummary, GetLastClosed, ListSessions |
| `tests/Feature/Cash/` (1 file) | Feature | 7 tests |

### Fase 5 — Menu (114 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/Menu/Domain/ValueObject/` (8 files) | Unit VOs | MenuId, MenuName, MenuDescription, MenuPrice, MenuActive, MenuArchived, MenuAvailability, MenuDateRange |
| `tests/Unit/Menu/Domain/Entity/` (3 files) | Unit Entities | Menu, MenuSection, MenuSectionItem |
| `tests/Unit/Menu/Shared/` (2 files) | Unit Shared | MenuFilterer, MenuSorter |
| `tests/Unit/Menu/Application/` (6 files) | Unit Use Cases | Create, Update, SetActive, Archive, Get, List |

### Fase 6 — Audit + AuditSavedView (86 tests)
| Archivo | Tipo | Tests |
|---|---|---|
| `tests/Unit/Audit/Domain/ValueObject/` (3 files) | Unit VOs | ActionSlug, Severity, Category |
| `tests/Unit/Audit/Domain/Entity/` (1 file) | Unit Entity | AuditLog |
| `tests/Unit/Audit/Domain/` (5 files) | Unit Domain | AuditEventDraft, AuditChainHasher, AuditEventCatalog, AnomalyDetector, AuditLogPage, ListAuditLogsCriteria |
| `tests/Unit/Audit/Application/` (3 files) | Unit Use Cases | ListAuditEvents, GetAuditEvent, VerifyAuditChain |
| `tests/Unit/AuditSavedView/Domain/Entity/` (1 file) | Unit Entity | AuditSavedView |
| `tests/Unit/AuditSavedView/Application/` (4 files) | Unit Use Cases | Create, List, Update, Delete |

**Total completado: ~463 tests verdes**

---

## 2. Tests existentes que fallan 🔴 (refactorizar)

Tests escritos anteriormente que están desactualizados y fallan. Requieren revisión deconstructores, tipos, y lógica de dominio.

### Unit tests (16 errors)

| Archivo | Tests | Causa probable |
|---|---|---|
| `tests/Unit/Family/CreateFamilyTest.php` | `test_invoke_creates_family_and_saves_it` | Constructor de CreateFamilyCommand o repositorio ha cambiado |
| `tests/Unit/Order/OrderLineEntityTest.php` | `test_ddd_create_builds_entity_with_value_objects` | Firma de OrderLine::dddCreate ha cambiado |
| `tests/Unit/Restaurant/RestaurantEntityTest.php` | 3 tests | Constructor de RestaurantEntity desactualizado |
| `tests/Unit/Sale/SaleEntityTest.php` | 3 tests | Constructor de SaleEntity desactualizado |
| `tests/Unit/Sale/SaleLineEntityTest.php` | 1 test | Constructor de SaleLineEntity desactualizado |
| `tests/Unit/SuperAdmin/SuperAdminEntityTest.php` | 1 test | fromPersistence/hydrate desactualizado |
| `tests/Unit/Tax/CreateTaxTest.php` | 2 tests | Constructor de CreateTaxCommand ha cambiado |
| `tests/Unit/User/AuthenticateUserTest.php` | 1 test | Firma del caso de uso ha cambiado |
| `tests/Unit/User/CreateUserTest.php` | 1 test | Firma del caso de uso ha cambiado |
| `tests/Unit/User/Infrastructure/*/GetMeControllerTest.php` | 3 tests | Request/session mock desactualizado |
| `tests/Unit/User/Infrastructure/*/GetQuickUsersControllerTest.php` | 1 test | Request/response mock desactualizado |
| `tests/Unit/Zone/CreateZoneTest.php` | 1 test | Constructor de CreateZoneCommand ha cambiado |
| `tests/Unit/Zone/ZoneEntityTest.php` | 1 test | Constructor de ZoneEntity ha cambiado |

### Feature tests (44 failures)

| Archivo | Tests | Causa probable |
|---|---|---|
| `tests/Feature/Family/FamilyCrudTest.php` | 1 test | Endpoints o auth han cambiado |
| `tests/Feature/Order/AddLineToOrderTest.php` | 1 test | Mensaje de error desactualizado |
| `tests/Feature/Order/OrderCrudTest.php` | 1 test | Flujo CRUD desactualizado |
| `tests/Feature/Product/ProductCrudTest.php` | 1 test | Flujo CRUD desactualizado |
| `tests/Feature/Restaurant/CreateRestaurantTest.php` | 4 tests | Endpoints o lógica de creación cambiaron |
| `tests/Feature/Sale/ChargeSessionTest.php` | varios | Flujo ChargeSession cambió |
| `tests/Feature/Sale/SaleCrudTest.php` | 2 tests | Flujo CRUD desactualizado |
| `tests/Feature/Shard/ShardKeyPlanTest.php` | 3 tests | Middleware multi-tenant cambió |
| `tests/Feature/SuperAdmin/DeveloperDashboardTest.php` | 6 tests | Endpoints SuperAdmin cambiaron |
| `tests/Feature/Table/TableCrudTest.php` | 3 tests | Flujo CRUD desactualizado |
| `tests/Feature/Tax/TaxCrudTest.php` | 1 test | Flujo CRUD desactualizado |
| `tests/Feature/User/LoginUserTest.php` | varios | Flujo login cambió |
| `tests/Feature/User/UpdateRestaurantUserRoleGuardTest.php` | 1 test | Mensaje de error desactualizado |
| `tests/Feature/Zone/ZoneCrudTest.php` | 2 tests | Endpoints o permisos cambiaron |

---

## 3. Tests pendientes de crear ➕

### Unit tests — Use Cases

| Dominio | Use Cases a testear | Prioridad |
|---|---|---|
| **Family** | `CreateFamily`, `UpdateFamily`, `DeleteFamily`, `GetFamily`, `ListFamilies`, `SetFamilyActive` | Alta |
| **Order** | `CreateOrder`, `AddLineToOrder`, `DeleteOrderLine`, `MarkOrderToCharge`, `ReopenOrder`, `TransferOrder`, `CancelOrder` | Alta |
| **Product** | `CreateProduct`, `UpdateProduct`, `DeleteProduct`, `SetProductActive`, `GetProduct`, `ListProducts` | Alta |
| **Sale** | `CreateSale`, `CancelSale`, `CreateCreditNote`, `AddLineToSale`, `ChargeSession` commands | Alta |
| **Restaurant** | `CreateRestaurant` (use case ya existe), `GetRestaurant`, `ListRestaurants` | Media |
| **Tax** | `CreateTax`, `UpdateTax`, `DeleteTax`, `GetTax`, `ListTaxes` | Alta |
| **User** | `AuthenticateUserByPin`, `AuthenticateForDeviceLink`, `CreateRestaurantUser`, `UpdateRestaurantUser`, `DeleteRestaurantUser` | Alta |
| **Zone** | `CreateZone`, `UpdateZone`, `DeleteZone`, `GetZone`, `ListZones` | Alta |
| **Tables** | `CreateTable`, `UpdateTable`, `DeleteTable`, `MergeTables`, `UnmergeTables` | Alta |
| **SuperAdmin** | Login, logout, list restaurants, select context | Media |

### Unit tests — Entities (refactor + completar)

| Dominio | Estado |
|---|---|
| **Family** | Entity test exists pero desactualizado |
| **Order** | Entity test + VOs existen pero desactualizados |
| **Product** | Entity test exists pero desactualizado |
| **Sale** | Entity tests + VOs existen pero desactualizados |
| **Restaurant** | Entity test exists pero desactualizado |
| **Tax** | Entity test → pendiente de crear (solo existe CreateTax use case test) |
| **User** | Entity test exists pero desactualizado |
| **Zone** | Entity test exists pero desactualizado |
| **Tables** | Entity test → pendiente de crear |
| **SuperAdmin** | Entity + VOs tests existen pero desactualizados |

### Feature tests (por dominio)

| Dominio | Estado |
|---|---|
| **Cash** | ✅ Feature test completo (7 tests) |
| **ProductModifier** | ✅ Feature test completo |
| **ProductVariant** | ✅ Feature test completo |
| **Menu** | ❌ Pendiente (0 feature tests) |
| **Audit** | ❌ Pendiente (0 feature tests) |
| **Family** | 🔴 Feature test exists pero falla |
| **Order** | 🔴 Feature tests exist pero fallan |
| **Product** | 🔴 Feature test exists pero falla |
| **Sale** | 🔴 Feature tests exist pero fallan |
| **Restaurant** | 🔴 Feature tests exist pero fallan |
| **Tax** | 🔴 Feature test exists pero falla |
| **User** | 🔴 Feature tests exist pero fallan |
| **Zone** | 🔴 Feature tests exist pero fallan |
| **Tables** | 🔴 Feature tests exist pero fallan |
| **SuperAdmin** | 🔴 Feature tests exist pero fallan |

---

## 4. Plan de trabajo por fases

### Fase 7 — Refactorizar tests existentes (Unit)
Refactorizar 16 unit tests que fallan para que se alineen con el código actual:
1. `Restaurant\RestaurantEntityTest` — 3 tests
2. `Sale\SaleEntityTest` — 3 tests
3. `Sale\SaleLineEntityTest` — 1 test
4. `SuperAdmin\SuperAdminEntityTest` — 1 test
5. `Order\OrderLineEntityTest` — 1 test
6. `User\*` — 5 tests (AuthenticateUser, CreateUser, GetMeController, GetQuickUsersController)
7. `Zone\CreateZoneTest` — 1 test
8. `Zone\ZoneEntityTest` — 1 test
9. `Tax\CreateTaxTest` — 2 tests
10. `Family\CreateFamilyTest` — 1 test

### Fase 8 — Domain: Family
- Refactor `CreateFamilyTest` (unit)
- Crear: `UpdateFamily`, `DeleteFamily`, `GetFamily`, `ListFamilies`, `SetFamilyActive` (use case tests)
- Refactor feature test `FamilyCrudTest`

### Fase 9 — Domain: Zone
- Refactor `CreateZoneTest` + `ZoneEntityTest` (unit)
- Crear: `UpdateZone`, `DeleteZone`, `GetZone`, `ListZones` (use case tests)
- Refactor feature test `ZoneCrudTest`

### Fase 10 — Domain: Tax
- Refactor `CreateTaxTest` (unit)
- Crear: `TaxEntityTest`, `UpdateTax`, `DeleteTax`, `GetTax`, `ListTaxes` (use case tests)
- Refactor feature test `TaxCrudTest`

### Fase 11 — Domain: Product
- Refactor `ProductEntityTest` (unit)
- Crear: `CreateProduct`, `UpdateProduct`, `DeleteProduct`, `SetProductActive`, `GetProduct`, `ListProducts` (use case tests)
- Refactor feature test `ProductCrudTest`

### Fase 12 — Domain: Tables
- Crear: `TableEntityTest`, `CreateTable`, `UpdateTable`, `DeleteTable`, `MergeTables`, `UnmergeTables` (use case tests)
- Refactor feature test `TableCrudTest`

### Fase 13 — Domain: Order
- Refactor `OrderLineEntityTest` (unit)
- Crear: `OrderEntityTest`, `OrderStatusVOTest`, `CreateOrder`, `AddLineToOrder`, `DeleteOrderLine`, `MarkOrderToCharge`, `ReopenOrder`, `TransferOrder`, `CancelOrder` (use case tests)
- Refactor feature tests `AddLineToOrderTest` + `OrderCrudTest`

### Fase 14 — Domain: Sale
- Refactor `SaleEntityTest` + `SaleLineEntityTest` (unit)
- Crear: `CreateSale`, `CancelSale`, `CreateCreditNote`, `AddLineToSale`, `ChargeSession*` (use case tests)
- Refactor feature tests `SaleCrudTest` + `ChargeSessionTest`

### Fase 15 — Domain: User
- Refactor `AuthenticateUserTest`, `CreateUserTest`, `GetMeControllerTest`, `GetQuickUsersControllerTest` (unit)
- Crear: `AuthenticateForDeviceLink`, `UpdateRestaurantUser`, `DeleteRestaurantUser`, `UserEntityTest` (use case tests)
- Refactor feature tests `LoginUserTest` + `UpdateRestaurantUserRoleGuardTest`

### Fase 16 — Domain: Restaurant
- Refactor `RestaurantEntityTest` (unit)
- Crear: `CreateRestaurant`, `GetRestaurant`, `ListRestaurants` (use case tests)
- Refactor feature tests `CreateRestaurantTest`

### Fase 17 — Domain: SuperAdmin
- Refactor `SuperAdminEntityTest`, `SuperAdminNameValueObjectTest`, `SuperAdminPasswordHashValueObjectTest` (unit)
- Crear: Login, logout, list restaurants, select context (use case tests)
- Refactor feature test `DeveloperDashboardTest`

### Fase 18 — Feature tests pendientes
- Menu: `MenuCrudTest` (happy path + validaciones + activación/archivado)
- Audit: `AuditLogCrudTest` (list, get, verify, filtros, paginación)
- AuditSavedView: `AuditSavedViewCrudTest` (CRUD completo)

### Fase 19 — Non-happy path tests
- Revisar todos los use cases y añadir tests para: validaciones, 404, 409, 422, 403, excepciones de dominio
- Casos frontera en VOs (strings vacíos, formatos inválidos, valores límite)

---

## 5. Resumen de cobertura objetivo

| Dominio | Unit VOs | Unit Entities | Unit Use Cases | Feature |
|---|---|---|---|---|
| Shared | ✅ 4 files | — | — | — |
| ProductModifier | ✅ | ✅ | ✅ | ✅ |
| ProductVariant | ✅ | ✅ | ✅ | ✅ |
| Cash | ✅ 6 files | ✅ 4 files | ✅ 13 files | ✅ 1 file |
| Menu | ✅ 8 files | ✅ 3 files | ✅ 6 files | ➕ Pendiente |
| Audit | ✅ 3 files | ✅ 1 file | ✅ 3 files | ➕ Pendiente |
| AuditSavedView | — | ✅ 1 file | ✅ 4 files | ➕ Pendiente |
| Family | ➕ Pendiente | 🔴 Refactor | ➕ 5 use cases | 🔴 Refactor |
| Zone | ➕ Pendiente | 🔴 Refactor | ➕ 4 use cases | 🔴 Refactor |
| Tax | ➕ Pendiente | ➕ Pendiente | ➕ 4 use cases | 🔴 Refactor |
| Product | ➕ Pendiente | 🔴 Refactor | ➕ 5 use cases | 🔴 Refactor |
| Tables | ➕ Pendiente | ➕ Pendiente | ➕ 4 use cases | 🔴 Refactor |
| Order | 🔴 Refactor | 🔴 Refactor | ➕ 6 use cases | 🔴 Refactor |
| Sale | ➕ Pendiente | 🔴 Refactor | ➕ 4 use cases | 🔴 Refactor |
| User | ➕ Pendiente | 🔴 Refactor | ➕ 4 use cases | 🔴 Refactor |
| Restaurant | ➕ Pendiente | 🔴 Refactor | ➕ 2 use cases | 🔴 Refactor |
| SuperAdmin | 🔴 Refactor | 🔴 Refactor | ➕ Pendiente | 🔴 Refactor |

**Total actual:** ~463 tests verdes + 16 errores + 44 fallos = 624 tests  
**Objetivo:** ~1200+ tests (cobertura completa de dominio + feature + non-happy path)
