# Plan de Retención y Archivado de Audit Logs

> Política de archivado semanal con soft-archive en la misma tabla. **Nunca se borran registros** — cumplimos retención legal (Cód. de Comercio 6 años, LGT 4 años, RGPD minimización justificada).

---

## 1. Decisiones de diseño fijadas

| Decisión | Resolución | Motivo |
|---|---|---|
| **Patrón de archivado** | Soft archive en la misma tabla (`archived_at`) | Cadena `integrity_hash`/`prev_hash` queda intacta, queries unificadas, sin doble esquema |
| **Criterio de archivado** | Edad fija configurable (default **90 días**) | Estándar, idempotente entre runs |
| **Frecuencia del job** | Semanal, lunes 02:00 (Laravel scheduler `withoutOverlapping`) | Cumple "cada semana" del requisito |
| **Visibilidad por defecto** | UI y API filtran `archived_at IS NULL`; toggle admin `?include_archived=1` para incluirlos | Performance + cumplimiento (acceso bajo demanda) |
| **Meta-auditoría** | Emite evento `audit.archived` cada run con metadata (count, threshold, restaurant) | Trazabilidad del propio proceso |
| **Borrado real** | **Nunca** | Política legal del cliente |
| **`VerifyAuditChain`** | Lee **todos** los eventos (incluidos archivados) | Si no, la cadena hash no validaría tras archivado |

---

## 2. Estado actual del código

- ✅ `audit_logs` table con columna `archived_at` añadida.
- ✅ `app/Audit/Application/ArchiveAuditData/` con `ArchiveOldAuditLogs`, `ArchiveOldAuditLogsCommand`, `ArchiveOldAuditLogsResponse` completos.
- ✅ `EloquentAuditLogRepository` implementa `bulkArchive`, `getArchivedStats` y `streamForExport`.
- ✅ Console command `audit:archive-old` funcional + schedule semanal (lunes 02:00).
- ✅ Meta-auditoría: `AuditEventCatalog` con slugs `audit.archived` y `audit.exported`; ambos use cases emiten el evento correspondiente.
- ✅ Endpoints HTTP: `GET /admin/audit-log/archived-stats` (con date range) y `GET /admin/audit-log/export` (CSV streaming + NDJSON).
- ✅ Panel `/registro-auditoria/historico` (`HistoricoFacade` + cards + bar chart + range picker + export dropdown).
- ✅ Suite backend: 762 tests verdes.

---

## 3. Fases

### Fase 0 — Schema + repo plumbing ✅

Objetivo: la columna existe, el repo filtra por defecto, todo lo existente sigue funcionando.

- [x] Migración `add_archived_at_to_audit_logs_table`: añadir `archived_at TIMESTAMP NULL` + índice compuesto `(restaurant_id, archived_at, created_at)`.
- [x] `EloquentAuditLog`: añadir `archived_at` a `$fillable` y a `$casts` como `datetime`.
- [x] `EloquentAuditLogRepository`:
  - Añadir parámetro `includeArchived: bool = false` a `listEvents()`, `findByUuid()`, `countRecentByActionAndUser()`.
  - Por defecto filtran `archived_at IS NULL`.
  - `VerifyAuditChain` recibe `includeArchived = true` desde el use case para no romper la cadena.
- [x] `AuditLogRepositoryInterface`: actualizar firma con el flag.
- [x] Suite backend completa en verde (`make test`).

**Commit:** `feat(audit): add archived_at column and repo support for retention`

### Fase 1 — Use case `ArchiveOldAuditLogs` + Console command ✅

Objetivo: poder ejecutar `php artisan audit:archive-old --older-than-days=90 [--dry-run] [--restaurant-uuid=…]`.

- [x] Domain Exception: `InvalidArchiveThresholdException` (si days ≤ 0 o threshold en el futuro).
- [x] Command DTO `ArchiveOldAuditLogsCommand` con `olderThanDays: int`, `restaurantId: ?Uuid`, `dryRun: bool`.
- [x] Response DTO `ArchiveOldAuditLogsResponse` (constructor privado + `::create()` + `toArray()`) con counters por restaurante (`{restaurant_uuid, archived_count, oldest_archived_at, newest_archived_at}[]`).
- [x] `ArchiveOldAuditLogs` use case: orquesta el bulk-archive contra el repositorio.
- [x] `AuditLogRepositoryInterface::bulkArchive(restaurantId, thresholdDate, dryRun): ArchiveStatsDto`.
- [x] Implementación Eloquent del bulkArchive (`UPDATE … SET archived_at = NOW() WHERE archived_at IS NULL AND created_at < ?`).
- [x] Console command `audit:archive-old` que parsea opciones → Command → llama al use case → imprime el Response como tabla en consola.
- [x] Unit test del use case con repositorio mockeado.
- [x] Feature test: insertar fixtures antiguos + recientes, correr command, verificar archivado correcto.

**Commit:** `feat(audit): ArchiveOldAuditLogs use case and artisan audit:archive-old command`

### Fase 2 — Meta-auditoría + Scheduler ✅

Objetivo: cada run del job queda registrado en la propia auditoría, y se ejecuta solo cada lunes.

- [x] `AuditEventCatalog`: añadir slug `audit.archived` con summary `"Archivado de auditoría: {metadata.archived_count} eventos previos a {metadata.threshold_date_formatted}."`, categoría `system`, severidad `info`.
- [x] El use case `ArchiveOldAuditLogs` emite el evento por cada restaurante archivado (con metadata `archived_count`, `threshold_date`, `oldest`, `newest`).
- [x] `bootstrap/app.php` (Laravel 12 schedule): `$schedule->command('audit:archive-old')->weekly()->mondays()->at('02:00')->withoutOverlapping(60)`.
- [x] Unit test: el use case llama al `AuditRecorder` con el slug correcto y la metadata esperada.

**Commit:** `feat(audit): emit audit.archived meta-event and schedule weekly archival`

### Fase 3 — API + UI ✅

Objetivo: el admin puede ver los archivados desde `/registro-auditoria` con un toggle.

- [x] `ListAuditEventsCommand`: añadir `includeArchived: bool`.
- [x] `ListAuditEventsRequest::rules()`: validar `include_archived` como boolean opcional; `toCommand()` lo propaga.
- [x] `ListAuditEventsController`: rechazar el flag si el usuario no es admin (lanzar `ForbiddenAuditAccessException`).
- [x] `ListAuditEvents` use case: pasa el flag al repositorio.
- [x] Feature test: con admin + `include_archived=1` devuelve archivados; sin flag los oculta; con no-admin + flag → 403.
- [x] Frontend `RegistroAuditoriaFacade`:
  - Signal `includeArchived = signal<boolean>(false)` + setter `setIncludeArchived(value: boolean)`.
  - Recargar lista cuando cambie el signal.
- [x] Frontend `registro-auditoria.page.html`: toggle "Mostrar histórico" + banner explicativo cuando esté activo.
- [x] Verificar con E2E (al menos visual smoke): la página carga con toggle visible para admin.

**Commit:** `feat(audit): include_archived flag end-to-end (API + facade + UI toggle)`

### Fase 4 — Verificación de cadena con archivados ✅

Objetivo: `VerifyAuditChain` sigue validando la cadena después de archivado.

- [x] Confirmar / ajustar el use case `VerifyAuditChain` para que lea con `includeArchived = true`.
- [x] Feature test: insertar N eventos, archivar X, ejecutar verify → cadena OK.
- [x] Feature test negativo: corromper el `integrity_hash` de uno archivado y verificar que el endpoint reporta el fallo.

**Commit:** `feat(audit): verify chain includes archived rows`

### Fase 5 — Documentación ✅

- [x] `README.md` (sección Características o Arquitectura): bloque "Retención de audit logs" con política (90 días → archivado, 6 años → conservación legal, **nunca borrado**).
- [x] Comentario en la migración explicando el racional legal.
- [x] Mención al comando `audit:archive-old` en la sección de comandos operativos del README.

**Commit:** `docs: document audit log retention policy and archival workflow`

### Fase 6 — Panel de gestión de histórico ✅

Objetivo: interfaz dedicada para que el admin explore, filtre y exporte el histórico de audit logs archivados.

**Commits:** `707db9c`, `26462a7`, `a5f9315`, `7ff6217`, `3721a6c`, `38696bc` (6 commits sobre la rama).

#### 6.1 Backend — endpoints de histórico ✅

- [x] Endpoint `GET /api/admin/audit-log/archived-stats` (commit `707db9c`):
  - Total de archivados para el restaurante autenticado (scope per-tenant vía `TenantContext`).
  - Rango (`oldest_created_at` / `newest_created_at`) calculado sobre `created_at`, no sobre `archived_at`.
  - Desglose mensual `{month: "YYYY-MM", count: N}` agrupado por `created_at`.
  - Caché 5 min, invalidación al final de `ArchiveOldAuditLogs`.
- [x] Endpoint `GET /api/admin/audit-log/export` (commit `7ff6217`):
  - Paleta de filtros completa (igual que el listado) + `?format=csv|ndjson`.
  - Streaming real: `lazy(1000)` + generator + `StreamedResponse` → memoria plana.
  - CSV con UTF-8 BOM, CRLF y comma para Excel-ES; NDJSON con payload completo.
  - Meta-evento `audit.exported` con `row_count`, `format` y filtros usados (sólo se graba cuando la generación termina).
  - Sólo accesible para admin vía el grupo de middleware `RequireAdminSession`.
- [x] Filtro temporal del panel (commit `38696bc`):
  - Decisión de scope: `date_from` / `date_to` en `/archived-stats` filtran por `created_at` (mismo eje que el chart y las cards), **no** por `archived_at`.
  - Razonamiento: la pregunta del admin desde el panel es "qué hay en mi corpus de esta época", no "qué se archivó en este run del cron". Filtrar por `archived_at` queda como mejora futura si surge un caso real (e.g. auditar runs concretos del cron).
  - El listado (`ListAuditEvents`) ya aceptaba `date_from`/`date_to` por `created_at` desde antes; ahora `archived-stats` y `export` están alineados.

#### 6.2 Frontend — página de histórico ✅

- [x] Ruta dedicada `/registro-auditoria/historico` con `HistoricoFacade` (commit `26462a7`).
- [x] **Entry point** desde la página principal: botón "Histórico" en la cabecera + link contextual junto al toggle "Mostrar histórico" (commits `26462a7`, `a5f9315`).
- [x] **Estadísticas en cabecera** (`26462a7`):
  - 4 cards: Total archivado (card hero), Rango temporal (con nº meses), Mes más activo, Promedio mensual.
  - Bar chart inline-SVG de la distribución mensual con hover counts y scroll horizontal cuando hay muchos meses.
- [x] **Filtros específicos** (commit `38696bc`):
  - Dropdown "Rango temporal" con presets (Todo, Último año, Último trimestre, Último mes) + rango personalizado con date inputs.
  - Banner cuando hay rango activo (`Filtrando: <label>` + botón Quitar).
  - El rango se aplica a stats, chart, KPIs y URLs de export — "what you see is what you export".
- [x] **Exportar** (commit `3721a6c`):
  - Dropdown "Exportar" con dos opciones: CSV (Compatible con Excel) y NDJSON (Una línea JSON por evento).
  - Descarga vía `<a href download>` directo (el backend manda `Content-Disposition: attachment`, no se necesita Blob).
  - Hint en el menú: "Incluye archivados · queda registrado en auditoría".

#### 6.3 Consideraciones técnicas ✅

- Streaming export: implementado con generators de PHP + `lazy(1000)` de Eloquent + `StreamedResponse`. Memoria plana incluso con exports de millones de filas.
- Frontend export: usamos `<a href download>` directo (Blob/`URL.createObjectURL` resultaban innecesarios porque el backend ya manda `Content-Disposition: attachment`).
- Caché `archived-stats`: 5 min, key compuesta por `restaurantUuid + dateFrom + dateTo`. Invalidación al final de `ArchiveOldAuditLogs` (sólo la key sin filtros — las date-ranged caducan en su TTL natural).

---

## 4. Convenciones aplicables

### Backend — DDD/Hexagonal (skill `ddd-controller-pattern`)

- **Use case sin HTTP** (Fase 1 console command): Command + Response + Use Case + Domain Exceptions, sin FormRequest.
- **Use case con HTTP** (Fase 3 endpoint): los 8 bloques completos.
- Response DTO con constructor **privado** + `::create(…)` factory mirror.
- Excepciones extienden `\DomainException`, una por error.
- Binding del repositorio (si cambia interfaz) en `AppServiceProvider::register()`.
- Catch en controlador: una por excepción de dominio + `\Throwable` final → 500.

### Frontend — Facade pattern (`facade-refactoring.md`)

- Signals privadas (`signal()`) + computed públicos (`computed()`).
- Setters explícitos (no asignación directa desde componente).
- HTML usa signals con paréntesis: `facade.includeArchived()`.

---

## 5. Cómo verificar cada fase

| Fase | Smoke check |
|---|---|
| 0 | `make test` (731+ verdes), `docker compose exec api php artisan migrate:fresh --seed --class=SaonaDemoSeeder` arranca sin error |
| 1 | `docker compose exec api php artisan audit:archive-old --older-than-days=1 --dry-run` muestra contadores; sin `--dry-run` archiva real |
| 2 | Tras correr el comando aparece un evento `audit.archived` en la tabla `audit_logs` |
| 3 | Login admin → `/registro-auditoria` → toggle "Mostrar histórico" → API call con `include_archived=1` retorna archivados |
| 4 | `POST /api/audit/verify` devuelve `ok: true` tras archivar |
| 5 | README incluye sección "Retención" con la política |
| 6 | Login admin → botón "Histórico" en la cabecera → `/registro-auditoria/historico` carga cards + chart; preset "Último año" recarga stats; "Exportar → CSV" descarga `audit-log-*.csv` y queda un `audit.exported` registrado |

---

## 6. Fuera del alcance (futuro)

- **Particionado MySQL** por restaurante+año si la tabla supera ~10M filas.
- **Cold storage** S3 / JSONL firmado para descargar y borrar tras 6+ años (decisión legal específica).
- **Anonimización** parcial de campos PII en archivados antiguos (RGPD minimización progresiva).
- **Exports masivos** de archivados con disclaimer.
- **Filtro por `archived_at`** (cuándo se archivó, no cuándo ocurrió): útil si surge la necesidad de auditar runs concretos del cron. La Fase 6 lo dejó fuera porque el panel usa `created_at` como eje principal.
