# 🍽️ TPV Hostelería — Lógica de Cobros y División de Cuenta

## Objetivo
Definir una lógica robusta, simple y escalable para gestionar todos los casos de cobro en un TPV de hostelería:

- Pagos parciales manuales
- División entre comensales
- Comensales que llegan o se van
- Nuevas comandas tras pagos parciales
- Cobro por comensal
- Cobro por productos

La meta es que el sistema sea coherente internamente y ultra rápido para el camarero.

---

# 🧠 Filosofía del sistema

## ❌ Enfoque incorrecto
Pensar en:
- quién debe cuánto  
- quién pagó de más  
- redistribuir cuentas continuamente  

Esto genera lógica compleja y bugs.

## ✅ Enfoque correcto
Pensar siempre en:

**La deuda viva de la mesa**

Una mesa NO tiene una cuenta fija.  
Tiene una deuda restante que va disminuyendo con los pagos.

---

# 💰 Concepto central

## Fórmula universal
deuda_restante = total_items - total_pagado

Esto se recalcula SIEMPRE en tiempo real.

Nunca se modifica el pasado.  
Nunca se redistribuyen pagos antiguos.  
Los pagos solo reducen la deuda pendiente.

---

# 🪑 Concepto de Comensal (Guest / Seat)

Los comensales NO son personas reales.  
Son placeholders de división de cuenta.

Se usan únicamente para:
- dividir cuentas
- asignar productos
- mejorar UX del camarero

Un comensal NO tiene dinero asociado.

---

# ⚠️ Regla crítica
## Los pagos NO pertenecen a un comensal
Los pagos pertenecen siempre a la mesa.

Un pago puede guardar guest_id solo como información visual (ticket / UX), pero no afecta a la deuda real.

---

# 🔁 Flujo base del sistema

## Estado inicial ejemplo
Mesa 4 comensales  
Total pedidos → 100€

Total items: 100€  
Total pagado: 0€  
Deuda restante: 100€  
Guests activos: 4

---

# 💵 Caso real clave

## 1️⃣ Un comensal paga 30€ y se va
Se registra un pago de 30€.

Nuevo estado:
Total items: 100€  
Total pagado: 30€  
Deuda restante: 70€  
Guests activos: 3

Importante:
No redistribuir.  
No recalcular cuentas.  
No guardar “pagó de más”.

El sistema sigue coherente automáticamente.

---

## 2️⃣ Los restantes quieren dividir la cuenta
Se divide la deuda restante, no la cuenta original.

Deuda restante = 70€  
Guests activos = 3  
70 / 3 = 23.33€

Este comportamiento resuelve automáticamente:
- pagos adelantados
- gente que se va antes
- pagos desiguales

---

# 👥 Comensales mutables

El número de comensales puede cambiar en cualquier momento.

Ejemplo:
Quedan 70€ por pagar → llegan 2 comensales nuevos.

Guests activos = 5  
Deuda restante = 70€  
70 / 5 = 14€

El sistema sigue siendo consistente sin recalcular nada previo.

---

# 🎯 Funcionalidades de cobro

## 1️⃣ Cobro libre (IMPRESCINDIBLE)
Botón principal del TPV:

💰 Cobrar importe libre [____ €]

Este botón:
- reduce la deuda restante
- resuelve el 80% de casos reales
- es el salvavidas universal del sistema

---

## 2️⃣ Dividir deuda restante

Flujo UX:
Dividir cuenta restante → preguntar número de personas → generar importes sugeridos → confirmar pagos

El camarero debe poder:
- usar guests activos
- modificar número manualmente

---

## 3️⃣ Cobro por comensal (siguiente fase)
Permite cobrar lo consumido por cada asiento.

---

## 4️⃣ Cobro por productos (fase avanzada)
Seleccionar líneas de pedido → cobrar selección.

Internamente sigue siendo:
registrar pago → reducir deuda restante

---

# 🧠 Regla de oro del sistema

Nunca recalcular el pasado.

El sistema debe ser append-only:
- Los pedidos se añaden
- Los pagos se añaden
- La deuda se calcula

Esto evita descuadres, bugs contables e inconsistencias.

---

# ❤️ Resumen final
Siempre dividir la deuda restante, nunca la cuenta original.

Si este principio se respeta:
- todas las casuísticas encajan
- el sistema escala
- el camarero trabaja rápido
- la contabilidad siempre cuadra
