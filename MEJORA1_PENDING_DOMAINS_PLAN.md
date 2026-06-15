# Event Bus Migration — Domain Migration Log

> **Context**: This project uses DDD/Hexagonal architecture. All modules have been migrated
> from `AuditRecorderInterface` (direct audit writes) to `EventBusInterface` (synchronous
> domain events). The subscriber `AuditEventSubscriber` picks up any `AuditableEvent` and
> writes the audit log automatically via `RequestContextInterface` (userId, ip, deviceId).
>
> **Migration status**: ✅ COMPLETE — all domains migrated.
>
> **Suite**: 1037/1037 passing (as of commit `6ab18c7`).

---

## Migrated domains (in order)

| Domain | Commit | Events | UCs | Notes |
|--------|--------|--------|-----|-------|
| Tax | early | 2 | 3 | — |
| Zone | early | 2 | 3 | — |
| Family | early | 2 | 3 | color + icon VOs |
| Table | early | 3 | 4 | merge/unmerge group events from UC |
| Product | early | 2 | 4 | — |
| ProductModifier | `a1925c4` | 3 | 3 | reference impl, uses RecordsEvents on entity |
| ProductVariant | `d6fc694` | 3 | 3 | — |
| Restaurant | `6e78800` | 2 | 2 | — |
| User | `4410318` | 4 | 5 | — |
| Menu | `cb357c3` | 4 | 4 | Menu + MenuItem entities |
| Order | (series) | 10 | 10 | Order entity uses RecordsEvents |
| Sale | `a0a56ce` | 12 | 12 | direct UC emission (metadata computed in UC) |
| Cash | `6ab18c7` | 7 | 7 | direct UC emission; deviceId kept in OpenCashSessionCommand |

---

## Architecture reference (kept for future modules)

### Pattern A — Entity emission (RecordsEvents on aggregate)
Used by: ProductModifier, ProductVariant, Restaurant, User, Menu, Order.
```php
// Entity
use RecordsEvents;
public static function dddCreate(...): self {
    $entity = new self(...);
    $entity->recordEvent(new EntityCreated(...));
    return $entity;
}

// UC — pull BEFORE or AFTER save (both work; pull before is safer)
$entity = Entity::dddCreate(...);
$events = $entity->pullDomainEvents();
$this->repo->save($entity);
$this->eventBus->publish(...$events);
```

### Pattern B — Direct UC emission
Used by: Sale, Cash (when event metadata is computed in the UC, not in the entity).
```php
// UC
$entity = Entity::dddCreate(...);
$this->repo->save($entity);
$this->eventBus->publish(new EntityCreated(
    entityId: $entity->id()->value(),
    someMetadata: $computedValue,
));
```

### Key rule — event buffer contamination in tests
`dddCreate()` puts an event in the buffer (Pattern A only). Tests that call `dddCreate()`
to build a fixture and feed it to a UC that calls `pullDomainEvents()` will see unexpected
events. **Fix**: use `fromPersistence()` in test helpers.

### AuditableEvent interface
```
App\Shared\Domain\Event\AuditableEvent extends DomainEvent
```
Methods: `auditSlug()`, `auditEntityType()`, `auditEntityId()`, `auditMetadata()`,
`auditBefore()`, `auditAfter()`.

---

## Files NOT to change (ever)

- `App\Audit\Application\*` — core audit infrastructure (ArchiveOldAuditLogs, ExportAuditEvents).
- `App\Audit\Infrastructure\Persistence\EloquentAuditRecorder.php`
- `App\Audit\Application\Subscriber\AuditEventSubscriber.php` — the bridge between events and audit log.
- `App\Providers\AppServiceProvider.php` — EventBus binding already in place.
