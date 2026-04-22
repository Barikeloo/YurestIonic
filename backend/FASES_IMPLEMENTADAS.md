# Documentación de Fases Implementadas - Módulo Caja (Cash Session)

Este documento describe la implementación de las Fases 3 y 4 del módulo de Caja (Cash Session) del sistema TPV.

---

## Índice

1. [Fase 3: Enhanced Payment Functionality](#fase-3-enhanced-payment-functionality)
   - 3.1 Multi-payment support
   - 3.2 CancelSale use case
2. [Fase 4: Z-Report Implementation](#fase-4-z-report-implementation)
   - 4.1 Z-Report Entity
   - 4.2 Z-Report Repository
   - 4.3 GenerateZReport Use Case
   - 4.4 GetZReport Use Case
   - 4.5 Integración con CloseCashSession
3. [Endpoints API](#endpoints-api)
4. [Estructura de Archivos](#estructura-de-archivos)
5. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Fase 3: Enhanced Payment Functionality

### 3.1 Multi-payment Support

**Objetivo:** Permitir que una venta tenga múltiples métodos de pago (efectivo, tarjeta, otros).

#### Cambios en Sale Entity

```php
// app/Sale/Domain/Entity/Sale.php
public function addPayment(SalePayment $payment): void
{
    if ($this->status !== 'open') {
        throw new \DomainException('Only open sales can have payments added.');
    }

    $this->payments[] = $payment;
    $this->updatedAt = DomainDateTime::now();
}

public function totalPaid(): Money
{
    $total = Money::create(0);
    foreach ($this->payments as $payment) {
        $total = $total->add($payment->amount());
    }
    return $total;
}
```

**Explicación:**
- `addPayment()`: Agrega un pago a la venta. Solo se puede agregar si la venta está abierta.
- `totalPaid()`: Calcula el total pagado sumando todos los pagos de la venta.

#### SalePayment Entity

```php
// app/Sale/Domain/Entity/SalePayment.php
final class SalePayment
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $saleId,
        private readonly string $method, // 'cash', 'card', 'other'
        private readonly Money $amount,
        private readonly ?array $metadata = null,
    ) {}

    public static function create(
        Uuid $saleId,
        string $method,
        Money $amount,
        ?array $metadata = null,
    ): self {
        return new self(
            id: Uuid::generate(),
            saleId: $saleId,
            method: $method,
            amount: $amount,
            metadata: $metadata,
        );
    }
}
```

**Explicación:**
- Entity que representa un pago individual.
- `method`: Puede ser 'cash' (efectivo), 'card' (tarjeta), u 'other' (otros).
- `amount`: Monto del pago usando el ValueObject `Money`.

#### Validación en CreateSale

```php
// app/Sale/Application/CreateSale/CreateSale.php
public function __invoke(array $data): CreateSaleResponse
{
    // ... creación de venta ...

    // Validación de pagos múltiples
    foreach ($data['payments'] as $paymentData) {
        $payment = SalePayment::create(
            saleId: $sale->id(),
            method: $paymentData['method'],
            amount: Money::create($paymentData['amount_cents']),
            metadata: $paymentData['metadata'] ?? null,
        );
        $sale->addPayment($payment);
        $this->salePaymentRepository->save($payment);
    }

    // Validación: suma de pagos = total de venta
    if ($sale->totalPaid()->toCents() !== $sale->total()->toCents()) {
        throw new \DomainException('Payments total does not match sale total.');
    }

    $this->saleRepository->save($sale);
}
```

**Explicación:**
- Se iteran los pagos del request.
- Cada pago se crea con `SalePayment::create()`.
- Se agrega a la venta con `sale->addPayment()`.
- Se guarda en el repositorio.
- **Validación crítica:** La suma de todos los pagos debe igualar el total de la venta.

### 3.2 CancelSale Use Case

**Objetivo:** Permitir cancelar una venta existente.

#### Sale Entity - Cancel Method

```php
// app/Sale/Domain/Entity/Sale.php
public function cancel(Uuid $cancelledByUserId, string $reason): void
{
    if ($this->status === 'cancelled') {
        throw new \DomainException('Sale is already cancelled.');
    }

    $this->cancelledByUserId = $cancelledByUserId;
    $this->cancellationReason = $reason;
    $this->status = 'cancelled';
    $this->updatedAt = DomainDateTime::now();
}
```

**Explicación:**
- Verifica que la venta no esté ya cancelada.
- Establece el usuario que canceló y la razón.
- Cambia el estado a 'cancelled'.

#### CancelSale Use Case

```php
// app/Sale/Application/CancelSale/CancelSale.php
final class CancelSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $saleId, string $cancelledByUserId, string $reason): CancelSaleResponse
    {
        $saleUuid = Uuid::create($saleId);
        $cancelledByUserUuid = Uuid::create($cancelledByUserId);

        $sale = $this->saleRepository->findByUuid($saleUuid);

        if ($sale === null) {
            throw new \DomainException('Sale not found.');
        }

        $sale->cancel($cancelledByUserUuid, $reason);

        $this->saleRepository->save($sale);

        return CancelSaleResponse::create($sale);
    }
}
```

**Explicación:**
- Busca la venta por UUID.
- Llama al método `cancel()` de la entidad.
- Guarda los cambios.
- Devuelve la respuesta con la venta cancelada.

#### CancelSale Controller

```php
// app/Sale/Infrastructure/Entrypoint/Http/CancelSaleController.php
final class CancelSaleController
{
    public function __construct(
        private readonly CancelSale $cancelSale,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'string', 'uuid'],
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['required', 'string'],
        ]);

        $response = ($this->cancelSale)(
            $validated['sale_id'],
            $validated['cancelled_by_user_id'],
            $validated['reason'],
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
```

**Explicación:**
- Valida el request.
- Llama al use case con los datos validados.
- Devuelve la venta cancelada en JSON.

---

## Fase 4: Z-Report Implementation

**Objetivo:** Implementar reportes fiscales Z-Report con cálculos detallados de ventas, movimientos de caja, propinas y discrepancias.

### 4.1 Z-Report Entity

```php
// app/Cash/Domain/Entity/ZReport.php
final class ZReport
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $cashSessionId,
        private readonly int $reportNumber,
        private readonly string $reportHash,
        private readonly Money $totalSales,
        private readonly Money $totalCash,
        private readonly Money $totalCard,
        private readonly Money $totalOther,
        private readonly Money $cashIn,
        private readonly Money $cashOut,
        private readonly Money $tips,
        private readonly Money $discrepancy,
        private readonly int $salesCount,
        private readonly int $cancelledSalesCount,
        private readonly DomainDateTime $generatedAt,
    ) {}

    public static function generate(
        Uuid $cashSessionId,
        int $reportNumber,
        Money $totalSales,
        Money $totalCash,
        Money $totalCard,
        Money $totalOther,
        Money $cashIn,
        Money $cashOut,
        Money $tips,
        Money $discrepancy,
        int $salesCount,
        int $cancelledSalesCount,
    ): self {
        $id = Uuid::generate();
        $reportHash = self::calculateHash(
            $cashSessionId,
            $reportNumber,
            $totalSales,
            $totalCash,
            $totalCard,
            $totalOther,
            $cashIn,
            $cashOut,
            $tips,
            $discrepancy,
            $salesCount,
            $cancelledSalesCount,
        );

        return new self(
            id: $id,
            cashSessionId: $cashSessionId,
            reportNumber: $reportNumber,
            reportHash: $reportHash,
            // ... otros campos
        );
    }

    private static function calculateHash(
        Uuid $cashSessionId,
        int $reportNumber,
        Money $totalSales,
        Money $totalCash,
        Money $totalCard,
        Money $totalOther,
        Money $cashIn,
        Money $cashOut,
        Money $tips,
        Money $discrepancy,
        int $salesCount,
        int $cancelledSalesCount,
    ): string {
        $data = implode('|', [
            $cashSessionId->value(),
            $reportNumber,
            $totalSales->toCents(),
            $totalCash->toCents(),
            $totalCard->toCents(),
            $totalOther->toCents(),
            $cashIn->toCents(),
            $cashOut->toCents(),
            $tips->toCents(),
            $discrepancy->toCents(),
            $salesCount,
            $cancelledSalesCount,
            DomainDateTime::now()->format('Y-m-d H:i:s'),
        ]);

        return hash('sha256', $data);
    }
}
```

**Explicación:**
- **Entity inmutable** que representa un Z-Report fiscal.
- `reportNumber`: Número secuencial del reporte.
- `reportHash`: Hash SHA-256 para integridad fiscal.
- **Totales por método de pago:** `totalCash`, `totalCard`, `totalOther`.
- **Movimientos de caja:** `cashIn`, `cashOut`.
- **Propinas:** `tips`.
- **Discrepancia:** Diferencia entre monto esperado y real.
- `calculateHash()`: Genera hash SHA-256 concatenando todos los datos del reporte + timestamp.

### 4.2 Z-Report Repository

#### Interface

```php
// app/Cash/Domain/Interfaces/ZReportRepositoryInterface.php
interface ZReportRepositoryInterface
{
    public function save(ZReport $zReport): void;

    public function findByUuid(Uuid $uuid): ?ZReport;

    public function findByCashSessionId(Uuid $cashSessionId): ?ZReport;

    public function nextReportNumber(string $restaurantId): int;
}
```

**Explicación:**
- `save()`: Persiste un Z-Report.
- `findByUuid()`: Busca por UUID.
- `findByCashSessionId()`: Busca por sesión de caja.
- `nextReportNumber()`: Calcula el siguiente número de reporte para un restaurante.

#### Implementación Eloquent

```php
// app/Cash/Infrastructure/Persistence/Repositories/EloquentZReportRepository.php
final class EloquentZReportRepository implements ZReportRepositoryInterface
{
    public function save(ZReport $zReport): void
    {
        $cashSessionInternalId = EloquentCashSession::query()
            ->where('uuid', $zReport->cashSessionId()->value())
            ->value('id');

        $restaurantId = EloquentCashSession::query()
            ->where('uuid', $zReport->cashSessionId()->value())
            ->value('restaurant_id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $zReport->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'cash_session_id' => $cashSessionInternalId,
                'report_number' => $zReport->reportNumber(),
                'report_hash' => $zReport->reportHash(),
                // ... otros campos
            ],
        );
    }

    public function nextReportNumber(string $restaurantId): int
    {
        $restaurantInternalId = EloquentRestaurant::query()
            ->where('uuid', $restaurantId)
            ->value('id');
        
        $max = $this->model->newQuery()
            ->where('restaurant_id', $restaurantInternalId)
            ->max('report_number');

        return $max !== null ? (int) $max + 1 : 1;
    }
}
```

**Explicación:**
- Convierte UUIDs a IDs internos para las relaciones.
- `updateOrCreate()`: Crea o actualiza por UUID.
- `nextReportNumber()`: Busca el máximo `report_number` del restaurante y retorna +1.

### 4.3 GenerateZReport Use Case

```php
// app/Cash/Application/GenerateZReport/GenerateZReport.php
final class GenerateZReport
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly TipRepositoryInterface $tipRepository,
        private readonly ZReportRepositoryInterface $zReportRepository,
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $cashSessionId): GenerateZReportResponse
    {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        // 1. Calcular totales por método de pago
        $payments = $this->salePaymentRepository->findByCashSessionId($cashSessionUuid);
        $totalCash = Money::create(0);
        $totalCard = Money::create(0);
        $totalOther = Money::create(0);

        foreach ($payments as $payment) {
            $amount = $payment->amount();
            switch ($payment->method()) {
                case 'cash':
                    $totalCash = $totalCash->add($amount);
                    break;
                case 'card':
                    $totalCard = $totalCard->add($amount);
                    break;
                default:
                    $totalOther = $totalOther->add($amount);
                    break;
            }
        }

        // 2. Calcular movimientos de caja
        $movements = $this->cashMovementRepository->findByCashSessionId($cashSessionUuid);
        $cashIn = Money::create(0);
        $cashOut = Money::create(0);

        foreach ($movements as $movement) {
            if ($movement->type()->value() === 'in') {
                $cashIn = $cashIn->add($movement->amount());
            } else {
                $cashOut = $cashOut->add($movement->amount());
            }
        }

        // 3. Calcular propinas
        $tips = $this->tipRepository->findByCashSessionId($cashSessionUuid);
        $totalTips = Money::create(0);

        foreach ($tips as $tip) {
            $totalTips = $totalTips->add($tip->amount());
        }

        // 4. Calcular total de ventas
        $totalSales = $totalCash->add($totalCard)->add($totalOther);

        // 5. Calcular conteo de ventas
        $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
        $salesCount = count($sales);
        $cancelledSalesCount = 0;

        foreach ($sales as $sale) {
            if ($sale->status() === 'cancelled') {
                $cancelledSalesCount++;
            }
        }

        // 6. Calcular discrepancia
        $expectedFinal = $cashSession->initialAmount()
            ->add($totalCash)
            ->add($cashIn)
            ->subtract($cashOut)
            ->add($totalTips);

        $finalAmount = $cashSession->finalAmount() ?? Money::create(0);
        $discrepancyCents = abs($finalAmount->toCents() - $expectedFinal->toCents());
        $discrepancy = Money::create($discrepancyCents);

        // 7. Generar número de reporte
        $reportNumber = $this->zReportRepository->nextReportNumber($cashSession->restaurantId()->value());

        // 8. Generar Z-Report
        $zReport = ZReport::generate(
            cashSessionId: $cashSessionUuid,
            reportNumber: $reportNumber,
            totalSales: $totalSales,
            totalCash: $totalCash,
            totalCard: $totalCard,
            totalOther: $totalOther,
            cashIn: $cashIn,
            cashOut: $cashOut,
            tips: $totalTips,
            discrepancy: $discrepancy,
            salesCount: $salesCount,
            cancelledSalesCount: $cancelledSalesCount,
        );

        $this->zReportRepository->save($zReport);

        return GenerateZReportResponse::create($zReport);
    }
}
```

**Explicación paso a paso:**
1. **Totales por método:** Itera pagos y suma según método.
2. **Movimientos:** Separa entradas (in) y salidas (out).
3. **Propinas:** Suma todas las propinas de la sesión.
4. **Total ventas:** Suma todos los métodos de pago.
5. **Conteo ventas:** Cuenta total y canceladas.
6. **Discrepancia:** 
   - Calcula monto esperado: inicial + efectivo + entradas - salidas + propinas
   - Compara con monto real (finalAmount)
   - Usa `abs()` para valor absoluto
7. **Número reporte:** Obtiene siguiente número secuencial.
8. **Genera entity:** Crea Z-Report con todos los datos.
9. **Persiste:** Guarda en repositorio.

### 4.4 GetZReport Use Case

```php
// app/Cash/Application/GetZReport/GetZReport.php
final class GetZReport
{
    public function __construct(
        private readonly ZReportRepositoryInterface $zReportRepository,
    ) {}

    public function __invoke(string $zReportId): ?GetZReportResponse
    {
        $zReportUuid = Uuid::create($zReportId);
        $zReport = $this->zReportRepository->findByUuid($zReportUuid);

        if ($zReport === null) {
            return null;
        }

        return GetZReportResponse::create($zReport);
    }
}
```

**Explicación:**
- Busca Z-Report por UUID.
- Si no existe, retorna null.
- Si existe, crea y retorna la respuesta.

### 4.5 Integración con CloseCashSession

**Objetivo:** Generar automáticamente Z-Report al cerrar sesión de caja.

#### CloseCashSession Modificado

```php
// app/Cash/Application/CloseCashSession/CloseCashSession.php
final class CloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly GenerateZReport $generateZReport,
    ) {}

    public function __invoke(
        string $cashSessionId,
        string $closedByUserId,
        int $finalAmountCents,
        int $expectedAmountCents,
        int $discrepancyCents,
        ?string $discrepancyReason = null,
    ): CloseCashSessionResponse {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        // Cerrar sesión
        $cashSession->close(
            closedByUserId: Uuid::create($closedByUserId),
            finalAmount: Money::create($finalAmountCents),
            expectedAmount: Money::create($expectedAmountCents),
            discrepancy: Money::create($discrepancyCents),
            discrepancyReason: $discrepancyReason,
        );

        $this->cashSessionRepository->save($cashSession);

        // Generar Z-Report automáticamente
        $zReportResponse = ($this->generateZReport)($cashSessionUuid);

        return CloseCashSessionResponse::create($cashSession, $zReportResponse);
    }
}
```

**Explicación:**
- Inyecta `GenerateZReport` use case.
- Después de cerrar la sesión, llama a `GenerateZReport`.
- Pasa el Z-Report generado a la respuesta.

#### CloseCashSessionResponse Modificado

```php
// app/Cash/Application/CloseCashSession/CloseCashSessionResponse.php
final class CloseCashSessionResponse
{
    private function __construct(
        // ... campos de CashSession
        public readonly array $zReport,
    ) {}

    public static function create(CashSession $cashSession, $zReportResponse = null): self
    {
        return new self(
            // ... campos de CashSession
            zReport: $zReportResponse ? $zReportResponse->toArray() : [],
        );
    }

    public function toArray(): array
    {
        return [
            // ... campos de CashSession
            'z_report' => $this->zReport,
        ];
    }
}
```

**Explicación:**
- Agrega campo `zReport` (array).
- En `toArray()`, incluye el Z-Report completo en la respuesta.

---

## Endpoints API

### Fase 3 - Multi-payment y CancelSale

#### Crear venta con múltiples pagos
```
POST /api/tpv/sales
Content-Type: application/json

{
  "restaurant_id": "44da98b6-5bba-4fa6-a546-671559ef9dc2",
  "order_id": "422d5f79-b79a-4143-9466-f7e0c32bd87d",
  "opened_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
  "closed_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
  "device_id": "test-device-013",
  "payments": [
    {
      "method": "cash",
      "amount_cents": 1000,
      "metadata": null
    },
    {
      "method": "card",
      "amount_cents": 400,
      "metadata": null
    }
  ]
}
```

**Validación:** `Σ payments.amount_cents` debe igualar el total de la orden.

#### Cancelar venta
```
POST /api/tpv/sales/cancel
Content-Type: application/json

{
  "sale_id": "UUID_DE_LA_VENTA",
  "cancelled_by_user_id": "UUID_DEL_USUARIO",
  "reason": "Error en pedido"
}
```

### Fase 4 - Z-Report

#### Generar Z-Report manualmente
```
POST /api/tpv/z-reports/generate
Content-Type: application/json

{
  "cash_session_id": "UUID_DE_LA_SESION"
}
```

#### Obtener Z-Report
```
GET /api/tpv/z-reports/{UUID_DEL_Z_REPORT}
```

#### Cerrar sesión de caja (genera Z-Report automáticamente)
```
POST /api/tpv/cash-sessions/close
Content-Type: application/json

{
  "cash_session_id": "UUID_DE_LA_SESION",
  "closed_by_user_id": "UUID_DEL_USUARIO",
  "final_amount_cents": 51400,
  "expected_amount_cents": 51400,
  "discrepancy_cents": 0
}
```

**Respuesta incluye:**
```json
{
  "id": "...",
  "uuid": "...",
  "z_report_number": 0,
  "z_report_hash": "",
  "status": "closed",
  "z_report": {
    "id": "...",
    "cash_session_id": "...",
    "report_number": 1,
    "report_hash": "sha256_hash...",
    "total_sales_cents": 1400,
    "total_cash_cents": 1400,
    "total_card_cents": 0,
    "total_other_cents": 0,
    "cash_in_cents": 0,
    "cash_out_cents": 0,
    "tips_cents": 0,
    "discrepancy_cents": 0,
    "sales_count": 1,
    "cancelled_sales_count": 0,
    "generated_at": "2026-04-22 12:00:00"
  }
}
```

---

## Estructura de Archivos

### Fase 3 - Multi-payment

```
app/Sale/
├── Domain/
│   ├── Entity/
│   │   └── Sale.php
│   │   └── SalePayment.php
│   └── Interfaces/
│       └── SalePaymentRepositoryInterface.php
├── Application/
│   ├── CreateSale/
│   │   └── CreateSale.php
│   └── CancelSale/
│       ├── CancelSale.php
│       └── CancelSaleResponse.php
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   └── EloquentSalePayment.php
    │   └── Repositories/
    │       └── EloquentSalePaymentRepository.php
    └── Entrypoint/
        └── Http/
            └── CancelSaleController.php
```

### Fase 4 - Z-Report

```
app/Cash/
├── Domain/
│   ├── Entity/
│   │   └── ZReport.php
│   └── Interfaces/
│       └── ZReportRepositoryInterface.php
├── Application/
│   ├── GenerateZReport/
│   │   ├── GenerateZReport.php
│   │   └── GenerateZReportResponse.php
│   ├── GetZReport/
│   │   ├── GetZReport.php
│   │   └── GetZReportResponse.php
│   └── CloseCashSession/
│       ├── CloseCashSession.php
│       └── CloseCashSessionResponse.php
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   └── EloquentZReport.php
    │   └── Repositories/
    │       └── EloquentZReportRepository.php
    └── Entrypoint/
        └── Http/
            ├── GenerateZReportController.php
            └── GetZReportController.php
```

### Rutas API Refactorizadas

```
routes/api/
├── auth.php          # Rutas de autenticación
├── tpv.php           # Rutas TPV (ventas, órdenes, caja, Z-Report)
├── admin.php         # Rutas admin (familias, productos, zonas, mesas, impuestos)
├── management.php    # Rutas gestión (restaurantes, usuarios)
└── superadmin.php    # Rutas superadmin
```

---

## Ejemplos de Uso

### Ejemplo 1: Venta con múltiples pagos

```bash
curl -X POST http://localhost:8000/api/tpv/sales \
  -H "Content-Type: application/json" \
  -d '{
    "restaurant_id": "44da98b6-5bba-4fa6-a546-671559ef9dc2",
    "order_id": "422d5f79-b79a-4143-9466-f7e0c32bd87d",
    "opened_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
    "closed_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
    "device_id": "test-device-013",
    "payments": [
      {"method": "cash", "amount_cents": 1000, "metadata": null},
      {"method": "card", "amount_cents": 400, "metadata": null}
    ]
  }'
```

### Ejemplo 2: Cancelar venta

```bash
curl -X POST http://localhost:8000/api/tpv/sales/cancel \
  -H "Content-Type: application/json" \
  -d '{
    "sale_id": "UUID_DE_LA_VENTA",
    "cancelled_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
    "reason": "Cliente canceló pedido"
  }'
```

### Ejemplo 3: Cerrar sesión con Z-Report automático

```bash
curl -X POST http://localhost:8000/api/tpv/cash-sessions/close \
  -H "Content-Type: application/json" \
  -d '{
    "cash_session_id": "UUID_DE_LA_SESION",
    "closed_by_user_id": "f01b7458-8602-4c78-98eb-7ebf2143c2e7",
    "final_amount_cents": 51400,
    "expected_amount_cents": 51400,
    "discrepancy_cents": 0
  }'
```

### Ejemplo 4: Obtener Z-Report

```bash
curl -X GET http://localhost:8000/api/tpv/z-reports/UUID_DEL_Z_REPORT
```

---

## Migraciones

### Tabla sale_payments (Fase 3)

```php
// database/migrations/2026_04_22_091534_create_sale_payments_table.php
Schema::create('sale_payments', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('sale_id');
    $table->string('method'); // 'cash', 'card', 'other'
    $table->unsignedBigInteger('amount_cents');
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('sale_id')->references('id')->on('sales');
});
```

### Tabla z_reports (Fase 4)

```php
// database/migrations/2026_04_22_103832_create_z_reports_table.php
Schema::create('z_reports', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->unsignedBigInteger('restaurant_id');
    $table->unsignedBigInteger('cash_session_id');
    $table->integer('report_number');
    $table->string('report_hash');
    $table->unsignedBigInteger('total_sales_cents')->default(0);
    $table->unsignedBigInteger('total_cash_cents')->default(0);
    $table->unsignedBigInteger('total_card_cents')->default(0);
    $table->unsignedBigInteger('total_other_cents')->default(0);
    $table->unsignedBigInteger('cash_in_cents')->default(0);
    $table->unsignedBigInteger('cash_out_cents')->default(0);
    $table->unsignedBigInteger('tips_cents')->default(0);
    $table->bigInteger('discrepancy_cents')->default(0);
    $table->integer('sales_count')->default(0);
    $table->integer('cancelled_sales_count')->default(0);
    $table->timestamp('generated_at');
    $table->timestamps();
    $table->softDeletes();

    $table->foreign('restaurant_id')->references('id')->on('restaurants');
    $table->foreign('cash_session_id')->references('id')->on('cash_sessions');
});
```

---

## Seeders

### ZReportSeeder

```php
// database/seeders/ZReportSeeder.php
class ZReportSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $cashSession = EloquentCashSession::first();

        $zReports = [
            [
                'uuid' => Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'cash_session_id' => $cashSession->id,
                'report_number' => 1,
                'report_hash' => hash('sha256', 'test_report_1'),
                'total_sales_cents' => 15000,
                // ... otros campos
            ],
            // ... más reportes
        ];

        foreach ($zReports as $zReport) {
            EloquentZReport::create($zReport);
        }
    }
}
```

---

## Registro en Service Provider

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    // ... otros binds

    $this->app->bind(SalePaymentRepositoryInterface::class, EloquentSalePaymentRepository::class);
    $this->app->bind(ZReportRepositoryInterface::class, EloquentZReportRepository::class);
}
```

---

## Configuración de Rutas

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api', // Carga directorio completo
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ...
```

---

## Resumen

### Fase 3
- ✅ Multi-payment support en ventas
- ✅ Validación de suma de pagos
- ✅ CancelSale use case
- ✅ Endpoint para cancelar ventas

### Fase 4
- ✅ Z-Report entity con hash SHA-256
- ✅ Z-Report repository
- ✅ GenerateZReport use case
- ✅ GetZReport use case
- ✅ Integración automática en CloseCashSession
- ✅ Endpoints para generar y consultar Z-Reports
- ✅ Seeder con datos de prueba
- ✅ Refactorización de rutas API

---

**Fecha de implementación:** 22 de abril de 2026
**Versión:** Laravel 11
**Patrón arquitectónico:** DDD + Hexagonal
