# Plan Shard Key Real (Multi-tenant por restaurante)

## Resumen ejecutivo

Si el requisito es shard key, el modelo debe pasar de tenant por convencion a tenant forzado por base de datos.

Objetivo final:

- Cada registro pertenece a un restaurante.
- Cada relacion hijo-padre valida que ambos pertenecen al mismo restaurante.
- Ninguna consulta ni insercion puede mezclar tenants por error.

---

## Estado actual vs estado objetivo

| Tema | Estado actual | Estado objetivo (shard key real) |
|---|---|---|
| restaurant_id en tablas | Presente en muchas tablas | Presente en todas las tablas de negocio |
| Integridad entre tablas | FKs simples en varios puntos | FKs compuestas con restaurant_id |
| Aislamiento tenant | Principalmente por codigo | Garantizado por la BD |
| Riesgo de cruce entre restaurantes | Medio si falla un filtro | Muy bajo (la BD lo bloquea) |

---

## Cambios en base de datos

### 1) Base estructural

- Mantener restaurant_id en todas las tablas de dominio.
- Anadir indices compuestos en tablas padre para soportar referencias por restaurante.

Tablas padre a indexar por (restaurant_id, id):

1. families
2. taxes
3. products
4. zones
5. tables
6. users
7. orders
8. order_lines
9. sales
10. sales_lines

### 2) Relaciones a migrar a FK compuesta

1. products -> families: (restaurant_id, family_id) -> families (restaurant_id, id)
2. products -> taxes: (restaurant_id, tax_id) -> taxes (restaurant_id, id)
3. tables -> zones: (restaurant_id, zone_id) -> zones (restaurant_id, id)
4. orders -> tables: (restaurant_id, table_id) -> tables (restaurant_id, id)
5. orders -> users opened_by: (restaurant_id, opened_by_user_id) -> users (restaurant_id, id)
6. orders -> users closed_by: (restaurant_id, closed_by_user_id) -> users (restaurant_id, id)
7. order_lines -> orders: (restaurant_id, order_id) -> orders (restaurant_id, id)
8. order_lines -> products: (restaurant_id, product_id) -> products (restaurant_id, id)
9. order_lines -> users: (restaurant_id, user_id) -> users (restaurant_id, id)
10. sales -> orders: (restaurant_id, order_id) -> orders (restaurant_id, id)
11. sales_lines -> sales: (restaurant_id, sale_id) -> sales (restaurant_id, id)
12. sales_lines -> order_lines: (restaurant_id, order_line_id) -> order_lines (restaurant_id, id)
13. sales_lines -> users: (restaurant_id, user_id) -> users (restaurant_id, id)

---

## Faltantes funcionales clave

1. Anadir table_id en sales y relacionarlo con shard key:
	(restaurant_id, table_id) -> tables (restaurant_id, id)
2. Anadir product_id en sales_lines y relacionarlo con shard key:
	(restaurant_id, product_id) -> products (restaurant_id, id)
3. En sales, separar opened_by_user_id y closed_by_user_id para trazabilidad completa:
	ambos con FK compuesta usando restaurant_id

---

## Eloquent: lo que hay que completar

1. Declarar belongsTo y hasMany en todos los modelos principales.
2. Asegurar filtro por restaurant_id en consultas de negocio.
3. Aplicar scope global tenant para usuarios no admin.
4. Para admin: sin scope fijo, con seleccion de restaurante de contexto.

---

## Admin global (CIF/NIF)

Si, hay que implementarlo.

Requisitos:

1. Endpoint admin para listar restaurantes.
2. Respuesta minima: uuid, name, legal_name, tax_id.
3. Acceso solo rol admin.
4. Si admin entra sin restaurant_id, debe elegir restaurante antes de operar en modulos tenant.

---

## Plan de ejecucion recomendado (manana)

### Fase 1 - Migraciones estructurales

- [ ] Anadir columnas faltantes en sales y sales_lines.
- [ ] Crear indices compuestos en tablas padre.
- [ ] Sustituir FKs simples por FKs compuestas shard key.

### Fase 2 - Aplicacion

- [ ] Actualizar repositorios/casos de uso para incluir restaurant_id en busquedas.
- [ ] Completar relaciones Eloquent.
- [ ] Crear endpoint admin para listado de restaurantes con CIF/NIF.

### Fase 3 - Validacion

- [ ] Test: no permite relacionar registros de distintos restaurantes.
- [ ] Test: no permite leer/modificar datos de otro tenant en endpoints normales.
- [ ] Test: admin si puede listar todos los restaurantes.

---

## Definicion de done

Este plan se considera completado cuando:

1. Todas las relaciones criticas de negocio usan shard key compuesta.
2. Las consultas tenant estan blindadas por codigo y por constraints de BD.
3. El flujo admin global de restaurantes (incluyendo CIF/NIF) esta operativo y testeado.
