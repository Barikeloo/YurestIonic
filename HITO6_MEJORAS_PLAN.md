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

## MEJORA 1 — Bus de eventos síncrono
**Objetivo:** desacoplar efectos secundarios (auditoría, recálculos, notificaciones) de la lógica principal del caso de uso, despachándolos como eventos de dominio dentro del ciclo de la petición.

### Decisiones a tomar (antes de codear)
- [ ] Caso piloto: empezar por **auditoría** (`AuditRecorder`, hoy inyectado en muchos casos de uso) → convertir en listener de eventos de dominio.
- [ ] ¿Eventos emitidos por la entidad (recordedEvents) o despachados explícitamente por el caso de uso? (recomendado: el caso de uso despacha tras persistir).
- [ ] Naming y ubicación: `app/Shared/Domain/Event/` (interfaz + base) y `app/Shared/Application/EventBus/`.

### Fases
- [ ] **Dominio compartido**: `DomainEvent` (interfaz/abstract), `EventBusInterface` (`publish(DomainEvent ...$events)`).
- [ ] **Infra**: `InMemorySyncEventBus` (resuelve listeners del contenedor y los invoca en orden, dentro de la petición). Binding en `AppServiceProvider`.
- [ ] **Registro de listeners**: mapa evento → listeners (config o provider).
- [ ] **Piloto auditoría**: emitir p.ej. `SaleOpenedEvent`, `SaleClosedEvent`, `CashSessionClosedEvent`; mover la lógica de `AuditRecorder->record(...)` a listeners. Quitar la inyección directa del recorder en esos casos de uso.
- [ ] **Tests**: unit del bus (publica → invoca listeners en orden, tolerante o no a fallos), unit de un caso de uso (verifica que publica el evento esperado), feature (la operación sigue dejando el registro de auditoría).
- [ ] **Doc**: nota de arquitectura (por qué síncrono, dónde se despacha, cómo añadir un listener).

### Riesgos / notas
- Mantener el orden determinista de listeners.
- Decidir transaccionalidad: ¿publicar dentro o fuera de la transacción de BD? (recomendado: tras commit para efectos externos; dentro para los que deban revertirse).

---

## MEJORA 2 — Personalización de familias (color / icono / imagen)
**Objetivo:** que cada familia tenga distintivos visuales para identificarla rápido en el TPV.

### Decisiones a tomar
- [ ] Campos: `color` (hex, validado), `icon` (slug de set de iconos existente en el front), `image_url` (opcional). ¿Los tres o empezar por color+icono?
- [ ] ¿Imagen reutiliza el mecanismo de subida por QR de productos, o subida directa desde backoffice?

### Fases
- [ ] **Migración**: añadir `color` (string nullable), `icon` (string nullable), `image_src` (string nullable) a `families`.
- [ ] **Dominio**: value objects `FamilyColor` (valida hex), `FamilyIcon` (valida contra lista permitida); añadir a la entidad `Family` (constructor/factories/`changeAppearance`).
- [ ] **Application**: extender `CreateFamily` / `UpdateFamily` (command + validación) para aceptar los nuevos campos; response los incluye.
- [ ] **Infra/HTTP**: FormRequests (reglas: color hex `regex`, icon `Rule::in`, imagen nullable), repositorio Eloquent (persistir/leer columnas), mapear en `toArray`.
- [ ] **Frontend backoffice**: formulario de familia con selector de color, picker de icono y (opcional) imagen.
- [ ] **Frontend TPV**: pintar el distintivo en los botones/grupos de familia.
- [ ] **Tests**: unit (`CreateFamily`/`UpdateFamily` con/ sin apariencia, validación de color/icono), feature (POST/PUT familia con color+icono → 200 y persistido; color inválido → 422).

### Riesgos / notas
- Validar `color` también en el front. Definir el set cerrado de iconos compartido front↔back.

---

## MEJORA 3 — Tiempo real de mesas (multi-terminal)
**Objetivo:** que el estado de las mesas (libre/ocupada, apertura/cierre/traslado) se sincronice entre terminales sin recargar.

### Decisiones a tomar
- [ ] Canal por restaurante: `private-restaurant.{restaurantId}.tables` (o similar). Autorización en `routes/channels.php`.
- [ ] Eventos a emitir: `TableOccupied`, `TableReleased`, `TableTransferred` (mínimo).
- [ ] ¿Se apoya en la MEJORA 1 (bus de eventos) para emitir? (recomendado: el listener de dominio dispara el broadcast).

### Fases
- [ ] **Infra broadcasting**: eventos `ShouldBroadcast` (mirar `Product/Infrastructure/Broadcasting/ProductPhotoUploaded` como referencia) para apertura/cierre/traslado.
- [ ] **Canal + auth**: definir canal privado por restaurante en `routes/channels.php`.
- [ ] **Disparo**: emitir el broadcast al abrir/cerrar/trasladar venta (idealmente vía listener de evento de dominio).
- [ ] **Frontend**: suscripción al canal en la vista de mesas; actualizar el estado en el signal al recibir el evento.
- [ ] **Tests**: feature con `Event::fake()` (la operación encola el broadcast en el canal correcto con el payload esperado). e2e queda opcional (requiere 2 contextos).

### Riesgos / notas
- Reverb ya está levantado (contenedor `reverb`). Verificar credenciales/envs de broadcasting en el front.
- Cuidado con bucles de actualización (no re-emitir al recibir).

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
