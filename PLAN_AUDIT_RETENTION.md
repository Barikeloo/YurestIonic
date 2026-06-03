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
- ✅ `EloquentAuditLogRepository` implementa `bulkArchive`.
- ✅ Console command `audit:archive-old` funcional + schedule semanal (lunes 02:00).
- ✅ Meta-auditoría: `AuditEventCatalog` con slug `audit.archived` y el use case emite el evento.
- ✅ Suite backend: 742 tests verdes.
- `ListAuditEvents` use case y `ListAuditEventsController` existen y funcionan sobre la tabla completa (sin filtro de archivado — pendiente Fase 3).
- `VerifyAuditChain` existe y lee toda la tabla por restaurante (compatible con archivado sin cambios).

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

### Fase 5 — Documentación ➕

- [ ] `README.md` (sección Características o Arquitectura): bloque "Retención de audit logs" con política (90 días → archivado, 6 años → conservación legal, **nunca borrado**).
- [ ] Comentario en la migración explicando el racional legal.
- [ ] Mención al comando `audit:archive-old` en la sección de comandos operativos del README.

**Commit:** `docs: document audit log retention policy and archival workflow`

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

---

## 6. Fuera del alcance (futuro)

- **Particionado MySQL** por restaurante+año si la tabla supera ~10M filas.
- **Cold storage** S3 / JSONL firmado para descargar y borrar tras 6+ años (decisión legal específica).
- **Anonimización** parcial de campos PII en archivados antiguos (RGPD minimización progresiva).
- **Exports masivos** de archivados con disclaimer.
