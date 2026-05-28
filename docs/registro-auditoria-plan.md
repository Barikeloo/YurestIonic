# Registro de Auditoría — Plan de implementación

> Documento de seguimiento. Recoge el estado actual, las decisiones de diseño pendientes y el plan por hitos para llevar la página `Registro de Auditoría` (ya maquetada en el front con datos mock) a un módulo real conectado con el backend y el TPV.

Última actualización: 2026-05-26

---

## 1. Estado actual (lo que ya hay vs. lo que falta)

### Frontend
Archivo: `frontend/src/app/pages/core/registro-auditoria/`

- 15 eventos hardcodeados en el array `EVENTS`. Filtros 100% en cliente.
- UI rica: KPIs, waterfall, drawer con diff/inline, payload JSON, hash de integridad, "live tail" simulado (contador), vistas guardadas, chips inteligentes.
- 9 categorías (`all, order, caja, sale, table, catalog, auth, config, system`) y 5 severidades (`info, warning, danger, critical, success`).
- Modelo `AuditEvent` rico: `cat, sev, action, entity, entityLabel, amount, user, device, ip, session, summary, reason, diff, inline, payload, related, actions`.

### Backend
- **Existe**: migración `backend/database/migrations/2026_04_22_000500_create_audit_logs_table.php` con `entity_type, entity_id, action, before, after, user_id, ip_address, device_id, created_at` (+ índices por `restaurant_id`).
- **No existe**: módulo `app/Audit/`, controllers, rutas, ni nadie llamando a "record audit event" desde `Order/`, `Sale/`, `Caja/`, `User/`, `Menu/`.

### Gap principal
El front pinta 10–12 conceptos que la tabla actual **no almacena**: `category`, `severity`, `summary`, `reason`, `session_id`, `related_ids`, `anomaly`, `integrity_hash`. Esta brecha condiciona la primera decisión de diseño.

---

### Cobertura de auditoría — instrumentados vs. pendientes

#### ✅ Instrumentados (18 slugs)

| Slug | Use case | Categoría | Severidad |
|---|---|---|---|
| `auth.login_pin_ok` | `User/AuthenticateUserByPin` | auth | success |
| `auth.login_pin_failed` | `User/AuthenticateUserByPin` (rama de fallo) | auth | critical |
| `auth.device_link` | `User/AuthenticateForDeviceLink` | auth | info |
| `order.created` | `Order/CreateOrder` | order | info |
| `order.marked_to_charge` | `Order/MarkOrderToCharge` | order | warning |
| `order.reopened` | `Order/ReopenOrder` | order | danger |
| `order.transferred` | `Order/TransferOrder` | order | info |
| `order.cancelled` | `Order/CancelOrder` | order | danger |
| `caja.opened` | `Cash/OpenCashSession` | caja | info |
| `caja.closed` | `Cash/CloseCashSession` | caja | success |
| `caja.force_closed` | `Cash/ForceCloseCashSession` | caja | critical |
| `caja.cash_movement` | `Cash/RegisterCashMovement` | caja | warning |
| `sale.created` | `Sale/CreateSale` | sale | success |
| `sale.credit_note_issued` | `Sale/CreateCreditNote` | sale | danger |
| `product.activated` | `Product/SetProductActive` (rama activar) | catalog | info |
| `product.deactivated` | `Product/SetProductActive` (rama desactivar) | catalog | info |
| `product.price_changed` | `Product/UpdateProduct` (condicional si cambia precio) | catalog | warning |
| `table.merged` | `Tables/MergeTables` | table | info |

#### 🚧 Pendientes (por instrumentar)

| Módulo | Slug propuesto | Use case | Prioridad |
|---|---|---|---|
| **Order** | `order.line_added` | `Order/AddOrderLine` | Alta |
| **Order** | `order.line_removed` | `Order/DeleteOrderLine` | Alta |
| **Product** | `product.created` | `Product/CreateProduct` | Media |
| **Product** | `product.updated` | `Product/UpdateProduct` (siempre) | Media |
| **Product** | `product.deleted` | `Product/DeleteProduct` | Media |
| **Sale** | `sale.cancelled` | `Sale/CancelSale` | Alta |
| **Tables** | `table.created` | `Tables/CreateTable` | Media |
| **Tables** | `table.updated` | `Tables/UpdateTable` | Media |
| **Tables** | `table.deleted` | `Tables/DeleteTable` | Media |
| **Tables** | `table.unmerged` | `Tables/UnmergeTables` | Media |
| **Family** | `family.created` | `Family/CreateFamily` | Baja |
| **Family** | `family.updated` | `Family/UpdateFamily` | Baja |
| **Family** | `family.deleted` | `Family/DeleteFamily` | Baja |
| **Zone** | `zone.created` | `Zone/CreateZone` | Baja |
| **Zone** | `zone.updated` | `Zone/UpdateZone` | Baja |
| **Zone** | `zone.deleted` | `Zone/DeleteZone` | Baja |
| **Tax** | `tax.created` | `Tax/CreateTax` | Baja |
| **Tax** | `tax.updated` | `Tax/UpdateTax` | Baja |
| **Tax** | `tax.deleted` | `Tax/DeleteTax` | Baja |
| **User** | `user.created` | `User/CreateUser` | Media |
| **User** | `user.updated` | `User/UpdateRestaurantUser` | Media |
| **User** | `user.deleted` | `User/DeleteRestaurantUser` | Media |
| **Menu** | `menu.created` | `Menu/CreateMenu` | Baja |
| **Menu** | `menu.updated` | `Menu/UpdateMenu` | Baja |
| **Menu** | `menu.activated` | `Menu/SetMenuActive` (rama activar) | Baja |
| **Menu** | `menu.deactivated` | `Menu/SetMenuActive` (rama desactivar) | Baja |
| **Menu** | `menu.archived` | `Menu/ArchiveMenu` | Baja |

---

## 2. Decisiones de diseño pendientes

Hasta que estas decisiones no se cierren, no se escribe código.

### A. ¿Persistir los campos derivados o calcularlos al leer?

| Enfoque | Pros | Contras |
|---|---|---|
| **A1. Persistir** (`category`, `severity`, `summary`, `reason`, `session_id`, `integrity_hash`, `metadata`) | Lectura barata; `summary` inmutable aunque cambie el código; auditoría real (hash protege manipulación). | Migración mayor; cada emisor rellena más campos. |
| **A2. Derivar al leer** desde `action`+`entity_type`+`before/after` vía catálogo en Domain. | Tabla mínima; refactor de mensajes "gratis". | Pierdes la auditoría inmutable: renombrar la acción reescribe el pasado. |

**Recomendación:** A1 con híbrido — categoría y severidad las decide un catálogo central pero se **persisten** al insertar.

**Decisión tomada:** ✅ **A1 híbrido** (2026-05-26). El catálogo decide `category`, `severity` y `summary` a partir del `action` slug, pero se persisten en la fila al insertar.

Campos que añade la migración de extensión del Hito 1:
- `category` (string, indexado)
- `severity` (string, indexado)
- `summary` (string ~500 chars, lo que se ve en la fila)
- `reason` (text nullable)
- `session_id` (uuid nullable — sesión de caja activa)
- `anomaly_kind` (string nullable)
- `integrity_hash` + `prev_hash` (char 64)
- `metadata` (json — slot libre: `amount`, `entity_label`, etc.)

### B. ¿Cómo emiten los eventos los otros módulos?

| Patrón | Encaja con `ddd-controller-pattern` | Esfuerzo |
|---|---|---|
| **B1.** Inyectar `AuditRecorderInterface` en cada use case y llamar `record(...)` al final. | Sí — "side-effects orchestrated in the use case". | Bajo, explícito, testeable. |
| **B2.** Domain events + listener (audit escucha eventos). | DDD puro. | Requiere un event bus que no existe en el repo. |
| **B3.** Observers de Eloquent. | **No** — viola "Eloquent solo en Infrastructure/Persistence". | Anti-patrón aquí. |

**Recomendación:** B1.

**Decisión tomada:** ✅ **B1 con fallo silencioso** (2026-05-26).

- Interface `AuditRecorderInterface` en `app/Audit/Domain/Interfaces/`, implementada por `EloquentAuditRecorder` en Persistence.
- Cada use case que muta estado inyecta el recorder y llama `record(...)` **después** del éxito del repositorio (nunca antes).
- Si el `record(...)` falla: `report($e)` y la operación de negocio continúa. Un TPV no se cae por auditoría caída — el hueco se cubre con alerting/monitorización.

### C. ¿Quién decide categoría y severidad de cada acción?

Opciones:
- En el emisor (cada use case sabe su categoría) → conocimiento distribuido, fácil de divergir.
- **Catálogo central** `app/Audit/Domain/AuditEventCatalog` con map `'order.line_voided' => [category: 'order', severity: 'warning']`.
- Híbrido: el emisor pasa `action` (slug estable), el catálogo resuelve categoría/severidad.

**Recomendación:** catálogo central + `action` como slug (`order.created`, `caja.closed_with_mismatch`, `auth.login_failed`). Una sola fuente de verdad.

**Decisión tomada:** ✅ **Catálogo central + slugs + placeholders simples** (2026-05-26).

- `app/Audit/Domain/AuditEventCatalog.php` mantiene el mapa `slug => [category, severity, summaryTemplate]`.
- El use case solo conoce su slug. El `EloquentAuditRecorder` consulta el catálogo y persiste `category`, `severity`, `summary` ya renderizado.
- Slug desconocido → `UnknownAuditActionException`. Nunca se inserta con valores por defecto.
- `summaryTemplate` usa placeholders simples (`{entityLabel}`, `{metadata.amount}`). Si una acción necesita formateo complejo, el use case precomputa el valor en `metadata` (ej. `metadata.amount_formatted = "−50,00 €"`) y el template solo lo sustituye.

### D. Alcance del MVP — ¿qué eventos se instrumentan primero?

Propuesta MVP demostrable:

| Módulo | Eventos MVP | Justificación |
|---|---|---|
| **Auth** | `login_ok`, `login_failed`, `device_link` | Sensible legalmente; `AuthenticateForDeviceLink` ya está al patrón. |
| **Order** | `order.created`, `order.line_voided`, `order.transferred`, `order.reopened`, `order.marked_to_charge` | `reopened` y `line_voided` son típicos para detectar fraude. |
| **Caja** | `caja.opened`, `caja.closed`, `caja.closed_with_mismatch`, `caja.cash_movement` | Caso clásico de auditoría en hostelería. |
| **Sale** | `sale.refunded`, `sale.partial_refund`, `sale.voided` | Cualquier devolución debe quedar trazada. |
| **Catalog/Config** | `product.price_changed`, `settings.changed` | Diferido a hito posterior si no hay tiempo. |

Las categorías `table` y `system` quedan **roadmap declarado**, no implementado.

**Decisión tomada:** ✅ **Cobertura completa del prototipo, 18 slugs** (2026-05-26).

Los 18 slugs instrumentados y los pendientes se detallan en la sección **"Cobertura de auditoría — instrumentados vs. pendientes"** al inicio de este documento. Comprobación previa: los use cases existentes y sus Response DTOs ya están al patrón DDD (constructor privado + factory `create`, sin `success/statusCode/message`). No requieren refactor previo, solo inyectar el recorder.

**Casos especiales:**

- **`auth.login_pin_failed`**: el use case `AuthenticateUserByPin` audita el fallo **antes** de lanzar `InvalidCredentialsException`. La excepción sigue subiendo al controller para devolver 401. Audit es side-effect del intento, no del éxito.
- **`product.price_changed`**: `UpdateProduct` siempre audita `product.updated` (no MVP, queda como evento general que se podría añadir más adelante). Si `before.price !== after.price`, audita **adicionalmente** `product.price_changed`. Patrón "evento general + evento específico para cambios sensibles" → permite filtrar en el front "muéstrame solo cambios de precio" sin parsear diffs.

Las categorías `config` y `system` quedan **roadmap** — el front ya las muestra como tab pero no se instrumentan en MVP.

### E. Filtros y paginación

- Paginación: cursor por `(created_at DESC, id DESC)`, `limit` 50.
- Filtros server-side: `category`, `severity`, `user_id`, `device_id`, `date_from`, `date_to`, `q` (action+summary+entity), `anomaly_only`.
- "Live tail" MVP: polling cada 5s con `since=<last_uuid>`. SSE/WebSocket → roadmap.

**Decisión tomada:** ✅ **Cursor opaco + filtros single-select** (2026-05-26).

Endpoint `GET /api/audit-log` acepta:

| Parámetro | Tipo | Comportamiento |
|---|---|---|
| `category` | string | Single-select |
| `severity` | string | Single-select |
| `user_id` | uuid | |
| `device_id` | string | |
| `date_from` / `date_to` | ISO date | Sin límite si no se envían |
| `q` | string ≥ 2 chars | LIKE en `summary`, `action`, `entity_id` |
| `anomaly_only` | bool | `anomaly_kind IS NOT NULL` |
| `cursor` | string opaco | Cursor de paginación |
| `since` | uuid | Modo live tail — eventos posteriores a ese uuid en orden ASC |

Response:
```json
{
  "data": [ /* hasta 50 eventos */ ],
  "next_cursor": "eyJ..." | null,
  "has_more": true|false
}
```

- `limit` fijo a 50 en servidor (no expuesto al cliente).
- Orden siempre `created_at DESC, id DESC` salvo `since=` que devuelve ASC.
- Search MVP con `LIKE` (acotado por `restaurant_id + fecha`). Fulltext index → roadmap.
- Validación en `ListAuditEventsRequest`. Controller no toca lógica.

### F. Integridad y anomalías

- **Hash SHA-256**: si se calcula al leer no protege nada. Persistirlo al insertar con encadenamiento ligero (`prev_hash`) por restaurante demuestra rigor.
- **Anomalías** MVP: `auth_failed_burst` (≥3 fallos en 5 min, mismo usuario) y `caja_mismatch`. El resto, roadmap.

**Decisión tomada:** ✅ **Hash encadenado por restaurante + detección server-side en recorder** (2026-05-26).

**F.1 — Integridad por cadena de hash:**
- Cada fila almacena `integrity_hash` y `prev_hash`.
- `integrity_hash = SHA-256(prev_hash + uuid + restaurant_id + created_at + action + entity_type + entity_id + user_id + summary + before_json + after_json + metadata_json)`.
- Cadena **por restaurante** (no global): cada restaurante es una cadena independiente.
- Lock concurrente en el insert: `SELECT ... FOR UPDATE` de la cabecera de cadena del restaurante dentro de transaction. Si el throughput crece, se migra a una tabla `audit_chain_head` con UPDATE atómico (roadmap).
- Verificación: el front pinta "✓ Verificado" si el hash recalculado coincide. Endpoint `/api/audit-log/verify` (roadmap) escanea la cadena entera y reporta filas rotas.
- **No protege**: filas falsas insertadas desde la app (la cadena prueba integridad, no autenticidad), ni borrado masivo (mitigación roadmap: firma externa del último hash diario).

**F.2 — Detección de anomalías en el recorder:**

`app/Audit/Domain/AnomalyDetector.php` (puro PHP, sin Laravel). Recibe el draft del evento + `AuditLogRepositoryInterface` para consultar eventos recientes. Devuelve `?string` con `anomaly_kind` o `null`.

Dos reglas MVP:
- **`auth_failed_burst`**: al insertar `auth.login_pin_failed`, contar `auth.login_pin_failed` del mismo `user_id` en los últimos 5 min. Si ≥3 → marcar la fila actual con `anomaly_kind = 'auth_failed_burst'`.
- **`caja_mismatch`**: al insertar `caja.closed` o `caja.force_closed`, si `metadata.delta_final_cents !== 0` → `anomaly_kind = 'caja_mismatch'`.

Roadmap de reglas: `order_reopened_after_charge_window`, `high_value_refund_solo`, `price_change_outside_hours`, etc.

### G. Multi-tenant y permisos

- Todos los endpoints bajo `ResolveTenantContext` (middleware ya existente en `routes/api.php`).
- Acceso a la página: solo `role = admin` (provisional, mientras no haya sistema de permisos maduro). Resto → 403.

**Decisión tomada:** ✅ **Tenant context estándar + acceso `role = admin`** (2026-05-26).

- **Aislamiento multi-tenant** (no es opción, es estándar): rutas bajo `ResolveTenantContext`. `EloquentAuditLogRepository` filtra siempre por `restaurant_id` desde `TenantContext`. `EloquentAuditRecorder` toma `restaurant_id` del mismo contexto al insertar. Domain/Application no conocen `TenantContext`.
- **Permiso de lectura**: `ListAuditEventsController` y `GetAuditEventController` verifican que el usuario autenticado tenga `role === 'admin'` antes de invocar el use case. Si no, `403 Forbidden`.
- **Front**: guard en `/registro-auditoria` y ocultar el botón "Auditoría" en el panel de gestión si el usuario no es admin (mejor UX).
- **Write API**: no existe. La auditoría solo se escribe desde el `EloquentAuditRecorder` invocado por otros use cases del backend. Esto da sentido al hash de integridad.

### H. Retención

- MVP: no purgar; declarar política (≥ 6 años por motivos fiscales en hostelería).
- Roadmap: job de archivado a almacenamiento frío.

**Decisión tomada:** _pendiente_

---

## 3. Plan por hitos

> Cada hito presupone las decisiones cerradas. Marcar tareas con `[x]` a medida que se completen.

### Hito 1 — Backend skeleton (sin instrumentación) ✅ Completado (2026-05-26)
Módulo `app/Audit/` creado siguiendo `ddd-controller-pattern`:

- [x] Migración de extensión: `category, severity, summary, reason, session_id, anomaly_kind, integrity_hash, prev_hash, metadata` + 3 índices
- [x] `Domain/`: `AuditLog` entity, VOs (`Category, Severity, ActionSlug`), `AuditEventCatalog` con los 18 slugs, `AnomalyDetector`, `AuditChainHasher`, `AuditEventDraft`, `ListAuditLogsCriteria`, `AuditLogPage`, exceptions (`AuditLogNotFoundException`, `UnknownAuditActionException`), interfaces (`AuditLogRepositoryInterface`, `AuditRecorderInterface`). **0 imports de Laravel.**
- [x] `Application/ListAuditEvents/{UseCase, Command, AuditEventItemResponse, Response}` con cursor opaco base64
- [x] `Application/GetAuditEvent/{UseCase, Command, Response}` con `fromAuditLog` factory
- [x] `Infrastructure/Persistence/`: `Models/EloquentAuditLog` (HasTenantScope, timestamps=false), `Repositories/EloquentAuditLogRepository` (save, findByUuid, list con filtros/cursor/since, `lockAndGetLastHashForRestaurant` con FOR UPDATE, `countRecentByActionAndUser`), `EloquentAuditRecorder` (orquesta catálogo + detector + hash + persist con fallo silencioso)
- [x] `Infrastructure/Entrypoint/Http/`: `ListAuditEventsController`, `GetAuditEventController`, `Requests/ListAuditEventsRequest`, `Requests/GetAuditEventRequest`
- [x] Rutas en `routes/api.php`: `GET /api/admin/audit-log` y `GET /api/admin/audit-log/{uuid}` bajo `RequireAdminSession`
- [x] Bindings en `AppServiceProvider`: `AuditLogRepositoryInterface → EloquentAuditLogRepository`, `AuditRecorderInterface → EloquentAuditRecorder`
- [x] `AuditLogSeeder` con 50 eventos por restaurante (sólo restaurantes con usuarios), cadena de hash íntegra verificada, 5 severidades + 6 categorías cubiertas, ~14 con `anomaly_kind`
- [x] End-to-end verificado: catálogo resuelve summaries, hashes encadenados se recomputan correctos, use case `ListAuditEvents` devuelve 50 items con paginación funcional

### Hito 2 — Frontend integración ✅ Completado (2026-05-26)
- [x] `AuditLogService` (`list`, `get`)
- [x] Sustituir `EVENTS` por signal cargada del backend
- [x] Mover filtros a server-side (parámetros HTTP) con debounce y race-condition safe
- [x] Scroll infinito / paginación por cursor
- [x] Cargar usuarios y dispositivos desde sus endpoints (no hardcodear)
- [x] Polling 5s con `liveTail()` activo
- [x] Date range por defecto = hoy

### Hito 3 — Instrumentación completa del prototipo ✅ Completado (2026-05-26)
Para cada use case de Decisión D, inyectar `AuditRecorderInterface` en el constructor y llamar tras éxito (o tras fallo en el caso de `login_pin_failed`) con `before/after`, `slug` del catálogo y `metadata`.

- [x] Auth: `login_pin_ok`, `login_pin_failed`, `device_link`
- [x] Order: `created`, `marked_to_charge`, `reopened`, `transferred`, `cancelled`
- [x] Caja: `opened`, `closed`, `force_closed`, `cash_movement`
- [x] Sale: `created`, `credit_note_issued`
- [x] Catalog: `product.activated`, `product.deactivated`, `product.price_changed`
- [x] Tables: `table.merged`

### Hito 4 — Hardening
> Nota: cadena de hash, ambos detectores de anomalía y permiso `role = admin` ya se entregaron como parte del Hito 1.
- [x] Endpoint admin `GET /api/admin/audit-log/verify` que recalcula la cadena y reporta filas rotas
- [ ] Alerting cuando aparece una anomalía crítica (Slack/email)
- [ ] Vistas guardadas persistidas por usuario (tabla `audit_saved_views`) — opcional

### Hito 4b — Fix de cobertura `device_id` + `ip_address` ✅ Completado (2026-05-26)
Fix aplicado: todos los use cases instrumentados en Hito 3 ahora pasan `deviceId` e `ipAddress` al `AuditEventDraft`. El interceptor del front ya envía `X-Device-Id`; el backend lo extrae del header o del body y la IP se captura con `$request->ip()`.

### Hito 5 — Roadmap (no se construye, se cuenta al CTO)
- SSE/WebSocket para live tail real
- Detección de anomalías por reglas configurables
- Política de retención y archivado en almacenamiento frío
- Export CSV/JSON

---

## 4. Próximo paso

**Decisiones cerradas (A–G):** ✅ A1 híbrido · ✅ B1 fallo silencioso · ✅ Catálogo central + slugs · ✅ Cobertura completa del prototipo (18 slugs) · ✅ Cursor + filtros single-select · ✅ Hash encadenado + detector en recorder · ✅ Acceso `role = admin`.

**Pendiente:** **H** (retención) — diferida a roadmap. MVP no purga.

**Estado actual:**
- ✅ **Hito 1 — Backend skeleton** completado (2026-05-26). Backend operativo en `/api/admin/audit-log`, 100 eventos seeded con cadena de hash íntegra.
- ✅ **Hito 2 — Frontend integración** completado (2026-05-26). Signal backend + filtros server-side + scroll infinito por cursor + live tail polling 5s + carga real de usuarios + date range por defecto hoy.
- ✅ **Hito 3 — Instrumentación completa** completado (2026-05-26). Los 18 slugs instrumentados en sus use cases: Auth, Order, Caja, Sale, Catalog, Tables.
- ✅ **Hito 4b — Fix `device_id` / `ip_address`** completado (2026-05-26). Todos los use cases de Hito 3 ahora capturan y persisten dispositivo e IP.
- ✅ **Endpoint verify** implementado (2026-05-26). `GET /api/admin/audit-log/verify` recalcula la cadena de hash por restaurante.
- 🚧 **Hito 4 — Hardening** pendiente: alerting, vistas guardadas persistidas.
- 🚧 **27 slugs pendientes** por instrumentar (tabla completa en sección "Cobertura de auditoría — instrumentados vs. pendientes"). Prioridades: Alta (`order.line_added`, `order.line_removed`, `sale.cancelled`), Media (resto de catalog, tables, user), Baja (family, zone, tax, menu).
