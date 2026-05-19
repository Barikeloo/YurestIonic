# Plan — Cerrar pantalla de Cobros

Estado: el reparto equal funciona en sus casos principales (líneas + equal, toggle ON/OFF, cuota fija entre cobros, ronda invalidada al pagar líneas o refund). Quedan estas tareas para dejar la pantalla pulida.

Orden recomendado: hacer **Alta** → smoke test → **Media** → cleanup **Baja** → verificar backend.

---

## Alta — Contador de comensales desincronizado del grid

**Síntoma:** el contador grande dice "3" pero el grid solo pinta 1 tarjeta. Al bajar a 2 con `−`, el comensal pendiente desaparece (no se ve en ningún sitio) y al subir vuelve. Confuso.

**Causa:** [split-bill-modal.component.html:83](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.html#L83) muestra `chargeSession.diners_count` (total absoluto de la mesa), mientras [split-bill-modal.component.html:104](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.html#L104) itera `displayedEqualDiners` (subconjunto).

**Fix:**
- Cambiar el número del contador a `displayedEqualDiners.length` (las tarjetas que se ven).
- Mantener `+ / −` actuando sobre `diners_count` real en backend; la sincronía visual queda 1:1 porque añadir un comensal añade una tarjeta pendiente y quitarlo quita su tarjeta.
- Quitar (o reescribir) la subetiqueta `"X de Y comensales"` de [split-bill-modal.component.html:90](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.html#L90) ya que el contador grande pasa a ser X y la Y deja de aportar.

**Verificación:**
1. Mesa 4 comensales, D1+D2 pagan líneas → contador muestra "2" (los pendientes). Grid: 2 tarjetas.
2. Toggle ON → contador muestra "4". Grid: 4 tarjetas.
3. `+` → contador "5", aparece una tarjeta más. `−` → "4" otra vez, desaparece.

---

## Alta — Race condition al doble-click en "Cobrar"

**Síntoma:** doble-click rápido en el botón Cobrar de un comensal podría disparar dos `confirmSplit.emit` antes de que el primero termine. Riesgo: doble pago / `equalRoundPaidDinerNumbers` con un mismo número dos veces / cuota fija contaminada.

**Causa:** [split-bill-modal.component.ts:591](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts#L591) el `if (this.isLoading) return` se evalúa, pero el `this.isLoading = true` ocurre **después** de validaciones; en la práctica el segundo click puede llegar antes de que el guard se active si Angular no ha re-renderizado.

**Fix:** mover el bloque a un guard atómico:
```ts
public chargeEqualPart(dinerNum: number): void {
  if (this.isLoading) return;
  this.isLoading = true;
  // resto...
}
```
y aplicar el mismo patrón a `chargeDiner` ([split-bill-modal.component.ts:529](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts#L529)).

**Verificación:** abrir DevTools, atar un breakpoint en `chargeEqualPart` y hacer doble-click — el segundo debe salirse en el guard sin reentrar.

---

## Baja — `@Output() paymentRecorded` muerto

**Causa:** declarado en [split-bill-modal.component.ts](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts) y nunca emitido. No se escucha desde [caja.page.html](frontend/src/app/features/cash/pages/caja/caja.page.html).

**Fix:** borrar la declaración del `@Output`. Cinco segundos.

---

## Baja — `paidDiners` @Input legacy

**Causa:** [split-bill-modal.component.ts:36](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts#L36) sigue declarado y se usa como fallback solo cuando `chargeSession` es null. Con el flujo actual `chargeSession` siempre está cargado antes de que el grid se pinte.

**Fix:** quitarlo del modal y del binding `[paidDiners]="paidDiners"` en [caja.page.html](frontend/src/app/features/cash/pages/caja/caja.page.html). Si queda algo huérfano en `caja.page.ts` (el campo `public paidDiners: number[] = []` solo lo usa el binding), borrarlo también.

**Verificación:** typecheck verde y que el grid sigue pintando correctamente con sesión cargada.

---

## Media — `onClose` sin aviso en modo equal a media ronda

**Síntoma:** si cierras con la X estando en `equal` con ronda en curso (pagó un comensal pero faltan otros), no se avisa. Los datos persisten en backend, no se pierde nada, pero el cajero puede confundirse.

**Causa:** [split-bill-modal.component.ts:622-625](frontend/src/app/features/cash/ui/split-bill-modal/split-bill-modal.component.ts#L622-L625) solo prompt si `mode === 'lines'` con asignaciones.

**Fix opcional (no bloqueante):** ampliar el confirm también cuando `mode === 'equal'` y existan pagos en `equalRoundPaidDinerNumbers` y queden pendientes.

**Verificación:** abrir reparto equal, cobrar a un comensal, cerrar con X → debe pedir confirmación.

---

## Verificar backend — Bajar `diners_count` con pendientes

**Caso a probar manualmente:**
1. Mesa 4 comensales, total 80 €, sin pagos.
2. Bajar a 3 → backend debería responder OK, `remaining_cents` sigue 80 €.
3. D1 y D2 pagan líneas (cada uno 20 €) → `paid_diner_numbers = [1, 2]`, `remaining_cents = 40 €`.
4. Bajar a 2 → backend debería bloquearlo o permitirlo dejando `remaining_cents = 40 €` repartido entre D1+D2 en equal toggle ON.
5. Subir a 3 → reaparece un D3 limpio con `remaining_cents` intacto.

**Qué mirar en backend:** [backend/app/Sale/Application/UpdateChargeSessionDiners/UpdateChargeSessionDiners.php](backend/app/Sale/Application/UpdateChargeSessionDiners/UpdateChargeSessionDiners.php) y la entidad [ChargeSession.php](backend/app/Sale/Domain/Entity/ChargeSession.php) — comprobar que no permite quedarse con `paid_diner_numbers` fuera de rango y que `remaining_cents` no se descuadra.

**Si encuentras un descuadre:** abrir tarea aparte (no bloqueante para hoy a menos que sea grave).

---

## Smoke test final (5 min)

Antes de dar la pantalla por cerrada, recorrer estos seis casos:

- [ ] Mesa 4 comensales, 80 €. Todos pagan equal sin redondeo. Verificar que cuota se mantiene estable.
- [ ] Mesa 4 comensales, 35,21 €. Quien quede solo al final absorbe el céntimo (8,81 €), el resto ven 8,80 € siempre.
- [ ] Mesa 4, D1+D2 pagan líneas, D3+D4 reparten 52,40 € → 26,20 € cada uno.
- [ ] Mismo caso con toggle ON: 4 comensales, 13,10 € cada uno.
- [ ] Refund de una línea ya pagada → cuota equal se recalcula sobre el nuevo `remaining`.
- [ ] Bajar/subir `diners_count` en plena ronda → cuota equal se recalcula sin números raros.

---

## Fuera de scope (roadmap, no tocar hoy)

- `SalePayment` multi-método (efectivo + tarjeta en un mismo ticket).
- Anulaciones / notas de abono.
- Cierre forzado administrativo para turnos huérfanos.
- Permisos por rol para anulación / sangría / reapertura.
- Z fiscal (Veri*Factu / TicketBAI).
