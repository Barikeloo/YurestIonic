# Plan de implementación — Informes Programados (tab "Programados")

> Estado: **implementado (v1 completa)**. Integraciones se deja como está (no se toca).
> Sigue el patrón DDD/Hexagonal del proyecto (módulo `Reporting`). Ver skill `ddd-controller-pattern`.

## Decisiones ya tomadas (no re-preguntar)
- **Alcance**: real — BD + CRUD + comando programado + "enviar ahora".
- **Periodo que se genera al disparar**: el **periodo cerrado anterior**
  - diario → ayer · semanal → semana pasada · mensual → mes pasado · trimestral → trimestre pasado.
- **Frecuencias**: diaria, semanal, mensual, trimestral.
- **Destinatarios**: varios emails por programación.
- **Formato**: PDF o CSV por programación.
- **Zona horaria**: `config('app.timezone')` (Europe/Madrid).
- **Mail**: en dev `MAIL_MAILER=log` (no sale de verdad). El **disparo automático** necesita que ops arranque el scheduler worker; el comando se deja escrito y registrado.

---

## Arquitectura (resumen)
- Tabla `scheduled_reports` (un restaurante, varias programaciones).
- Servicio compartido `ReportFileGenerator` que devuelve `{filename, mimeType, contents}` para `(restaurantId, type, format, DateRange)` — reutilizado por "enviar ahora", el comando, y (opcional) por los controllers PDF/CSV actuales para deduplicar (pendiente de refactor).
- `NextRunCalculator` (dominio puro) para `next_run_at`.
- Casos de uso CRUD + `SendScheduledReportNow` + `DispatchDueScheduledReports`.
- `ScheduledReportMail` (adjunto genérico) + comando `reports:dispatch-scheduled`.
- Frontend: modal crear/editar + tabla real en la tab Programados.

---

## FASE 1 — Persistencia
- [x] **Migración** `database/migrations/2026_06_11_000000_create_scheduled_reports_table.php`:
  - `id`, `uuid` (unique), `restaurant_id` (FK),
  - `report_type` string(32) — `daily|products|families|cash|tips|taxes`
  - `format` string(8) — `PDF|CSV`
  - `frequency` string(16) — `daily|weekly|monthly|quarterly`
  - `time` string(5) — `HH:MM`
  - `weekday` tinyint nullable (1-7, ISO; solo weekly)
  - `day_of_month` tinyint nullable (1-28; solo monthly)
  - `recipients` json (array de emails)
  - `name` string (etiqueta visible; opcional, derivable del tipo)
  - `active` boolean default true
  - `last_run_at` timestamp nullable
  - `next_run_at` timestamp (index)
  - `created_by_user_uuid` uuid nullable
  - timestamps + softDeletes
  - índices: `(restaurant_id, id)` unique, `(active, next_run_at)` para el comando.
- [x] `Domain/Interfaces/ScheduledReportRepositoryInterface` (save, update, findByUuid, listForRestaurant, delete, setActive, listDue(now), markRun(uuid, lastRun, nextRun)).
- [x] `Infrastructure/Persistence/EloquentScheduledReportRepository` (estilo `DB::table`, como `EloquentReportExportRepository`).
- [x] Binding en `app/Providers/AppServiceProvider.php`.

## FASE 2 — Generación de informes compartida
- [x] `Infrastructure/Services/ReportFileGenerator`:
  - Entrada: `restaurantId`, `type`, `format` (PDF|CSV), `DateRange` (+ quarter/year si taxes).
  - PDF: llama al repositorio directamente y pasa datos a `Pdf::loadView('pdf.<tipo>', ...)`.
  - CSV: incorpora la lógica de `ExportReportController::buildData/buildCsv`.
  - Salida: `{filename, mimeType, contents}`.
  - **Nota dedupe (pendiente)**: refactorizar los 6 controllers PDF + `ExportReportController` para que usen este servicio (hoy cada uno repite el patrón). Pendiente para futura iteración.

## FASE 3 — Lógica de dominio
- [x] `Application/Shared/DateRange`: **helpers de periodo cerrado anterior añadidos**:
  - `yesterday()` (ya existe vía `fromPeriod('yesterday')`).
  - `lastWeek()` (lunes-domingo de la semana pasada).
  - `lastMonth()` (mes natural anterior completo).
  - `lastQuarter()` / reutiliza `forQuarter(year, prevQuarter)`.
  - `fromFrequency(string $frequency): self` — mapa frecuencia → rango.
- [x] `Application/Shared/NextRunCalculator` (puro, recibe `now`):
  - daily: hoy a `time`, si ya pasó → mañana.
  - weekly: próximo `weekday` a `time`.
  - monthly: próximo `day_of_month` a `time` (clamp a días del mes destino).
  - quarterly: primer día del próximo trimestre a `time`.
  - Devuelve `DateTimeImmutable`.

## FASE 4 — Casos de uso (Application)
Cada uno con Command + (Response si aplica) + excepciones de dominio:
- [x] `CreateScheduledReport` (valida tipo/formato/frecuencia/campos según frecuencia; calcula `next_run_at`).
- [x] `UpdateScheduledReport` (recalcula `next_run_at`; si inactive, next=9999-12-31).
- [x] `DeleteScheduledReport`.
- [x] `ToggleScheduledReport` (active on/off; al reactivar, recalcula next_run).
- [x] `ListScheduledReports` (por restaurante; devuelve array completo).
- [x] `SendScheduledReportNow` (genera con `ReportFileGenerator` para el periodo cerrado anterior + envía con `ScheduledReportMail`).
- [x] `DispatchDueScheduledReports` (recibe `now`; `listDue`; por cada una genera+envía, `markRun` con last=now y next=NextRunCalculator). Tolerante a fallos por item (loggea y sigue).
- [x] Excepciones: `ScheduledReportNotFoundException`, `InvalidScheduleException`.

## FASE 5 — Infraestructura HTTP + Mail + Comando
- [x] `ScheduledReportMail` (Mailable) — asunto por tipo+periodo, cuerpo `models.scheduled-report` (nueva vista), adjunto `Attachment::fromData`.
- [x] Controllers (DDD) en `Infrastructure/Entrypoint/Http/`:
  - `ListScheduledReportsController` (GET `/admin/reports/scheduled`)
  - `CreateScheduledReportController` (POST `/admin/reports/scheduled`)
  - `UpdateScheduledReportController` (PUT `/admin/reports/scheduled/{uuid}`)
  - `DeleteScheduledReportController` (DELETE `/admin/reports/scheduled/{uuid}`)
  - `ToggleScheduledReportController` (PUT `/admin/reports/scheduled/{uuid}/toggle`)
  - `SendScheduledReportNowController` (POST `/admin/reports/scheduled/{uuid}/send`)
  - FormRequests con `rules()` (recipients: array de emails; time HH:MM; weekday 1-7 si weekly; day_of_month 1-28 si monthly).
- [x] Rutas en `routes/api.php` (grupo admin, junto a `/admin/reports/...`) + imports.
- [x] **Comando** `app/Console/Commands/DispatchScheduledReports.php` → `reports:dispatch-scheduled` (invoca `DispatchDueScheduledReports(now())`).
- [x] **Registrar en scheduler** (Laravel 12): en `routes/console.php` →
  `Schedule::command('reports:dispatch-scheduled')->everyFifteenMinutes();`

## FASE 6 — Frontend (tab Programados)
- [x] `models/finanzas.models.ts`: `ScheduledReport` (uuid, report_type, format, frequency, time, weekday, day_of_month, recipients[], active, next_run_at, last_run_at, name) + payloads `CreateScheduledReportPayload`, `UpdateScheduledReportPayload`.
- [x] `services/finanzas.service.ts`: `getScheduled()`, `createScheduled(p)`, `updateScheduled(uuid,p)`, `deleteScheduled(uuid)`, `toggleScheduled(uuid)`, `sendScheduledNow(uuid)`.
- [x] `facades/finanzas.facade.ts`: señal `scheduledReports`, cargar en init, métodos + refresco.
- [x] `tabs/informes-tab.component.*`:
  - Sustituir el array mock `scheduledReports` por `facade.scheduledReports()`.
  - Cablear toggle (→ `facade.toggleScheduledReport`), borrar (→ `deleteScheduled`), editar (✎ → modal editar), enviar ahora (⤓ → `sendScheduledNow`).
  - **Modal crear/editar** (botón "+ Nueva programación"): selects de tipo de informe, frecuencia (+ weekday/day-of-month condicional), hora, formato, input de destinatarios (varios), activo.
  - Mostrar `next_run_at` formateados y badges de formato/estado (estilos `.fmt-badge`, `.toggle` ya existentes).

## FASE 7 — Verificación
- [ ] **Tinker**: crear programación → `ListScheduledReports` → `SendScheduledReportNow` (revisar `storage/logs/laravel.log` por el email + adjunto) → `DispatchDueScheduledReports(now)` con una `next_run_at` en el pasado → comprobar `last_run_at`/`next_run_at` recalculados.
- [ ] `NextRunCalculator`: casos diario/semanal/mensual/trimestral con `now` antes/después de la hora.
- [ ] **Playwright (1280×800, login barmanolo@gmail.com / 12345678)**: abrir tab Programados, crear una programación en el modal, ver que aparece en la tabla, toggle, editar, "enviar ahora".
- [x] `tsc --noEmit -p tsconfig.app.json` limpio (ejecutado, sin errores).

---

## Notas / riesgos
- **Disparo automático**: requiere `php artisan schedule:work` (o cron del SO) corriendo + `MAIL_MAILER` real para envío de verdad. En dev queda en log. Documentarlo en el PR.
- `DateRange` tiene ahora `lastWeek`, `lastMonth`, `lastQuarter` y `fromFrequency`.
- **Deviation**: `ReportFileGenerator` no usa los 6 casos de uso existentes (inyectaría 6 dependencias); usa el repositorio directamente para ambos formatos, evitando acoplamiento pesado. Los controllers PDF/CSV actuales aún no lo usan — refactor opcional pendiente.
- **Deviation**: `ToggleScheduledReport` devuelve `{uuid, active}` en lugar de void, para que el frontend pueda actualizar el estado sin recargar toda la lista.
- Quarterly: día 1 del trimestre siguiente a `time`.
- Validar emails de `recipients` en el FormRequest y en el front.
- `report_type='taxes'` + `format='PDF'` = Modelo 303; `format='CSV'` = desglose de impuestos (ya soportado por `ExportReportController`/`GetTaxPdfController`).
- Credenciales de prueba y flujo de verificación en vivo: ver memoria `project_finanzas_tablet_ui`.
