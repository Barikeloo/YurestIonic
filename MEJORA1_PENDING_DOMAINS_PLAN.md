# Event Bus Migration — Pending Domains Plan

> **Context**: This project uses DDD/Hexagonal architecture. We are migrating every module
> from `AuditRecorderInterface` (direct audit writes) to `EventBusInterface` (synchronous
> domain events). The subscriber `AuditEventSubscriber` picks up any `AuditableEvent` and
> writes the audit log automatically via `RequestContextInterface` (userId, ip, deviceId).
>
> **Already migrated**: Family · Tax · Zone · Table · Product · ProductModifier · ProductVariant
>
> **Suite baseline**: 991 tests / 991 passing (as of commit `d6fc694`).

---

## Architecture reference

### RecordsEvents trait (already exists)
```
App\Shared\Domain\Event\RecordsEvents
```
Methods: `recordEvent(object $event): void`, `pullDomainEvents(): array`.
`dddCreate()` → records event. `fromPersistence()` → never records events.

### AuditableEvent interface (already exists)
```
App\Shared\Domain\Event\AuditableEvent
```
Methods: `auditSlug(): string`, `auditEntityType(): string`, `auditEntityId(): string`,
`auditMetadata(): array`, `auditBefore(): ?array`, `auditAfter(): ?array`.

### EventBusInterface
```
App\Shared\Application\Event\EventBusInterface
```
`publish(object ...$events): void` — synchronous, dispatches immediately.

### Completed example to copy
See `App\ProductModifier\Domain\Event\ProductModifierCreated.php` (slug prefix `catalog.modifier_*`).
See `App\ProductModifier\Domain\Entity\ProductModifier.php` for `use RecordsEvents` + `snapshot()`.
See `App\ProductModifier\Application\CreateProductModifier\CreateProductModifier.php` for the UC pattern.

### Key rule — event buffer contamination in tests
`dddCreate()` puts an event in the buffer. Tests that call `dddCreate()` to build a fixture
and then feed it to the UC will see unexpected events when `pullDomainEvents()` is called.
**Fix**: use `fromPersistence()` in test helpers, never `dddCreate()`.

### Standard UC migration pattern
```php
// CREATE
$entity = Entity::dddCreate(...);
$this->repo->save($entity);
$this->eventBus->publish(...$entity->pullDomainEvents());

// UPDATE
$entity->update(...);              // entity records event internally
$events = $entity->pullDomainEvents();
$this->repo->save($entity);
$this->eventBus->publish(...$events); // pull BEFORE save

// DELETE
$entity->delete();                 // entity records event
$events = $entity->pullDomainEvents();
$this->repo->deleteById($id);
$this->eventBus->publish(...$events);
```

### Per-domain commit instruction
After each domain: run `docker compose exec api php artisan test` (must be 991+ passing),
then commit in English with no `Co-Authored-By` trailer.

---

## Domain 1 — Restaurant

**Files to touch**: 10 files (2 events, 1 entity, 2 UCs, 2 commands, 2 requests, 1 feature test)

### Domain events to create

#### `App\Restaurant\Domain\Event\RestaurantCreated.php`
```
auditSlug()       → 'restaurant.created'
auditEntityType() → 'restaurant'
auditEntityId()   → $restaurantUuid  (the public UUID, not the int-based internal id)
auditMetadata()   → ['restaurant_name' => ..., 'restaurant_uuid' => ...]
auditBefore()     → null
auditAfter()      → null

constructor(string $restaurantUuid, string $restaurantName)
```

#### `App\Restaurant\Domain\Event\RestaurantUpdated.php`
```
auditSlug()       → 'restaurant.updated'
auditEntityType() → 'restaurant'
auditEntityId()   → $restaurantUuid
auditMetadata()   → ['restaurant_name' => $this->after['name']]
auditBefore()     → ['name', 'legal_name', 'tax_id', 'email']
auditAfter()      → ['name', 'legal_name', 'tax_id', 'email']

constructor(string $restaurantUuid, array $before, array $after)
```

#### `App\Restaurant\Domain\Event\RestaurantPasswordChanged.php`
```
auditSlug()       → 'auth.password_changed'
auditEntityType() → 'restaurant'
auditEntityId()   → $restaurantUuid
auditMetadata()   → []
auditBefore()     → null
auditAfter()      → null

constructor(string $restaurantUuid)
```

### Entity changes — `App\Restaurant\Domain\Entity\Restaurant.php`

The entity has separate mutators (`updateName()`, `updateLegalName()`, `updateTaxId()`,
`updateEmail()`, `updatePassword()`). There is NO single `update()` method.

1. Add `use RecordsEvents;`
2. Add imports for the 3 events.
3. In `dddCreate()`: after `new self(...)`, call `$restaurant->recordEvent(new RestaurantCreated(...))`.
4. Add private `snapshot(): array` returning `['name', 'legal_name', 'tax_id', 'email']`.
5. **Do NOT add events in individual mutators** — the UC controls when/which events fire.
   Instead, the UC will build before/after arrays and call `recordEvent()` directly on the entity...

   Actually simpler: **let the UC emit events directly** (not via the entity), because
   `UpdateRestaurant` conditionally updates multiple fields and emits 1–2 events. This avoids
   complex event logic inside the entity. Only `dddCreate()` records on the entity.

   Specifically, `UpdateRestaurant` should:
   - Call entity mutators normally.
   - After save, call `$this->eventBus->publish(new RestaurantUpdated(...before, ...after))`.
   - If password changed, also publish `new RestaurantPasswordChanged(...)`.

6. In `dddCreate()`, DO record `RestaurantCreated` on the entity.
   CreateRestaurant UC then does `$this->eventBus->publish(...$restaurant->pullDomainEvents())`.

> **Note on `id()` vs `uuid()`**: `Restaurant::id()` returns the internal Uuid (used as
> `restaurantId` in AuditEventDraft). `Restaurant::uuid()` returns the public UUID (used as
> `entityId`). All events use `uuid()->value()` as the `auditEntityId`.

### Use case changes

#### `CreateRestaurant.php`
- Replace `AuditRecorderInterface $auditRecorder` with `EventBusInterface $eventBus`.
- After `$this->restaurantRepository->save($restaurant)`:
  `$this->eventBus->publish(...$restaurant->pullDomainEvents())`
- Remove the `AuditEventDraft` block entirely.

#### `UpdateRestaurant.php`
- Replace `AuditRecorderInterface $auditRecorder` with `EventBusInterface $eventBus`.
- Build `$before` array before mutators (already done).
- After `$this->restaurantRepository->save($restaurant)`:
  ```php
  $events = [new RestaurantUpdated($restaurant->uuid()->value(), $before, $after)];
  if ($command->plainPassword !== null) {
      $events[] = new RestaurantPasswordChanged($restaurant->uuid()->value());
  }
  $this->eventBus->publish(...$events);
  ```

### Command changes

#### `CreateRestaurantCommand.php`
Remove: `deviceId`, `ipAddress`.
Keep: `name`, `legalName`, `taxId`, `email`, `password`, `pin`, `companyMode`.

#### `UpdateRestaurantCommand.php`
Remove: `deviceId`, `ipAddress`.
Keep: `id`, `name`, `legalName`, `taxId`, `email`, `plainPassword`, `authUserUuid`, `isSuperAdmin`
(these last two are business logic, not audit).

### Request changes
The two Restaurant HTTP requests (`CreateRestaurantRequest`, `UpdateRestaurantRequest`) need
`toCommand()` simplified — remove `deviceId`/`ipAddress` extraction. Find them under:
`App\Restaurant\Infrastructure\Entrypoint\Http\Requests\`

### Tests
- **Unit**: There are no unit application tests for Restaurant UC (only `RestaurantEntityTest`).
  Check if any test instantiates the UC — if so, replace `AuditRecorderInterface` mock with
  `EventBusInterface` mock.
- **Feature**: `tests/Feature/Restaurant/` has 6 test files.
  Add `tests/Feature/Restaurant/RestaurantAuditEventsTest.php` checking:
  - POST `/api/superadmin/restaurants` → `audit_logs` has `restaurant.created` row.
  - PUT `/api/superadmin/restaurants/{id}` → `audit_logs` has `restaurant.updated` row.

  Use the existing `CreateRestaurantTest.php` as reference for the superadmin setup.

---

## Domain 2 — User

**Files to touch**: ~16 files (7 events, 0 entity changes, 6 UCs, ~6 commands, ~6 requests, tests)

### Special architecture for User

The User module **does NOT follow the standard entity-emits-events pattern** because the
use cases call repository methods directly (`saveWithRestaurant`, `updatePartial`, `delete`)
without going through `User::dddCreate()` / `update()` etc. Therefore:

> **All User domain events are emitted from the use case directly, not from the entity.**

The entity does NOT get `use RecordsEvents`. Events are instantiated in the UC and published
immediately after the repository operation.

### Domain events to create

#### `App\User\Domain\Event\UserCreated.php`
```
auditSlug()       → 'user.created'
auditEntityType() → 'user'
auditEntityId()   → $userUuid
auditMetadata()   → ['user_name', 'email', 'role', 'has_pin', 'actor_type', 'actor_super_admin_id']
auditBefore()     → null
auditAfter()      → null

constructor(string $userUuid, string $name, string $email, string $role, bool $hasPin,
            ?string $actorSuperAdminUuid)
```

#### `App\User\Domain\Event\UserUpdated.php`
```
auditSlug()       → 'user.updated'
auditEntityType() → 'user'
auditEntityId()   → $userUuid
auditMetadata()   → ['user_name', 'changed_fields', 'password_changed', 'pin_changed',
                      'actor_type', 'actor_super_admin_id']
auditBefore()     → ['name', 'email', 'role']
auditAfter()      → ['name', 'email', 'role']

constructor(string $userUuid, array $before, array $after, array $metadata)
```

#### `App\User\Domain\Event\UserDeleted.php`
```
auditSlug()       → 'user.deleted'
auditEntityType() → 'user'
auditEntityId()   → $userUuid
auditMetadata()   → ['user_name', 'email', 'role', 'actor_type', 'actor_super_admin_id']
auditBefore()     → null
auditAfter()      → null

constructor(string $userUuid, string $name, string $email, ?string $role,
            ?string $actorSuperAdminUuid)
```

#### `App\User\Domain\Event\UserPasswordChanged.php`
```
auditSlug()       → 'auth.password_changed'
auditEntityType() → 'user'
auditEntityId()   → $userUuid
auditMetadata()   → []
auditBefore/After → null

constructor(string $userUuid)
```

#### `App\User\Domain\Event\LoginSuccessful.php`
```
auditSlug()       → 'auth.login_successful'
auditEntityType() → 'auth_attempt'
auditEntityId()   → $userUuid
auditMetadata()   → []

constructor(string $userUuid)
```

#### `App\User\Domain\Event\LoginFailed.php`
```
auditSlug()       → 'auth.login_failed'
auditEntityType() → 'auth_attempt'
auditEntityId()   → $entityId  (email string if user not found, userId if wrong password)
auditMetadata()   → ['email' => ...]

constructor(string $entityId, string $email)
```

#### `App\User\Domain\Event\LoginPinSuccessful.php`
```
auditSlug()       → 'auth.login_pin_ok'
auditEntityType() → 'auth_attempt'
auditEntityId()   → $userUuid
auditMetadata()   → []

constructor(string $userUuid)
```

#### `App\User\Domain\Event\LoginPinFailed.php`
```
auditSlug()       → 'auth.login_pin_failed'
auditEntityType() → 'auth_attempt'
auditEntityId()   → $userUuid
auditMetadata()   → []

constructor(string $userUuid)
```

#### `App\User\Domain\Event\DeviceLinkAuthenticated.php`
```
auditSlug()       → 'auth.device_link'
auditEntityType() → 'user_session'
auditEntityId()   → $userUuid
auditMetadata()   → []

constructor(string $userUuid)
```

### Use case changes

All 6 UCs: replace `AuditRecorderInterface $auditRecorder` → `EventBusInterface $eventBus`.

#### `CreateRestaurantUser.php`
After `$this->userRepository->saveWithRestaurant(...)`:
```php
$this->eventBus->publish(new UserCreated(
    userUuid: $userUuid,
    name: $command->name,
    email: $command->email,
    role: $command->role,
    hasPin: $pinHash !== null,
    actorSuperAdminUuid: $command->actorSuperAdminUuid,
));
```

#### `UpdateRestaurantUser.php`
After `$this->userRepository->updatePartial(...)`, only if `count($changedFields) > 0`:
```php
$events = [new UserUpdated($command->userUuid, $before, $after, [...metadata...])];
if ($passwordChanged) {
    $events[] = new UserPasswordChanged($command->userUuid);
}
$this->eventBus->publish(...$events);
```

#### `DeleteRestaurantUser.php`
After `$this->userRepository->delete(...)`:
```php
$this->eventBus->publish(new UserDeleted(
    userUuid: $command->userUuid,
    name: $deletedUserName,
    email: $deletedUserEmail,
    role: $deletedUserRole,
    actorSuperAdminUuid: $command->actorSuperAdminUuid,
));
```

#### `AuthenticateUser.php`
⚠️ **Auth events fire BEFORE throwing exceptions** (audit the failure, then throw).
- User not found: `$this->eventBus->publish(new LoginFailed($command->email, $command->email));` then throw.
- Wrong password: `$this->eventBus->publish(new LoginFailed($user->id()->value(), $command->email));` then throw.
- Success (and restaurant found): `$this->eventBus->publish(new LoginSuccessful($user->id()->value()));`

#### `AuthenticateUserByPin.php`
- PIN invalid: `$this->eventBus->publish(new LoginPinFailed($command->userUuid));` (only if restaurantUuid resolvable).
- Success: `$this->eventBus->publish(new LoginPinSuccessful($command->userUuid));`

#### `AuthenticateForDeviceLink.php`
- Success (and restaurant found): `$this->eventBus->publish(new DeviceLinkAuthenticated($user->id()->value()));`

### Command changes
Fields to remove from commands (audit-only):
- `CreateRestaurantUserCommand`: remove `actorUserUuid`, `deviceId`, `ipAddress`. **Keep** `actorSuperAdminUuid` (used in event metadata for actor_type discrimination).
- `UpdateRestaurantUserCommand`: remove `actorUserUuid`, `deviceId`, `ipAddress`. **Keep** `actorSuperAdminUuid`, `restaurantUuid`, `userUuid`.
- `DeleteRestaurantUserCommand`: remove `actorUserUuid`, `deviceId`, `ipAddress`. **Keep** `actorSuperAdminUuid`.
- `AuthenticateUserCommand`: remove `deviceId`, `ipAddress`. **Keep** `email`, `plainPassword`.
- `AuthenticateUserByPinCommand`: remove `ipAddress`, `deviceId`. **Keep** `userUuid`, `pin`, `restaurantUuid`.
- `AuthenticateForDeviceLinkCommand`: remove `ipAddress`. **Keep** `email`, `password`, `deviceId` (deviceId is still used for `recordAccess` — NOT just audit).

> ⚠️ `AuthenticateForDeviceLink` uses `$command->deviceId` for `userQuickAccessRepository->recordAccess()` — this is business logic, not audit. Keep `deviceId` in that command.

> ⚠️ `AuthenticateUserByPin` uses `$command->deviceId` for `recordAccess()` too. Keep `deviceId` there.

### Tests
- Unit: `tests/Unit/User/AuthenticateUserTest.php`, `tests/Unit/User/CreateUserTest.php` use `AuditRecorderInterface` — replace with `EventBusInterface`.
- Feature: Add `tests/Feature/User/UserAuditEventsTest.php` checking `user.created`, `user.updated`, `user.deleted` in `audit_logs`. Auth event tests (login) likely already covered by `LoginUserTest.php`; verify the audit row is written rather than adding duplicate tests.

---

## Domain 3 — Menu

**Files to touch**: 10 files (5 events, 1 entity, 4 UCs, 4 commands, 4 requests, tests)

### Domain events to create

#### `App\Menu\Domain\Event\MenuCreated.php`
```
auditSlug()       → 'menu.created'
auditEntityType() → 'menu'
auditEntityId()   → $menuId
auditMetadata()   → ['menu_name', 'price_cents', 'price_formatted', 'sections_count', 'active']
constructor(string $menuId, string $menuName, int $priceCents, int $sectionsCount, bool $active)
```

#### `App\Menu\Domain\Event\MenuUpdated.php`
```
auditSlug()       → 'menu.updated'
auditEntityType() → 'menu'
auditEntityId()   → $menuId
auditMetadata()   → ['menu_name', 'sections_count']
auditBefore()     → ['name', 'price', 'active', 'tax_id']
auditAfter()      → ['name', 'price', 'active', 'tax_id']
constructor(string $menuId, array $before, array $after, string $menuName, int $sectionsCount)
```

#### `App\Menu\Domain\Event\MenuArchived.php`
```
auditSlug()       → 'menu.archived'
auditEntityType() → 'menu'
auditEntityId()   → $menuId
auditMetadata()   → ['menu_name']
constructor(string $menuId, string $menuName)
```

#### `App\Menu\Domain\Event\MenuActivated.php`
```
auditSlug()       → 'menu.activated'
auditEntityType() → 'menu'
auditEntityId()   → $menuId
auditMetadata()   → ['menu_name']
constructor(string $menuId, string $menuName)
```

#### `App\Menu\Domain\Event\MenuDeactivated.php`
```
auditSlug()       → 'menu.deactivated'
auditEntityType() → 'menu'
auditEntityId()   → $menuId
auditMetadata()   → ['menu_name']
constructor(string $menuId, string $menuName)
```

### Entity changes — `App\Menu\Domain\Entity\Menu.php`

The Menu entity has: `dddCreate()`, `updateHeader()`, `replaceSections()`, `activate()`,
`deactivate()`, `archive()`. Sections are rebuilt from scratch on update.

Pattern for this entity:
1. Add `use RecordsEvents;`
2. `dddCreate()`: record `MenuCreated` (needs sectionsCount = count of $sections parameter).
3. `archive()`: record `MenuArchived`.
4. `activate()`: record `MenuActivated`.
5. `deactivate()`: record `MenuDeactivated`.
6. For `MenuUpdated`: the UC builds before/after and emits directly from the UC (not from
   the entity), because `updateHeader()` + `replaceSections()` are called separately.
   OR: add a `snapshot()` method to Menu and let the UC call `recordEvent()` on the entity.
   **Recommended**: UC captures `$before = $this->snapshot()`, calls mutations, then records
   `MenuUpdated` directly via `$this->eventBus->publish(new MenuUpdated(...))`.

### Use case changes

#### `CreateMenu.php`
Replace AuditRecorder → EventBus.
After save: `$this->eventBus->publish(...$menu->pullDomainEvents())`.

#### `UpdateMenu.php`
Replace AuditRecorder → EventBus.
Build `$before` before calling `updateHeader()` (already done).
After save: `$this->eventBus->publish(new MenuUpdated($menu->id()->value(), $before, $after, $menu->name()->value(), count($sections)))`.

#### `ArchiveMenu.php`
Replace AuditRecorder → EventBus.
After `$menu->archive()` + save:
```php
$events = $menu->pullDomainEvents();
$this->eventBus->publish(...$events);
```

#### `SetMenuActive.php`
Replace AuditRecorder → EventBus.
After activate/deactivate + save: `$this->eventBus->publish(...$menu->pullDomainEvents())`.

### Command changes
Remove from all 4 commands: `restaurantId`, `userId`, `deviceId`, `ipAddress`.
Keep: domain-specific fields only (taxId, name, price, sections, validity, etc.).

### Tests
- Unit: `tests/Unit/Menu/Application/` has CreateMenuTest, UpdateMenuTest, ArchiveMenuTest,
  SetMenuActiveTest. Update all to replace AuditRecorder mock with EventBus mock.
  Use `Menu::fromPersistence()` in fixture helpers (not `dddCreate()`).
- Feature: No existing `MenuAuditEventsTest`. Add `tests/Feature/Menu/MenuAuditEventsTest.php`
  checking `menu.created`, `menu.updated`, `menu.archived`, `menu.activated`, `menu.deactivated`.

---

## Domain 4 — Order

**Files to touch**: ~25 files (11 events, 1–2 entities, 11 UCs, 11 commands, 11 requests, tests)

### Audit slugs to events mapping

| Slug | Event class |
|------|------------|
| `order.created` | `OrderCreated` |
| `order.cancelled` | `OrderCancelled` |
| `order.deleted` | `OrderDeleted` |
| `order.line_added` | `OrderLineAdded` |
| `order.line_removed` | `OrderLineRemoved` |
| `order.marked_to_charge` | `OrderMarkedToCharge` |
| `order.reopened` | `OrderReopened` |
| `order.transferred` | `OrderTransferred` |
| `order.diners_updated` | `OrderDinersUpdated` |
| `order.comanda_sent` | `OrderComandaSent` |
| `order.menu_line_added` | `OrderMenuLineAdded` |

All go in `App\Order\Domain\Event\`.

### Entity changes — `App\Order\Domain\Entity\Order.php`

The Order entity has: `dddCreate()`, `markToCharge()`, `close()`, `cancel()`, `reopen()`,
`updateDiners()`, `transferTo()`. There is no `delete()` method.

1. Add `use RecordsEvents;`
2. `dddCreate()`: record `OrderCreated`. Inspect existing UC to know metadata fields.
3. `cancel()`: record `OrderCancelled`.
4. `reopen()`: record `OrderReopened`.
5. `markToCharge()`: record `OrderMarkedToCharge`.
6. `transferTo()`: record `OrderTransferred` (metadata: old tableId → new tableId).
7. `updateDiners()`: record `OrderDinersUpdated`.
8. Add `delete()` method that records `OrderDeleted` (UC currently calls `deleteById` directly
   after recording — add the method to the entity so the pattern is consistent).

For **line operations** (`AddLineToOrder`, `DeleteOrderLine`, `BatchAddLinesToOrder`,
`AddMenuLineToOrder`): check whether `Order` has `addLine()` / `removeLine()` methods.
If yes, add event recording there. If not (lines are saved separately without going through
the Order aggregate), emit the events **from the UC directly** (not from the entity).

> Read `App\Order\Domain\Entity\Order.php` fully before implementing to determine line method locations.

### Metadata to preserve per event

Read each UC's `AuditEventDraft` block to extract the exact metadata fields. Key ones:
- `OrderCreated`: table_id, opened_by_user_id, diners
- `OrderCancelled`: table_id, reason (if any)
- `OrderDeleted`: table_id
- `OrderLineAdded`: product_name, quantity, price_cents (read AddLineToOrder UC)
- `OrderLineRemoved`: product_name, quantity (read DeleteOrderLine UC)
- `OrderMarkedToCharge`: table_id
- `OrderReopened`: table_id
- `OrderTransferred`: from_table_id, to_table_id
- `OrderDinersUpdated`: old_diners, new_diners
- `OrderComandaSent`: lines_count (read BatchAddLinesToOrder UC)
- `OrderMenuLineAdded`: menu_name, price_cents (read AddMenuLineToOrder UC)

### Command changes
Remove from all Order commands: `restaurantId`, `userId`, `deviceId`, `ipAddress`.

### Tests
- Unit: No existing unit application tests for Order UCs — only entity/VO tests exist.
  Add unit tests for each migrated UC under `tests/Unit/Order/Application/`.
- Feature: Existing feature tests (`CreateOrderTest`, `AddLineToOrderTest`, `OrderCrudTest`)
  don't check audit. Add `tests/Feature/Order/OrderAuditEventsTest.php` checking the most
  important slugs: `order.created`, `order.line_added`, `order.cancelled`.

---

## Domain 5 — Sale

**Files to touch**: ~28 files (12 events, 2 entities, 12 UCs, 12 commands, requests, tests)

### Audit slugs to events mapping

| Slug | Event class |
|------|------------|
| `sale.created` | `SaleCreated` |
| `sale.cancelled` | `SaleCancelled` |
| `sale.closed` | `SaleClosed` (note: UC is `UpdateSale`) |
| `sale.line_added` | `SaleLineAdded` |
| `sale.charge_session_created` | `ChargeSessionCreated` |
| `sale.charge_session_cancelled` | `ChargeSessionCancelled` |
| `sale.payment_recorded` | `ChargeSessionPaymentRecorded` |
| `sale.credit_note_issued` | `CreditNoteIssued` |
| `sale.final_ticket_created` | `FinalTicketCreated` |
| `sale.line_refunded` | `ChargeSessionLineRefunded` |
| `sale.lines_assigned` | `ChargeSessionLinesAssigned` |
| `sale.diners_updated` | `ChargeSessionDinersUpdated` |

All go in `App\Sale\Domain\Event\`.

### Entity changes

#### `App\Sale\Domain\Entity\Sale.php`
The entity has `dddCreate()`, `close()`, `cancel()`. No `addLine()` visible — check if
`AddLineToSale` UC creates a `SaleLine` entity directly.

1. Add `use RecordsEvents;`
2. `dddCreate()` → record `SaleCreated`.
3. `close()` → record `SaleClosed`.
4. `cancel()` → record `SaleCancelled`.

#### `App\Sale\Domain\Entity\ChargeSession.php` (if exists)
The `ChargeSession` is a separate aggregate. Check its methods.
Events `ChargeSessionCreated`, `ChargeSessionCancelled`, `ChargeSessionPaymentRecorded`,
`ChargeSessionLineRefunded`, `ChargeSessionLinesAssigned`, `ChargeSessionDinersUpdated`
should be recorded on the `ChargeSession` entity if it has corresponding methods, or emitted
from the UC directly if not.

> Read `App\Sale\Domain\Entity\ChargeSession.php` fully before implementing.

### Metadata to preserve
Read each UC's `AuditEventDraft` block to extract metadata. Key:
- `SaleCreated`: order_id, table_id, total_cents, ticket_number
- `SaleCancelled`: reason, ticket_number
- `SaleClosed`: total_cents, ticket_number
- `SaleLineAdded`: product_name, quantity, price_cents
- `ChargeSessionCreated`: sale_id, diners, charge_method
- `ChargeSessionPaymentRecorded`: amount_cents, method
- etc. (read each UC's `AuditEventDraft` to get exact fields)

### Command changes
Remove from all Sale commands: `restaurantId`, `userId`, `deviceId`, `ipAddress`.

### Tests
- Unit: `SaleEntityTest` and `ChargeSessionEntityTest` exist — add event assertions there
  after adding `RecordsEvents` to entities.
- Feature: Existing Sale feature tests don't check audit. Add
  `tests/Feature/Sale/SaleAuditEventsTest.php` covering `sale.created`, `sale.closed`,
  `sale.cancelled`, `sale.charge_session_created`, `sale.payment_recorded`.

---

## Domain 6 — Cash

**Files to touch**: ~18 files (7 events, 1 entity, 7 UCs, 7 commands, requests, tests)

### Audit slugs to events mapping

| Slug | Event class |
|------|------------|
| `caja.opened` | `CashSessionOpened` |
| `caja.closing_started` | `CashSessionClosingStarted` |
| `caja.closing_cancelled` | `CashSessionClosingCancelled` |
| `caja.closed` | `CashSessionClosed` |
| `caja.force_closed` | `CashSessionForceClosed` |
| `caja.cash_movement` | `CashMovementRegistered` |
| `caja.z_report_generated` | `ZReportGenerated` |

All go in `App\Cash\Domain\Event\`.

### Entity changes — `App\Cash\Domain\Entity\CashSession.php`

The entity has: `dddCreate()`, `startClosing()`, `cancelClosing()`, `close()`, `forceClose()`.
`RegisterCashMovement` creates a `CashMovement` entity separately.

1. Add `use RecordsEvents;`
2. `dddCreate()` → record `CashSessionOpened`.
3. `startClosing()` → record `CashSessionClosingStarted`.
4. `cancelClosing()` → record `CashSessionClosingCancelled`.
5. `close()` → record `CashSessionClosed` (with final_amount, expected_amount, discrepancy).
6. `forceClose()` → record `CashSessionForceClosed`.
7. `CashMovementRegistered` and `ZReportGenerated` are emitted from UCs directly (if
   `CashMovement` is a value object, not an entity) or from `CashMovement::dddCreate()`.

### Metadata to preserve

Read each UC's `AuditEventDraft` block:
- `CashSessionOpened`: device_id, initial_amount_cents, opened_by_user
- `CashSessionClosingStarted`: expected_amount_cents, actual_amount_cents
- `CashSessionClosed`: final_amount_cents, discrepancy_cents, discrepancy_reason
- `CashSessionForceClosed`: device_id
- `CashMovementRegistered`: amount_cents, movement_type, reason
- `ZReportGenerated`: z_report_number, session_uuid, total_sales_cents

### Command changes
Remove from all Cash commands: `restaurantId`, `userId`, `deviceId` (check per UC — some
may use deviceId for business logic), `ipAddress`.

> ⚠️ Read each Cash command carefully. `OpenCashSession` likely uses `deviceId` as a
> domain concept (which device opened the session), not just for audit. Check if
> `CashSession` entity stores the deviceId in domain state — if yes, keep it in command.

### Tests
- Unit: 7 UC test files exist under `tests/Unit/Cash/Application/` — update all that mock
  `AuditRecorderInterface` to mock `EventBusInterface` instead.
- Entity tests: `CashSessionEntityTest` — add event assertions.
- Feature: `CashSessionFullCycleTest` exists. Add
  `tests/Feature/Cash/CashSessionAuditEventsTest.php` covering `caja.opened`, `caja.closed`,
  `caja.cash_movement`.

---

## Suggested implementation order

1. **Restaurant** (2 UCs, straightforward) — verify superadmin routes work correctly.
2. **Menu** (4 UCs, clear entity methods) — good warmup for conditional events.
3. **User** (6 UCs, special: no entity events, auth failure events) — the auth failure
   pattern is the key learning for this domain.
4. **Cash** (7 UCs, entity-based) — standard pattern, check deviceId business logic.
5. **Order** (11 UCs, large — check line operations) — read entity fully first.
6. **Sale** (12 UCs, largest — ChargeSession is a second aggregate) — read both entities first.

## Verification command

After each domain commit, run:
```bash
docker compose exec api php artisan test --stop-on-failure
```
Suite must pass with N/N tests (count increases as new tests are added).

---

## Files NOT to change

- `App\Audit\Application\*` — the Audit module itself uses `AuditRecorderInterface` internally
  (e.g., `ArchiveOldAuditLogs`, `ExportAuditEvents`). These are the core audit infrastructure
  and should NOT be migrated.
- `App\Audit\Infrastructure\Persistence\EloquentAuditRecorder.php` — keep as-is.
- `App\Audit\Application\Subscriber\AuditEventSubscriber.php` — keep as-is (it's the bridge).
- `App\Providers\AppServiceProvider.php` — already binds EventBus; leave untouched.
