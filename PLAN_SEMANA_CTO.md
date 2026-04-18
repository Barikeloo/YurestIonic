# Plan de la Semana — Presentación al CTO

> **Fecha de creación:** 19 de abril de 2026  
> **Objetivo:** Completar el Hito 4 (Front de Venta TPV) y preparar una demo funcional del flujo completo para presentar al CTO.

---

## 1. Dónde estás ahora

### Hitos completados

| Hito | Estado | Detalle |
|---|---|---|
| **Hito 1** — Modelo de datos | ✅ 100% | 20 migraciones, 14 seeders, shard key multi-tenant, relaciones completas |
| **Hito 2** — API REST Backoffice | ✅ 100% | CRUDs completos (familias, impuestos, productos, zonas, mesas), auth (email + PIN), SuperAdmin |
| **Hito 3** — Interfaz Backoffice | ✅ 100% | Página de Gestión robusta (~1600 líneas), componentes por entidad, formularios con validación |

### Extras ya implementados (fuera del roadmap)

- **Multi-tenancy** con `restaurant_id` como shard key en todas las tablas
- **SuperAdmin** con flujo de autenticación propio y gestión de restaurantes
- **Login por PIN** para operadores (Hito 6)
- **Roles de usuario** — operator / supervisor / admin (Hito 6)
- **Vinculación de dispositivo** — flujo de device link para tabletas
- **Quick access** — usuarios frecuentes para acceso rápido
- **Middlewares de seguridad** — `RequireAdminSession`, `RequireManagementSession`, `RequireSuperAdminSession`
- **Composite shard keys** para aislamiento de datos entre restaurantes

### Lo que falta (Hito 4 — último obligatorio)

El **backend del Hito 4 ya está hecho**. Tienes:
- Endpoints de órdenes: `POST /tpv/orders`, `POST /tpv/orders/lines`, `GET /tpv/orders`, `GET /tpv/orders/{id}`, `GET /tpv/orders/{id}/lines`, `PUT /tpv/orders/{id}`, `DELETE /tpv/orders/{id}`
- Endpoints de ventas: `POST /tpv/sales`, `POST /tpv/sales/lines`, `GET /tpv/sales`, `GET /tpv/sales/{id}`, `PUT /tpv/sales/{id}`, `DELETE /tpv/sales/{id}`
- Endpoints de catálogo TPV: `GET /tpv/families`, `GET /tpv/products`, `GET /tpv/zones`, `GET /tpv/tables`, `GET /tpv/taxes`
- `TpvService` en frontend con todos los métodos ya preparados (`createOrder`, `addOrderLine`, `createSale`, `listFamilies`, `listProducts`, etc.)

**Lo que falta es la interfaz de usuario**: las páginas `mesas`, `pedidos` y `caja` están creadas como esqueletos vacíos (11 líneas cada una).

---

## 2. Por qué avanzar y no solo pulir

### Tu código ya está bien

- Arquitectura DDD + Hexagonal consistente en los 10 dominios
- Value Objects con constructor privado + `create()` estático
- Entidades con `dddCreate()` como método de fábrica
- Responses como DTOs de salida (ej: `CreateFamilyResponse`)
- Separación Domain / Application / Infrastructure limpia
- Repositorios programados contra interfaces
- Sin facades de Laravel en lógica de dominio

**Pulir lo existente daría mejoras marginales.** Lo que realmente falta es poder enseñar el producto funcionando de punta a punta.

### El roadmap lo dice claro

> *"Se valora más un hito bien terminado que varios a medias"*

Un Hito 4 funcional aunque visualmente sencillo vale mucho más que un Hito 3 perfecto sin pantalla de venta. **Un TPV sin pantalla de venta no es un TPV.**

---

## 3. Lo que el CTO quiere ver

### La historia completa

El CTO no va a leer tu código línea a línea en la presentación. Quiere ver un **flujo de trabajo de principio a fin** que demuestre que el sistema funciona como producto real:

```
1. Entro al sistema → Login (email/password o PIN)
2. Backoffice → Gestiono restaurante, creo familias, impuestos, productos, zonas, mesas
3. TPV → Veo las zonas y mesas del restaurante
4. Abro una mesa → Selecciono usuario operador, registro comensales
5. Tomo el pedido → Navego por familias, añado productos, modifico cantidades
6. Cierro la venta → Calculo total, genero ticket, la mesa vuelve a libre
```

**Sin el paso 3-6 tu demo se queda a la mitad.** Con el TPV funcionando, cuentas la historia completa de un camarero usando el sistema en su turno.

### Puntos clave para la presentación

#### Arquitectura (lo que ya tienes para explicar)

- **DDD + Hexagonal**: cada dominio es autocontenido (Entity, VO, Interfaces, Application, Infrastructure)
- **Multi-tenancy**: un solo despliegue sirve a múltiples restaurantes con aislamiento de datos
- **Separación de responsabilidades**: el dominio no conoce Laravel, los controladores solo adaptan entrada/salida
- **Value Objects**: validaciones y reglas de negocio encapsuladas, inmutabilidad garantizada
- **Flujo de datos limpio**: Controller → Use Case → Entity/VO → Repository (interfaz) → Eloquent (implementación)

#### Decisiones técnicas que puedes justificar

| Decisión | Justificación |
|---|---|
| Separar `orders` y `sales` | Los pedidos abiertos tienen un ciclo de vida diferente a las ventas cerradas. Un pedido puede cancelarse, modificarse; una venta es un registro fiscal inmutable |
| `restaurant_id` como shard key | Preparación para multi-tenancy real. Permite escalar horizontalmente particionando por restaurante |
| Login por PIN | En hostelería los camareros comparten dispositivos. Un PIN de 4 dígitos es más rápido que email+password para el día a día |
| SuperAdmin separado de User | El superadmin gestiona la plataforma (todos los restaurantes), no es un usuario de un restaurante. Dominios separados = responsabilidades separadas |
| Responses como DTOs | El controlador no depende de la entidad del dominio para serializar. Si la entidad cambia internamente, la API no se rompe |
| VOs con constructor privado | Imposible crear un VO en estado inválido. Las validaciones se ejecutan siempre, no dependen de que alguien recuerde llamar a `validate()` |

#### Flujo técnico para explicar (ejemplo: abrir una mesa)

```
1. Camarero toca mesa "Mesa 3" en la tableta
2. Frontend llama POST /tpv/orders { tableId: "uuid-mesa-3" }
3. Controller recibe request, valida input
4. Controller llama al caso de uso CreateOrder
5. CreateOrder construye VOs: Uuid::generate(), DomainDateTime::now()
6. CreateOrder llama Order::dddCreate(...) → entidad inmutable
7. CreateOrder persiste via OrderRepositoryInterface::save()
8. EloquentOrderRepository (implementación) guarda en MySQL
9. CreateOrder devuelve CreateOrderResponse::create($order)
10. Controller devuelve JsonResponse con los datos
11. Frontend actualiza la mesa a "ocupada" (cambio de color)
```

#### Demo en vivo — Guion sugerido

1. **Inicio** (1 min): "Esto es un TPV para hostelería. Voy a mostrar el flujo completo desde la configuración hasta una venta."
2. **Login** (30 seg): Entrar con credenciales de admin
3. **Backoffice** (3 min): Mostrar la gestión — crear un producto nuevo, ver familias, zonas y mesas
4. **TPV - Mesas** (2 min): Cambiar a la vista de mesas, ver zonas, tocar una mesa libre
5. **TPV - Apertura** (1 min): Seleccionar operador, indicar comensales, la mesa pasa a ocupada
6. **TPV - Pedido** (3 min): Navegar por familias, añadir productos, modificar cantidad, ver el resumen
7. **TPV - Cierre** (2 min): Cerrar la venta, ver el ticket, la mesa vuelve a verde
8. **Arquitectura** (3-5 min): Mostrar brevemente la estructura de un dominio, explicar las capas, justificar decisiones
9. **Preguntas**: Tener preparado abrir código concreto si preguntan por algo

---

## 4. Plan de implementación día a día

### Lunes — Página de Mesas

**Objetivo:** Vista principal del TPV con zonas y mesas.

**Qué construir:**
- Tabs/botones para cambiar entre zonas del restaurante
- Grid de mesas dentro de cada zona
- Cada mesa muestra su nombre y estado con color:
  - 🟢 Verde = libre (sin orden abierta)
  - 🔴 Rojo/naranja = ocupada (tiene orden abierta)
- Al cargar la página, llamar a `TpvService.listZones()`, `TpvService.listTables()` y `TpvService.listOrders()` para cruzar el estado

**Archivos a tocar:**
- `frontend/src/app/pages/core/mesas/mesas.page.ts` — Lógica del componente
- `frontend/src/app/pages/core/mesas/mesas.page.html` — Template
- `frontend/src/app/pages/core/mesas/mesas.page.scss` — Estilos

**Datos que ya tienes en el service:**
```typescript
TpvService.listZones()   → TpvZoneItem[]   { id, name }
TpvService.listTables()  → TpvTableItem[]  { id, name, zoneId, seats }
TpvService.listOrders()  → TpvOrder[]      { id, tableId, status, total, createdAt }
```

**Lógica clave:**
- Cruzar `tables` con `orders` (status === 'open') para saber qué mesas están ocupadas
- Agrupar `tables` por `zoneId` para mostrarlas por zona
- Al hacer click en una mesa libre → abrir modal de apertura de venta
- Al hacer click en una mesa ocupada → navegar a la pantalla de pedido de esa orden

---

### Martes — Apertura de venta

**Objetivo:** Modal/pantalla para abrir una venta en una mesa.

**Qué construir:**
- Modal que aparece al tocar una mesa libre
- Selector de usuario operador (lista de quick users o lista completa)
  - Ya tienes `GET /auth/quick-users` para los usuarios frecuentes
- Teclado numérico para indicar número de comensales (pensado para táctil)
  - Botones grandes: 1-9, 0, borrar, confirmar
- Botón "Abrir mesa" que:
  1. Llama a `TpvService.createOrder({ tableId })` 
  2. Actualiza el estado de la mesa a ocupada
  3. Navega a la pantalla de pedido

**Consideraciones UX (táctil):**
- Botones grandes, mínimo 48x48px (mejor 64x64px para hostelería)
- Pocos pasos: seleccionar usuario → comensales → confirmar
- Feedback visual claro al tocar

---

### Miércoles — Página de Pedidos (parte 1: catálogo)

**Objetivo:** Pantalla de toma de pedido — lado izquierdo con el catálogo.

**Qué construir — Layout a dos columnas:**

```
┌─────────────────────────────────┬──────────────────────┐
│                                 │                      │
│   FAMILIAS (tabs/botones)       │   RESUMEN PEDIDO     │
│   ─────────────────────         │   ──────────────     │
│                                 │   Mesa 3 - 4 com.    │
│   [Producto 1]  [Producto 2]    │                      │
│   [Producto 3]  [Producto 4]    │   Cerveza    x2  6€  │
│   [Producto 5]  [Producto 6]    │   Tortilla   x1  8€  │
│                                 │   Café       x3  4€  │
│                                 │                      │
│                                 │   TOTAL: 18€         │
│                                 │   [CERRAR VENTA]     │
└─────────────────────────────────┴──────────────────────┘
```

**Lado izquierdo — Catálogo:**
- Fila superior: botones por familia (Bebidas, Entrantes, Carnes, Postres...)
- Grid de productos de la familia seleccionada
- Cada producto muestra nombre y precio
- Al tocar un producto → se añade al pedido (o incrementa cantidad si ya existe)

**Datos:**
```typescript
TpvService.listFamilies()  → TpvFamilyItem[]  { id, name, active }
TpvService.listProducts()  → TpvProductItem[] { id, name, price, familyId, active }
```

**Filtrar solo productos y familias activos.**

---

### Jueves — Página de Pedidos (parte 2: resumen + líneas)

**Objetivo:** Lado derecho del pedido — resumen con líneas.

**Qué construir:**
- Lista de líneas del pedido actual
- Cada línea muestra: nombre producto, cantidad, precio unitario, subtotal
- Botones por línea: +1, -1, eliminar
- Al tocar +1 → incrementar cantidad de esa línea
- Al tocar -1 → decrementar (si llega a 0, eliminar la línea)
- Total calculado en tiempo real (suma de subtotales)
- Info de la mesa: nombre, comensales, usuario que abrió

**Llamadas al backend al añadir línea:**
```typescript
TpvService.addOrderLine({
  orderId: 'uuid-de-la-orden',
  productId: 'uuid-del-producto',
  quantity: 1,
  price: 8.50
})
```

**Decisión de implementación:**
- Puedes mantener el estado de las líneas en memoria (array local) y sincronizar con el backend al añadir/modificar
- O hacer llamada al backend en cada acción y recargar líneas
- Para la demo, el enfoque local + sincronización es más fluido (sin esperas)

---

### Viernes — Cierre de venta + pulir + ensayar

**Objetivo:** Cerrar el ciclo completo y preparar la demo.

**Cierre de venta — Qué construir:**
- Botón "Cerrar venta" en la pantalla de pedido
- Modal de confirmación con:
  - Resumen del pedido (líneas + total)
  - Selector de usuario que cierra (puede ser diferente al que abrió)
  - Botón "Confirmar cierre"
- Al confirmar:
  1. Llamar a `TpvService.createSale({ orderId })` para generar la venta/ticket
  2. Actualizar la orden a status `invoiced`
  3. La mesa vuelve a estado libre (verde)
  4. Mostrar confirmación con número de ticket
  5. Navegar de vuelta a la vista de mesas

**Pulir:**
- Revisar que el flujo completo funcione sin errores: mesas → apertura → pedido → cierre → mesa libre
- Probar con los datos de los seeders
- Asegurarte de que los colores de mesa se actualizan correctamente
- Revisar la responsividad (la app debe verse bien en tablet, que es el dispositivo real de un TPV)

**Ensayar la demo:**
- Hacer el recorrido completo 2-3 veces
- Cronometrar: la demo debería durar 10-15 minutos con explicación
- Preparar "plan B" por si algo falla en vivo: tener capturas de pantalla o grabación
- Tener preparado el código de un dominio (ej: `Family` o `Order`) para mostrar la arquitectura si preguntan

---

## 5. Lo que NO hacer esta semana

- ❌ **No intentar Hito 5 (Informes)** — mejor un Hito 4 sólido que dos a medias
- ❌ **No refactorizar el backoffice** — ya está bien, no toques lo que funciona
- ❌ **No añadir mejoras del Hito 6** (descuentos, métodos de pago, etc.) — ya tienes PIN y roles como bonus
- ❌ **No obsesionarse con el diseño visual** — funcional y limpio es suficiente para un TPV, no necesitas animaciones ni glassmorphism
- ❌ **No escribir tests esta semana** — prioriza la demo funcional, los tests los puedes mencionar como "siguiente paso"

---

## 6. Checklist de entregables para el viernes

- [ ] **Página de Mesas** — Zonas con tabs, grid de mesas, colores libre/ocupada
- [ ] **Apertura de venta** — Modal con usuario + comensales + teclado numérico
- [ ] **Página de Pedidos** — Catálogo por familias + resumen de líneas con +/-/eliminar
- [ ] **Cierre de venta** — Generar ticket, liberar mesa, confirmación
- [ ] **Flujo completo probado** — De mesas → apertura → pedido → cierre → mesa libre
- [ ] **Demo ensayada** — Recorrido completo cronometrado (10-15 min)
- [ ] **Código preparado** — Tener abierto un dominio (ej: Order) para mostrar arquitectura si preguntan

---

## 7. Estructura de la presentación al CTO

### Guion (15-20 minutos)

**1. Introducción (1 min)**
> "He construido un TPV completo para hostelería. Voy a mostrar el flujo desde la configuración del negocio hasta una venta real."

**2. Demo — Backoffice (3-4 min)**
- Login como admin
- Mostrar la gestión: restaurante, familias, productos, impuestos, zonas, mesas
- Crear o editar algo en vivo para demostrar que funciona

**3. Demo — TPV (5-6 min)**
- Ir a la vista de mesas
- Abrir una mesa libre → seleccionar operador → comensales
- Tomar un pedido: navegar por familias, añadir productos, modificar cantidades
- Cerrar la venta: total, ticket, la mesa se libera
- Volver a la vista de mesas → verificar que la mesa está libre

**4. Arquitectura (3-5 min)**
- Mostrar la estructura de carpetas de un dominio (ej: `Order/`)
- Explicar las capas: Domain (entidades, VOs, interfaces) → Application (casos de uso) → Infrastructure (Eloquent, controladores)
- Mencionar decisiones clave:
  - "Separé orders y sales porque tienen ciclos de vida distintos"
  - "Usé multi-tenancy con shard key para que un despliegue sirva a múltiples restaurantes"
  - "El login por PIN es para el uso real en hostelería, donde los camareros comparten tablet"
  - "Los Value Objects garantizan que nunca se crea un objeto en estado inválido"

**5. Extras implementados (1-2 min)**
- PIN de operador (Hito 6)
- Roles (operator/supervisor/admin)
- SuperAdmin para gestión de plataforma
- Vinculación de dispositivo
- Seeders completos para desarrollo

**6. Siguientes pasos (1 min)**
> "Como evolución natural, los siguientes pasos serían: informes con gráficas de ventas (Hito 5), métodos de pago, cierre de caja, y actualización en tiempo real entre terminales."

**7. Preguntas**

### Tips para la presentación

- **Empieza por la demo, no por la arquitectura.** El CTO quiere ver el producto primero, la técnica después.
- **No pidas disculpas** por lo que no está hecho. Presenta lo que SÍ funciona con confianza.
- **Si algo falla en la demo:** "Esto es un entorno de desarrollo, déjame mostrar cómo se vería" → enseña el código o una captura.
- **Si preguntan por tests:** "Los hitos obligatorios priorizan funcionalidad. Los tests unitarios del dominio y de integración de la API son el siguiente paso natural."
- **Si preguntan por despliegue:** "El sistema se levanta con Docker (`make start`), con contenedores separados para API, frontend, base de datos y DbGate."
- **Si preguntan por escalabilidad:** "El shard key (`restaurant_id`) en todas las tablas prepara el sistema para particionamiento horizontal. Cada restaurante es un tenant aislado."

---

## 8. Archivos de referencia rápida

| Qué | Dónde |
|---|---|
| Rutas API | `backend/routes/api.php` |
| Dominio Order (ejemplo para mostrar) | `backend/app/Order/` |
| TpvService (ya tiene todos los métodos) | `frontend/src/app/services/tpv.service.ts` |
| Página Mesas (por implementar) | `frontend/src/app/pages/core/mesas/` |
| Página Pedidos (por implementar) | `frontend/src/app/pages/core/pedidos/` |
| Página Caja (por implementar) | `frontend/src/app/pages/core/caja/` |
| Gestión (referencia de componente completo) | `frontend/src/app/pages/core/gestion/` |
| Diseño de referencia | `DiseñoTPV.html` |
| Modelo de datos | `DATA_MODEL.md` |
| Roadmap completo | `ROADMAP.md` |

---

> **Recuerda:** El roadmap dice *"No esperamos que llegues a todo. Lo que buscamos es ver cómo trabajas, cómo evolucionas y qué decisiones técnicas tomas."* Tu arquitectura ya demuestra eso. El Hito 4 cierra el círculo y convierte tu proyecto en un producto demostrable.
