# Plan de implementación: Dashboard de Finanzas
> Estado: EN CURSO · Última actualización: 2026-06-09

## Estado de fases

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Prerequisitos frontend (topbar, campana, facade signals) | ✅ Completado |
| 1 | Tab Caja con datos reales (cash sessions) | ✅ Completado |
| 2 | Backend `GetDashboardSummary` + Tab Resumen wired | ✅ Completado |
| 2b | Fase 2 frontend pendientes (forecast hide, mesas live, skeleton) | ✅ Completado |
| 3 | Backend `GetSalesReport` + `GetSaleDetail` + Tab Ventas + heatmap | ✅ Completado |
| 4 | Backend `GetProductsReport` + Tab Productos | ✅ Completado |
| 5 | Backend `GetEmployeesReport` + Tab Empleados | ✅ Completado |
| 6 | Backend `GetTaxReport` + Tab Impuestos | ⏳ Pendiente |
| 7 | Tab Informes (descargas reales) | ⏳ Pendiente |

---

## 0. Contexto y restricciones

### Lo que existe
| Capa | Estado |
|---|---|
| Prototipo visual (7 tabs) | **Completo** — diseño HTML/SCSS no se toca |
| Datos del prototipo | **100% mock** → reemplazados progresivamente por datos reales |
| `GET /tpv/cash-sessions/{id}/summary` | **Disponible** |
| `GET /tpv/cash-sessions`, `cash-movements` | **Disponible** |
| `GET /tpv/orders` | **Disponible** — órdenes abiertas (mesas activas) |
| `GET /api/admin/audit-alerts` | **Disponible** — campana real |
| `GET /api/admin/reports/summary` | **Disponible** ✅ |
| `GET /api/admin/reports/sales` | **Disponible** ✅ |
| `GET /api/admin/reports/sales/{uuid}` | **Disponible** ✅ |
| `GET /api/admin/reports/heatmap` | **Disponible** ✅ |
| `GET /api/admin/reports/products` | **Disponible** ✅ |
| `GET /api/admin/reports/employees` | **Disponible** ✅ |

### Datos disponibles en BD
- `sales` → `restaurant_id`, `value_date`, `total`, `status`, `cash_session_id`, `opened_by_user_id`, `cancelled_by_user_id`
- `sales_lines` → `restaurant_id`, `sale_id`, `product_id`, `user_id`, `quantity`, `price`, `tax_percentage`
- `products` → `id`, `family_id`, `name`, `price`, **`stock`** (gestionado, se decrementa en venta)
- `families` → `id`, `restaurant_id`, `name` (sin columna `color` — se asigna paleta en PHP)
- `sale_payments` → `restaurant_id`, `sale_id`, `method`, `amount_cents`, `user_id`
- `users` → `id`, `restaurant_id`, `name`, `role`
- `cash_sessions` → `id`, `restaurant_id`, `status`, `initial_amount_cents`, `opened_at`
- `tables` → `id`, `zone_id`, `name`
- `zones` → `id`, `restaurant_id`, `name`

### Widgets — estado actual
| Widget | Estado | Motivo |
|---|---|---|
| Predicción de cierre (IA) | 🚫 Oculto | Sin modelo ML |
| Estado de hardware | 🚫 Oculto | Sin integración física |
| Cross-sell / Canibalización | ⚠️ Placeholder | Análisis complejo, baja prioridad |
| Stock crítico / Dead stock | ✅ Real | `products.stock` existe y se gestiona |
| Rendimiento por zona | ✅ Real | `sales → orders → tables → zones` |
| Beneficio bruto (coste) | ⚠️ Placeholder `—` | Sin módulo de costes |
| Integraciones (Holded…) | 🚫 Decorativo | Feature futura |
| Informes programados | 🚫 Decorativo | Feature futura |

---

## 1. Arquitectura de datos: dominio `Reporting`

### Por qué un dominio separado
La capa de reporting cruza `Sale`, `Cash`, `Product`, `User` y `Family`. El dominio `Reporting` es
**read-only**: no tiene entidades, no muta estado. Sus casos de uso consultan la BD y devuelven proyecciones.

### Estructura de ficheros del backend
```
backend/app/Reporting/
├── Application/
│   ├── Shared/
│   │   └── DateRange.php
│   ├── GetDashboardSummary/   ✅ (3 archivos)
│   ├── GetSalesReport/        ✅ (3 archivos)
│   ├── GetSaleDetail/         ✅ (3 archivos)
│   ├── GetHeatmap/            ✅ (3 archivos)
│   ├── GetProductsReport/     ✅ (3 archivos)
│   ├── GetEmployeesReport/    ✅ (3 archivos)
│   └── GetTaxReport/          ⏳ (3 archivos)
├── Domain/
│   └── Interfaces/
│       └── ReportingRepositoryInterface.php
└── Infrastructure/
    ├── Persistence/
    │   └── EloquentReportingRepository.php
    └── Entrypoint/Http/
        ├── GetDashboardSummaryController.php   ✅
        ├── GetSalesReportController.php        ✅
        ├── GetSaleDetailController.php         ✅
        ├── GetHeatmapController.php            ✅
        ├── GetProductsReportController.php     ✅
        ├── GetEmployeesReportController.php    ✅
        ├── GetTaxReportController.php          ⏳
        └── Requests/
            ├── GetDashboardSummaryRequest.php  ✅
            ├── GetSalesReportRequest.php       ✅
            ├── GetProductsReportRequest.php    ✅
            ├── GetEmployeesReportRequest.php   ✅
            └── GetTaxReportRequest.php         ⏳
```

### Rutas (todas bajo `auth:sanctum` + `role:admin,supervisor`)
```
GET /api/admin/reports/summary?period=today|yesterday|week|month   ✅
GET /api/admin/reports/sales?period=...&page=1&per_page=50         ✅
GET /api/admin/reports/sales/{uuid}                                ✅
GET /api/admin/reports/heatmap                                     ✅
GET /api/admin/reports/products?period=...                         ✅
GET /api/admin/reports/employees?period=...                        ✅
GET /api/admin/reports/taxes?period=...&quarter=T1|T2|T3|T4        ⏳
```

---

## 2. Contratos de API

### 2.4 `GET /api/admin/reports/products` ✅

**Response actual:**
```json
{
  "period_revenue": 247385,
  "items": [
    {
      "name": "Caña de Cerveza",
      "family": "Bebidas",
      "family_color": "#ff4d4d",
      "units": 48,
      "revenue": 9600,
      "cost": 0,
      "price": 200,
      "pct": 3.88,
      "avg_daily": 12.0,
      "trend_spark": [10, 12, 8, 14, 11, 9, 12, 14, 10, 13, 11, 12, 14, 48]
    }
  ],
  "stock_critical": [
    { "name": "Agua sin gas", "family": "Bebidas", "stock": 2 }
  ],
  "no_sales_7d": [
    { "name": "Postre del día", "family": "Postres", "stock": 14 }
  ],
  "alert_count": 3,
  "by_zone": [
    { "name": "Terraza", "revenue": 148200, "tickets": 52 },
    { "name": "Interior", "revenue": 99185, "tickets": 32 }
  ]
}
```

**Queries en `EloquentReportingRepository.getProductsReport()`:**
- `period_revenue`: `SUM(total)` de `sales` en el rango
- `items`: JOIN `sales_lines + sales + products + families`, GROUP BY product_id, ORDER BY revenue DESC
- `trend_spark`: query separada GROUP BY `product_id × DATE(value_date)` últimos 14 días, post-procesada en PHP (array de 14 posiciones, 0 si sin venta ese día)
- `family_color`: paleta de 8 colores asignada en PHP por `family_id` (families no tiene columna color)
- `stock_critical`: productos con `stock ≤ 10` via JOIN families, ORDER BY stock ASC, LIMIT 8
- `no_sales_7d`: productos activos sin `sales_lines` en los últimos 7 días (subquery NOT IN)
- `alert_count`: COUNT productos con `stock ≤ 5`
- `by_zone`: `sales → orders → tables → zones`, SUM(total) + COUNT(DISTINCT sale_id) GROUP BY zone

### 2.5 `GET /api/admin/reports/employees` ⏳

**Response:**
```json
{
  "items": [
    {
      "user_uuid": "...",
      "name": "María García",
      "role": "supervisor",
      "initials": "MG",
      "color": "#ff4d4d",
      "tickets": 34,
      "revenue": 98400,
      "avg_ticket": 2894,
      "items_sold": 127,
      "tips": 1240,
      "discounts": 0,
      "cancellations": 1,
      "spark_revenue": [7200, 8100, 6800, 9200, 8400, 9840, 8900, 9100, 8700, 9400, 9100, 9840, 9600, 9840]
    }
  ]
}
```

**Queries:**
- Revenue/tickets: `SELECT u.uuid, u.name, u.role, COUNT(DISTINCT s.id) tickets, SUM(s.total) revenue, SUM(sl.quantity) items FROM sales s JOIN users u ON u.id=s.opened_by_user_id JOIN sales_lines sl ON sl.sale_id=s.id WHERE ... GROUP BY u.id`
- Tips: `SELECT user_id, SUM(amount_cents) FROM sale_payments WHERE method='tip' AND ...`
- Cancellations: `SELECT cancelled_by_user_id, COUNT(*) FROM sales WHERE status='cancelled' AND ...`
- Spark 14d: GROUP BY `user_id × DATE(value_date)`, post-procesado en PHP
- `initials`: primeras letras de `name` (PHP, no BD)
- `color`: paleta por `user_id % 8` (no hay columna color en users)

### 2.6 `GET /api/admin/reports/taxes` ⏳

**Response:**
```json
{
  "period_label": "Hoy · lunes 9 junio 2026",
  "breakdown": [
    { "rate": 10, "label": "Hostelería (IVA reducido)", "base": 224895, "tax": 22489, "total": 247384 },
    { "rate": 21, "label": "Otros (IVA general)", "base": 0, "tax": 0, "total": 0 }
  ],
  "tips_card": 2840,
  "quarterly": {
    "quarter": "T2",
    "period": "T2 · abr-jun 2026",
    "elapsed_pct": 31,
    "rates": [
      { "rate": 10, "base": 1847200, "tax": 184720 },
      { "rate": 21, "base": 0,       "tax": 0 }
    ]
  }
}
```

**Query:** `SELECT sl.tax_percentage rate, SUM(sl.price*sl.quantity) base, SUM(sl.price*sl.quantity*sl.tax_percentage/100) tax FROM sales_lines sl JOIN sales s WHERE ... GROUP BY sl.tax_percentage`

---

## 3. Arquitectura frontend

### 3.1 `FinanzasService` ✅
```
frontend/src/app/services/finanzas.service.ts
```
Métodos: `getSummary`, `getSales`, `getSaleDetail`, `getHeatmap`, `getProducts`. Pendientes: `getEmployees`, `getTaxes`.

### 3.2 `FinanzasFacade` ✅
Patrón: signals privados + readonly públicos + `_periodChanges$ = toObservable(_period)` con `switchMap` para reload automático al cambiar periodo. `takeUntilDestroyed` en todas las subscriptions.

### 3.3 Tipos en `finanzas.models.ts`
Añadidos: `ProductReportItem`, `StockAlertItem`, `ZoneReportItem`, `ProductsReportResponse` (con `stock_critical`, `no_sales_7d`, `alert_count`, `by_zone`), `EmployeeReportItem`, `EmployeesReportResponse`. Pendientes: tipos de impuestos (`TaxBreakdown`, `TaxReportResponse`).

---

## 4. Detalle de fases

### Fase 4 — Tab Productos ✅ Completado

#### Backend ✅
1. `GetProductsReportCommand` / `GetProductsReportResponse` / `GetProductsReport` use case
2. `EloquentReportingRepository.getProductsReport()` — 6 queries (ranking, sparks 14d, stock_critical, no_sales_7d, alert_count, by_zone)
3. `GetProductsReportRequest` (valida `period`)
4. `GetProductsReportController`
5. Ruta `GET /api/admin/reports/products`

#### Frontend ✅
1. `FinanzasService.getProducts()` + `FinanzasFacade.loadProducts()`
2. Recarga automática al cambiar periodo via `_periodChanges$`
3. KPI strip: Ingresos (real), Unidades (real), Beneficio bruto (`—` por falta de módulo costes), Alertas stock (real, rojo si > 0)
4. Ventas por familia: gráfico de barras verticales con colores de paleta (real)
5. Stock crítico: lista con badge Agotado/X uds/Bajo (real desde `products.stock`)
6. Sin ventas: lista de productos activos sin venta en 7 días (real)
7. Cross-sell: placeholder informativo (sin datos de correlación suficientes)
8. Rendimiento por zona: ranking con barra proporcional y conteo de tickets (real)
9. Filtros: por familia + contador + exportar CSV (BOM UTF-8, separador `;`)
10. Tabla ranking: columnas Pos/Producto/Familia/Uds/Ingresos/% periodo (barra)/Tendencia 14d
11. Skeleton loaders en todas las secciones
12. Estado vacío en tabla con mensaje contextual
13. **Fix tendencia**: cuando `older = 0` (sin historial previo) → badge "Nuevo" azul en lugar de `+100%` hardcodeado

#### Decisiones tomadas
- `families` no tiene columna `color` → paleta de 8 colores asignada en PHP por `family_id`
- Columnas Margen y Stock eliminadas de la tabla (datos no disponibles sin módulo costes/stock bidireccional)
- Umbral stock crítico: `≤ 10` en lista, `≤ 5` para `alert_count` (hardcodeado, configurable en futuro)
- `no_sales_7d` siempre mira los últimos 7 días reales, independiente del periodo del filtro

---

### Fase 5 — Backend `GetEmployeesReport` + Tab Empleados ✅ Completado

#### Backend ✅
1. `GetEmployeesReportCommand` / `GetEmployeesReportResponse` / `GetEmployeesReport` use case
2. `EloquentReportingRepository.getEmployeesReport()` — 4 queries:
   - Revenue/tickets/items por empleado: JOIN `users + sales + sales_lines`, GROUP BY `u.id`, ORDER BY revenue DESC
   - Tips: `sale_payments WHERE method='tip'` GROUP BY `user_id` (mapa por `(int)$row->user_id`)
   - Cancellations: `sales WHERE status='cancelled'` GROUP BY `cancelled_by_user_id`
   - Spark 14d: GROUP BY `user_id × DATE(value_date)`, post-procesado en PHP (array 14 posiciones, 0 si sin venta)
   - `initials` (primeras letras de `name`) y `color` (paleta por `user_id % 8`) generados en PHP
3. `GetEmployeesReportRequest` (valida `period`)
4. `GetEmployeesReportController` (patrón try/catch domain exception + `\Throwable`)
5. Ruta `GET /api/admin/reports/employees`

#### Frontend ✅
1. `FinanzasService.getEmployees()` + `FinanzasFacade` signals: `_employeesReport`, `_loadingEmployees`, recarga via `_periodChanges$`
2. `empleados-tab.component.ts` — computed signals: `sortedEmps`, `selectedEmp`, KPI aggregates; sort por revenue/tickets/avg/tips
3. `empleados-tab.component.html` — skeleton completo (KPIs + sort row + cards + detalle), cards 2×N con spark SVG inline, panel detalle lateral con spark 14d grande, stat-boxes Anulaciones y Artículos vendidos, flags de descuentos/anulaciones
4. Estado vacío cuando no hay empleados con ventas en el periodo

#### Decisiones tomadas
- Solo aparecen empleados con ventas cerradas en el periodo — comportamiento correcto para un dashboard de rendimiento
- `discounts = 0` hardcodeado hasta módulo de descuentos por línea
- KPI "Empleados del periodo" en lugar de "activos X/Y" — no existe columna `active` en `users`
- Color por `user_id % 8` con la misma paleta de 8 colores que familias

---

### Fase 6 — Backend `GetTaxReport` + Tab Impuestos ⏳

#### Backend
1. `GetTaxReportCommand` / `Response` / UseCase
2. `EloquentReportingRepository.getTaxReport()`:
   - Breakdown por tipo IVA: GROUP BY `tax_percentage` desde `sales_lines`
   - Tips desde `sale_payments`
   - Quarterly: misma query con rango trimestral calculado en `DateRange`
3. `GetTaxReportRequest` (valida `period` O `quarter`, mutuamente excluyentes)
4. `GetTaxReportController`
5. Ruta `GET /api/admin/reports/taxes`

#### Frontend
1. `FinanzasService.getTaxes()` + `FinanzasFacade.loadTaxes(quarter?)`
2. `impuestos-tab.component.ts`:
   - Breakdown fiscal: `facade.taxReport()?.breakdown`
   - Modelo 303 (quarterly): `facade.taxReport()?.quarterly`
   - El tab llama a `facade.loadTaxes(activeQ())` cuando cambia el trimestre activo
3. Datos del PDF borrador desde `RestaurantContextService`

---

### Fase 7 — Tab Informes (descargas reales) ⏳

```
GET /api/admin/reports/export/sales?period=...&format=csv|pdf
GET /api/admin/reports/export/products?period=...&format=csv|pdf
GET /api/admin/reports/export/employees?period=...&format=csv|pdf
GET /api/admin/reports/export/taxes?period=...&format=csv|pdf
```

Reutilizan la lógica de los endpoints existentes + formateo. Integraciones y programados siguen siendo mock decorativo.

---

## 5. Mejoras y deuda técnica identificada

### Pendientes de producto
| Item | Prioridad | Nota |
|---|---|---|
| Umbral stock crítico configurable por restaurante | Media | Ahora hardcodeado a 10/5 en repositorio |
| Módulo de costes | Baja (roadmap) | Desbloquea "Beneficio bruto" y margen en tabla productos |
| Cross-sell real | Baja | Requiere market basket analysis; datos existen en `sales_lines` |
| Widget drag-and-drop en tabs | Media | Solicitado; propuesta: `angular-gridster2` + localStorage por tab |
| Color de familia configurable | Media | Ahora paleta PHP; debería ser campo en `families` |
| `no_sales_7d` con umbral configurable | Baja | Ahora fijo 7 días |

### Deuda técnica
| Item | Archivo | Nota |
|---|---|---|
| `families` sin columna `color` | migration | Añadir `color` varchar nullable; mientras tanto paleta PHP |
| Spark de tendencia solo muestra "Nuevo" si `older=0` | `productos-tab.component.ts` | Correcto para MVP; mejorar con más historial |
| `pct` calculado con `period_revenue` total, no familia | `EloquentReportingRepository` | El % refleja sobre el total del periodo, no sobre la familia |
| Skeleton mid-row fijo 2 cols | `productos-tab.component.html` | Si se añaden más cards al mid-row hay que actualizar el skeleton |

---

## 6. Convenciones a respetar

| Convención | Referencia |
|---|---|
| 8 building blocks por endpoint | `.claude/skills/ddd-controller-pattern/SKILL.md` |
| Response constructor `private` + factory `::create()` | SKILL.md §3 |
| Command `final readonly`, sin framework types | SKILL.md §2 |
| Controlador: `try/catch` domain exception + `\Throwable` | SKILL.md §6 |
| Repositorio `final` con binding en `AppServiceProvider.register()` | SKILL.md §7–8 |
| Use case: no importa `Illuminate\*` | SKILL.md §4 |
| Facade: signals privados + readonly públicos + setters explícitos | patrón existente |
| `takeUntilDestroyed` en todas las subscriptions del facade | patrón existente |
| `_periodChanges$ = toObservable(_period).pipe(switchMap(...))` para recarga automática | patrón existente |
| Paleta de colores para familias: `['#ff4d4d','#1a9e5a','#0077cc','#d18a1c','#7857d6','#3d3d3d','#ff8800','#9b59b6']` | `EloquentReportingRepository` |
