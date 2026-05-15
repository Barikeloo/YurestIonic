# Especificacion: Dashboard de Contabilidad y Finanzas

Documento de planificacion para el modulo de finanzas del TPV.
Este modulo vive dentro del panel de administracion (`/admin/finanzas`) y consume datos de pedidos, caja, productos y familias.

---

## 1. Vision General

Una unica pagina de gestion financiera con multiples secciones tabuladas (tabs o sidebar).
El admin accede desde el menu de gestion y ve un resumen ejecutivo por defecto.

**URL propuesta:** `/admin/finanzas`
**Entrada en menu:** `Finanzas` (junto a Productos, Familias, Zonas...)

---

## 2. Estructura de la Pagina

```
+-------------------------------------------------------------+
|  FINANZAS  |  [Hoy] [Ayer] [Esta semana] [Este mes] [Personalizado]  |
+-------------------------------------------------------------+
|                                                             |
|  [Resumen] [Ventas] [Productos] [Caja] [Impuestos] [Informes] |
|  +---------------------------------------------------------+ |
|  |  CONTENIDO DE LA PESTANA ACTIVA                         | |
|  +---------------------------------------------------------+ |
|                                                             |
+-------------------------------------------------------------+
```

**Selector de periodo global:** afecta a todas las pestanas salvo que el usuario sobrescriba.
- **Hoy** (00:00 hasta ahora)
- **Ayer**
- **Esta semana** (lunes a domingo actual)
- **Este mes**
- **Personalizado** (datepickers desde/hasta)

---

## 3. PESTANA: Resumen (Dashboard Ejecutivo)

Objetivo: ver el estado del negocio de un vistazo.

### 3.1 KPIs superiores (tarjetas)

| KPI | Formula | Color tendencia |
|-----|---------|-----------------|
| **Ingresos totales** | SUM(total de pedidos pagados) | vs periodo anterior |
| **N tickets** | COUNT(pedidos) | vs periodo anterior |
| **Ticket promedio** | Ingresos / N tickets | vs periodo anterior |
| **Productos vendidos** | SUM(cantidad de lineas) | vs periodo anterior |
| **Caja actual** | Saldo de sesion abierta (si hay) | -- |

**Tendencia:** flecha verde/roja con % de diferencia vs el mismo periodo anterior.
Ejemplo: "Ingresos hoy: 1.247,50 EUR ↑ +12% vs ayer"

### 3.2 Grafico de ventas por hora (barras)

- Eje X: horas del dia (08, 09, 10... 23)
- Eje Y: importe vendido
- Serie: ingresos por hora
- Util: identificar horas pico y valle para staffing

### 3.3 Top 5 productos (hoy / periodo)

Tabla compacta:
| # | Producto | Familia | Uds | Ingresos |
|---|----------|---------|-----|----------|
| 1 | Tarta de queso | Postres | 24 | 216,00 EUR |

### 3.4 Distribucion por familia (donut chart)

% de ingresos por familia sobre el total del periodo.
Permite ver que categorias tiran del carro.

### 3.5 Estado de caja (si hay sesion abierta)

- Inicio: 150,00 EUR
- Ventas: 1.247,50 EUR
- Movimientos: -50,00 EUR (retirada)
- Teorico: 1.347,50 EUR
- Arqueo: pendiente / 1.340,00 EUR
- Diferencia: -7,50 EUR (si cerrada)

---

## 4. PESTANA: Ventas Detalladas

### 4.1 Tabla de pedidos

Filtros: fecha desde/hasta, estado (pagado/pendiente/cancelado), zona, rango de importe.

| ID | Mesa/Zona | Estado | Total | Propina | Fecha | Acciones |
|----|-----------|--------|-------|---------|-------|----------|
| 1024 | Barra 3 | Pagado | 45,50 | 2,00 | 14/05 14:32 | Ver |

- Paginacion: 20 por pagina
- Ordenacion: por fecha descendente por defecto
- Accion "Ver": modal con detalle de lineas, impuestos, pagos

### 4.2 Totales del filtro

Sobre la tabla, una barra de resumen:
- Total filtrado: X EUR
- N pedidos: Y
- Efectivo: X EUR | Tarjeta: Y EUR (si guardamos metodo de pago)

### 4.3 Grafico de evolucion

Line chart: un punto por dia del periodo seleccionado.
Y: ingresos. Tooltip: fecha + importe + n tickets.

---

## 5. PESTANA: Productos y Rentabilidad

### 5.1 Ranking de productos

Tabla ordenable con paginacion:

| Producto | Familia | Uds vendidas | Ingresos | % del total | Stock actual |
|----------|---------|-------------|----------|-------------|--------------|
| Cafe solo | Bebidas | 1.240 | 1.240,00 | 18% | 45 |

Filtros: familia, solo con stock bajo, solo sin ventas.

### 5.2 Productos sin ventas (dead stock)

Lista de productos activos con 0 unidades vendidas en el periodo.
Accion directa: "Desactivar" (llama al endpoint de desactivacion).

### 5.3 Rotacion de stock (si se quiere avanzado)

Stock actual / ventas promedio diarias = dias de existencias.
Productos con < 3 dias de stock: alerta.

### 5.4 Ventas por familia

Bar chart vertical: cada barra es una familia.
Altura = ingresos. Encima: % del total.

---

## 6. PESTANA: Caja y Arqueos

### 6.1 Sesion de caja actual (si abierta)

Tarjeta grande con:
- Hora de apertura
- Responsable (usuario que abrio)
- Saldo inicial
- Ventas acumuladas
- Movimientos (entradas/salidas)
- Saldo teorico actual
- Boton: "Cerrar caja" (redirige a cierre)

### 6.2 Historial de sesiones

Tabla:

| ID | Apertura | Cierre | Inicial | Ventas | Movimientos | Teorico | Arqueo | Diferencia | Estado |
|----|----------|--------|---------|--------|-------------|---------|--------|------------|--------|
| 42 | 14/05 08:00 | 14/05 23:45 | 150 | 1.247 | -50 | 1.347 | 1.340 | -7,50 | Cerrada |

**Diferencia**:
- 0: verde
- +/- < 5 EUR: amarillo
- +/- >= 5 EUR: rojo

### 6.3 Movimientos de caja del periodo

Tabla de todos los movimientos manuales:

| Fecha | Tipo | Importe | Motivo | Usuario |
|-------|------|---------|--------|---------|
| 14/05 16:00 | Salida | -50,00 | Compra cafe | Juan |

Filtros: tipo (entrada/salida/todos), usuario.

---

## 7. PESTANA: Impuestos

### 7.1 Desglose por tramo

El sistema ya maneja `Tax` con `percentage`.

| Tipo IVA | Base imponible | IVA | Total |
|----------|----------------|-----|-------|
| 4% | 500,00 | 20,00 | 520,00 |
| 10% | 800,00 | 80,00 | 880,00 |
| 21% | 200,00 | 42,00 | 242,00 |
| **Total** | **1.500,00** | **142,00** | **1.642,00** |

### 7.2 Selector de periodo fiscal

- Trimestre 1 (enero-marzo)
- Trimestre 2 (abril-junio)
- Trimestre 3
- Trimestre 4
- Personalizado

### 7.3 Resumen para modelo 303

Base imponible total + IVA repercutido por tramo.
Formato listo para copiar al modelo 303 simplificado.

---

## 8. PESTANA: Informes y Exportacion

### 8.1 Informes predefinidos

1. **Resumen diario** (ventas, tickets, caja)
2. **Ventas por producto** (CSV)
3. **Ventas por familia** (CSV)
4. **Movimientos de caja** (CSV)
5. **Desglose de impuestos** (PDF para contable)

### 8.2 Formatos de exportacion

- **CSV**: para Excel / importacion contable
- **PDF**: para archivar / enviar al gestor
- **Pantalla**: para consulta rapida

---

## 9. Navegacion y Layout Responsive

### Desktop (>1024px)
- Sidebar izquierda con pestanas
- Contenido derecha con grids y graficos
- Selector de periodo arriba, sticky

### Tablet (640-1024px)
- Tabs horizontales scrollables
- Graficos se apilan en 1 columna
- Tablas con scroll horizontal

### Movil (<640px)
- Selector de periodo compacto (botones)
- KPIs en tarjetas apiladas verticalmente
- Tablas con filas colapsables
- Graficos simplificados o con zoom

---

## 10. Datos Necesarios del Backend

### 10.1 Tablas que YA existen y se usan

- `orders`: id, table_zone_id, status, total, tip_amount, created_at, updated_at
- `order_lines`: id, order_id, product_id, quantity, unit_price, total_price, created_at
- `products`: id, family_id, tax_id, name, price, stock, active...
- `families`: id, name, active...
- `taxes`: id, name, percentage...
- `cash_sessions`: id, user_id, opening_amount, closing_amount, opened_at, closed_at, status...
- `cash_movements`: id, cash_session_id, type, amount, reason, created_at...
- `users`: id, name... (para saber quien abrio/cerro caja)

### 10.2 Nuevos endpoints API necesarios

Todos bajo `/api/admin/finanzas` con middleware de admin.

#### GET `/api/admin/finanzas/summary`
Query params: `from`, `to` (ISO 8601)
Response:
```json
{
  "totalRevenue": 124750,
  "totalOrders": 42,
  "averageTicket": 2970,
  "totalItemsSold": 156,
  "previousPeriod": {
    "totalRevenue": 110000,
    "totalOrders": 38,
    "changePercent": 13.4
  },
  "topProducts": [...],
  "salesByHour": [0,0,0,0,1500,3200,...],
  "salesByFamily": [...]
}
```

#### GET `/api/admin/finanzas/orders`
Query params: `from`, `to`, `status`, `zone_id`, `page`, `per_page`
Response: paginated list de pedidos con totales agregados.

#### GET `/api/admin/finanzas/orders/{id}`
Detalle completo de un pedido (lineas, impuestos).

#### GET `/api/admin/finanzas/products`
Query params: `from`, `to`, `family_id`, `onlyNoSales`, `page`
Response: ranking de productos con unidades, ingresos, stock.

#### GET `/api/admin/finanzas/cash-summary`
Query params: `from`, `to`
Response: resumen de sesiones de caja en el periodo.

#### GET `/api/admin/finanzas/cash-movements`
Query params: `from`, `to`, `type`, `page`
Response: lista de movimientos de caja.

#### GET `/api/admin/finanzas/taxes`
Query params: `from`, `to`
Response: desglose de IVA por tramo.

#### GET `/api/admin/finanzas/reports/{type}`
Query params: `from`, `to`, `format` (csv/pdf)
Response: archivo para descargar.

### 10.3 Nuevos casos de uso (Application)

Siguiendo DDD del proyecto:

```
backend/app/Reporting/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   │   ├── ReportPeriod.php          (from, to, validation)
│   │   ├── Revenue.php               (VO para dinero, como ProductPrice)
│   │   └── TaxBreakdown.php          (tramo + base + cuota)
│   └── Interfaces/
│       └── ReportingRepositoryInterface.php
├── Application/
│   ├── GetSummary/
│   │   ├── GetSummary.php
│   │   ├── GetSummaryCommand.php
│   │   └── GetSummaryResponse.php
│   ├── GetOrderDetail/
│   ├── GetProductRanking/
│   ├── GetCashSummary/
│   ├── GetTaxBreakdown/
│   └── ExportReport/
└── Infrastructure/
    ├── Persistence/
    │   └── Repositories/
    │       └── EloquentReportingRepository.php
    └── Entrypoint/
        └── Http/
            ├── SummaryController.php
            ├── OrdersController.php
            ├── ProductsController.php
            ├── CashController.php
            ├── TaxesController.php
            └── ExportController.php
```

**Nota:** No modificar entidades existentes. El repositorio de reporting hace joins/aggregations directamente sobre las tablas existentes (read-only).

---

## 11. Componentes Frontend Propuestos

```
frontend/src/app/features/finanzas/
├── pages/
│   └── finanzas/
│       ├── finanzas.page.ts
│       ├── finanzas.page.html
│       └── finanzas.page.scss
├── components/
│   ├── period-selector/
│   │   └── period-selector.component.ts
│   ├── kpi-cards/
│   │   └── kpi-cards.component.ts
│   ├── sales-chart/
│   │   └── sales-chart.component.ts
│   ├── family-donut/
│   │   └── family-donut.component.ts
│   ├── orders-table/
│   │   └── orders-table.component.ts
│   ├── product-ranking/
│   │   └── product-ranking.component.ts
│   ├── cash-summary/
│   │   └── cash-summary.component.ts
│   ├── tax-breakdown/
│   │   └── tax-breakdown.component.ts
│   └── export-panel/
│       └── export-panel.component.ts
├── facades/
│   └── finanzas.facade.ts
└── services/
    └── finanzas.service.ts
```

### Libreria de graficos
Recomendacion: **ApexCharts** (angular-apexcharts) o **Chart.js** (ng2-charts).
ApexCharts tiene mas estilo moderno y animaciones suaves.

---

## 12. Permisos y Seguridad

- Solo accesible para usuarios con rol `admin` o `manager`.
- Reutilizar middleware existente `RequireManagementSession`.
- Los datos son read-only (no modifica pedidos ni productos).
- Exportaciones pueden ser pesadas: limitar a 10.000 registros o usar streaming para CSV.

---

## 13. Fases de Implementacion Recomendadas

### Fase 1: Resumen + Ventas (MVP)
- Backend: `GetSummary`, `GetOrders` con filtros de fecha
- Frontend: pestana Resumen con KPIs + tabla de pedidos
- Sin graficos aun, solo numeros y tablas

### Fase 2: Graficos
- Anadir ApexCharts
- Grafico ventas por hora en Resumen
- Grafico evolucion en Ventas
- Donut por familia

### Fase 3: Productos y Caja
- Ranking de productos
- Historial de caja
- Movimientos de caja

### Fase 4: Impuestos y Exportacion
- Desglose de IVA
- Exportacion CSV/PDF
- Informes predefinidos

### Fase 5: Polish
- Responsive completo
- Loading states skeleton
- Tooltips explicativos
- Accesos directos desde tarjetas (click en KPI -> pestana detalle)

---

## 14. Preguntas Pendientes (para decidir antes de empezar)

1. **Metodo de pago**: Guardamos si el pedido se pago en efectivo o tarjeta? (ahora no, se podria anadir en Order)
2. **Costes de producto**: Tenemos coste de compra para calcular margen? (ahora no, se podria anadir campo `cost` a Product)
3. **Usuarios en caja**: El `CashSession` tiene `user_id`? (verificar modelo)
4. **Propinas**: Ya se guarda `tip_amount` en Order. Se incluye en ingresos totales o va aparte?
5. **Zonas vs Mesas**: Se vende por zonas o por mesas individuales? Afecta al analisis por zona.

---

## 15. Mockup de Texto del Tablero Principal

```
+------------------------------------------------------------------+
|  FINANZAS                                           [Hoy] [Mes]  |
+------------------------------------------------------------------+
|  [Resumen] [Ventas] [Productos] [Caja] [Impuestos]               |
+------------------------------------------------------------------+
|                                                                  |
|  +-------------+  +-------------+  +-------------+  +----------+  |
|  | 1.247,50 EUR|  |    42       |  |   29,70 EUR |  |  156     |  |
|  | Ingresos    |  |  Tickets    |  |Ticket medio |  | Productos|  |
|  |   +12%      |  |   +10%      |  |    +2%      |  |   +5%    |  |
|  +-------------+  +-------------+  +-------------+  +----------+  |
|                                                                  |
|  +---------------------------+    +---------------------------+   |
|  | Ventas por hora (barras)  |    |   Top 5 productos         |   |
|  |                           |    |   1. Cafe solo  124 uds   |   |
|  |   |||                     |    |   2. Coca cola   98 uds   |   |
|  |   ||||||                  |    |   3. Tarta queso 45 uds   |   |
|  |   |||||||||||             |    |                           |   |
|  |   10  12  14  16  18  20  |    |                           |   |
|  +---------------------------+    +---------------------------+   |
|                                                                  |
|  +---------------------------+    +---------------------------+   |
|  | Distribucion por familia  |    |   Estado de caja          |   |
|  |   [donut chart]           |    |   Abierta desde 08:00     |   |
|  |   Bebidas 45%             |    |   Saldo teorico: 1.347   |   |
|  |   Comidas 35%             |    |   Ventas: 1.247           |   |
|  |   Postres 20%             |    |   Movimientos: -50        |   |
|  +---------------------------+    +---------------------------+   |
|                                                                  |
+------------------------------------------------------------------+
```

---

*Documento creado para planificacion. No incluye codigo de implementacion.*
