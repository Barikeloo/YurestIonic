# Feature: Menús en el TPV — Contexto para el siguiente agente

> Documento escrito el 22/05/2026. Resume la implementación completa del módulo de **menús** (productos compuestos por secciones) en el TPV de YurestIonic. Léelo antes de tocar cualquier archivo relacionado.

---

## 1. Qué es esto

Un **menú** es un producto compuesto que el restaurador configura con secciones. Cada sección tiene reglas `min_choices / max_choices` y una lista de items (productos del catálogo, con variante y suplemento opcional).

Ejemplo real: *Menú del día* → Sección 1: "Primer plato" (elige 1), Sección 2: "Segundo plato" (elige 1), Sección 3: "Postre" (elige 1).

Cuando el camarero añade un menú a una comanda, se abre un modal donde elige un producto por cada sección, y opcionalmente variantes y modificadores (extras/acompañamientos) de ese producto. La línea de orden guarda todo el menú como una única línea con las elecciones denormalizadas en JSON.

---

## 2. Backend (DDD + Hexagonal)

### 2.1 Dominio `Menu` — estructura de carpetas

```
backend/app/Menu/
├── Domain/
│   ├── Entity/
│   │   ├── Menu.php              # Aggregate root
│   │   ├── MenuSection.php     # Sección con reglas min/max
│   │   └── MenuItem.php        # Producto dentro de una sección
│   ├── ValueObject/
│   │   ├── MenuName.php
│   │   ├── MenuDescription.php
│   │   ├── MenuPrice.php
│   │   ├── MenuValidity.php
│   │   ├── MenuAvailability.php
│   │   ├── MenuSectionName.php
│   │   ├── MenuSectionChoiceRule.php
│   │   └── MenuItemExtraPrice.php
│   ├── Interfaces/
│   │   └── MenuRepositoryInterface.php
│   └── Exception/
│       └── Menu*Exception.php
├── Application/
│   └── (no hay casos de uso de menú todavía; CRUD vía repo directo)
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/
    │   │   ├── EloquentMenu.php
    │   │   ├── EloquentMenuSection.php
    │   │   └── EloquentMenuItem.php
    │   └── Repositories/
    │       └── EloquentMenuRepository.php
    └── Entrypoint/
        └── Http/
            └── (list/get endpoints en TpvController)
```

### 2.2 Migraciones ejecutadas

| Archivo | Tabla |
|---------|-------|
| `2026_05_21_000100_create_menus_table` | `menus` |
| `2026_05_21_000200_create_menu_sections_table` | `menu_sections` |
| `2026_05_21_000300_create_menu_items_table` | `menu_items` |
| `2026_05_21_001000_add_menu_fields_to_order_lines` | `order_lines` (campos `menu_id`, `menu_name`, `menu_selections` JSON) |

La migración de `order_lines` hace `product_id` nullable (antes era `NOT NULL`) y añade los campos de menú, porque una línea puede ser **producto** o **menú**, nunca ambos.

### 2.3 Caso de uso: añadir menú a orden

```
backend/app/Order/Application/AddMenuLineToOrder/
├── AddMenuLineToOrder.php        # Caso de uso
├── AddMenuLineToOrderCommand.php # DTO de entrada
└── AddMenuLineToOrderResponse.php # DTO de salida
```

**Flujo del caso de uso:**
1. Valida que la orden esté abierta.
2. Carga el menú activo (no archivado).
3. Valida que cada sección reciba un número de selecciones dentro de `[min, max]`.
4. Valida que cada producto elegido exista, esté activo y pertenezca a la sección.
5. Calcula extras (suplementos del item + precio de modificadores).
6. Decrementa stock de cada producto elegido en 1 unidad.
7. Crea la `OrderLine` como **línea de menú** (`OrderLine::dddCreateMenuLine`) con las elecciones denormalizadas en JSON.

**Controller:** `backend/app/Order/Infrastructure/Entrypoint/Http/AddMenuLineController.php` — invocable, responde `201` con el JSON de la línea creada.

**Request validation:** `backend/app/Order/Infrastructure/Entrypoint/Http/Requests/AddMenuLineToOrderRequest.php`

### 2.4 Entidad `OrderLine` — cambios

`OrderLine::dddCreateMenuLine(...)` es un factory estático nuevo que crea una línea sin `product_id` pero con `menu_id`, `menu_name` y `menu_selections` (array de selecciones con `section_name`, `product_id`, `product_name`, `variant_id`, `variant_name`, `modifiers`, `extra_price`).

### 2.5 Endpoints relevantes

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET`  | `/api/tpv/menus?active=true&archived=false` | Lista menús del restaurante |
| `POST` | `/api/tpv/orders/menu-lines` | Añade un menú a una orden |

El endpoint `GET /api/tpv/menus` está implementado en `TpvController` (o similar) y devuelve la lista de menús con secciones e items anidados.

---

## 3. Frontend (Angular + Ionic standalone)

### 3.1 Tipos añadidos en `tpv.service.ts`

```ts
export interface TpvMenuItem {
  id: string;
  product_id: string;
  variant_id: string | null;
  extra_price: number;
  position: number;
}

export interface TpvMenuSection {
  id: string;
  name: string;
  position: number;
  min_choices: number;
  max_choices: number;
  items: TpvMenuItem[];
}

export interface TpvMenu {
  id: string;
  tax_id: string;
  name: string;
  description: string | null;
  price: number;
  active: boolean;
  archived: boolean;
  validity_from: string | null;
  validity_to: string | null;
  available_days: number;
  available_from_time: string | null;
  available_to_time: string | null;
  sections: TpvMenuSection[];
}

export interface TpvOrderLineMenuSelection {
  section_name: string;
  product_id: string;
  product_name: string;
  variant_id: string | null;
  variant_name: string | null;
  modifiers: Array<{ id: string; name: string; price: number; type?: 'extra' | 'accompaniment' }>;
  extra_price: number;
}
```

`TpvOrderLine` ya tenía campos `menu_id`, `menu_name`, `menu_selections` (añadidos en una fase anterior).

### 3.2 Servicio — métodos añadidos

En `frontend/src/app/features/cash/services/tpv.service.ts`:

```ts
public listMenus(): Observable<TpvMenu[]>   // GET /tpv/menus
public addMenuLineToOrder(payload): Observable<unknown>  // POST /tpv/orders/menu-lines
```

### 3.3 Facade — estado y método

En `frontend/src/app/features/orders/facades/comanda.facade.ts`:

- Señales: `_menus`, `_activeCatalog` (`'products' | 'menus'`), `_addingMenuLine`.
- `setActiveCatalog(catalog)` — cambia entre catálogo de productos y menús.
- `addMenuLine(menuId, selections, notes?)` — envía al backend y refresca `existingLines` para ver el menú inmediatamente en el panel.

### 3.4 Componente `menu-config-modal`

Nuevo componente standalone en:

```
frontend/src/app/features/orders/components/menu-config-modal/
├── menu-config-modal.component.ts
├── menu-config-modal.component.html
└── menu-config-modal.component.scss
```

**Entradas:**
- `[menu]` — `TpvMenu` a configurar.
- `[products]` — `TpvProductItem[]` del catálogo (para resolver nombres, variantes y modificadores de los productos elegidos).

**Salidas:**
- `(confirm)` — `MenuConfigResult { selections: MenuSelectionPayload[], notes: string | null }`
- `(closeModal)` — cierra sin añadir.

**Funcionalidad:**
- Muestra cada sección del menú con su regla `min/max`.
- Grid de cards con los productos disponibles en esa sección.
- Toggle de selección: si `max_choices === 1`, comportamiento tipo radio (reemplaza); si `> 1`, permite múltiples hasta el máximo.
- Al seleccionar un producto, aparecen sub-paneles para:
  - **Variantes:** chips para elegir variante (o base).
  - **Extras:** chips toggle para modificadores del producto.
- Campo de notas opcionales (textarea).
- Footer con precio total (menú + suplementos + extras) y botón "Añadir al pedido" (deshabilitado hasta que todas las secciones cumplan `min_choices`).
- Validación en tiempo real: `blockReason()` muestra qué sección falta.

### 3.5 Página `comanda.page` — integración

**Archivos:**
- `frontend/src/app/features/orders/pages/comanda/comanda.page.ts`
- `frontend/src/app/features/orders/pages/comanda/comanda.page.html`
- `frontend/src/app/features/orders/pages/comanda/comanda.page.scss`

**Cambios clave:**

1. **Tabs de catálogo** (`Productos` / `Menús`) encima del área de catálogo. `setActiveCatalog()` alterna entre vistas.
2. **Grid de menús** cuando `activeCatalog === 'menus'`: cards azules (`menu-card`) con nombre y precio. Click → abre `menu-config-modal`.
3. **Modal integrado:** `<app-menu-config-modal>` recibe `[menu]`, `[products]` y emite confirm/close.
4. **Panel de comanda — render de líneas de menú:** en `existingLines`, si `line.menu_id` existe, se renderiza:
   - Nombre del menú (`menu_name`) en vez de `product_name`.
   - Subtítulo con los nombres de los productos seleccionados (`menu_selections.map(s => s.product_name).join(', ')`).
5. **Métodos añadidos:** `addMenu()`, `onMenuConfigConfirm()`, `onMenuConfigClose()`, `isMenuLine()`, `menuLineName()`, `menuLineSelections()`.

---

## 4. Flujo de usuario completo

1. El camarero entra a **Comanda** (página `comanda`).
2. En el área de catálogo, hace click en la pestaña **Menús**.
3. Ve un grid de cards azules con los menús activos.
4. Toca un menú → se abre el **menu-config-modal**.
5. Dentro del modal:
   - Elige un producto por cada sección (respetando min/max).
   - Opcionalmente selecciona variante y extras de cada producto.
   - Puede añadir notas.
   - El precio total se calcula en tiempo real.
6. Toca "Añadir al pedido" → se envía al backend (`POST /tpv/orders/menu-lines`).
7. La línea aparece en el panel derecho de la comanda como **"Menú del día"** con el listado de elecciones.
8. El camarero puede eliminar la línea (igual que cualquier otra línea confirmada).

---

## 5. Archivos clave (lista completa)

### Backend

```
backend/app/Menu/Domain/Entity/Menu.php
backend/app/Menu/Domain/Entity/MenuSection.php
backend/app/Menu/Domain/Entity/MenuItem.php
backend/app/Menu/Domain/Interfaces/MenuRepositoryInterface.php
backend/app/Menu/Infrastructure/Persistence/Repositories/EloquentMenuRepository.php
backend/app/Menu/Infrastructure/Persistence/Models/EloquentMenu.php
backend/app/Menu/Infrastructure/Persistence/Models/EloquentMenuSection.php
backend/app/Menu/Infrastructure/Persistence/Models/EloquentMenuItem.php
backend/app/Order/Application/AddMenuLineToOrder/AddMenuLineToOrder.php
backend/app/Order/Application/AddMenuLineToOrder/AddMenuLineToOrderCommand.php
backend/app/Order/Application/AddMenuLineToOrder/AddMenuLineToOrderResponse.php
backend/app/Order/Infrastructure/Entrypoint/Http/AddMenuLineController.php
backend/app/Order/Infrastructure/Entrypoint/Http/Requests/AddMenuLineToOrderRequest.php
backend/app/Order/Domain/Entity/OrderLine.php          # dddCreateMenuLine añadido
backend/database/migrations/2026_05_21_000100_create_menus_table.php
backend/database/migrations/2026_05_21_000200_create_menu_sections_table.php
backend/database/migrations/2026_05_21_000300_create_menu_items_table.php
backend/database/migrations/2026_05_21_001000_add_menu_fields_to_order_lines.php
backend/routes/...  # rutas de TPV (GET /tpv/menus, POST /tpv/orders/menu-lines)
```

### Frontend

```
frontend/src/app/features/cash/services/tpv.service.ts
frontend/src/app/features/orders/facades/comanda.facade.ts
frontend/src/app/features/orders/components/menu-config-modal/menu-config-modal.component.ts
frontend/src/app/features/orders/components/menu-config-modal/menu-config-modal.component.html
frontend/src/app/features/orders/components/menu-config-modal/menu-config-modal.component.scss
frontend/src/app/features/orders/pages/comanda/comanda.page.ts
frontend/src/app/features/orders/pages/comanda/comanda.page.html
frontend/src/app/features/orders/pages/comanda/comanda.page.scss
```

---

## 6. Estado del build

- `ng build` (frontend): ✅ exitoso (solo warnings de deprecación Sass `@import`).
- `php artisan migrate:fresh` + `db:seed --class=SaonaDemoSeeder`: ✅ ejecutado el 22/05/2026.

---

## 7. Notas para mejoras (el usuario ha visto "cosillas")

El usuario mencionó que ha visto algunas cosas a mejorar. Aquí van las áreas más probables y sugerencias de mejora:

### 7.1 UX del `menu-config-modal`

- **Scroll interno:** el modal tiene `max-height: 85vh` pero si el menú tiene muchas secciones el body no hace scroll bien. Verificar `.menu-modal__body { overflow-y: auto; }`.
- **Estados de carga:** no hay spinner mientras se añade el menú (`facade.addingMenuLine()` existe pero no se usa en la UI del modal ni en la page).
- **Deshacer selección:** actualmente se puede deseleccionar un producto haciendo click de nuevo. Esto está bien, pero quizás se quiere un indicador más claro.

### 7.2 Validaciones

- **Stock de productos dentro del menú:** el modal no consulta stock de los productos de las secciones. Podría deshabilitar productos sin stock.
- **Variantes desactivadas:** el modal filtra `v.active` pero no maneja stock de variantes.
- **Precio base del menú + suplementos:** verificar que el cálculo de `totalPrice()` en el modal coincida exactamente con el del backend.

### 7.3 Render de líneas de menú en la comanda

- **Detalle modal:** el modal de detalle de línea (`detailModalOpen`) no tiene lógica especial para menús. Si se abre el detalle de una línea de menú, mostrará `lineProductName()` que devolverá `'Producto'` porque `product_name` es null. Hay que mejorar `lineProductName()`, `lineVariantName()`, etc., para manejar menús.
- **Notas del menú:** las notas del menú (`result.notes`) se envían al backend pero no se renderizan en ningún lado (ni en el panel de comanda ni en el detalle modal).

### 7.4 Backend

- **Variante en `menu_items`:** en `MenuItem` del dominio se guarda `variant_id`, pero en el `AddMenuLineToOrder` el caso de uso solo usa `variant_id` para obtener el nombre de la variante vía Eloquent directamente (`EloquentProductVariant::query()`). Esto rompe un poco la capa de dominio. Idealmente debería pasar por un repositorio de variantes.
- **Stock:** el caso de uso decrementa stock de los productos elegidos pero no hace rollback automático si algo falla después (aunque está dentro de una transacción de BD, el `decreaseStock` modifica la entidad del dominio antes de guardarla). Verificar que el `productRepository->save()` se ejecute correctamente dentro de la transacción.
- **Disponibilidad temporal del menú:** el frontend no filtra menús por `validity_from/to`, `available_days`, ni franja horaria. El backend tampoco filtra en `listMenus` (solo `active=true&archived=false`).

### 7.5 Seeder

- `SaonaDemoSeeder` crea familias, productos, zonas, mesas, impuestos, variantes, modificadores y usuarios. **No crea menús de demo todavía.** Si quieres probar el flujo completo sin crear menús a mano en BD, añade menús de demo al seeder.

---

## 8. Comandos útiles

```bash
# Desde la raíz del repo:

# Levantar todo (API:8000, frontend:4200, DB, DbGate:9051)
make start

# Migraciones
make db-migrate

# Refrescar DB + seed demo
make db-migrate      # o migrate:fresh si hace falta
# luego dentro del contenedor api:
docker compose exec api php artisan db:seed --class=SaonaDemoSeeder

# Build frontend (desde raíz, usa ruta relativa al binario)
frontend/node_modules/.bin/ng build --configuration=development

# Tests backend
make test

# Logs backend
make logs-backend
```

---

## 9. Convenciones del proyecto (recordatorio)

- **Backend:** DDD + Hexagonal. Entidades creadas con `Entity::dddCreate(...)`. VOs con `VO::create(...)`. No usar `new` desde Application/Infrastructure.
- **Frontend:** Angular standalone + Ionic. Signals para estado reactivo. Pipes `FilterByPipe` y `SearchPipe` para filtrado en templates.
- **Routing de TPV:** todos los endpoints empiezan con `/api/tpv/...`.
