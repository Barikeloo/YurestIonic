# Revisión DDD + Hexagonal — Dominio Cash

Fecha de revisión: 2026-04-23

Revisión exhaustiva de los 56 archivos del dominio `Cash` y sus dependencias (`Shared`, referencias desde `Sale`).

---

## Resultado general

Cumplimiento estimado **85 %**. La estructura base es correcta: entidades con `dddCreate` + `fromPersistence`, VOs con constructor privado + `create()`, casos de uso puros, repositorios implementando interfaces. Los problemas encontrados son puntuales y acotados.

---

## 🔴 Crítico — Importación cruzada de dominios

**Archivo:** `Cash/Domain/Interfaces/SalePaymentRepositoryInterface.php`

```php
use App\Sale\Domain\Entity\SalePayment; // dominio Sale dentro de dominio Cash
```

Una interfaz del dominio Cash define el contrato para gestionar `SalePayment`, que es una entidad del dominio Sale. Viola AGENTS.md §2.1 ("dominios autocontenidos").

**Solución:** mover la interfaz a `Sale/Domain/Interfaces/SalePaymentRepositoryInterface.php` y la implementación Eloquent a `Sale/Infrastructure/Persistence/Repositories/`. Actualizar bindings en el service provider y los `use` en `CreateSale`.

---

## 🟠 Alto — Primitivos en firmas de interfaces de dominio

AGENTS.md §2.6 exige tipar con VOs, nunca con `string`/`int` primitivos para IDs. Tres interfaces lo incumplen:

| Interfaz | Línea | Actual | Correcto |
|---|---|---|---|
| `CashSessionRepositoryInterface` | 14 | `getById(string $id)` | `getById(Uuid $id)` |
| `CashMovementRepositoryInterface` | 14 | `getById(string $id)` | `getById(Uuid $id)` |
| `TipRepositoryInterface` | 14 | `getById(string $id)` | `getById(Uuid $id)` |

Además, `CashSessionRepositoryInterface` línea 20:

```php
public function findActiveByDeviceId(string $deviceId, Uuid $restaurantId): ?CashSession;
```

`$deviceId` llega como primitivo. Si se crea un `DeviceId` VO (recomendado), debería ser `DeviceId $deviceId`.

**Solución:** cambiar las firmas en las interfaces y en las implementaciones Eloquent. Los repositorios ya convierten internamente con `Uuid::create()`, así que el cambio es mecánico.

---

## 🟡 Menor — `new` directo en caso de uso y propiedad no `readonly`

**Archivo:** `Cash/Application/ListCashMovements/ListCashMovements.php`

```php
// Línea 13 — falta readonly
private CashMovementRepositoryInterface $cashMovementRepository,

// Línea 20 — usa new en lugar de ::create()
return new ListCashMovementsResponse($movements);
```

Todos los demás casos de uso del dominio usan `private readonly` y `::create()` en sus Responses. Este es inconsistente.

**Solución:**
```php
// Línea 13
private readonly CashMovementRepositoryInterface $cashMovementRepository,

// Línea 20
return ListCashMovementsResponse::create($movements);
```

---

## ✅ Lo que está correcto

### Entidades
- `CashSession`, `CashMovement`, `Tip`, `ZReport`: constructor privado + `dddCreate()` + `fromPersistence()` en todos.
- Orden de argumentos en `fromPersistence()` verificado y correcto en los 5 repositorios.

### Value Objects de Cash
- `CashSessionStatus`, `MovementType`, `MovementReasonCode`, `TipSource`: constructor privado + `create()` + validación en todos.

### Modelos Eloquent
Todos los 5 modelos (`EloquentCashSession`, `EloquentCashMovement`, `EloquentTip`, `EloquentZReport`, `EloquentSalePayment`) tienen:
- `HasTenantScope` ✓
- `SoftDeletes` ✓
- `$fillable` completo ✓
- `$casts` configurado ✓

### Casos de uso
- Puros: solo inyectan interfaces por constructor, sin código de framework.
- `CloseCashSession` usa `TransactionManagerInterface` correctamente.
- Sin referencias fully-qualified inline (`\App\...`) en ningún archivo.
- Sin lógica de negocio en controllers ni modelos Eloquent.

### Repositorios
- Implementan las interfaces de `Domain/Interfaces/`.
- `toDomain()` convierte correctamente con `fromPersistence()`.
- Modelos Eloquent solo se usan desde los repositorios.

---

## Plan de corrección

| Prioridad | Tarea | Archivos afectados |
|---|---|---|
| 1 | Mover `SalePaymentRepositoryInterface` a dominio Sale | interfaz, repositorio Eloquent, service provider, `CreateSale` |
| 2 | Tipar `getById` con `Uuid` en las 3 interfaces | `CashSessionRepositoryInterface`, `CashMovementRepositoryInterface`, `TipRepositoryInterface` + sus implementaciones |
| 3 | Añadir `readonly` y usar `::create()` en `ListCashMovements` | `ListCashMovements.php`, `ListCashMovementsResponse.php` |
