# Plan de implementación — Hito 6 (Mejoras pendientes)

> Estado: **plan**. Documento vivo para afrontar las mejoras del roadmap que aún no están implementadas, poco a poco.
> Patrón: DDD/Hexagonal del proyecto (módulos `app/<Module>/{Domain,Application,Infrastructure}`). Ver skill `ddd-controller-pattern`.
> Convención de tests: unit (casos de uso con repo mockeado) + feature (HTTP) y, solo para flujos con estado, e2e Playwright.

## Contexto — qué ya está hecho (no re-implementar)
Roles, PIN, división de cuenta, métodos de pago + mixto, cierre de caja, traslado de mesa, devoluciones (notas de crédito), descuentos por comensal, validación en dominio (value objects), auditoría con cadena de hash, y separación lectura/escritura parcial (repos de Reporting). Detalle en la conversación de revisión.

## Orden recomendado (valor / esfuerzo)
1. **Bus de eventos síncrono** — alto valor arquitectónico, encaja con DDD.
2. **Personalización de familias** — bajo esfuerzo, muy visible en el TPV.
3. **Tiempo real de mesas** — infra (Reverb) ya existe, gran impacto multi-terminal.
4. **Diseño interactivo del salón** — más trabajo, muy vistoso.
5. *(Baja prioridad)* Bus asíncrono, auth por tokens, impresora física, imágenes IA.

> Regla: un hito bien terminado (con tests) vale más que varios a medias. Cerrar cada mejora antes de empezar la siguiente.

---

## MEJORA 1 — Bus de eventos síncrono  ✅ COMPLETA
**Objetivo:** desacoplar efectos secundarios (auditoría, recálculos, notificaciones) de la lógica principal del caso de uso, despachándolos como eventos de dominio dentro del ciclo de la petición.

> Plan detallado y log de migración: **`MEJORA1_EVENT_BUS_PLAN.md`** (renombrado a migration log).

### Hecho
- [x] **Núcleo en `Shared`**: `DomainEvent`, `RecordsEvents`, `EventBusInterface`, `EventSubscriber`, `InMemorySyncEventBus`. Commit `6fb7dff`.
- [x] **Auditoría cross-cutting**: `AuditableEvent` + `AuditEventSubscriber`. Commit `6fb7dff`.
- [x] **Todos los módulos migrados**: Tax · Zone · Family · Table · Product · ProductModifier · ProductVariant · Restaurant · User · Menu · Order · Sale · Cash.
- [x] Suite: **1049/1049** tests passing. Commit final Cash: `6ab18c7`.

---

## MEJORA 2 — Personalización de familias (color / icono)  ✅ COMPLETA
**Objetivo:** que cada familia tenga distintivos visuales para identificarla rápido en el TPV.

> Decisión: **color + icono** (sin imagen/ficheros). Family migrado antes al bus de eventos (commit `980802a`).

### Hecho
- [x] **Migración**: `color` (string(7) nullable) + `icon` (string(32) nullable) en `families`.
- [x] **Dominio**: VOs `FamilyColor` (hex) y `FamilyIcon` (set permitido, espejo del front); entidad `Family` con color/icono en `dddCreate`/`update`/`fromPersistence`; `FamilyUpdated` lleva color/icono en before/after.
- [x] **Application**: `CreateFamily`/`UpdateFamily` aceptan color/icono; todas las respuestas (get/list/create/update/set-active) los exponen.
- [x] **Infra/HTTP**: FormRequests validan color (regex hex) e icono (`Rule::in`); repo + modelo persisten/leen las columnas.
- [x] **Frontend backoffice**: gestión de familias con picker de color (swatches) e icono, y chip de color/icono en la lista.
- [x] **Frontend TPV**: pestañas de familia con icono y acento de color en la comanda.
- [x] **Tests**: VOs + entidad (unit) + `FamilyAppearanceTest` (persistencia/normalización/validación end-to-end). Backend **961 passed**, `tsc` limpio.

### Commits
- `f899b3b` backend (apariencia) · `2a08a59` frontend (backoffice + TPV)

---

## MEJORA 3 — Tiempo real de mesas (multi-terminal)  ✅ COMPLETA
**Objetivo:** que el estado de las mesas (libre/ocupada, apertura/cierre/traslado) se sincronice entre terminales sin recargar.

> Plan detallado: **`MEJORA3_REALTIME_TABLES_PLAN.md`**. Commits: `818b6e9`, `358ef04`, `e281e15`, `f32ab74`, `bbdbf35`.

### Hecho
- [x] Canal público `restaurant.{restaurantId}` via Reverb. Evento broadcast `OrderStatusChanged` (`ShouldBroadcastNow`).
- [x] `TablesBroadcastSubscriber` registrado en `InMemorySyncEventBus`. Escucha **10 eventos**: Created, Cancelled, Deleted, MarkedToCharge, Reopened, Transferred, LineAdded, LineRemoved, ComandaSent, Invoiced.
- [x] `MesasFacade`: suscripción al canal en `loadData()`, `reloadOpenOrders()` refresca `_tables` + `_orderLines` + `_selectedTable`.
- [x] Tests: **12/12 unit** backend · **2/2 e2e** Playwright (abrir mesa, marcar cobrar, cobrar → mesa libre).

---

## MEJORA 4 — Diseño interactivo del salón (plano)
**Objetivo:** colocar las mesas en un plano con posición y tamaño reales por zona.

### Decisiones a tomar
- [ ] Campos: `pos_x`, `pos_y`, `width`, `height` (enteros, en px/grid) y opcional `shape` (`rect`/`circle`). ¿Unidad: grid lógico o px absolutos?
- [ ] ¿Edición del plano en backoffice y solo lectura en el TPV?

### Fases
- [ ] **Migración**: añadir `pos_x`, `pos_y`, `width`, `height`, `shape` a `tables` (con defaults sensatos para las existentes).
- [ ] **Dominio/Application**: value object `TableLayout` (valida no-negativos / límites); caso de uso `UpdateTableLayout` (o extender update de mesa).
- [ ] **Infra/HTTP**: endpoint para guardar layout (posiblemente batch por zona), FormRequest con validación.
- [ ] **Frontend backoffice**: editor drag-and-drop del plano por zona (arrastrar/redimensionar, guardar posiciones).
- [ ] **Frontend TPV**: render del plano con las posiciones reales y el color de estado.
- [ ] **Tests**: unit (validación de layout), feature (guardar layout → persiste; valores inválidos → 422).

### Riesgos / notas
- Migración debe dar posiciones por defecto a las mesas ya existentes (auto-grid) para no romper la vista.
- Es la de más esfuerzo de UI; considerar hacerla la última.

---

## Baja prioridad (anotadas, no planificadas en detalle)
- [ ] **Bus de eventos asíncrono** — requiere cola (queue worker). Buen siguiente paso tras la MEJORA 1: mover listeners no críticos (notificaciones, stock) a jobs `ShouldQueue`.
- [ ] **Autenticación por tokens** — el modelo actual de sesión + dispositivo vinculado es coherente; cambiar a tokens aporta poco salvo que se quiera un cliente 100% desacoplado.
- [ ] **Integración con impresoras (ESC/POS)** — depende de hardware, difícil de demostrar en evaluación.
- [ ] **Generación de imágenes IA** — ya se resolvió con subida de foto por QR; podría añadirse como alternativa (sugerencia automática vía servicio externo al crear producto).

---

## Cómo trabajar cada mejora
1. Discutir la casuística/decisiones de la sección antes de codear (preferencia del proyecto).
2. Backend primero (migración → dominio → aplicación → infra/HTTP) con tests unit+feature.
3. Frontend después.
4. Verificar (`php artisan test` del módulo + `tsc --noEmit` + e2e si aplica) y commit en inglés sin co-author.
5. Marcar las casillas de esta sección al terminar.
