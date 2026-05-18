# Reembolso de línea + fix del Diners-Status — contexto

> Sesión 2026-05-18. Implementación completa pendiente de QA en navegador.

## 1. Qué hemos implementado

### A. Reembolso de una línea ya pagada en una charge-session
**Caso de uso:** mesa con varios comensales, uno paga una línea (split por líneas). El cajero se da cuenta de que ese cobro no procede y quiere deshacerlo. Hoy el botón "X" sobre líneas pagadas estaba **bloqueado**. Ahora hay un botón **↶** que pide confirmación y revierte el cobro.

**Modelo de dominio elegido (decisiones cerradas):**
- **A2** — Refund explícito mediante **cancelación de la `Sale` parcial entera** (`status=cancelled`, `cancelled_by_user_id`, `cancelled_at`, `cancellation_reason`). La `Sale` queda inmutable como huella auditiva.
- **B** — Granularidad: por **línea**. Pero como cada `Sale` parcial cobra exactamente las líneas asignadas a un comensal en ese pago, en la práctica cancelar la `Sale` reembolsa esa(s) línea(s) juntas. Si una `Sale` cubre 2 líneas y se da X a una, **se reembolsan las dos** (limitación MVP — ver §4).
- **C** — Sin motivo obligatorio (`reason` opcional). Sin permisos por rol (cualquier usuario logueado).
- **D** — En efectivo se registra un `CashMovement` compensatorio (`MovementType::out`, `MovementReasonCode::cancellation`). NO se abre cajón físico (no hay integración hardware todavía).
- **E** — El `OrderFinalTicket` previo (si la session estaba `completed`) **no se borra**. Queda como dato histórico; la próxima vez que se cierre la session se generará un ticket nuevo con número diferente.

### B. Fix crítico en `ChargeSessionResponseBuilder`
Antes **no filtraba** ventas canceladas. Si cancelabas una `Sale` los `paid_order_line_ids`, `paid_cents` y `paid_diner_numbers` seguían contando como pagados. Sin esto, todo lo demás del refund hubiera sido invisible.

### C. Diners-status real por comensal
La sidebar en el modal de split mostraba el reparto equitativo (`total/diners`) en cada card aunque un comensal hubiera pagado sólo SUS líneas. Ahora acepta un input `dinerAmounts: Record<number, number>` con los importes reales calculados desde `line_assignments` + precios.

---

## 2. Archivos tocados

### Backend (Laravel/DDD)
| Archivo | Cambio |
|---|---|
| `backend/app/Sale/Application/CreateChargeSession/ChargeSessionResponseBuilder.php` | Filtra Sales canceladas en `collect()` y `collectPaidOrderLineIds()`. Cruza `paidDinerNumbers` con saleIds activos. Eliminados `error_log` de debug. |
| `backend/app/Sale/Domain/Entity/ChargeSession.php` | Añadido método `reactivate()` (active ← completed). No reactiva si está `cancelled`. |
| `backend/app/Sale/Domain/Exception/RefundablePaidLineNotFoundException.php` | **Nuevo**. Lanzada cuando no hay venta activa que contenga la order_line. |
| `backend/app/Sale/Application/RefundChargeSessionLine/RefundChargeSessionLineCommand.php` | **Nuevo**. DTO de entrada (`chargeSessionId`, `orderLineId`, `refundedByUserId`, `reason?`). |
| `backend/app/Sale/Application/RefundChargeSessionLine/RefundChargeSessionLine.php` | **Nuevo**. Use case completo. |
| `backend/app/Sale/Infrastructure/Entrypoint/Http/Requests/RefundChargeSessionLineRequest.php` | **Nuevo**. Validación + `toCommand()`. |
| `backend/app/Sale/Infrastructure/Entrypoint/Http/RefundChargeSessionLineController.php` | **Nuevo**. Mapea excepciones a HTTP (404/422/500). |
| `backend/routes/api.php` | **Nueva ruta**: `POST /tpv/charge-sessions/{id}/refund-line`. |

### Frontend (Angular)
| Archivo | Cambio |
|---|---|
| `frontend/src/app/features/cash/services/charge-session.service.ts` | `RefundLineRequest` + método `refundLine(sessionId, request)`. |
| `frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts` | `@Output() refundLine`. Método `requestRefund(line, event)` que pide confirm y emite. |
| `frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.html` | Botón `↶` reemplaza al icono 🔒 en líneas pagadas. |
| `frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.scss` | Clase `.diner-tile-line-refund`. |
| `frontend/src/app/features/cash/pages/caja/caja.page.ts` | Computed `dinerAmountsByLines`. Handler `onRefundLine($event)`. |
| `frontend/src/app/features/cash/pages/caja/caja.page.html` | `(refundLine)="onRefundLine($event)"` + `[dinerAmounts]="dinerAmountsByLines()"`. |
| `frontend/src/app/shared/components/diners-status/diners-status.component.ts` | Nuevo `@Input() dinerAmounts: Record<number, number> \| null`. Sobrescribe el reparto equitativo. |
| `frontend/src/app/features/tables/ui/mesas-abiertas/mesas-abiertas.component.html` | Fix NG0956: `track mesa` → `track mesa.order_id`. |

---

## 3. Flujo end-to-end del refund

1. Cajero abre modal de split sobre mesa con un comensal ya pagado.
2. La línea pagada se ve tachada con un botón `↶` (clase `.diner-tile-line-refund`).
3. Click → `requestRefund(line)` muestra `confirm("¿Reembolsar ... por X,XX €?")`.
4. Si OK → emite `refundLine` con `{ orderLineId, lineName, price }`.
5. `caja.page.onRefundLine` llama a `chargeSessionService.refundLine(sessionId, {...})` con `reason = "Reembolso de \"<lineName>\""`.
6. Backend `POST /tpv/charge-sessions/{id}/refund-line`:
   - Busca la `Sale` (no cancelada) cuyo `SaleLine.orderLineId` matchea.
   - `Sale.cancel(userId, reason)` → persist.
   - Cuenta efectivo de los `SalePayment` de esa Sale (para `CashMovement` compensatorio).
   - `SalePaymentRepository.delete($id)` para cada payment (igual que `CancelSale`).
   - Si la Sale tenía `cashSessionId` y había efectivo > 0 → crea `CashMovement` (out, cancellation).
   - Si `Order` está `invoiced` → `Order.reopen()` (vuelve a `to-charge`).
   - Si `ChargeSession` no estaba `active` ni `cancelled` (=`completed`) → `reactivate()`.
   - Recarga session y devuelve `CreateChargeSessionResponse` (mismo shape que `getCurrent`).
7. Frontend recibe la session fresca → `onChargeSessionUpdated` actualiza `paymentFacade`, `paidDiners`, `selectedTable.diners`. UI se redibuja (la línea reaparece en el pool, el comensal vuelve a pendiente, el `diners-status` recalcula).

---

## 4. Bugs conocidos / limitaciones / cosas mejorables

### 🐛 Limitaciones del refund (MVP)
- **Granularidad por Sale, no por línea individual**: si una `Sale` cobra 2+ líneas (comensal que pagó alitas + cerveza en el mismo asiento), darle ↶ a una reembolsa las dos. La UX no avisa de esto. **Fix futuro**: añadir columna `refunded_at` en `sale_lines` y filtrar SaleLines refunded en el builder. O detectar el caso y abrir confirm con la lista de líneas que se reembolsarán juntas.
- **`OrderFinalTicket` no se invalida** al reembolsar tras session completa. Queda flotando con datos históricos incorrectos. Próximo ticket tendrá número nuevo (no hay unique constraint que rompa), pero auditoría vería un ticket "fantasma". **Fix futuro**: marcar `OrderFinalTicket` como `voided` o relacionarlo con la Sale cancelada.
- **`SalePayment` se borra duro** (igual que `CancelSale`). Mejor sería un soft-delete o un flag `voided_by_sale_cancellation` para AEAT. **Fix futuro**: cambiar `salePaymentRepository->delete()` por marcar voided (requiere columna en BD).
- **Reembolso sin tarjeta automática**: en pago con tarjeta el frontend muestra "reembolso registrado" pero el cajero debe hacer la operación física en el TPV bancario aparte. No hay aviso explícito de esto.
- **Sin motivo obligatorio**: hoy `reason` viene autogenerado del frontend (`"Reembolso de \"<nombre>\""`). Para AEAT/auditoría real conviene pedir motivo al cajero (typo, cliente se queja, error de cobro...).
- **Sin permisos por rol**: cualquier usuario logueado puede reembolsar. Habrá que añadir middleware o check de rol (admin/encargado).
- **Sin reembolsos parciales por cantidad**: si pidieron 2 cervezas y solo se quiere reembolsar 1, no se puede. Habría que modelar cantidades en `sale_lines.quantity` y hacer split.

### 🐛 Limitaciones de `diners-status`
- **`dinerAmountsByLines` solo cuenta líneas con `id` conocido en `selectedTableLines`**. Si tras un cobro parcial `selectedTableLines` se queda únicamente con las "pendientes" (pasa en algunas ramas del código de caja), las líneas YA pagadas no estarán en `priceByLineId` y el importe del comensal aparecerá en 0. Revisar caja.page.ts línea ~880 (`onSplitBill` finalize) y validar que `selectedTableLines` incluye TODAS las order_lines, no solo unpaid. **Tarea pendiente** para mañana.
- El reparto equitativo de fallback (`total/diners`) no contempla `remainder` (la "vuelta" del último comensal). Es minor y AEAT-irrelevante pero pinta 32.70 € + 32.70 € + 32.70 € = 98.10 € (en este caso cuadra). En otros totales puede faltar 0.01 € que sí está reflejado en el `paidTotal`.

### 🐛 Posibles regresiones / cosas que NO he tocado y conviene revisar
- **CancelSale** (el use case existente) sigue sin reactivar la `ChargeSession` si estaba `completed`. Si alguien llama `CancelSale` directamente sobre una Sale completa, la session quedará en `completed` pero sin pago suficiente. Decidir si CancelSale debería también reactivar la session.
- **`MovementReasonCode::cancellation`** se reutiliza para refunds. Plantearse añadir `::refund()` específico para distinguir en informes Z.
- **`SaleStatus`** en BD admite `refunded` pero el VO PHP solo conoce `closed|cancelled|open|pending`. Si en el futuro se quiere distinguir refund de cancel, hay que ampliar `SaleStatus`.
- **`error_log` de debug** quedaba en el builder antiguo. Lo he limpiado. Si aparece de nuevo en otro PR, evitar.
- **Logs `console.log`** de `split-bill-modal` (`rebuildAssignedLines - chargeSession:` etc.) siguen ahí. Limpiarlos cuando se haga merge final.
- **`paymentFacade.paidLineIds()`** se pasa al modal — verificar que se sincroniza correctamente tras refund (no debería contener la línea reembolsada después de `onChargeSessionUpdated`).

### 🧪 QA pendiente en navegador
- [ ] Caso del usuario: mesa 98.10 €, 3 comensales, comensal 1 paga alitas (9.90 €), click ↶ → debería volver a 98.10 € restantes y comensal 1 a pendiente.
- [ ] Caso "última línea cobrada": comensal último paga y completa la session → cancelar esa Sale → session vuelve a `active`, order vuelve a `to-charge`, todas las líneas reaparecen.
- [ ] Caso multi-línea en una Sale: comensal paga 2 líneas juntas → click ↶ en una → confirmar que se reembolsan las 2 (limitación MVP).
- [ ] Pago con tarjeta: ↶ debería funcionar pero sin movimiento de caja compensatorio.
- [ ] Pago con efectivo: verificar que aparece `CashMovement` tipo `out` con motivo cancellation.
- [ ] Verificar `diners-status` sidebar: comensal 1 con line_assignments debería mostrar SU importe real (9.90 €), no 32.70 €. Los demás siguen con reparto equitativo.
- [ ] Tras refund, reabrir el modal y comprobar que la línea reaparece en el pool y se puede asignar a otro comensal.

### 🐛 Otros no relacionados que vi
- `mesas-abiertas.component.html:7` tenía `@for ... track mesa` (identidad). Lo cambié a `track mesa.order_id`. Resuelto NG0956 que aparecía en consola.
- Hay un warning recurrente de Sass `@import` deprecated en `global.scss:20-21` (Ionic). Sin impacto, futuro Dart Sass 3 lo romperá.

---

## 5. Cómo retomar mañana

1. **Probar el flujo en navegador** (checklist QA de arriba).
2. **Si todo OK**: limpiar `console.log` en `split-bill-modal.component.ts` (líneas ~132-143) y commit.
3. **Si la limitación de Sale multi-línea molesta** (probable, porque varios comensales del MVP pueden pagar juntos varias líneas): planear refactor para refund por línea individual — implica columna `refunded_at` en `sale_lines` + filtro en builder.
4. **Pulir UX del confirm**: hoy es `window.confirm()` nativo. Convertir a modal del proyecto cuando haya tiempo.
5. **Decidir si reason debe ser obligatorio** antes de Hito 4 (AEAT).

---

## 6. Endpoint reference

**`POST /tpv/charge-sessions/{id}/refund-line`**

Request:
```json
{
  "order_line_id": "uuid",
  "refunded_by_user_id": "uuid",
  "reason": "Reembolso de \"Alitas de pollo\"" // optional
}
```

Response 200 (CreateChargeSessionResponse):
```json
{
  "id": "uuid",
  "order_id": "uuid",
  "diners_count": 3,
  "total_cents": 9810,
  "paid_cents": 0,
  "remaining_cents": 9810,
  "suggested_per_diner_cents": 3270,
  "paid_diner_numbers": [],
  "status": "active",
  "line_assignments": [...],
  "paid_order_line_ids": [],
  "created_at": "...",
  "updated_at": "..."
}
```

Errores:
- `404 ChargeSessionNotFoundException` — session no existe.
- `404 RefundablePaidLineNotFoundException` — no hay Sale activa con esa línea en esa session.
- `422 DomainException` — Sale ya cancelada (`Sale.cancel` lanza) u otros del dominio.
