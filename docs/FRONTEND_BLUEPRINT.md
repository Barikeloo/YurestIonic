# Frontend Blueprint (Prototipo TPV -> Angular + Ionic)

## 1) Objetivo
Este documento define el mapa funcional y tecnico del prototipo en `DisenoTPV.html` para migrarlo por paginas y componentes a Angular + Ionic.

Meta: implementar el front de forma incremental, manteniendo el look and feel del prototipo y conectando gradualmente con la API real.

## 2) Inventario de pantallas del prototipo

1. `screen-home`
- Landing inicial.
- Branding, mensaje principal, CTA de entrada/registro.

2. `screen-login`
- Login por PIN (usuario rapido).
- Login por email/password.
- Modal de crear cuenta.

3. `screen-mesas`
- Vista principal de TPV: zonas + mesas + panel de pedido.
- Seleccion de mesa y detalle de cuenta.

4. `screen-comanda`
- Catalogo por familias.
- Busqueda de productos.
- Construccion de comanda (lineas + total).

5. `screen-pedidos`
- Listado historico con filtros.
- KPI resumen.
- Panel detalle del pedido seleccionado.

6. `screen-autoservicio`
- Listado de pedidos QR/App.
- Estado de preparacion.
- Acciones operativas (aceptar, listo, entregar, cancelar).

7. `screen-gestion`
- Backoffice visual.
- Lista de restaurantes.
- CRUD por entidad: restaurante, usuarios, familias, productos, zonas, mesas, impuestos.

8. `screen-caja`
- Apertura de caja con fondo inicial y observaciones.

## 3) Modulos funcionales JS detectados

### Navegacion y UI base
- `showScreen`, `navigateTo`, `syncActiveNav`
- Modales: `showModal`, `closeModal`, `closeModalOutside`

### Login y acceso
- `pinKey`, `pinDel`, `updateDots`, `pinEnter`, `selectUser`

### TPV Mesas y Comanda
- Mesas: `renderTables`, `selectTable`, `showOrderPanel`
- Productos/comanda: `renderProducts`, `setFamily`, `addToComanda`, `removeFromComanda`, `renderComanda`, `sendComanda`

### Pedidos
- `renderPedidosScreen` y helpers de filtro/lista/detalle

### Autoservicio
- `renderAutoservicioScreen` y helpers de filtro/lista/detalle

### Gestion (backoffice)
- Restaurantes: selector + resumen
- CRUD entidades: `saveManagementEntity`, `deleteSelectedManagementItem`, etc.
- Zonas/Mesas anidado: `saveManagementTable`, `deleteSelectedManagementTable`

## 4) Mapa de entidades (alineado con DATA_MODEL)

Backoffice principal:
- Restaurants
- Users
- Families
- Taxes
- Products
- Zones
- Tables

Operacion TPV:
- Orders
- OrderLines
- Sales
- SaleLines

## 5) Mapa API actual (backend/routes/api.php)

Autenticacion:
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`

Backoffice tenant:
- Families CRUD + activate/deactivate
- Taxes CRUD
- Zones CRUD
- Tables CRUD
- Products CRUD + activate/deactivate

Operacion:
- Orders CRUD + lines
- Sales CRUD + lines

Admin:
- `GET /admin/restaurants`
- `POST /admin/context/restaurant`
- Restaurant CRUD admin

## 6) Arquitectura Angular + Ionic recomendada

## 6.1 Paginas
Crear/usar paginas en `frontend/src/app/pages/core/`:
- `home`
- `login`
- `mesas`
- `comanda`
- `pedidos`
- `autoservicio`
- `gestion`
- `caja`

## 6.2 Componentes reutilizables
En `frontend/src/app/components/`:
- `topbar`
- `entity-list-panel`
- `entity-form-panel`
- `kpi-card`
- `status-pill`
- `table-card`
- `order-lines-list`
- `product-card`
- `empty-state`

## 6.3 Servicios por dominio
En `frontend/src/app/services/`:
- `auth.service.ts`
- `restaurant.service.ts`
- `user.service.ts`
- `family.service.ts`
- `tax.service.ts`
- `product.service.ts`
- `zone.service.ts`
- `table.service.ts`
- `order.service.ts`
- `sale.service.ts`

## 6.4 Estado UI (recomendado)
Empezar con estado local por pagina y evolucionar a store por feature:
- filtros
- seleccion activa
- draft de formularios
- loading/saving/error

## 7) Plan de implementacion por fases

Fase 0 - Base shell
- Layout global, topbar, variables visuales, helpers de estilos.

Fase 1 - Home + Login
- Pasar diseno a componentes Ionic.
- Navegacion real con router.
- Auth mock primero, API despues.

Fase 2 - Mesas + Comanda
- Render por zonas/mesas.
- Flujo de apertura/edicion de pedido.
- Comanda con lineas y totales.

Fase 3 - Pedidos + Autoservicio
- Filtros, listado y panel detalle.
- Acciones de estado.

Fase 4 - Gestion (Backoffice)
- Lista de restaurantes.
- CRUD entidades (incluye zonas y mesas anidado).
- Validaciones de formulario.

Fase 5 - Caja + hardening
- Apertura/cierre de caja.
- Manejo de errores, loading states, edge cases.

## 8) Regla de migracion pagina a pagina

Para cada pagina:
1. Extraer estructura visual del prototipo.
2. Crear pagina Angular standalone + SCSS.
3. Mover piezas repetidas a componentes.
4. Implementar estado local y eventos UI.
5. Conectar API real.
6. Probar flujo completo.

## 9) Criterios de terminado por pagina

Checklist de done:
- Layout responsive desktop/tablet/mobile
- Estados vacio/cargando/error
- Formularios validados
- Acciones principales funcionando
- Integracion con servicios
- Sin console errors

## 10) Nota de implementacion actual

Estado actual del proyecto:
- Prototipo HTML/JS muy completo (incluyendo Gestion con CRUD visual y zonas/mesas anidado).
- En Angular existe base minima y pagina `home` creada en `frontend/src/app/pages/core/home/`.
- Siguiente paso recomendado: migrar `screen-home` + `screen-login` a Angular/Ionic real.
