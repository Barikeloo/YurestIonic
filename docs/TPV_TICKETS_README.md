# 🧾 TPV Hostelería — Tickets, Pagos Parciales y Cierre de Mesa

## Objetivo
Definir cómo debe funcionar la emisión de tickets cuando existen pagos parciales mientras la mesa sigue abierta.

Este documento separa claramente:
- Cobrar
- Emitir ticket
- Cerrar mesa

---

# 🧠 Concepto clave

## Cerrar mesa ≠ Emitir ticket

Una mesa puede tener MUCHOS tickets antes de cerrarse definitivamente.

La mesa solo se cierra cuando:
deuda_restante == 0

---

# 💰 Nuevo flujo del sistema

Antes:
Mesa abierta → Cerrar mesa → Cobrar → Imprimir ticket

Ahora:
Mesa abierta → Cobrar → Imprimir ticket → Mesa sigue abierta
Mesa abierta → Cobrar → Imprimir ticket → Mesa sigue abierta
Mesa abierta → Último pago → Mesa se cierra automáticamente

---

# 🧾 Nuevo concepto: Receipt (Ticket de pago)

Cada pago genera automáticamente un ticket.

Relación:
Mesa
 ├─ pedidos
 ├─ pagos
 │    └─ ticket generado
 └─ deuda restante

Los tickets pertenecen a los PAGOS, no al cierre de mesa.

---

# 💵 Ejemplo real

Mesa total = 100€

Un comensal paga 30€ y se va.

Se registra pago → se imprime ticket:

TICKET DE PAGO
Mesa: 12
Importe pagado: 30€
Método: tarjeta
Deuda restante mesa: 70€
Mesa sigue abierta

Este ticket es un RECIBO DE PAGO, no la cuenta final.

---

# 🍔 Nuevas comandas tras pagos parciales

Si después del pago se añaden nuevos productos:
Deuda nueva = deuda previa + nuevos pedidos

Ejemplo:
Quedaban 70€
Se añaden pedidos por 20€
Nueva deuda restante = 90€

El sistema sigue funcionando sin recalcular nada anterior.

---

# 🧾 Ticket final al cerrar mesa

Cuando la deuda restante llega a 0:
La mesa se cierra automáticamente.

Se imprime ticket final:

TICKET FINAL DE MESA
Total consumido: 120€
Total pagado: 120€

Pagos realizados:
- 30€ tarjeta (12:10)
- 45€ efectivo (13:02)
- 45€ tarjeta (13:05)

Mesa cerrada ✔

Este ticket es el resumen global.

---

# ❗ Regla importante

Imprimir ticket NO cierra la mesa.
Cobrar NO cierra la mesa.

La mesa solo se cierra cuando:
deuda_restante == 0

---

# 🎯 Cambios UX necesarios

Mientras la mesa esté abierta debe existir:

💰 Botón COBRAR
- Permite pagos parciales
- Genera ticket automáticamente
- La mesa sigue abierta

Cuando deuda restante = 0:

✅ Mesa cerrada automáticamente
🧾 Botón reimprimir ticket final

---

# 🧠 Resumen final

No existe el concepto de “cierre parcial de mesa”.

Existe:
- Pagos (con ticket)
- Mesa abierta mientras haya deuda
- Cierre automático al saldar la deuda
