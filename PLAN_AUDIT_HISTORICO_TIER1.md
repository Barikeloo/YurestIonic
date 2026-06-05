# Plan — Audit Histórico Panel · Tier 1 mejoras

> **Iniciado**: 2026-06-05
> **Estado actual**: 🟡 En curso · Paso 11 de 11 (verificación manual final)
> **Sesión activa**: A — Verificación de cadena + drill-down del chart

Mejoras al panel `/registro-auditoria/historico` orientadas a los 3 perfiles del usuario admin:
- **Compliance**: prueba visual de integridad y trazabilidad
- **Operativo**: navegación rápida a eventos puntuales del pasado
- **Análisis**: tendencias y desglose del corpus archivado

---

## Sesiones del Tier 1

| Sesión | Entregas | Estado |
|---|---|---|
| **A** | (4) Verificación de cadena en panel · (3) Drill-down del chart | 🟡 En curso |
| **B** | (1) Desglose por categoría · (5) Top usuarios del corpus | ⚪ Pendiente |
| **C** | (2) Anomalías históricas en timeline | ⚪ Pendiente |

> Numeración (1–5) corresponde a las "Ideas Tier 1" originales (ver al final).

---

## Decisiones tomadas

### Persistencia de la verificación de cadena
- **MVP**: `localStorage` por restaurante (`audit_chain_verify:{restaurantUuid}` → `{ at, isValid, total, verified, broken }`).
- ⚠️ **TODO imprescindible**: migrar a persistencia **server-side** antes de producción. Razones:
  - Compliance real exige que la verificación sea reproducible desde cualquier dispositivo del admin.
  - Un inspector debe poder pedir "última verificación" y obtener una respuesta canónica, no algo guardado en el navegador del que abrió la página.
  - Si el admin abre la app desde otro dispositivo, debe ver el mismo estado.
  - Requiere endpoint nuevo (`POST /admin/audit-chain/verifications` para registrar; `GET /admin/audit-chain/verifications/latest` para leer) + tabla `audit_chain_verifications` + use case.
- Marcar este TODO como bloqueante para release.

### Visibilidad del bloque de verificación
- Mostrar **siempre**, incluso si no hay eventos archivados.
- Si total = 0 → badge en estado vacío con "—" y CTA suave. Evita romper la jerarquía visual del panel.

### Orden de implementación
- **Paso a paso secuencial**. Cada paso verifica antes de pasar al siguiente.
- Verificación de cadena primero (Pasos 1–7) → Drill-down después (Pasos 8–11).

---

## Sesión A — Plan detallado

### Parte 1 — Verificación de cadena en panel

**Endpoint backend (ya existe)**: `GET /admin/audit-log/verify`
Respuesta:
```json
{
  "total_events": 40,
  "verified_count": 37,
  "broken_events": [{ "uuid": "...", "expected_hash": "...", "actual_hash": "..." }],
  "first_broken_index": 12,
  "is_valid": false
}
```

**Ubicación en UI**: bloque nuevo entre KPIs y chart mensual.

**Estados visuales del badge**:
| Estado | Color | Texto |
|---|---|---|
| No verificado | Gris | `🛡️ No verificado · [Verificar cadena]` |
| Cargando | Azul | `Verificando... · spinner` |
| Íntegro | Verde | `✅ Cadena íntegra · X/X eventos · hace Y · [Verificar de nuevo]` |
| Roto | Rojo | `⚠️ Cadena rota · X/Y verificados · N corruptos · [Ver eventos rotos ↓]` |
| Error | Naranja | `❌ No se pudo verificar · [Reintentar]` |

**Casos especiales**:
- `RetentionDemoSeeder` usa hashes placeholder (sha256 de ceros) → la verificación SIEMPRE reportará roto en demo. Buen test del estado rojo.
- Sin eventos en restaurante → estado vacío con "—" en lugar de números.
- La verificación cubre **todo el corpus** (activos + archivados), no respeta los filtros de rango del panel.

### Parte 2 — Drill-down del chart

**Hoy**: el chart es decorativo. Cada `bar-cell` es un div sin interacción.
**Objetivo**: click en barra → `/registro-auditoria?historico=1&dateFrom=YYYY-MM-01&dateTo=YYYY-MM-LL`

**Cálculo del rango por mes**:
- `key` viene como `YYYY-MM` (ej `2025-08`)
- `dateFrom` = `${key}-01`
- `dateTo` = último día del mes = `new Date(year, month, 0).getDate()`

**UX**:
- `bar-cell` se convierte en `<button>` con cursor pointer
- Hover: micro `translateY(-2px)` + fill más saturado
- `aria-label`: "Ver eventos archivados de {label}"

---

## Progreso paso a paso

| # | Paso | Archivos | Estado | Notas |
|---|------|----------|--------|-------|
| **1** | Backend smoke check del endpoint `/verify` | — | ✅ | Shape: `total_events`, `verified_count`, `broken_events[]`, `first_broken_index`, `is_valid`. Demo: 121/75 con 46 rotos (placeholder hashes del seeder). |
| **2** | `AuditLogService.verifyChain()` | `audit-log.service.ts` | ✅ | Tipos `VerifyAuditChainApi` + `BrokenAuditEventApi` añadidos. |
| **3** | Facade: estado `verifyState`, `verifyResult`, método `runVerify()` | `historico.facade.ts` | ✅ | `runVerify`, `hydrateVerifyFromStorage`, `toggleVerifyBrokenExpanded`. Persistencia en `audit_chain_verify:{uuid}`. TODO server-side marcado en cabecera del archivo. |
| **4** | Page TS: proxies y método | `historico.page.ts` | ✅ | Proxies `verifyState/Result/Error/BrokenExpanded`, `runVerify`, `toggleVerifyBrokenExpanded`, formatter `formatVerifiedAt`. |
| **5** | Page HTML: bloque badge entre KPIs y chart | `historico.page.html` | ✅ | 5 estados con `@switch (verifyState())` · clase `is-broken` para tinte rojo · lista colapsable de eventos corruptos. |
| **6** | Page SCSS: estilos por estado | `historico.page.scss` | ✅ | Gris/azul/verde/naranja + `is-broken` (rojo). Spinner CSS. Lista de rotos con typography mono. |
| **7** | Verificación manual estado roto (seeder placeholder) | — | ✅ | Confirmado por el usuario. |
| **8** | Page TS: método `drillDownToMonth(key)` | `historico.page.ts` | ✅ | Calcula `dateFrom = YYYY-MM-01` y `dateTo` = último día del mes con `new Date(y, m, 0).getDate()`. |
| **9** | Chart HTML: `bar-cell` → `<button>` clickable | `historico.page.html` | ✅ | Botón real con `type="button"`, `aria-label` y `title` ampliado. Hint "toca una barra para explorar ese mes" cuando hay datos. |
| **10** | Chart SCSS: hover + cursor | `historico.page.scss` | ✅ | Cursor pointer, lift `translateY(-3px)`, saturate + box-shadow en hover, label se oscurece/bold, `:focus-visible` con outline azul, `:active` micro-press. `pointer-events: none` en `.bar-fill` para que todo el botón sea click target. |
| **11** | Verificación manual drill-down | — | 🟡 En curso | Hard refresh y prueba: click en barra mensual → registro vivo con modo histórico y rango del mes. |
| **12** | Resumen final + handoff para commit | — | ⚪ | Sin tocar git hasta confirmación |

Leyenda: ✅ hecho · 🟡 en curso · ⚪ pendiente · ❌ bloqueado

---

## TODOs imprescindibles (no parte de Sesión A)

- [ ] **Verificación server-side**: migrar `localStorage` a tabla `audit_chain_verifications` + endpoints. Bloqueante para producción.
- [ ] Tests E2E del flujo completo de verificación (no aplica aún, esperar al commit final de la sesión).
- [ ] Considerar si el botón "Verificar de nuevo" debe rate-limitarse (1 por minuto) para evitar bombing.

---

## Sesiones futuras (Tier 1 restante)

### Sesión B — Desglose por categoría + Top usuarios
- Extender endpoint `archived-stats` con campos `by_category` y `top_users` (limit 5).
- Mini-chart de pie/donut + lista de usuarios con avatar.

### Sesión C — Anomalías históricas
- Query nueva: `audit_logs WHERE anomaly_kind IS NOT NULL AND archived_at IS NOT NULL`.
- Overlay de puntos rojos sobre el chart mensual + widget "Anomalías históricas (3): …".

---

## Apéndice — Ideas Tier 1 originales

1. **Desglose por categoría dentro del corpus archivado** → Sesión B
2. **Anomalías históricas en el timeline** → Sesión C
3. **Drill-down desde el chart** → Sesión A
4. **Verificación de cadena visible en panel** → Sesión A
5. **Top usuarios del corpus archivado** → Sesión B

## Apéndice — Ideas Tier 2/3 (futuro)

Tier 2: filtros adicionales en panel · comparación de periodos · trend indicators en KPIs · últimos runs del archivador
Tier 3: export PDF compliance-ready · exports programados · anotaciones en eventos · onboarding/tooltips
