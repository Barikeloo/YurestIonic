# Plan: Separación Lectura/Escritura — CQRS-lite en Reporting

## Contexto y diagnóstico

El codebase ya tiene la separación conceptual de escritura/lectura:

- **Modelo de escritura** (por módulo): `OrderRepositoryInterface`, `SaleRepositoryInterface`, etc.
  trabajan con **agregados de dominio**, value objects y domain events. Los Eloquent repos
  guardan/recuperan entidades a través del dominio.

- **Modelo de lectura** (`Reporting`): `EloquentReportingRepository` usa `DB::table()` crudo
  con JOINs optimizados, **sin tocar ningún agregado**. Devuelve arrays planos.
  Ya es un read model — pero de forma implícita.

### El problema concreto

`ReportingRepositoryInterface` es una **interfaz dios** con 10 métodos y 1280 líneas de implementación.
Cada use case depende del contrato completo aunque sólo use 1–2 métodos.
Esto viola ISP (Interface Segregation Principle) y hace imposible testear/sustituir en aislamiento.

```
GetProductsReport → ReportingRepositoryInterface (10 métodos) ← solo usa getProductsReport() + getRestaurantInfo()
GetSaleDetail     → ReportingRepositoryInterface (10 métodos) ← solo usa getSaleDetail()
GetHeatmap        → ReportingRepositoryInterface (10 métodos) ← solo usa getHeatmap()
...
```

### Objetivo

Partir la interfaz dios en **interfaces segregadas por responsabilidad**, en un namespace
`ReadModel` explícito, manteniendo `ReportingRepositoryInterface` como **interfaz compuesta**
para los dos consumers de infraestructura que realmente necesitan la API completa.
Cero cambio de comportamiento — sólo contratos más precisos.

---

## Mapa de métodos → interfaces objetivo

| Método actual | Interface nueva | Namespace |
|---|---|---|
| `getDashboardData()` | `DashboardReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getRestaurantInfo()` | `RestaurantInfoReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getSalesList()` | `SalesReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getSaleDetail()` | `SalesReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getProductsReport()` | `ProductsReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getCashReport()` | `CashReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getFamiliesReport()` | `FamiliesReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getTaxReport()` | `TaxReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getEmployeesReport()` | `EmployeesReadRepositoryInterface` | `Reporting\Domain\ReadModel` |
| `getHeatmap()` | `HeatmapReadRepositoryInterface` | `Reporting\Domain\ReadModel` |

## Mapa de use cases → interfaces que inyectarán

| Use case | Interfaces que necesita |
|---|---|
| `GetDashboardSummary` | `DashboardReadRepositoryInterface` |
| `GetDailyReport` | `DashboardReadRepositoryInterface`, `RestaurantInfoReadRepositoryInterface` |
| `GetProductsReport` | `ProductsReadRepositoryInterface`, `RestaurantInfoReadRepositoryInterface` |
| `GetSalesReport` | `SalesReadRepositoryInterface` |
| `GetSaleDetail` | `SalesReadRepositoryInterface` |
| `GetCashReport` | `CashReadRepositoryInterface` |
| `GetFamiliesReport` | `FamiliesReadRepositoryInterface`, `RestaurantInfoReadRepositoryInterface` |
| `GetTaxReport` | `TaxReadRepositoryInterface` |
| `GetEmployeesReport` | `EmployeesReadRepositoryInterface` |
| `GetHeatmap` | `HeatmapReadRepositoryInterface` |

## Consumers de infraestructura (no cambian)

`ExportReportController` y `ReportFileGenerator` usan prácticamente todos los métodos
(son orquestadores transversales). Seguirán inyectando `ReportingRepositoryInterface`,
que se refactorizará para extender todas las interfaces segmentadas. No tocarlos.

---

## Arquitectura resultado

```
Reporting/
  Domain/
    Interfaces/
      ReportingRepositoryInterface.php   ← MODIFICADO: extends todos los ReadModel interfaces
    ReadModel/                           ← NUEVO namespace
      DashboardReadRepositoryInterface.php
      RestaurantInfoReadRepositoryInterface.php
      SalesReadRepositoryInterface.php
      ProductsReadRepositoryInterface.php
      CashReadRepositoryInterface.php
      FamiliesReadRepositoryInterface.php
      TaxReadRepositoryInterface.php
      EmployeesReadRepositoryInterface.php
      HeatmapReadRepositoryInterface.php
  Application/
    GetDashboardSummary/GetDashboardSummary.php   ← MODIFICADO
    GetDailyReport/GetDailyReport.php             ← MODIFICADO
    GetProductsReport/GetProductsReport.php       ← MODIFICADO
    GetSalesReport/GetSalesReport.php             ← MODIFICADO
    GetSaleDetail/GetSaleDetail.php               ← MODIFICADO
    GetCashReport/GetCashReport.php               ← MODIFICADO
    GetFamiliesReport/GetFamiliesReport.php       ← MODIFICADO
    GetTaxReport/GetTaxReport.php                 ← MODIFICADO
    GetEmployeesReport/GetEmployeesReport.php     ← MODIFICADO
    GetHeatmap/GetHeatmap.php                     ← MODIFICADO
  Infrastructure/
    Persistence/
      EloquentReportingRepository.php   ← MODIFICADO: implements nuevas interfaces via ReportingRepositoryInterface
    Entrypoint/Http/
      ExportReportController.php        ← SIN CAMBIOS
    Services/
      ReportFileGenerator.php           ← SIN CAMBIOS
Providers/
  AppServiceProvider.php                ← MODIFICADO: añadir bindings de las 9 interfaces nuevas
```

---

## Pasos de implementación

### PASO 1 — Crear las 9 interfaces de ReadModel `[x]`

Crear directorio `backend/app/Reporting/Domain/ReadModel/` con 9 ficheros.
Cada interfaz declara SOLO los métodos que le corresponden, con los mismos tipos de firma
que los métodos actuales en `ReportingRepositoryInterface`.

Ficheros a crear:
- `DashboardReadRepositoryInterface.php` → `getDashboardData(int, DateRange): array`
- `RestaurantInfoReadRepositoryInterface.php` → `getRestaurantInfo(int): array`
- `SalesReadRepositoryInterface.php` → `getSalesList(int, DateRange, int, int): array` + `getSaleDetail(int, string): ?array`
- `ProductsReadRepositoryInterface.php` → `getProductsReport(int, DateRange): array`
- `CashReadRepositoryInterface.php` → `getCashReport(int, DateRange): array`
- `FamiliesReadRepositoryInterface.php` → `getFamiliesReport(int, DateRange): array`
- `TaxReadRepositoryInterface.php` → `getTaxReport(int, DateRange, DateRange, string, int): array`
- `EmployeesReadRepositoryInterface.php` → `getEmployeesReport(int, DateRange): array`
- `HeatmapReadRepositoryInterface.php` → `getHeatmap(int): array`

### PASO 2 — Refactorizar `ReportingRepositoryInterface` como interfaz compuesta `[x]`

Modificar `Domain/Interfaces/ReportingRepositoryInterface.php` para que extienda
todas las interfaces del Paso 1 en lugar de declarar los métodos directamente.

```php
interface ReportingRepositoryInterface extends
    DashboardReadRepositoryInterface,
    RestaurantInfoReadRepositoryInterface,
    SalesReadRepositoryInterface,
    ProductsReadRepositoryInterface,
    CashReadRepositoryInterface,
    FamiliesReadRepositoryInterface,
    TaxReadRepositoryInterface,
    EmployeesReadRepositoryInterface,
    HeatmapReadRepositoryInterface
{}
```

`EloquentReportingRepository` implementa `ReportingRepositoryInterface` →
hereda automáticamente la obligación de implementar todos los métodos segmentados. Sin cambios en la clase.

### PASO 3 — Añadir bindings en `AppServiceProvider` `[x]`

Para cada una de las 9 interfaces nuevas, añadir un binding que resuelva a `EloquentReportingRepository`.

```php
$this->app->bind(DashboardReadRepositoryInterface::class, EloquentReportingRepository::class);
$this->app->bind(RestaurantInfoReadRepositoryInterface::class, EloquentReportingRepository::class);
// ... etc para las 9
```

El binding existente de `ReportingRepositoryInterface` no se toca.

### PASO 4 — Actualizar los 10 use cases `[x]`

Para cada use case: cambiar el constructor para que inyecte su(s) interfaz/interfaces
narrow correspondiente(s) en lugar de `ReportingRepositoryInterface`.

Sub-tareas:
- `[x]` `GetDashboardSummary` → `DashboardReadRepositoryInterface`
- `[x]` `GetDailyReport` → `DashboardReadRepositoryInterface` + `RestaurantInfoReadRepositoryInterface`
- `[x]` `GetProductsReport` → `ProductsReadRepositoryInterface` + `RestaurantInfoReadRepositoryInterface`
- `[x]` `GetSalesReport` → `SalesReadRepositoryInterface`
- `[x]` `GetSaleDetail` → `SalesReadRepositoryInterface`
- `[x]` `GetCashReport` → `CashReadRepositoryInterface` + `RestaurantInfoReadRepositoryInterface`
- `[x]` `GetFamiliesReport` → `FamiliesReadRepositoryInterface` + `RestaurantInfoReadRepositoryInterface`
- `[x]` `GetTaxReport` → `TaxReadRepositoryInterface`
- `[x]` `GetEmployeesReport` → `EmployeesReadRepositoryInterface` + `RestaurantInfoReadRepositoryInterface`
- `[x]` `GetHeatmap` → `HeatmapReadRepositoryInterface`

### PASO 5 — Verificación `[x]`

```bash
cd backend && php artisan config:clear && php artisan cache:clear
php artisan route:list | grep -i report   # sin errores
./vendor/bin/phpunit --filter Reporting   # tests verdes
```

Revisar manualmente en el navegador:
- Dashboard summary carga
- Informe de productos carga
- Exportar CSV funciona
- Exportar PDF funciona

---

## Lo que NO cambia

- `EloquentReportingRepository` — cero cambios en la implementación (1280 líneas intactas)
- `ExportReportController` — sigue inyectando `ReportingRepositoryInterface`
- `ReportFileGenerator` — sigue inyectando `ReportingRepositoryInterface`
- Binding original `ReportingRepositoryInterface → EloquentReportingRepository` en `AppServiceProvider`
- Todos los PDF controllers, scheduled reports, etc.

## Posible Paso 6 futuro — ReadModel DTOs tipados (no en este plan)

Una vez estabilizados los contratos, se puede introducir un DTO tipado para el informe
de productos como demostración: `ProductReportItemDto` con propiedades readonly,
cambiando el return type de `getProductsReport()` de `array` a `ProductsReportData`.
Esto haría el contrato completamente explícito en compile time.
Se propone como mejora posterior porque requiere actualizar también `ExportReportController`
y `ReportFileGenerator`.
