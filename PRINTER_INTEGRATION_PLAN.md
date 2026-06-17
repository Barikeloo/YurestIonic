# Plan: Integración impresora térmica ESC/POS por TCP

## Objetivo
Imprimir el ticket de cierre de venta en una impresora ESC/POS de red (TCP puerto 9100).
Disparo automático al cerrar venta + botón manual en UI.
Config por zona: cada zona puede tener su propia impresora, con una impresora "por defecto" del restaurante como fallback.

---

## Arquitectura

```
[Sale cerrada] → SaleClosed event → PrintOnSaleClosedSubscriber
                                          ↓
                                   PrintFinalTicket use case
                                          ↓
                              resolve: saleId → orderId → zoneId → PrinterConfig
                                          ↓
                               EscPosTicketBuilder → bytes ESC/POS
                                          ↓
                           TcpEscPosPrinterService → TCP socket → impresora:9100
```

**Manual:** Frontend → POST /api/tpv/orders/{orderId}/print-ticket → mismo use case

---

## Modelo de datos

### Tabla `printer_configs`

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `uuid` | uuid unique | identificador externo |
| `restaurant_id` | bigint FK | |
| `name` | varchar(100) | "Sala", "Cocina", "Barra" |
| `ip` | varchar(45) | IPv4/IPv6 |
| `port` | smallint unsigned | default 9100 |
| `paper_width` | tinyint | 58 o 80 (mm) → 32 o 48 chars |
| `enabled` | boolean | default true |
| `is_default` | boolean | fallback del restaurante |
| timestamps + softDeletes | | |

### Tabla `zones` (modificación)

Añadir columna nullable: `printer_config_id` → FK a `printer_configs.id`

### Resolución de impresora al cerrar venta

```
Sale → order_id → Order → table_id → Table → zone_id → Zone → printer_config_id
  si Zone.printer_config_id IS NULL → fallback a PrinterConfig WHERE is_default=true AND restaurant_id=X
  si no hay default → skip silencioso (log warning)
```

---

## Evento `SaleClosed` — cambio mínimo

El evento actual tiene `saleId` pero no `orderId`. El subscriber necesita el orderId para resolver la cadena.
Se añade `orderId` a `SaleClosed` y se pasa desde `UpdateSale`.

---

## ESC/POS — comandos a implementar

`EscPosTicketBuilder` genera bytes usando estos comandos:

| Comando | Bytes | Uso |
|---|---|---|
| Initialize | `\x1b\x40` | Al inicio |
| Align center | `\x1b\x61\x01` | Cabecera restaurante |
| Align left | `\x1b\x61\x00` | Líneas de producto |
| Bold on | `\x1b\x45\x01` | Totales, títulos |
| Bold off | `\x1b\x45\x00` | |
| Double size | `\x1d\x21\x11` | TOTAL |
| Normal size | `\x1d\x21\x00` | |
| Line feed | `\x0a` | Saltos de línea |
| Full cut | `\x1d\x56\x00` | Al final del ticket |

Encoding: CP437 (Latin/ASCII) — suficiente para español con sustitución de caracteres especiales.

---

## Estructura de ficheros a crear

```
backend/app/Printer/
  Domain/
    Entity/PrinterConfig.php
    ValueObject/PrinterIp.php
    ValueObject/PrinterPort.php
    ValueObject/PrinterPaperWidth.php
    Interfaces/PrinterConfigRepositoryInterface.php
    Interfaces/PrinterServiceInterface.php
    Exception/PrinterConfigNotFoundException.php
    Exception/PrinterConnectionException.php
  Application/
    PrintFinalTicket/
      PrintFinalTicket.php           ← use case principal
      PrintFinalTicketCommand.php
    UpsertPrinterConfig/
      UpsertPrinterConfig.php
      UpsertPrinterConfigCommand.php
      UpsertPrinterConfigResponse.php
    DeletePrinterConfig/
      DeletePrinterConfig.php
      DeletePrinterConfigCommand.php
    ListPrinterConfigs/
      ListPrinterConfigs.php
      ListPrinterConfigsCommand.php
      ListPrinterConfigsResponse.php
    TestPrinterConfig/
      TestPrinterConfig.php
      TestPrinterConfigCommand.php
    Subscriber/
      PrintOnSaleClosedSubscriber.php ← reacciona a SaleClosed
  Infrastructure/
    Persistence/
      Models/EloquentPrinterConfig.php
      Repositories/EloquentPrinterConfigRepository.php
    Printing/
      EscPosTicketBuilder.php         ← genera bytes ESC/POS desde ticket data
      TcpEscPosPrinterService.php     ← envía bytes por TCP socket
    Entrypoint/Http/
      ListPrinterConfigsController.php
      UpsertPrinterConfigController.php
      DeletePrinterConfigController.php
      TestPrinterConfigController.php
      PrintFinalTicketController.php  ← endpoint manual
      Requests/UpsertPrinterConfigRequest.php

backend/database/migrations/
  xxxx_create_printer_configs_table.php
  xxxx_add_printer_config_id_to_zones_table.php

backend/app/Sale/Domain/Event/SaleClosed.php   ← MODIFICADO: añadir orderId
backend/app/Sale/Application/UpdateSale/UpdateSale.php ← MODIFICADO: pasar orderId al evento
backend/app/Providers/AppServiceProvider.php   ← MODIFICADO: bindings + subscriber
backend/routes/api.php                         ← MODIFICADO: nuevas rutas

frontend/src/app/features/printer/
  services/printer-config.service.ts
  pages/printer-settings/
    printer-settings.page.ts
    printer-settings.page.html
    printer-settings.page.scss

frontend/src/app/features/orders/facades/pedidos.facade.ts  ← MODIFICADO: API call en vez de printWindow
frontend/src/app/features/cash/ui/payment-success/          ← MODIFICADO: auto-print hook
```

---

## Pasos de implementación

### PASO 1 — Modificar `SaleClosed` + `UpdateSale` para incluir `orderId` `[ ]`

Añadir campo `orderId` al evento y pasarlo desde `UpdateSale` (la `Sale` tiene `orderId()`).

### PASO 2 — Migraciones `[ ]`

- `create_printer_configs_table` — tabla con todos los campos del modelo de datos
- `add_printer_config_id_to_zones_table` — columna nullable + FK

### PASO 3 — Domain: `PrinterConfig` entity + value objects + interfaces `[ ]`

- `PrinterIp` — valida formato IPv4/IPv6, máx 45 chars
- `PrinterPort` — valida rango 1-65535
- `PrinterPaperWidth` — enum: 58 → 32 chars, 80 → 48 chars
- `PrinterConfig` entity — aggregate sin eventos de dominio (CRUD simple)
- `PrinterConfigRepositoryInterface` — findById, findByZoneId, findDefaultForRestaurant, findAllForRestaurant, save, delete
- `PrinterServiceInterface` — `send(string $ip, int $port, string $bytes): void`

### PASO 4 — Infrastructure: persistence `[ ]`

- `EloquentPrinterConfig` Eloquent model
- `EloquentPrinterConfigRepository` implementando la interfaz

### PASO 5 — Infrastructure: ESC/POS printer service `[ ]`

**`EscPosTicketBuilder`**: recibe el array que devuelve `GetFinalTicketPrint::toArray()` y produce bytes ESC/POS:
- Init printer
- Cabecera restaurante centrada y en negrita
- Línea "FACTURA SIMPLIFICADA · Nº xxx"
- Divisores como `\x1b\x61\x00` + cadena de guiones
- Líneas de producto (descripción, qty, importe)
- Desglose IVA
- TOTAL en doble tamaño + negrita
- Métodos de pago
- "GRACIAS POR SU VISITA" centrado
- Corte de papel `\x1d\x56\x00`

**`TcpEscPosPrinterService`**: implementa `PrinterServiceInterface`:
```php
public function send(string $ip, int $port, string $bytes): void
{
    $socket = fsockopen($ip, $port, $errno, $errstr, 3.0);
    if ($socket === false) throw PrinterConnectionException::cannotConnect($ip, $port, $errstr);
    fwrite($socket, $bytes);
    fclose($socket);
}
```

### PASO 6 — Application: `PrintFinalTicket` use case `[ ]`

Inyecta: `GetFinalTicketPrint`, `PrinterConfigRepository`, `EscPosTicketBuilder`, `PrinterServiceInterface`, `OrderRepository`, `TableRepository`.

Flujo:
1. `GetFinalTicketPrint` → datos del ticket
2. Resolver PrinterConfig: orderId → Order → tableId → Table → zoneId → zone printer_config → fallback default
3. Si no hay config habilitada → lanza `PrinterConfigNotFoundException`
4. `EscPosTicketBuilder::build(ticketData, paperWidth)` → bytes
5. `PrinterServiceInterface::send(ip, port, bytes)`

### PASO 7 — Application: `PrintOnSaleClosedSubscriber` `[ ]`

Implementa `EventSubscriber`, suscrito a `SaleClosed`.
Llama a `PrintFinalTicket` con el `orderId` del evento.
Si la impresora no está configurada → skip silencioso (no lanza, solo loguea).
Si falla la conexión TCP → loguea el error pero NO relanza (no quiere rollback de la venta).

### PASO 8 — Application: CRUD use cases de config `[ ]`

- `UpsertPrinterConfig` — create/update (UUID en command → update si existe, create si no)
- `DeletePrinterConfig`
- `ListPrinterConfigs` — lista todas las impresoras del restaurante con su zona asignada
- `TestPrinterConfig` — imprime una página de test (texto fijo "TEST OK" + corte)

### PASO 9 — HTTP controllers + rutas `[ ]`

Rutas bajo `/api/admin/printers`:
```
GET    /api/admin/printers              → ListPrinterConfigsController
POST   /api/admin/printers              → UpsertPrinterConfigController
PUT    /api/admin/printers/{uuid}       → UpsertPrinterConfigController
DELETE /api/admin/printers/{uuid}       → DeletePrinterConfigController
POST   /api/admin/printers/{uuid}/test  → TestPrinterConfigController
```

Ruta manual en TPV:
```
POST   /api/tpv/orders/{orderId}/print-ticket → PrintFinalTicketController
```

### PASO 10 — Registrar bindings + subscriber en `AppServiceProvider` `[ ]`

- Bind `PrinterConfigRepositoryInterface → EloquentPrinterConfigRepository`
- Bind `PrinterServiceInterface → TcpEscPosPrinterService`
- Añadir `PrintOnSaleClosedSubscriber` al `InMemorySyncEventBus`

### PASO 11 — Frontend: servicio + settings UI `[ ]`

**`PrinterConfigService`** — CRUD contra `/api/admin/printers` + test

**`printer-settings.page`**:
- Lista de impresoras configuradas (nombre, IP:puerto, ancho papel, habilitada, zona asignada)
- Formulario (panel lateral o modal): nombre, IP, puerto (default 9100), papel (58/80mm), habilitada, "impresora por defecto"
- Dropdown "Zona asignada" (opciones: ninguna + lista de zonas del restaurante)
- Botón "Test" → llamada a POST /test + feedback toast
- Se accede desde la sección de Configuración del admin

### PASO 12 — Frontend: reemplazar `printWindow` por llamada API `[ ]`

En `pedidos.facade.ts`:
- `printSelectedTicket()` → `POST /api/tpv/orders/{id}/print-ticket`
- Toast "Imprimiendo..." → "Ticket enviado a impresora" / "Error al imprimir"
- Si no hay impresora configurada → toast informativo, sin error crítico

En `payment-success.component`:
- Revisar si ya tiene lógica de auto-print o si el subscriber del backend lo gestiona todo
- El subscriber del backend gestiona el auto-print; el botón manual del frontend es independiente

### PASO 13 — Verificación end-to-end `[ ]`

```bash
# Backend: tests unitarios del subscriber y use case
docker exec training_api php artisan test --filter Printer

# Verificar rutas
docker exec training_api php artisan route:list | grep printer

# Test manual: configurar IP de una impresora real o simulador ESC/POS
# (e.g. ESC/POS virtualPrinter en localhost para testing)
```

---

## Lo que NO cambia

- `TicketTextFormatter` — sigue usándose para el endpoint `?format=text` (ventana del navegador como fallback)
- `GetFinalTicketPrint` use case — se reutiliza internamente en `PrintFinalTicket`
- Flujo de cierre de venta (`UpdateSale`) — sólo se añade `orderId` al evento, sin más cambios
- Endpoints existentes de impresión por texto/JSON — se mantienen

---

## Orden de ejecución recomendado

1 → 2 → 3 → 4 → 5 → 6 → 7 → 10 (backend completo, verificar subscriber)
→ 8 → 9 → 10 (CRUD + rutas)
→ 11 → 12 (frontend)
→ 13 (verificación)
