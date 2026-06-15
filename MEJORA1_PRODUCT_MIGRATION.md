# Migración al bus de eventos — Módulo Product

> Subdocumento de [`HITO6_MEJORAS_PLAN.md`](./HITO6_MEJORAS_PLAN.md) · Mejora 1.
> Mismo patrón que Tax / Zone / Family / Tables ya migrados.
> Estado: **pendiente**.

---

## Contexto del módulo

### Use cases que auditan (5 — son los que migrar)

| Use case | Evento(s) actuales | Peculiaridad |
|---|---|---|
| `CreateProduct` | `product.created` | — |
| `UpdateProduct` | `product.updated` + `product.price_changed` (si cambió) | **Dos eventos** si el precio cambia |
| `DeleteProduct` | `product.deleted` | Entidad no tiene `delete()` todavía |
| `SetProductActive` | `product.activated` o `product.deactivated` | Decide según flag `active` |
| `UploadProductPhoto` | `product.photo_updated` | No hay `userId` (token público, sin sesión) |

### Use cases que NO auditan (no tocar)

`GetProduct`, `ListProducts`, `ListActiveProducts`,
`GenerateProductPhotoUploadToken`, `GetProductPhotoUploadContext`.

### Commands con campos de auditoría (se limpiarán)

| Command | Campos a eliminar |
|---|---|
| `CreateProductCommand` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `UpdateProductCommand` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `DeleteProductCommand` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `SetProductActiveCommand` | `restaurantId`, `deviceId`, `ipAddress` (ya no tiene `userId`) |
| `UploadProductPhotoCommand` | `deviceId`, `ipAddress` (ya no tiene `userId` ni `restaurantId`) |

---

## Decisiones de diseño

### D1 — `ProductPriceChanged` como evento independiente

El bus actual de auditoría registraba dos filas distintas cuando el precio cambia:
`product.updated` + `product.price_changed`. Se mantiene el mismo comportamiento con
**dos eventos de dominio emitidos en el mismo `update()`**.

La entidad emite `ProductUpdated` siempre que algo cambie; y adicionalmente
`ProductPriceChanged` si `$newPrice !== $oldPrice`. El caso de uso publica
`...$product->pullDomainEvents()` (puede ser 1 o 2 eventos).

### D2 — `activate()` y `deactivate()` SÍ auditan

Family dejó `activate/deactivate` sin auditar (para no romper comportamiento anterior).
Product hoy SÍ audita ambas operaciones → los métodos de la entidad registrarán
`ProductActivated` / `ProductDeactivated` respectivamente.

### D3 — `delete()` en entidad

`DeleteProduct` usa `repository->deleteById()` sin llamar a ningún método de la entidad.
Se añade un método `delete()` al agregado (igual que en Family/Tables) que registra
`ProductDeleted`. El caso de uso llama `$product->delete()` antes de persistir
la eliminación.

### D4 — `UploadProductPhoto` sin `userId`

La subida de foto usa un token público (sin sesión HTTP). `RequestContext::userId()`
devolverá `null` y el subscriber de auditoría lo dejará vacío — idéntico al
comportamiento previo.

### D5 — Orden de emisión en `UpdateProduct`

```
1. product->update(...)
2. $events = $product->pullDomainEvents()
   → siempre: [ProductUpdated]
   → si precio cambió: [ProductUpdated, ProductPriceChanged]
3. repo->save($product)
4. eventBus->publish(...$events)
```

---

## Eventos de dominio a crear

Todos en `app/Product/Domain/Event/`. Todos implementan `AuditableEvent`.

### `ProductCreated`

```
auditSlug()       → 'product.created'
auditEntityType() → 'product'
auditEntityId()   → $productId (string)
auditMetadata()   → [product_name, price_cents, price_formatted, family_id, active]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName, int $priceCents, string $familyId, bool $active)`

### `ProductUpdated`

```
auditSlug()       → 'product.updated'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → []
auditBefore()     → [name, price_cents, family_id, tax_id, active, allergens, image_src]
auditAfter()      → [name, price_cents, family_id, tax_id, active, allergens, image_src]
```

Constructor: `(string $productId, array $before, array $after)`

### `ProductPriceChanged`

```
auditSlug()       → 'product.price_changed'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → [product_name, old_price_cents, old_price_formatted,
                      new_price_cents, new_price_formatted]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName, int $oldPriceCents, int $newPriceCents)`

### `ProductDeleted`

```
auditSlug()       → 'product.deleted'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → [product_name, price_cents, price_formatted]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName, int $priceCents)`

### `ProductActivated`

```
auditSlug()       → 'product.activated'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → [product_name]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName)`

### `ProductDeactivated`

```
auditSlug()       → 'product.deactivated'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → [product_name]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName)`

### `ProductPhotoUpdated`

```
auditSlug()       → 'product.photo_updated'
auditEntityType() → 'product'
auditEntityId()   → $productId
auditMetadata()   → [product_name, image_src]
auditBefore()     → []
auditAfter()      → []
```

Constructor: `(string $productId, string $productName, string $imageSrc)`

---

## Cambios en la entidad `Product`

Archivo: `app/Product/Domain/Entity/Product.php`

```diff
+ use App\Shared\Domain\Event\RecordsEvents;

  final class Product
  {
+     use RecordsEvents;

      public static function dddCreate(...): self
      {
          $product = new self(...);
+         $product->recordEvent(new ProductCreated(
+             $product->id()->value(),
+             $product->name()->value(),
+             $product->price()->value(),
+             $product->familyId()->value(),
+             $product->isActive(),
+         ));
          return $product;
      }

      public function update(...): void
      {
+         // Capture before-state
+         $before = $this->snapshot();
+         $oldPrice = $this->price->value();

          // ... existing mutation logic ...

+         if ($this->snapshot() === $before) return; // nothing changed
+
+         $this->recordEvent(new ProductUpdated(
+             $this->id()->value(), $before, $this->snapshot(),
+         ));
+         if ($this->price->value() !== $oldPrice) {
+             $this->recordEvent(new ProductPriceChanged(
+                 $this->id()->value(), $this->name()->value(),
+                 $oldPrice, $this->price->value(),
+             ));
+         }
      }

+     public function delete(): void
+     {
+         $this->recordEvent(new ProductDeleted(
+             $this->id()->value(),
+             $this->name()->value(),
+             $this->price()->value(),
+         ));
+     }

      public function activate(): void
      {
          if ($this->active) return;
          $this->active = true;
          $this->touch();
+         $this->recordEvent(new ProductActivated(
+             $this->id()->value(), $this->name()->value(),
+         ));
      }

      public function deactivate(): void
      {
          if (!$this->active) return;
          $this->active = false;
          $this->touch();
+         $this->recordEvent(new ProductDeactivated(
+             $this->id()->value(), $this->name()->value(),
+         ));
      }

      public function changeImage(ProductImageSrc $imageSrc): void
      {
          $this->imageSrc = $imageSrc;
          $this->touch();
+         $this->recordEvent(new ProductPhotoUpdated(
+             $this->id()->value(),
+             $this->name()->value(),
+             $imageSrc->value(),
+         ));
      }

+     private function snapshot(): array
+     {
+         return [
+             'name'       => $this->name->value(),
+             'price_cents'=> $this->price->value(),
+             'family_id'  => $this->familyId->value(),
+             'tax_id'     => $this->taxId->value(),
+             'active'     => $this->active,
+             'allergens'  => $this->allergens->values(),
+             'image_src'  => $this->imageSrc->value(),
+         ];
+     }
  }
```

**Nota sobre `update()`:** el método actual actualiza incondicionalmente. Hay que leer
el estado ANTES de mutar para construir `$before`. Si `$before === $after`, no se emite
nada (equivalente al comportamiento de Tax).

---

## Cambios en los use cases

### `CreateProduct`

```diff
- public function __construct(
-     private ProductRepositoryInterface $productRepository,
-     private AuditRecorderInterface $auditRecorder,
- ) {}
+ public function __construct(
+     private ProductRepositoryInterface $productRepository,
+     private EventBusInterface $eventBus,
+ ) {}

  $product = Product::dddCreate(...);
  $this->productRepository->save($product);
- $this->auditRecorder->record(new AuditEventDraft(...));
+ $this->eventBus->publish(...$product->pullDomainEvents());
```

### `UpdateProduct`

```diff
- public function __construct(..., AuditRecorderInterface $auditRecorder) {}
+ public function __construct(..., EventBusInterface $eventBus) {}

  $before = $product->snapshot(); // NOW the entity does this internally
  $product->update(...);
+ $events = $product->pullDomainEvents();
  $this->productRepository->save($product);
- $this->auditRecorder->record(new AuditEventDraft('product.updated', before, after));
- if ($priceChanged) $this->auditRecorder->record(new AuditEventDraft('product.price_changed', ...));
+ $this->eventBus->publish(...$events); // 1 or 2 events
```

El UC ya no necesita calcular `$before`/`$after` ni comparar precios — la entidad lo hace.

### `DeleteProduct`

```diff
- // (no entity method today)
+ $product->delete();
  $this->productRepository->deleteById($command->id);
- $this->auditRecorder->record(new AuditEventDraft('product.deleted', ...));
+ $this->eventBus->publish(...$product->pullDomainEvents());
```

### `SetProductActive`

```diff
  $product->activate() o $product->deactivate();
  $this->productRepository->save($product);
- $this->auditRecorder->record(new AuditEventDraft('product.activated/deactivated', ...));
+ $this->eventBus->publish(...$product->pullDomainEvents());
```

El UC ya no necesita el `if/else` de qué slug usar — la entidad emite el evento correcto.

### `UploadProductPhoto`

```diff
  $product->changeImage(...);
  $this->productRepository->save($product);
  $this->tokenRepository->markAsUsed(...);
- $this->auditRecorder->record(new AuditEventDraft('product.photo_updated', ...));
+ $this->eventBus->publish(...$product->pullDomainEvents());
  $this->notifier->uploaded(...);
```

---

## Cambios en HTTP Requests (toCommand)

Eliminar de cada `toCommand()` la extracción y paso de `userId`, `restaurantId`,
`deviceId`, `ipAddress`:

| Request | Campos a quitar de toCommand() |
|---|---|
| `CreateProductRequest` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `UpdateProductRequest` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `DeleteProductRequest` | `restaurantId`, `userId`, `deviceId`, `ipAddress` |
| `SetProductActiveRequest` | `restaurantId`, `deviceId`, `ipAddress` |
| `UploadProductPhotoRequest` | `deviceId`, `ipAddress` |

---

## Tests a escribir

### Unit — Entidad (`tests/Unit/Product/ProductEntityTest.php`)

| Test | Qué verifica |
|---|---|
| `dddCreate_records_ProductCreated` | `pullDomainEvents()` devuelve 1 evento `ProductCreated` |
| `update_records_ProductUpdated` | cambio de nombre → `ProductUpdated` |
| `update_records_both_events_when_price_changes` | cambio de precio → `[ProductUpdated, ProductPriceChanged]` |
| `update_records_nothing_when_nothing_changes` | mismos datos → `pullDomainEvents()` vacío |
| `delete_records_ProductDeleted` | `delete()` → `ProductDeleted` |
| `activate_records_ProductActivated` | `activate()` en producto inactivo → `ProductActivated` |
| `activate_records_nothing_if_already_active` | `activate()` en activo → sin eventos |
| `deactivate_records_ProductDeactivated` | `deactivate()` en activo → `ProductDeactivated` |
| `changeImage_records_ProductPhotoUpdated` | `changeImage()` → `ProductPhotoUpdated` |

### Unit — Use cases (`tests/Unit/Product/Application/`)

| Archivo | Tests |
|---|---|
| `CreateProductTest.php` | publica `ProductCreated`; lanza excepción si familia no existe (si aplica) |
| `UpdateProductTest.php` | publica `[ProductUpdated]` cuando solo cambia nombre; publica `[ProductUpdated, ProductPriceChanged]` cuando cambia precio; `eventBus->publish` no se llama si nothing changes |
| `DeleteProductTest.php` | llama `deleteById`, publica `ProductDeleted`; lanza `ProductNotFoundException` si no existe |
| `SetProductActiveTest.php` | activa → publica `ProductActivated`; desactiva → publica `ProductDeactivated` |
| `UploadProductPhotoTest.php` | foto OK → `changeImage` + publica `ProductPhotoUpdated`; token expirado → lanza excepción sin publicar |

### Feature — Audit end-to-end (`tests/Feature/Product/ProductAuditEventsTest.php`)

| Test | Qué verifica |
|---|---|
| `create_writes_audit_log` | POST /products → `audit_logs` tiene `product.created` |
| `update_writes_audit_log` | PUT /products/{id} → `audit_logs` tiene `product.updated` |
| `update_price_writes_two_audit_logs` | PUT con precio distinto → 2 filas (`product.updated` + `product.price_changed`) |
| `delete_writes_audit_log` | DELETE /products/{id} → `audit_logs` tiene `product.deleted` |
| `activate_writes_audit_log` | POST /products/{id}/activate → `product.activated` |
| `deactivate_writes_audit_log` | POST /products/{id}/deactivate → `product.deactivated` |

*(UploadProductPhoto no se prueba en feature por la complejidad del token + S3)*

---

## Fases de implementación

```
FASE 1 — Eventos de dominio (7 clases en Domain/Event/)
FASE 2 — Entidad Product (RecordsEvents + recordEvent en cada método + snapshot())
FASE 3 — Use cases (5): swap AuditRecorder → EventBus
FASE 4 — Commands (5): eliminar campos de auditoría
FASE 5 — HTTP Requests (5): limpiar toCommand()
FASE 6 — Tests unit entidad (9 casos)
FASE 7 — Tests unit use cases (5 archivos)
FASE 8 — Tests feature audit (6 casos)
FASE 9 — php artisan test (suite completa) + commit
```

> Regla: no pasar de fase hasta que la anterior compile sin errores.
> En FASE 3, el container de Laravel fallará si `AuditRecorderInterface` sigue inyectado —
> limpiar el provider si es necesario (aunque Product no tiene binding propio,
> Laravel resuelve `AuditRecorderInterface` del binding global en `AppServiceProvider`).
