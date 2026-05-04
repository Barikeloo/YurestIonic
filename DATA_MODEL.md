# Modelo de datos

Este documento describe el esquema de base de datos del sistema TPV. Puedes usarlo como referencia durante el desarrollo.

> Los importes monetarios se almacenan como enteros en céntimos para evitar problemas de precisión con decimales.
> Ejemplo: 10,50 € → `1050`

El sistema utiliza **Soft Deletes** en todas las entidades: los registros eliminados no se borran físicamente, sino que se marcan con `deleted_at`.

Se incluye la columna `restaurant_id` a modo de `shard key` para facilitar la segmentación de datos por restaurante y evitar cruces de datos.

---

## Entidades

### `restaurants`
Restaurantes clientes del sistema.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `name` | VARCHAR | Nombre del restaurante |
| `legal_name` | VARCHAR | Nombre legal, razón social |
| `tax_id` | VARCHAR | Identificador fiscal (NIF/CIF) |
| `email` | VARCHAR | Email |
| `password` | VARCHAR | Contraseña |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `users`
Usuarios que operan el TPV.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `role` | VARCHAR | Rol del usuario |
| `image_src` | VARCHAR | Ruta de imagen de perfil |
| `name` | VARCHAR | Nombre |
| `email` | VARCHAR | Email |
| `password` | VARCHAR | Contraseña |
| `pin` | VARCHAR | PIN numérico para acceso rápido |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `families`
Categorías para organizar el catálogo de productos.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `name` | VARCHAR | Nombre de la familia |
| `active` | BOOLEAN | Si está activa |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `taxes`
Impuestos aplicables a los productos (p. ej. IVA).

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `name` | VARCHAR | Nombre (ej. "IVA General") |
| `percentage` | INT | Porcentaje (ej. `21` para el 21%) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `products`
Catálogo de productos disponibles en el TPV.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `family_id` | BIGINT | FK → `families` |
| `tax_id` | BIGINT | FK → `taxes` |
| `image_src` | VARCHAR | Ruta de imagen del producto |
| `name` | VARCHAR | Nombre |
| `price` | INT | PVP en céntimos |
| `stock` | INT | Stock disponible |
| `active` | BOOLEAN | Si está disponible para la venta |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `zones`
Zonas o salones del establecimiento.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `name` | VARCHAR | Nombre de la zona |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `tables`
Mesas dentro de cada zona.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `zone_id` | BIGINT | FK → `zones` |
| `name` | VARCHAR | Nombre o número de mesa |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `orders`
Pedidos en el TPV, que se convierten en ventas al cerrarlos/registrar cobros.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `status` | VARCHAR | Estado del pedido (`open`, `cancelled`, `invoiced`) |
| `table_id` | BIGINT | FK → `tables` |
| `opened_by_user_id` | BIGINT | FK → `users` |
| `closed_by_user_id` | BIGINT | FK → `users` (nullable) |
| `diners` | INT | Número de comensales |
| `opened_at` | TIMESTAMP | Fecha de apertura |
| `closed_at` | TIMESTAMP | Fecha de cierre (nullable) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `order_lines`
Líneas de producto dentro de cada pedido.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `order_id` | BIGINT | FK → `orders` |
| `product_id` | BIGINT | FK → `products` |
| `user_id` | BIGINT | FK → `users` |
| `quantity` | INT | Cantidad |
| `price` | INT | Precio en céntimos en el momento de la venta |
| `tax_percentage` | INT | Porcentaje de impuesto aplicado |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `sales`
Ticket o venta en el TPV.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `order_id` | BIGINT | FK → `orders` |
| `user_id` | BIGINT | FK → `users` |
| `ticket_number` | INT | Número visible en el ticket (se asigna al cerrar) |
| `value_date` | TIMESTAMP | Fecha valor |
| `total` | INT | Total en céntimos (calculado al cerrar) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

> `ticket_number` se asigna automáticamente en el momento del cierre, no al crear la venta.

---

### `sales_lines`
Líneas de producto dentro de cada venta.

El precio y el impuesto se guardan en el momento de la venta para preservar el histórico, aunque el producto cambie de precio posteriormente.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador público único |
| `restaurant_id` | BIGINT | FK → `restaurants` |
| `sale_id` | BIGINT | FK → `sales` |
| `order_line_id` | BIGINT | FK → `order_lines` |
| `user_id` | BIGINT | FK → `users` |
| `quantity` | INT | Cantidad |
| `price` | INT | Precio en céntimos en el momento de la venta |
| `tax_percentage` | INT | Porcentaje de impuesto aplicado |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

### `order_final_tickets`
Ticket final de mesa (resumen global al cerrar la deuda).

| Campo | Tipo | Descripcion |
|---|---|---|
| `id` | BIGINT | Identificador interno |
| `uuid` | VARCHAR | Identificador publico unico |
| `restaurant_id` | BIGINT | FK -> `restaurants` |
| `order_id` | BIGINT | FK -> `orders` |
| `closed_by_user_id` | BIGINT | FK -> `users` |
| `ticket_number` | BIGINT | Numero visible del ticket final |
| `total_consumed_cents` | INT | Total consumido en centimos |
| `total_paid_cents` | INT | Total pagado en centimos |
| `payments_snapshot` | JSON | Snapshot de pagos (metodo, importe, fecha) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |
| `deleted_at` | TIMESTAMP | Soft delete |

---

## Relaciones

```
restaurants ──< users
restaurants ──< families
restaurants ──< taxes
restaurants ──< products
restaurants ──< zones
restaurants ──< tables
restaurants ──< orders
restaurants ──< order_lines
restaurants ──< sales
restaurants ──< sales_lines
restaurants ──< order_final_tickets
families  ──< products
taxes     ──< products
zones     ──< tables
tables    ──< sales
orders    ──< order_lines
orders    ──< sales
orders    ──< order_final_tickets
order_lines ──< sales_lines
sales     ──< sales_lines
products  ──< order_lines
products  ──< sales_lines
users     ──< orders (opened_by / closed_by)
users     ──< order_lines
users     ──< sales (opened_by / closed_by)
users     ──< sales_lines
users     ──< order_final_tickets
```

---

## Reglas de negocio

- Solo se pueden vender productos con `active = true`.
- Una venta debe tener al menos una línea para poder cerrarse.
- El `total` de la venta se calcula sumando todas las líneas:
  ```
  línea = price × quantity
  total venta = suma de totales con impuesto de todas las líneas
  ```
- El `ticket_number` solo se asigna al cerrar la venta.
- Se debe registrar qué usuario abre y qué usuario cierra cada venta.
- Cada línea registra el usuario que la añadió.
- Los registros eliminados se gestionan con Soft Deletes (`deleted_at`).
