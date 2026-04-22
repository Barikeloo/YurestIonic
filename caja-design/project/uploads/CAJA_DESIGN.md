# Especificación funcional y técnica — Módulo Caja

> Sistema TPV hostelería — Hito 4
> Ámbito: diseño completo del subsistema de Caja (cash session), cobro, arqueo y cierre, con toda la casuística de un restaurante profesional.

---

## 1. Visión y alcance

El módulo **Caja** es el subsistema responsable de gestionar las sesiones de turno, controlar los ingresos y salidas de efectivo, vincular las ventas al turno en que se producen, ejecutar el arqueo y emitir el cierre fiscal del día (Z).

A diferencia de un MVP de demo, este diseño asume un producto listo para producción real en hostelería:

- Pagos mixtos (efectivo + tarjeta + bizum en un mismo ticket).
- División de cuenta por líneas, por comensal o por partes iguales.
- Anulaciones y notas de abono.
- Descuentos, invitaciones, precios manuales, propinas.
- Reimpresión de ticket como factura simplificada o completa con NIF.
- Arqueo ciego.
- Auditoría inmutable de todas las operaciones sensibles.
- Cola offline y sincronización multi-dispositivo.
- Cumplimiento fiscal (marco Veri*Factu / TicketBAI).

Los principios aplicables al resto del TPV siguen vigentes: DDD + Hexagonal, multi-tenant con `restaurant_id`, Value Objects con constructor privado y `create()` estático, separación entre estado mutable (`Order`) e inmutable fiscal (`Sale`).

---

## 2. Ciclo de vida y estados

```
            abrir                           cerrar
  CERRADA ──────────▶  ABIERTA  ──────────▶  EN ARQUEO
     ▲                    │                       │
     │                    ├─ Sale                 │
     │                    ├─ SalePayment          │ confirmar
     │                    ├─ CashMovement         ▼
     │                    ├─ Refund / Abono    CERRADA (Z emitido)
     │                    ├─ Cambio de operador
     │                    └─ Auditoría
     │                                            │
     └────────────── cierre forzado administrativo┘
```

### Estados de `CashSession`

| Estado | Descripción |
|---|---|
| `open` | Sesión activa. Acepta ventas, movimientos y operaciones. Existe **como mucho una** por `device_id`. |
| `closing` | Se ha iniciado el wizard de cierre. La sesión no acepta nuevos cobros pero el wizard puede persistirse paso a paso. |
| `closed` | Sesión cerrada con Z emitido. Inmutable. Toda reclamación posterior genera movimientos en la sesión actual, no en ésta. |
| `abandoned` | Sesión que no se cerró correctamente (apagón, tablet rota). Requiere cierre forzado administrativo antes de poder abrir una nueva en ese device. |

### Invariantes del dominio

- Un `device_id` no puede tener dos sesiones simultáneas en estado `open` o `closing`.
- No se puede crear `Sale` si el device no tiene sesión `open`.
- El `final_amount_cents` declarado en el cierre es inmutable una vez confirmado el Z.
- Los documentos fiscales emitidos (`Sale`, `CreditNote`, Z) no se editan nunca; toda corrección se hace emitiendo un documento nuevo.
- El `expected_amount_cents` es siempre derivado, nunca persistido manualmente.

---

## 3. Modelo de dominio

### 3.1. Entidades principales

```
CashSession
  id                        UUID
  restaurant_id             UUID      (shard key)
  device_id                 UUID
  opened_by_user_id         UUID
  closed_by_user_id         UUID?
  opened_at                 timestamp
  closed_at                 timestamp?
  initial_amount_cents      int       (fondo declarado al abrir)
  final_amount_cents        int?      (contado declarado al cerrar)
  expected_amount_cents     int?      (calculado en cierre)
  discrepancy_cents         int?      (final - expected)
  discrepancy_reason        string?
  z_report_number           int?      (correlativo por restaurante)
  z_report_hash             string?   (encadenado con el Z anterior)
  notes                     text?
  status                    enum(open, closing, closed, abandoned)

CashMovement
  id                        UUID
  cash_session_id           UUID
  restaurant_id             UUID
  type                      enum(in, out)
  reason_code               enum(change_refill, supplier_payment, tip_declared,
                                  sangria, adjustment, other)
  amount_cents              int
  description               string?
  user_id                   UUID
  created_at                timestamp

SalePayment
  id                        UUID
  sale_id                   UUID
  cash_session_id           UUID
  restaurant_id             UUID
  method                    enum(cash, card, bizum, voucher, invitation, other)
  amount_cents              int
  metadata                  json?     (últimos 4 tarjeta, ref Bizum, etc.)
  user_id                   UUID
  created_at                timestamp

Sale (extensión)
  + cash_session_id         UUID
  + status                  enum(closed, cancelled, refunded)
  + cancelled_at            timestamp?
  + cancelled_by_user_id    UUID?
  + cancel_reason           string?
  + parent_sale_id          UUID?     (para credit notes)
  + document_type           enum(simplified, full_invoice)
  + customer_fiscal_data    json?     (NIF, razón social, dirección, para factura)

OrderLine (extensión)
  + diner_number            int?      (para split por comensal, 1..N)
  + discount_percent        decimal?  (descuento línea)
  + discount_reason         string?
  + is_invitation           bool
  + price_override_cents    int?      (precio manual)
  + notes                   text?     (modificadores, "sin cebolla", etc.)

Tip
  id                        UUID
  sale_id                   UUID
  cash_session_id           UUID
  restaurant_id             UUID
  amount_cents              int
  source                    enum(card_added, cash_declared)
  beneficiary_user_id       UUID?
  created_at                timestamp

AuditLog
  id                        UUID
  restaurant_id             UUID
  entity_type               string    (cash_session, sale, order, ...)
  entity_id                 UUID
  action                    string    (open, close, cancel, refund, ...)
  user_id                   UUID
  before                    json?
  after                     json?
  ip_address                string?
  device_id                 UUID?
  created_at                timestamp
```

### 3.2. Value Objects

Siguiendo el patrón del resto del dominio:

- `Money` — cantidad en céntimos, no negativa salvo en `CreditNote`.
- `CashSessionStatus`
- `PaymentMethod`
- `MovementType`
- `MovementReasonCode`
- `DocumentType`

Cada uno con constructor privado y `create()` que valida. Imposible instanciar estado inválido.

### 3.3. Relaciones

- `CashSession 1..N CashMovement`
- `CashSession 1..N Sale` (vía `sale.cash_session_id`)
- `Sale 1..N SalePayment` (permite pagos mixtos)
- `Sale 1..N OrderLine` (las líneas del ticket)
- `Sale 0..1 Sale` (`parent_sale_id` para nota de abono)
- Todas las entidades con `restaurant_id` como shard key.

---

## 4. Casuística completa

Se detalla en tres fases del ciclo de vida más los casos transversales.

### 4.1. Apertura

| Caso | Tratamiento |
|---|---|
| Apertura estándar | Usuario con rol ≥ operator introduce fondo inicial. Se crea `CashSession(status=open)`. |
| Sesión huérfana del turno anterior | Detectar al intentar abrir. Bloquear con mensaje: "Turno sin cerrar desde DD/MM HH:MM". Solo admin puede ejecutar cierre forzado. |
| Fondo inicial distinto al cierre anterior | Permitido. No se compara contra el cierre anterior: lo de ayer ya es fiscalmente inmutable. |
| Dos devices en el mismo restaurante | Cada device abre su propia sesión independiente. Las ventas se contabilizan por device. |
| Turno partido (mediodía + noche) | Dos sesiones separadas. Facilita arqueo y control de propinas por turno. |
| Rol insuficiente | 403. Solo admin puede configurar qué roles pueden abrir. |

### 4.2. Durante el turno

#### 4.2.1. Cobro normal

- El cobro crea `Sale` (inmutable) y uno o más `SalePayment` que deben sumar exactamente el total del Sale.
- Si no hay `CashSession` activa para el device → 409 "Caja cerrada".
- El método de pago no altera el total; solo el desglose.

#### 4.2.2. Pagos mixtos

- Un cliente paga 30€ con tarjeta + 5€ en efectivo.
- UI: el usuario escoge "pago mixto" y distribuye el total entre métodos hasta que suma el 100%.
- `Sale.total = Σ SalePayment.amount`.
- Un único Sale, múltiples SalePayments.

#### 4.2.3. División de cuenta (split bill)

Tres modalidades según lo que pida el cliente:

- **Por partes iguales** — `total / N` pagos. Genera N Sales o 1 Sale con N SalePayments (decisión: 1 Sale con N SalePayments por método, porque fiscalmente es un único ticket dividido en cobros).
  - *Matiz:* si cada comensal pide factura con su NIF, son N Sales distintos. El sistema debe permitir ambas.
- **Por líneas** — el usuario arrastra líneas a "cuenta 1", "cuenta 2"… Cada subcuenta se cierra con su Sale independiente. La Order original se marca `closed` cuando todas sus líneas están pagadas.
- **Por comensal** — cada `OrderLine` lleva `diner_number`. Al cobrar se agrupa por comensal. Requiere que las líneas se hayan marcado con comensal en comanda.

#### 4.2.4. Descuentos

- **Descuento de línea**: `OrderLine.discount_percent` o `discount_amount`. Se refleja en el ticket con línea adicional "Dto. -10%".
- **Descuento global**: se aplica como pseudo-línea negativa al final del pedido.
- **Invitación**: `OrderLine.is_invitation = true`. El producto sale de stock pero su importe es 0. En el Z aparece en una línea específica "Invitaciones: 3 productos por valor teórico de 12,00€".
- **Precio manual**: `OrderLine.price_override_cents`. Requiere rol ≥ supervisor. Queda auditado con `discount_reason`.

#### 4.2.5. Propinas

| Tipo | Flujo | Efecto en caja |
|---|---|---|
| Efectivo dejada en mesa | El camarero se la lleva directamente. | Ninguno salvo que se declare. |
| Efectivo declarada al cierre | Movimiento `CashMovement(type=in, reason=tip_declared)` o ajuste en cierre. | Suma al teórico efectivo. |
| Tarjeta añadida al cobrar | El cliente firma un importe > total ticket. Se crea `Tip(source=card_added)` ligado al Sale. | No incrementa `Sale.total`; se liquida aparte al camarero. |

#### 4.2.6. Anulación de Sale (mismo turno)

- Solo mientras la sesión esté `open`.
- Permiso: supervisor+.
- Efecto: `Sale.status = cancelled`, `SalePayment` se anulan, la Order vuelve a `open` si procede (caso "cobré la mesa 7 por error, siguen consumiendo").
- Queda auditado.

#### 4.2.7. Nota de abono (devolución en turno posterior)

- El Sale original ya está en un Z cerrado: inmutable.
- Se crea un `Sale(document_type=credit_note, parent_sale_id=X, total<0)` en la sesión actual.
- El importe negativo se descuenta del arqueo del turno actual.
- Permiso: admin.

#### 4.2.8. Reapertura de mesa cobrada por error

- Subcaso de anulación: anular el Sale + volver `Order.status = open`.
- Solo si el Sale es del turno actual.

#### 4.2.9. Movimientos manuales de caja

- Entrada (`in`): reposición de cambio, propina declarada, aportación.
- Salida (`out`): pago a proveedor, cambio al turno siguiente, sangría (retirada al banco).
- Motivos tipificados con código enum + descripción libre.
- Salidas grandes (> umbral por restaurante, típico 100€) requieren supervisor+.

#### 4.2.10. Venta directa sin mesa (modo barra)

- Común en cafeterías, barras, take-away.
- Flujo: pantalla "Ticket rápido" → seleccionar productos → cobrar.
- Internamente genera Order efímera + Sale, o bien Sale directo sin Order (decisión: Order efímera para mantener homogeneidad del modelo y que la comanda llegue a cocina si aplica).
- `Order.table_id = null` y `is_quick_sale = true`.

#### 4.2.11. Factura con NIF

- El ticket por defecto es simplificado.
- El cliente pide factura al cobrar → el flujo de cobro ofrece introducir datos fiscales (`NIF`, razón social, dirección).
- El Sale queda marcado `document_type = full_invoice`.
- Se puede reimprimir un ticket simplificado como factura completa **si** aún no se ha cerrado el Z; a partir de ese momento requiere emitir un documento nuevo.

#### 4.2.12. Impagados

- Cliente se va sin pagar.
- Marcar Sale como `unpaid` con motivo. Afecta al arqueo: falta efectivo esperado. Se justifica como descuadre.

#### 4.2.13. Cambio de turno sin cerrar

- Opcional. Camarero A entrega a camarero B sin hacer arqueo.
- Se registra un "handover" en audit log; la sesión sigue abierta. `closed_by` se determina al cierre final.
- Para simplificar: puede dejarse fuera en una primera iteración y forzar cierre entre turnos.

#### 4.2.14. Arqueo intermedio de control

- El encargado quiere ver cómo va la caja a media tarde.
- No es una operación; el dashboard en vivo lo ofrece siempre.

### 4.3. Cierre y arqueo

#### 4.3.1. Inicio del cierre

- Un usuario con permiso inicia "Cerrar caja".
- La sesión pasa a `status = closing`.
- Mientras esté `closing`: no se aceptan nuevos `Sale` ni `CashMovement` en ese device. El wizard se puede abandonar y retomar (se persiste estado).

#### 4.3.2. Cálculo del teórico

```
teórico_efectivo = initial_amount
                 + Σ SalePayment(method=cash, sale.status=closed) del turno
                 + Σ CashMovement(type=in) del turno
                 - Σ CashMovement(type=out) del turno
                 + Σ Tip(source=cash_declared) del turno
                 - Σ credit_notes en efectivo del turno
```

Para tarjeta, bizum, voucher: cada método se totaliza por separado. El cajero contrasta contra sus fuentes externas (liquidación bancaria, app Bizum).

#### 4.3.3. Arqueo ciego (recomendado)

- Paso 1 — el operador introduce el **contado real** en efectivo antes de ver el teórico.
- Paso 2 — el sistema muestra teórico y diferencia.
- Paso 3 — si hay descuadre: pedir motivo (chips + nota libre).
- Esto evita el fraude del "ajuste mental".

#### 4.3.4. Emisión del Z

El informe Z incluye:

- Cabecera: restaurante, device, operador apertura/cierre, fecha/hora apertura/cierre.
- Número Z correlativo por restaurante.
- Hash encadenado con el Z anterior (integridad).
- Totales del turno:
  - Nº de tickets, Nº de comensales
  - Ventas brutas, descuentos, ventas netas, base imponible por IVA, IVA, total
  - Desglose por método: efectivo, tarjeta, bizum, voucher, invitación
  - Invitaciones (valor teórico), anulaciones, notas de abono
  - Propinas tarjeta recibidas, propinas efectivo declaradas
  - Top productos
- Arqueo:
  - Fondo inicial, entradas manuales, salidas manuales
  - Teórico efectivo, contado efectivo, diferencia, motivo
- Firma digital del operador + timestamp.

#### 4.3.5. Cierre forzado administrativo

- Para sesiones `abandoned` de días anteriores.
- Solo admin.
- Registra "cierre forzado, sin arqueo físico", diferencia no calculable.
- Requerido antes de poder abrir nueva sesión en ese device.

### 4.4. Casos transversales

| Caso | Tratamiento |
|---|---|
| Caída de red durante cobro | Cola offline local. Sincroniza al volver. Cada acción con idempotency key. |
| Tablet sin batería a mitad de arqueo | Wizard persiste estado paso a paso; al volver retoma donde estaba. |
| Dos cobros simultáneos en la misma tablet | Locking optimista con versión. Si colisionan: el segundo reintenta. |
| Multi-caja física (barra + sala) | Dos devices distintos con sus dos sesiones; cada una contra su cajón. |
| Retroactividad fiscal | Nunca se modifica un Z. Toda corrección genera documento nuevo. |
| Idioma / moneda | Configurable por restaurante; todos los importes en céntimos + `currency_code`. |

---

## 5. Roles y permisos

| Operación | Camarero | Supervisor | Admin |
|---|:-:|:-:|:-:|
| Abrir caja | si | si | si |
| Cobrar mesa | si | si | si |
| Ver dashboard caja activa | si | si | si |
| Ver dashboard de otras cajas | | si | si |
| Movimiento manual <50€ | si | si | si |
| Movimiento manual >=50€ / sangría | | si | si |
| Aplicar descuento línea | | si | si |
| Aplicar descuento global / invitación | | si | si |
| Precio manual | | si | si |
| Cambiar método de pago post-cobro | | si | si |
| Anular Sale mismo turno | | si | si |
| Nota de abono de turno anterior | | | si |
| Reabrir mesa ya cobrada | | si | si |
| Cerrar caja con descuadre < umbral | si | si | si |
| Cerrar caja con descuadre >= umbral | | si | si |
| Cierre forzado administrativo | | | si |
| Editar datos fiscales cliente post-Z | | | si (solo emitiendo documento nuevo) |

Umbrales configurables por restaurante.

---

## 6. Pantallas UI

### 6.1. Estado pre-apertura

- Card central: último cierre con operador, fecha, importe, descuadre.
- CTA: **Abrir caja**.
- Si hay sesión huérfana: banner rojo con "Turno sin cerrar desde …" y CTA solo visible a admin: **Forzar cierre del turno anterior**.

### 6.2. Modal de apertura

- Selección de operador (chips de quick access users).
- Numpad para importe inicial en céntimos.
- Nota opcional.
- Confirmar → `POST /cash-sessions`.

### 6.3. Dashboard de caja activa

Layout:

```
┌─────────────────────────────────────────────────────────┐
│ Cabecera: operador, hora apertura, cronómetro, [Cerrar] │
├─────────────────────────────────────────────────────────┤
│ KPIs: ventas · tickets · ticket medio · efectivo teórico│
├─────────────────────────────────┬───────────────────────┤
│ Desglose por método (progreso)  │ Movimientos           │
│                                 │ + Entrada  - Salida   │
│                                 │ Lista cronológica     │
├─────────────────────────────────┴───────────────────────┤
│ Últimos tickets del turno (desplegable)                 │
├─────────────────────────────────────────────────────────┤
│ Alertas: invitaciones, anulaciones, descuadre estimado  │
└─────────────────────────────────────────────────────────┘
```

### 6.4. Cobro en pedidos/mesas — modificaciones

Al cerrar cuenta, el modal de cobro añade:

- Selector de método: efectivo · tarjeta · bizum · voucher · mixto.
- Si mixto: líneas de pago con importe por método hasta completar el total.
- Si factura completa: datos fiscales del cliente.
- Cambio calculado (efectivo): "Cliente paga 50€ de un total de 37,80€ → cambio 12,20€".

### 6.5. Split bill

- Botón "Dividir cuenta" en el panel de mesa.
- Tres tabs: **Partes iguales · Por líneas · Por comensal**.
- En modo por líneas: drag & drop de líneas a subcuentas. Cada subcuenta con su total.
- Cobro subcuenta por subcuenta.

### 6.6. Wizard de cierre (3 pasos a pantalla completa)

- **Paso 1 — Contar**: título "¿Cuánto efectivo hay en la caja?" + numpad grande.
- **Paso 2 — Justificar** (solo si descuadre): chips de motivos + nota libre.
- **Paso 3 — Revisar y confirmar Z**: resumen completo del Z, botones Imprimir · Enviar por email · Confirmar.

### 6.7. Histórico de sesiones

- Listado de cierres pasados por restaurante.
- Filtros: rango de fechas, operador, device, descuadre.
- Click → ver Z completo.

### 6.8. Modo barra (ticket rápido)

- Acceso desde topbar: "Venta rápida".
- Grid de productos + carrito lateral + cobro directo.

---

## 7. API REST

### 7.1. Sesiones

```
POST   /tpv/cash-sessions
       body: { device_id, initial_amount_cents, opened_by_user_id, notes? }
       201: CashSession

GET    /tpv/cash-sessions/active?device_id=...
       200: CashSession | 204

GET    /tpv/cash-sessions/:id
GET    /tpv/cash-sessions/:id/summary      (teórico calculado en vivo)
GET    /tpv/cash-sessions/:id/z-report     (solo si status=closed)

PATCH  /tpv/cash-sessions/:id
       action=start-closing        → status: open → closing
       action=close
         body: { final_amount_cents, discrepancy_reason?, closed_by_user_id }
         → status: closing → closed; emite Z
       action=cancel-closing       → status: closing → open
       action=force-close          (admin) → status: abandoned → closed con marca
```

### 7.2. Movimientos

```
POST   /tpv/cash-movements
       body: { cash_session_id, type, reason_code, amount_cents, description?, user_id }
       201: CashMovement
```

### 7.3. Pagos (Sale modificado)

```
POST   /tpv/sales
       body: {
         order_id,
         cash_session_id,
         payments: [{ method, amount_cents, metadata? }],
         document_type,
         customer_fiscal_data?,
         diner_number?      (si split por comensal)
       }
       201: Sale

PATCH  /tpv/sales/:id
       action=cancel              (supervisor+) → status=cancelled
POST   /tpv/sales/:id/credit-note (admin)       → crea Sale hijo negativo
```

### 7.4. Tips

```
POST   /tpv/tips
       body: { sale_id, cash_session_id, amount_cents, source, beneficiary_user_id? }
```

### 7.5. Validaciones clave

- `createSale`: si no hay sesión `open` para `device_id` → 409 `NO_ACTIVE_CASH_SESSION`.
- `createSale`: `Σ payments.amount_cents = Sale.total_cents` → si no, 422.
- `closeCashSession`: si hay Sales con `status=pending` → 409.

---

## 8. Auditoría y cumplimiento fiscal

### 8.1. Audit log

Toda operación sensible genera un registro en `AuditLog`:

- Apertura/cierre de sesión
- Cobro / anulación / credit note
- Descuento, invitación, precio manual
- Movimiento manual de caja
- Cambio de datos fiscales
- Cierre forzado

Campos: quién, cuándo, qué, estado antes/después, device, IP.

### 8.2. Integridad del Z

- Cada Z lleva `z_report_number` correlativo por restaurante.
- `z_report_hash = sha256(datos_Z + hash_Z_anterior)`.
- Romper la cadena (modificar un Z antiguo) invalida los posteriores: detectable en auditoría.

### 8.3. Marco normativo (España)

- **Veri*Factu** (Real Decreto 1007/2023): sistema de emisión de facturas verificables. Exige firma, encadenamiento y envío automático a la AEAT (o modo "no verificable" con generación obligatoria de hashes). Aplicable desde 2026 según tamaño empresa.
- **TicketBAI** (País Vasco): equivalente autonómico con XML firmado + envío a Hacienda Foral.
- **Ley Antifraude** (Ley 11/2021 + RD 1007/2023): prohíbe software "de doble uso" (que permita ocultar ventas). El TPV debe garantizar no-modificabilidad.

Para este proyecto: implementar hash chain y numeración correlativa; dejar la conexión con la API AEAT/Foral como integración futura documentada.

---

## 9. Plan de implementación por fases

Orden diseñado para ir de dentro hacia fuera: dominio → API → UI, y de lo crítico a lo opcional.

### Fase 1 — Núcleo de dominio y datos

1. Migraciones: `cash_sessions`, `cash_movements`, `sale_payments`, `tips`, `audit_logs`.
2. Extensiones en `sales` y `order_lines` (columnas nuevas).
3. Entidades y VOs: `CashSession`, `CashMovement`, `SalePayment`, `Tip`, `Money`, enums.
4. Repositorios Eloquent + interfaces.
5. Seeders mínimos para tests manuales.

### Fase 2 — Casos de uso de sesión

1. `OpenCashSession`
2. `GetActiveCashSession`
3. `GetCashSessionSummary` (teórico en vivo)
4. `RegisterCashMovement`
5. `StartClosingCashSession` / `CancelClosingCashSession`
6. `CloseCashSession` con generación de Z + hash
7. `ForceCloseCashSession` (admin)
8. Todos los endpoints REST asociados.

### Fase 3 — Cobro reforzado

1. `CreateSale` con `payments[]` multi-método.
2. Validación `Σ payments = total`.
3. Bloqueo por sesión cerrada.
4. `CancelSale` / `CreateCreditNote`.
5. Vinculación `sale.cash_session_id`.

### Fase 4 — Caja UI

1. Página `caja` con los cuatro estados (pre-apertura, activa, en arqueo, histórico).
2. Modal de apertura.
3. Dashboard activo con KPIs y desgloses en vivo.
4. Modal de movimiento manual.
5. Wizard de cierre de 3 pasos.
6. Vista de Z.
7. Histórico de sesiones.

### Fase 5 — Cobro mejorado (UI)

1. Modal de cobro con selector de método + mixto.
2. Calculadora de cambio en efectivo.
3. Datos fiscales del cliente (factura completa).
4. Selector de método bloqueado si no hay caja abierta.

### Fase 6 — Split bill y comensales

1. Marcar `diner_number` en línea desde comanda.
2. UI de "Dividir cuenta": partes iguales / por líneas / por comensal.
3. Cierre progresivo de Order (cerrada cuando todas las líneas pagadas).

### Fase 7 — Descuentos, invitaciones, propinas

1. Descuento de línea y global.
2. Invitación.
3. Precio manual (supervisor+).
4. Tips por tarjeta (al cobrar) y en efectivo (declarada en cierre).

### Fase 8 — Anulaciones y notas de abono

1. Anulación en turno (supervisor+).
2. Credit note en turno posterior (admin).
3. Reapertura de mesa cobrada por error.

### Fase 9 — Modo barra

1. Página "Venta rápida".
2. Orden efímera sin mesa.
3. Cobro directo.

### Fase 10 — Roles y permisos

1. Middleware de policies por operación.
2. UI adaptativa: ocultar/bloquear acciones según rol.
3. Umbrales configurables por restaurante.

### Fase 11 — Auditoría y fiscal

1. `AuditLog` con interceptores/observers.
2. Hash chain en Z.
3. Numeración correlativa.
4. Exportación de logs para inspección.

### Fase 12 — Avanzadas

1. Cola offline + idempotency keys.
2. Sincronización multi-device (broadcast o polling).
3. Reimpresión / envío por email del Z.
4. Integración Veri*Factu / TicketBAI (out of scope inicial, dejar interfaz preparada).

---

## 10. Criterios de aceptación globales

- Imposible crear un `Sale` sin `CashSession.status = open` para el device.
- Un `CashSession` cerrado no admite nunca más `Sale`, `SalePayment` ni `CashMovement`.
- `Σ SalePayment.amount = Sale.total` siempre.
- El Z muestra cifras consistentes con la suma atómica de Sales y Movements del turno.
- Los hashes Z forman cadena válida.
- Todo cambio de estado genera `AuditLog`.
- El dashboard en vivo y el Z final coinciden céntimo a céntimo.

---

## 11. Glosario

| Término | Definición |
|---|---|
| Caja / Sesión / Turno | Periodo durante el cual se aceptan cobros contra un cajón físico en un device. |
| Fondo | Importe en efectivo con el que se inaugura la caja para dar cambios. |
| Arqueo | Conteo de efectivo al cierre y contraste con el teórico. |
| Teórico | Importe de efectivo que *debería* haber en caja según el sistema. |
| Contado | Importe de efectivo realmente contado por el operador. |
| Descuadre | `contado - teórico`. Positivo es sobrante, negativo es faltante. |
| Z | Informe fiscal de cierre del turno. |
| Sangría | Retirada intermedia de efectivo durante el turno (normalmente al banco). |
| Nota de abono / Credit note | Documento fiscal negativo que anula o rectifica un Sale de un Z ya cerrado. |
| Ticket simplificado | Factura sin NIF del cliente (la mayoría de tickets). |
| Factura completa | Ticket con datos fiscales completos del cliente. |
| Ticket rápido / Venta directa | Cobro sin pasar por mesa (barra, take-away). |
| Split bill | División de cuenta. |
| Caja ciega | Arqueo en el que el operador no ve el teórico hasta declarar su contado. |
