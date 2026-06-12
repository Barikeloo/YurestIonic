# Plan — Mejora 1: Bus de eventos síncrono (piloto módulo Tax)

> Estado: **plan**. Subdocumento de [`HITO6_MEJORAS_PLAN.md`](./HITO6_MEJORAS_PLAN.md).
> Patrón DDD/Hexagonal. Tests unit + feature antes de pasar de fase.

## Decisiones tomadas (no re-preguntar)
- **Alcance piloto:** solo el módulo **Tax** (Create/Update/Delete). Luego se replica módulo a módulo.
- **Emisión:** la **entidad agregada registra eventos** (`recordEvent` / `pullDomainEvents`); el caso de uso los publica tras persistir.
- **Contexto de petición aparte:** el evento de dominio lleva **solo datos de dominio**; un `RequestContext` request-scoped aporta IP/device/user y el listener de auditoría los combina (restaurantId vía `TenantContext`).
- **Síncrono, en la petición.** El subscriber corre en el acto; si falla, la excepción propaga (mismo comportamiento que hoy con `AuditRecorder` inline).
- **Publicación tras persistir** (`repo->save()` → `eventBus->publish(...)`).

## Objetivo y valor
Que el módulo Tax **deje de depender del módulo Audit**: en vez de inyectar `AuditRecorderInterface` y construir el `AuditEventDraft`, la entidad registra eventos de dominio y un subscriber de Audit los traduce a auditoría. Sienta la base para que **un evento tenga N listeners** (auditoría hoy; stock y tiempo real después — Mejora 3).

---

## Arquitectura (resumen)

**Núcleo compartido (`app/Shared`)**
- `Domain/Event/DomainEvent` — interfaz (`occurredOn(): \DateTimeImmutable`).
- `Domain/Event/RecordsEvents` — trait para agregados (`recordEvent`, `pullDomainEvents`).
- `Domain/Event/AuditableEvent extends DomainEvent` — métodos para auditar: `auditSlug`, `auditEntityType`, `auditEntityId`, `auditMetadata`, `auditBefore`, `auditAfter`.
- `Application/Event/EventBusInterface` — `publish(DomainEvent ...$events): void`.
- `Application/Event/EventSubscriber` — `subscribedTo(): array` (clases/interfaces) + `handle(DomainEvent $event): void`.
- `Infrastructure/Event/InMemorySyncEventBus` — recibe subscribers; por cada evento invoca los subscribers cuyo `subscribedTo` casa con `instanceof`, en orden, síncrono.
- `Application/Context/RequestContextInterface` — `userId()`, `ipAddress()`, `deviceId()`, `sessionId()`.
- `Infrastructure/Context/HttpRequestContext` — lee request/sesión bajo demanda (en consola devuelve null).

**Audit (cross-cutting)**
- `Audit/Application/Subscriber/AuditEventSubscriber` — `subscribedTo(): [AuditableEvent::class]`; en `handle` construye `AuditEventDraft` con datos del evento + `RequestContext` + `TenantContext` (restaurantUuid) y llama `AuditRecorderInterface`.

**Tax (piloto)**
- `Tax/Domain/Event/TaxCreated|TaxUpdated|TaxDeleted` implements `AuditableEvent` (datos de dominio + before/after).
- Entidad `Tax` usa `RecordsEvents`: `dddCreate` → `TaxCreated`; `update()` → `TaxUpdated` (before/after); `delete()` → `TaxDeleted`.
- Casos de uso `CreateTax/UpdateTax/DeleteTax`: quitar `AuditRecorderInterface`, inyectar `EventBusInterface`; publicar `...$tax->pullDomainEvents()` tras persistir.
- Commands: quitar `userId/deviceId/ipAddress` (y `restaurantId` si solo servía para auditar). Requests/Controllers: dejar de pasarlos.

---

## FASE 1 — Núcleo del bus (Shared) + tests  ✅
- [x] `DomainEvent`, `RecordsEvents`, `EventBusInterface`, `EventSubscriber`.
- [x] `InMemorySyncEventBus` (match por `instanceof`, orden estable, síncrono).
- [x] Binding en `AppServiceProvider`.
- [x] **Unit** `InMemorySyncEventBusTest` (5): match, interfaz, una vez por evento, noop, trait.

## FASE 2 — Contexto de petición + AuditableEvent + subscriber de auditoría  ✅
- [x] `AuditableEvent` (Shared/Domain/Event).
- [x] `RequestContextInterface` + `HttpRequestContext` (lee request/sesión, degrada a null en consola) + binding.
- [x] `AuditEventSubscriber` (Audit): `AuditableEvent` → `AuditEventDraft` (+ RequestContext, restaurantId vía TenantContext) → `AuditRecorderInterface`.
- [x] Subscriber registrado en el bus (provider).
- [x] **Unit** `AuditEventSubscriberTest` (3): mapeo correcto, subscribedTo, contexto null.

## FASE 3 — Migrar el módulo Tax al bus  ✅
- [x] Eventos `TaxCreated/TaxUpdated/TaxDeleted` (implements `AuditableEvent`).
- [x] Entidad `Tax` con `RecordsEvents` y registro en `dddCreate`/`update` (condicional)/`delete`.
- [x] `CreateTax/UpdateTax/DeleteTax`: inyectan `EventBusInterface`, sin `AuditRecorderInterface`, publican tras persistir.
- [x] Commands limpios (sin contexto de auditoría) + Requests `toCommand` simplificados.
- [x] **Unit** actualizados: CreateTax/UpdateTax/DeleteTax (mock bus) + `TaxEntityTest` (registro de eventos).

## FASE 4 — Verificación end-to-end  ✅
- [x] **Feature** `TaxAuditEventsTest` (4): create/update/delete dejan fila en `audit_logs` vía bus real; update sin cambios no audita.
- [x] Suite completa en verde: **941 passed**.
- [ ] Commit(s) en inglés sin co-author.

## FASE 5 — (Opcional, fuera del piloto) Replicar patrón
- [ ] Documentar "cómo añadir eventos a un módulo" y replicar en el siguiente módulo pequeño (Zone), validando que el patrón escala.

---

## Riesgos / notas
- **Tolerancia a fallos:** de momento síncrono y propaga (igual que hoy). Si en el futuro se quiere que un fallo de auditoría no tumbe la operación, se decide por subscriber.
- **Orden de subscribers:** determinista según registro en el provider.
- **Consola/cron:** `HttpRequestContext` debe degradar a null fuera de HTTP (el dispatcher de informes no audita Tax, pero conviene que no rompa).
- **Acoplamiento:** Tax depende de `AuditableEvent` (en Shared, no del módulo Audit). Audit depende de la interfaz, no de Tax. ✔ desacoplado.
- **Transacciones:** no hay transacciones explícitas hoy; publicar tras `save()` mantiene el comportamiento actual.
