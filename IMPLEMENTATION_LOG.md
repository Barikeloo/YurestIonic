# Resumen de Implementación - Modelo de Datos TPV 📋

Fecha: 24 de marzo de 2026
Estado: ✅ COMPLETADO

---

## 📌 Lo que faltaba del DATA_MODEL.md

### 1. **Tabla `restaurants` - IMPLEMENTADA** ✅
   - Migración: `2026_03_23_000050_create_restaurants_table.php`
   - Dominio: `App\Restaurant\`
   - Contiene: ID, UUID, nombre, razón social, NIF, email, contraseña

### 2. **Campo `restaurant_id` (Shard Key) - IMPLEMENTADO** ✅
   - Migración: `2026_03_24_000000_add_restaurant_id_to_all_tables.php`
   - Añadido a todas las tablas:
     - users
     - families
     - taxes
     - products
     - zones
     - tables
     - sales
     - sales_lines

### 3. **Campo `pin` en users - IMPLEMENTADO** ✅
   - Para acceso rápido del operador al TPV
   - Añadido en la misma migración de restaurant_id

### 4. **Tablas `orders` y `order_lines` - IMPLEMENTADAS** ✅
   - Migración: `2026_03_24_000100_create_orders_tables.php`
   - Separan pedidos abiertos de ventas cerradas
   - Flujo: órdenes → líneas de orden → ventas → líneas de venta

### 5. **Refactorización de `sales` - IMPLEMENTADA** ✅
   - Migración: `2026_03_24_000200_refactor_sales_tables.php`
   - Ahora vinculada a `orders` (relación)
   - Contiene: ticket_number, value_date, total
   - Tiene relación con `order_id` y `user_id`

---

## 🏗️ Dominios creados

### **1. Restaurant** (`App\Restaurant\`)
```
backend/app/Restaurant/
├── Domain/
│   ├── Entity/
│   │   └── Restaurant.php
│   ├── ValueObject/
│   └── Interfaces/
│       └── RestaurantRepositoryInterface.php
├── Application/
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   └── EloquentRestaurant.php
    │   └── Repositories/
    │       └── EloquentRestaurantRepository.php
    └── Entrypoint/Http/
```

**Entidad Restaurant:**
- ID (BIGINT)
- UUID (VARCHAR - identificador público)
- Name, LegalName, TaxId
- Email, Password (contraseña hasheada)
- Timestamps + SoftDeletes

### **2. Order** (`App\Order\`)
```
backend/app/Order/
├── Domain/
│   ├── Entity/
│   │   ├── Order.php
│   │   └── OrderLine.php
│   ├── ValueObject/
│   │   └── OrderStatus.php (open, cancelled, invoiced)
│   └── Interfaces/
│       ├── OrderRepositoryInterface.php
│       └── OrderLineRepositoryInterface.php
├── Application/
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   ├── EloquentOrder.php
    │   │   └── EloquentOrderLine.php
    │   └── Repositories/
    │       ├── EloquentOrderRepository.php
    │       └── EloquentOrderLineRepository.php
    └── Entrypoint/Http/
```

**Entidades:**
- **Order:** Pedido abierto en una mesa con estado (open, cancelled, invoiced)
- **OrderLine:** Línea de productos dentro de una orden

**OrderStatus VO:**
- `open()` - Orden abierta
- `cancelled()` - Orden cancelada
- `invoiced()` - Orden convertida en factura/venta

### **3. Sale** (`App\Sale\`)
```
backend/app/Sale/
├── Domain/
│   ├── Entity/
│   │   ├── Sale.php
│   │   └── SaleLine.php
│   ├── Interfaces/
│   │   ├── SaleRepositoryInterface.php
│   │   └── SaleLineRepositoryInterface.php
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   ├── EloquentSale.php
    │   │   └── EloquentSaleLine.php
    │   └── Repositories/
    │       ├── EloquentSaleRepository.php
    │       └── EloquentSaleLineRepository.php
```

**Entidades:**
- **Sale:** Venta cerrada (ticket) con ticket_number, value_date, total
- **SaleLine:** Línea de venta con referencia a order_line

---

## 📝 Migraciones creadas

| # | Nombre | Descripción |
|---|--------|-------------|
| 1 | `2026_03_23_000050_create_restaurants_table.php` | Tabla restaurants |
| 2 | `2026_03_24_000000_add_restaurant_id_to_all_tables.php` | Shard key a todas las tablas |
| 3 | `2026_03_24_000100_create_orders_tables.php` | Tablas orders y order_lines |
| 4 | `2026_03_24_000200_refactor_sales_tables.php` | Refactorizar sales para vincular con orders |

---

## 🔌 Inyección de dependencias

Registado en `AppServiceProvider.php`:

```php
$this->app->bind(RestaurantRepositoryInterface::class, EloquentRestaurantRepository::class);
$this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
$this->app->bind(OrderLineRepositoryInterface::class, EloquentOrderLineRepository::class);
$this->app->bind(SaleRepositoryInterface::class, EloquentSaleRepository::class);
$this->app->bind(SaleLineRepositoryInterface::class, EloquentSaleLineRepository::class);
```

---

## 🚀 Próximos pasos

1. **Ejecutar migraciones**
   ```bash
   make db-migrate
   ```

2. **Crear casos de uso (Application)** para:
   - Crear restaurante
   - Abrir orden
   - Agregar líneas a orden
   - Cerrar orden (convertir a venta)
   - Emitir ticket

3. **Crear controladores HTTP** en cada dominio

4. **Registrar rutas** en `routes/api.php`

5. **Tests** para asegurar la integridad del modelo

---

## 📊 Diagrama de relaciones

```
restaurants ──< users (restaurant_id)
restaurants ──< families (restaurant_id)
restaurants ──< taxes (restaurant_id)
restaurants ──< products (restaurant_id)
restaurants ──< zones (restaurant_id)
restaurants ──< tables (restaurant_id)
restaurants ──< orders (restaurant_id)
restaurants ──< order_lines (restaurant_id)
restaurants ──< sales (restaurant_id)
restaurants ──< sales_lines (restaurant_id)

users ──< orders (opened_by_user_id, closed_by_user_id)
users ──< order_lines (user_id)
users ──< sales (user_id)
users ──< sales_lines (user_id)

families ──< products
taxes ──< products
zones ──< tables (zone_id)
tables ──< orders (table_id)

orders ──< order_lines (order_id)
orders ──< sales (order_id)
order_lines ──< sales_lines (order_line_id)

sales ──< sales_lines (sale_id)
products ──< order_lines
products ──< sales_lines
```

---

## ✅ Checklist de validación

- [x] Tabla `restaurants` creada
- [x] `restaurant_id` agregado a todas las tablas
- [x] Campo `pin` agregado a `users`
- [x] Tablas `orders` y `order_lines` creadas
- [x] Tabla `sales` refactorizada
- [x] Dominio `Restaurant` implementado
- [x] Dominio `Order` implementado
- [x] Dominio `Sale` implementado
- [x] Entidades con constructores privados y métodos `dddCreate()`
- [x] VOs (`OrderStatus`) creados
- [x] Repositorios con interfaces
- [x] Modelos Eloquent
- [x] Binding en `AppServiceProvider`
- [x] Migraciones con rollback

