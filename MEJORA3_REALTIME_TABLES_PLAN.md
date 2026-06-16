# Tiempo real de mesas — Plan de implementación

> **Objetivo**: cuando cualquier pedido cambia de estado (creado, marcado para cobrar, cancelado, eliminado, reabierto, transferido), todas las pantallas TPV conectadas se actualizan al instante — sin recarga manual.
>
> **Stack ya disponible**: Reverb en puerto 8080, `laravel-echo@2.3.7` + `pusher-js@8.5.0` en Angular, `EchoService`, `InMemorySyncEventBus` con `AuditEventSubscriber`, eventos de dominio de Order fluyendo por el bus.

---

## Progreso

| Paso | Descripción | Estado |
|------|-------------|--------|
| PASO 0 | Añadir `restaurantId` a los 6 eventos de dominio de Order + entidad | ✅ HECHO |
| PASO 1 | Crear evento de broadcast Laravel `OrderStatusChanged` | ✅ HECHO |
| PASO 2 | Crear `TablesBroadcastSubscriber` | ✅ HECHO |
| PASO 3 | Registrar subscriber en `AppServiceProvider` | ✅ HECHO |
| PASO 4 | Extender `EchoService` con `listen()` + `leaveChannel()` | ✅ HECHO |
| PASO 5 | Actualizar `MesasFacade` con suscripción y recarga parcial | ✅ HECHO |
| TESTS | `TablesBroadcastSubscriberTest` (backend unit) | ✅ HECHO — 8/8 |

---

## Arquitectura

```
Entidad Order → recordEvent()
  → UC extrae eventos → EventBus.publish()
      ├── AuditEventSubscriber      (existente) → audit_logs
      └── TablesBroadcastSubscriber (nuevo)     → event(OrderStatusChanged) → Reverb → Angular
```

El bus de eventos de dominio es síncrono e in-process. El broadcast a Reverb ocurre dentro del ciclo de la petición HTTP — no se necesita cola (`ShouldBroadcastNow`).

---

## Pre-requisito crítico: añadir `restaurantId` a los eventos de dominio de Order

**Problema**: los eventos de Order actuales solo llevan `orderUuid`. El subscriber de broadcast necesita `restaurantId` para enrutar al canal correcto (`restaurant.{restaurantId}`).

**Decisión**: añadir `restaurantId` como parámetro de constructor a cada uno de los 6 eventos de Order relevantes. La entidad `Order` ya tiene `$this->restaurantId` disponible en todos sus métodos — no se requieren cambios en los UCs.

**¿Por qué no consultar la BD en el subscriber?** Acoplaría el subscriber a persistencia, sería más lento y rompería el patrón. Todos los eventos de dominio deben ser autocontenidos para enrutarse solos.

---

## Implementación paso a paso

### PASO 0 — Enriquecer los eventos de dominio de Order con `restaurantId`

**Archivos**: `backend/app/Order/Domain/Event/{OrderCreated,OrderCancelled,OrderDeleted,OrderMarkedToCharge,OrderReopened,OrderTransferred}.php`

En cada evento, añadir:
```php
public function __construct(
    // ... parámetros existentes ...
    private string $restaurantId,  // ← nuevo
) {}

public function restaurantId(): string
{
    return $this->restaurantId;
}
```

**Archivo**: `backend/app/Order/Domain/Entity/Order.php`

Pasar `restaurantId` en cada llamada a `recordEvent()`:

```php
// dddCreate()
$order->recordEvent(new OrderCreated(
    orderUuid: $id->value(),
    tableUuid: $tableId->value(),
    diners: $diners->value(),
    restaurantId: $restaurantId->value(),   // ← nuevo
));

// markToCharge()
$this->recordEvent(new OrderMarkedToCharge(
    orderUuid: $this->uuid->value(),
    restaurantId: $this->restaurantId->value(),
));

// cancel()
$this->recordEvent(new OrderCancelled(
    orderUuid: $this->uuid->value(),
    restaurantId: $this->restaurantId->value(),
));

// reopen()
$this->recordEvent(new OrderReopened(
    orderUuid: $this->uuid->value(),
    restaurantId: $this->restaurantId->value(),
));

// transferTo()
$this->recordEvent(new OrderTransferred(
    orderUuid: $this->uuid->value(),
    fromTableId: $fromTableId,
    toTableId: $newTableId->value(),
    restaurantId: $this->restaurantId->value(),
));

// delete()
$this->recordEvent(new OrderDeleted(
    orderUuid: $this->uuid->value(),
    before: [...],
    restaurantId: $this->restaurantId->value(),
));
```

**Nota**: `AuditEventSubscriber` solo lee los métodos de la interfaz `AuditableEvent` (`auditSlug`, `auditEntityId`, etc.) — ignora el nuevo método `restaurantId()`. No hay que tocar el subscriber ni los tests de auditoría.

**Nota**: los tests unitarios de Order que construyen eventos directamente necesitarán añadir `restaurantId`.  
Revisar: `backend/tests/Unit/Order/` — cualquier test que llame `new OrderCreated(...)` directamente.

---

### PASO 1 — Evento de broadcast Laravel `OrderStatusChanged`

**Crear**: `backend/app/Order/Infrastructure/Broadcasting/OrderStatusChanged.php`

```php
<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

final class OrderStatusChanged implements ShouldBroadcastNow
{
    public function __construct(
        private readonly string $restaurantId,
        public readonly string $eventType,    // 'order.created', 'order.cancelled', etc.
        public readonly string $orderId,
        public readonly ?string $tableId = null,
        public readonly ?string $fromTableId = null,
        public readonly ?string $toTableId = null,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->restaurantId}");
    }

    public function broadcastAs(): string
    {
        return 'order.status_changed';
    }
}
```

Angular escuchará `.order.status_changed` (Echo antepone el punto automáticamente).

**Payload** que recibe el frontend:
```json
{
  "event_type": "order.created",
  "order_id": "uuid",
  "table_id": "uuid",
  "from_table_id": null,
  "to_table_id": null
}
```

---

### PASO 2 — `TablesBroadcastSubscriber`

**Crear**: `backend/app/Order/Infrastructure/Broadcasting/TablesBroadcastSubscriber.php`

```php
<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Broadcasting;

use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\OrderDeleted;
use App\Order\Domain\Event\OrderMarkedToCharge;
use App\Order\Domain\Event\OrderReopened;
use App\Order\Domain\Event\OrderTransferred;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\EventSubscriber;

final class TablesBroadcastSubscriber implements EventSubscriber
{
    public function subscribedTo(): array
    {
        return [
            OrderCreated::class,
            OrderCancelled::class,
            OrderDeleted::class,
            OrderMarkedToCharge::class,
            OrderReopened::class,
            OrderTransferred::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        $broadcast = match (true) {
            $event instanceof OrderCreated => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.created',
                orderId: $event->auditEntityId(),
                tableId: $event->auditMetadata()['table_id'],
            ),
            $event instanceof OrderMarkedToCharge => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.marked_to_charge',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderCancelled => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.cancelled',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderReopened => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.reopened',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderTransferred => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.transferred',
                orderId: $event->auditEntityId(),
                fromTableId: $event->auditMetadata()['from_table_id'],
                toTableId: $event->auditMetadata()['to_table_id'],
            ),
            $event instanceof OrderDeleted => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.deleted',
                orderId: $event->auditEntityId(),
                tableId: $event->auditBefore()['table_id'] ?? null,
            ),
            default => null,
        };

        if ($broadcast !== null) {
            event($broadcast);
        }
    }
}
```

---

### PASO 3 — Registrar el subscriber en `AppServiceProvider`

**Archivo**: `backend/app/Providers/AppServiceProvider.php`

```php
// Antes:
return new \App\Shared\Infrastructure\Event\InMemorySyncEventBus(
    $app->make(\App\Audit\Application\Subscriber\AuditEventSubscriber::class),
);

// Después:
return new \App\Shared\Infrastructure\Event\InMemorySyncEventBus(
    $app->make(\App\Audit\Application\Subscriber\AuditEventSubscriber::class),
    $app->make(\App\Order\Infrastructure\Broadcasting\TablesBroadcastSubscriber::class),
);
```

`TablesBroadcastSubscriber` no tiene dependencias en el constructor — `make()` equivale a `new`.

---

### PASO 4 — Extender `EchoService`

**Archivo**: `frontend/src/app/core/services/echo.service.ts`

Añadir dos métodos junto al `listenOnce()` existente:

```ts
listen<T>(
  channelName: string,
  eventName: string,
  handler: (data: T) => void,
): void {
  this.getEcho().channel(channelName).listen(`.${eventName}`, handler);
}

leaveChannel(channelName: string): void {
  this.echo?.leave(channelName);
}
```

`listenOnce` no se toca — lo usa la subida de fotos de producto (canales de un solo uso por token de subida).

---

### PASO 5 — Actualizar `MesasFacade`

**Archivo**: `frontend/src/app/features/tables/facades/mesas.facade.ts`

La facade necesita:
1. Inyectar `EchoService`
2. Tras la primera carga de datos, suscribirse a `restaurant.{restaurantId}` y gestionar eventos
3. Ante cualquier evento `order.status_changed`, hacer una **recarga parcial** (solo pedidos, sin zonas ni mesas — son datos estables)
4. Limpiar la suscripción al canal al destruirse

```ts
// Añadir a la clase:
private readonly echoService = inject(EchoService);
private restaurantChannelName: string | null = null;
private reloadingOrders = false;

// Modificar loadData():
public async loadData(): Promise<void> {
  this._loading.set(true);
  try {
    const [zones, tables, orders] = await Promise.all([...]);
    // ... actualizaciones de señales existentes ...

    // Suscribirse solo la primera vez
    if (!this.restaurantChannelName) {
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (currentUser?.restaurant_id) {
        this.subscribeToRestaurantChannel(currentUser.restaurant_id);
      }
    }
  } finally {
    this._loading.set(false);
  }
}

// Añadir:
private subscribeToRestaurantChannel(restaurantId: string): void {
  this.restaurantChannelName = `restaurant.${restaurantId}`;
  this.echoService.listen<OrderStatusChangedEvent>(
    this.restaurantChannelName,
    'order.status_changed',
    () => this.reloadOpenOrders(),
  );
}

private async reloadOpenOrders(): Promise<void> {
  if (this.reloadingOrders) return;
  this.reloadingOrders = true;

  try {
    const orders = await firstValueFrom(this.tpvService.listOrders());
    const activeOrders = orders.filter(
      (o) => o.status === OrderStatus.OPEN || o.status === OrderStatus.TO_CHARGE,
    );
    this._openOrders.set(activeOrders);

    const orderByTable = new Map(activeOrders.map((o) => [o.table_id, o]));
    const paidTotals = await this.fetchPaidTotals(activeOrders);

    this._tables.update((tables) =>
      tables.map((table) => {
        const order = orderByTable.get(table.id);
        const total = order?.total ?? 0;
        const paidTotal = order ? (paidTotals.get(order.id) ?? 0) : 0;
        return {
          ...table,
          occupied: !!order,
          status: order?.status,
          order_id: order?.id,
          diners: order?.diners,
          opened_at: order?.opened_at,
          total,
          remaining_total: Math.max(0, total - paidTotal),
        };
      }),
    );

    // Refrescar el puntero de mesa seleccionada si sigue existiendo
    const selectedId = this._selectedTable()?.id;
    if (selectedId) {
      const refreshed = this._tables().find((t) => t.id === selectedId) ?? null;
      this._selectedTable.set(refreshed);
    }
  } finally {
    this.reloadingOrders = false;
  }
}

// Añadir OnDestroy + limpieza:
ngOnDestroy(): void {
  if (this.restaurantChannelName) {
    this.echoService.leaveChannel(this.restaurantChannelName);
  }
}
```

Añadir la interfaz cerca de los imports:
```ts
interface OrderStatusChangedEvent {
  event_type: string;
  order_id: string;
  table_id?: string;
  from_table_id?: string;
  to_table_id?: string;
}
```

Añadir `implements OnDestroy` a la declaración de la clase e importar `OnDestroy` desde `@angular/core`.

**Verificar** que `currentUser.restaurant_id` es el nombre de campo correcto en el objeto de usuario de `AuthService`. Si no, ajustar el nombre — es la única suposición del plan.

---

## Tests unitarios a escribir

### Backend

**`backend/tests/Unit/Order/Infrastructure/Broadcasting/TablesBroadcastSubscriberTest.php`**

```php
// Matriz de tests: para cada uno de los 6 tipos de evento
// - el subscriber devuelve la clase del evento en subscribedTo()
// - handle() llama a event() con los parámetros correctos de OrderStatusChanged

// Para OrderDeleted en particular: table_id viene de auditBefore()['table_id']
// Para OrderTransferred: fromTableId y toTableId se rellenan correctamente
```

Usar `Event::fake()` de Laravel para verificar que se despachan los eventos de broadcast.

### Frontend

Sin tests unitarios para la suscripción WebSocket — las actualizaciones de señales de Angular desde canales asíncronos son difíciles de testear en aislamiento. Se apoya en el testing manual.

---

## Protocolo de prueba manual

1. Abrir dos pestañas del navegador en la página de mesas TPV, mismo restaurante.
2. En pestaña A: crear un pedido en una mesa → pestaña B debe mostrar la mesa como ocupada en ~1s.
3. En pestaña A: marcar pedido para cobrar → pestaña B debe mostrar el cambio de estado.
4. En pestaña A: cancelar pedido → pestaña B debe mostrar la mesa libre.
5. Repetir para traslado: mover pedido de mesa X a mesa Y → ambas mesas se actualizan en pestaña B.
6. Ejecutar `php artisan test --filter=TablesBroadcastSubscriber` para verificar el test unitario de backend.

---

## Fuera de alcance (posponer)

- **Autenticación de canal privado**: `restaurant.{restaurantId}` es un canal público por ahora. Añadir auth de presencia cuando el aislamiento multi-tenant sea un requisito.
- **Actualizaciones a nivel de línea**: añadir/eliminar líneas de pedido no dispara broadcast. La cuadrícula de mesas no necesita conteos de líneas.
- **Tiempo real de merge/unmerge**: los eventos de dominio de Table para fusión de mesas no existen aún. Se añadirán cuando se implemente el plano de salón.
- **Actualizaciones optimistas**: el frontend actualmente espera a que la mutación HTTP complete antes de llamar a `loadData()`. Con WebSocket, el llamador podría omitir su `loadData()` y confiar en el broadcast. Optimización posterior una vez el broadcast sea estable.
- **Deduplicar eventos rápidos**: si dos pedidos cambian consecutivamente, `reloadOpenOrders()` se dispara dos veces. El flag `reloadingOrders` colapsa la segunda llamada. Suficiente para MVP.

---

## Resumen de ficheros

| Fichero | Acción |
|---------|--------|
| `backend/app/Order/Domain/Event/Order{Created,Cancelled,Deleted,MarkedToCharge,Reopened,Transferred}.php` | añadir parámetro `restaurantId` + getter |
| `backend/app/Order/Domain/Entity/Order.php` | pasar `$this->restaurantId->value()` en cada `recordEvent()` |
| `backend/app/Order/Infrastructure/Broadcasting/OrderStatusChanged.php` | **crear** |
| `backend/app/Order/Infrastructure/Broadcasting/TablesBroadcastSubscriber.php` | **crear** |
| `backend/app/Providers/AppServiceProvider.php` | registrar `TablesBroadcastSubscriber` en el bus |
| `backend/tests/Unit/Order/Infrastructure/Broadcasting/TablesBroadcastSubscriberTest.php` | **crear** |
| `frontend/src/app/core/services/echo.service.ts` | añadir `listen()` + `leaveChannel()` |
| `frontend/src/app/features/tables/facades/mesas.facade.ts` | suscripción al canal, añadir `reloadOpenOrders()`, `ngOnDestroy` |
