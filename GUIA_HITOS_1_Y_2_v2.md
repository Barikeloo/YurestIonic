# Guía paso a paso — Hito 1 y Hito 2 (sin código)

Solo archivos, ubicaciones y orden. Cuando necesites el código de algo concreto, pídemelo.

---

## Cómo leer esta guía

- Cada paso indica **qué archivo crear**, **dónde va** y **qué hace**
- El orden es importante: cada archivo depende de los anteriores
- Vamos **dominio a dominio** (primero todo lo de User, luego todo lo de Family, etc.)
- Las migraciones ya las tienes hechas, así que empezamos desde los Shared VOs

---

## Recordatorio rápido de capas

| Capa | Qué va aquí | ¿Puede importar Laravel? |
|------|-------------|--------------------------|
| `Domain/Entity/` | Entidad pura con lógica de negocio | NO |
| `Domain/ValueObject/` | VOs que validan datos | NO |
| `Domain/Interfaces/` | Contratos (repos, servicios) | NO |
| `Application/<CasoDeUso>/` | Orquestador + Response DTO | NO |
| `Infrastructure/Persistence/Models/` | Modelo Eloquent | SÍ |
| `Infrastructure/Persistence/Repositories/` | Implementación del repo | SÍ |
| `Infrastructure/Services/` | Implementaciones de servicios | SÍ |
| `Infrastructure/Entrypoint/Http/` | Controladores | SÍ |

---

# FASE 1 — Value Objects compartidos

Estos los usan TODOS los dominios. Créalos primero.

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 1 | `Uuid.php` | `app/Shared/Domain/ValueObject/` | Genera y valida UUIDs (usa ramsey/uuid) |
| 2 | `DomainDateTime.php` | `app/Shared/Domain/ValueObject/` | Envuelve fechas con DateTimeImmutable |
| 3 | `Email.php` | `app/Shared/Domain/ValueObject/` | Valida formato de email |

> Todos siguen el patrón: constructor privado + método estático `create()`. Uuid además tiene `generate()` y DomainDateTime tiene `now()`.

---

# FASE 2 — Seeders

Las migraciones ya las tienes. Ahora crea los seeders para tener datos de prueba.

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 4 | `RestaurantSeeder.php` | `database/seeders/` | Crea 1 restaurante de prueba |
| 5 | `UserSeeder.php` | `database/seeders/` | Crea 3 usuarios (admin, 2 operadores) |
| 6 | `FamilySeeder.php` | `database/seeders/` | Crea 5 familias (Bebidas, Entrantes, Carnes, Pescados, Postres) |
| 7 | `TaxSeeder.php` | `database/seeders/` | Crea 3 impuestos (IVA 21%, 10%, 4%) |
| 8 | `ProductSeeder.php` | `database/seeders/` | Crea ~8 productos repartidos entre familias e impuestos |
| 9 | `ZoneSeeder.php` | `database/seeders/` | Crea 3 zonas (Terraza, Salón, Barra) |
| 10 | `TableSeeder.php` | `database/seeders/` | Crea mesas por zona (8, 10, 4) |
| 11 | `DatabaseSeeder.php` | `database/seeders/` | **MODIFICAR** — llama a todos los seeders en orden |

> **Orden en DatabaseSeeder:** Restaurant → User → Family → Tax → Product → Zone → Table (respeta las FK).

**Ejecutar:** `php artisan migrate` + `php artisan db:seed`. Comprueba en DbGate que todo está.

---

# FASE 3 — Dominio USER

El más complejo porque tiene un servicio extra (password hasher). Sirve como referencia para el resto.

## 3.1 — Value Objects del dominio

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 12 | `UserName.php` | `app/User/Domain/ValueObject/` | Valida nombre no vacío, max 255 chars |
| 13 | `PasswordHash.php` | `app/User/Domain/ValueObject/` | Envuelve el hash (NO la contraseña en claro) |
| 14 | `UserRole.php` | `app/User/Domain/ValueObject/` | Valida roles permitidos: admin, supervisor, operator |
| 15 | `Pin.php` | `app/User/Domain/ValueObject/` | Valida PIN nullable de 4-6 dígitos |

## 3.2 — Entidad

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 16 | `User.php` | `app/User/Domain/Entity/` | Entidad con `dddCreate()` + `fromPrimitives()` + métodos update |

> `dddCreate()` genera UUID y fechas. `fromPrimitives()` reconstruye desde la BD. Métodos como `updateName()`, `updateEmail()`, `updateRole()`, `updatePassword()`.

## 3.3 — Interfaces (contratos)

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 17 | `UserRepositoryInterface.php` | `app/User/Domain/Interfaces/` | Contrato: save, findByUuid, findByEmail, findAll, update, delete |
| 18 | `PasswordHasherInterface.php` | `app/User/Domain/Interfaces/` | Contrato: hash(plain) → string, verify(plain, hashed) → bool |

## 3.4 — Infrastructure: Modelo Eloquent

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 19 | `EloquentUser.php` | `app/User/Infrastructure/Persistence/Models/` | Modelo Eloquent con SoftDeletes, tabla `users` |

## 3.5 — Infrastructure: Repositorio concreto

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 20 | `EloquentUserRepository.php` | `app/User/Infrastructure/Persistence/Repositories/` | Implementa `UserRepositoryInterface`. Traduce entidad ↔ Eloquent |

> Este archivo tiene un método privado `toDomainEntity()` que convierte el modelo Eloquent en la entidad de dominio, y otro `resolveRestaurantId()` para pasar de UUID a id interno.

## 3.6 — Infrastructure: Servicio (hasher)

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 21 | `LaravelPasswordHasher.php` | `app/User/Infrastructure/Services/` | Implementa `PasswordHasherInterface` usando `Hash::make()` y `Hash::check()` |

## 3.7 — Casos de uso + Responses

Cada caso de uso es una carpeta con 2 archivos (el caso de uso y su Response). Delete no necesita Response.

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 22 | `CreateUser.php` | `app/User/Application/CreateUser/` | Crea VOs, hashea password, crea entidad, persiste, devuelve Response |
| 23 | `CreateUserResponse.php` | `app/User/Application/CreateUser/` | DTO con toArray() — expone uuid, name, email, role, image_src, fechas |
| 24 | `ListUsers.php` | `app/User/Application/ListUsers/` | Llama findAll del repo, devuelve lista de Responses |
| 25 | `ListUsersResponse.php` | `app/User/Application/ListUsers/` | DTO para la lista |
| 26 | `GetUser.php` | `app/User/Application/GetUser/` | Busca por UUID, devuelve Response o lanza excepción |
| 27 | `GetUserResponse.php` | `app/User/Application/GetUser/` | DTO individual |
| 28 | `UpdateUser.php` | `app/User/Application/UpdateUser/` | Busca usuario, actualiza campos que vengan, persiste |
| 29 | `UpdateUserResponse.php` | `app/User/Application/UpdateUser/` | DTO con datos actualizados |
| 30 | `DeleteUser.php` | `app/User/Application/DeleteUser/` | Llama delete del repo (soft delete). Sin Response, devuelve void |
| 31 | `LoginUser.php` | `app/User/Application/LoginUser/` | Busca por email, verifica password con hasher, genera token |
| 32 | `LoginUserResponse.php` | `app/User/Application/LoginUser/` | DTO con uuid, name, email, role, token |

## 3.8 — Controladores HTTP

Un controlador = una acción = un `__invoke()`.

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 33 | `CreateUserController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Valida request → llama CreateUser → devuelve 201 |
| 34 | `ListUsersController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Llama ListUsers → devuelve 200 |
| 35 | `GetUserController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Recibe {uuid} → llama GetUser → devuelve 200 |
| 36 | `UpdateUserController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Valida request → llama UpdateUser → devuelve 200 |
| 37 | `DeleteUserController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Recibe {uuid} → llama DeleteUser → devuelve 204 |
| 38 | `LoginController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Valida email+password → llama LoginUser → devuelve 200 con token |
| 39 | `LogoutController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Invalida token/sesión → devuelve 204 |
| 40 | `MeController.php` | `app/User/Infrastructure/Entrypoint/Http/` | Devuelve usuario autenticado → 200 |

---

# FASE 4 — Dominio FAMILY

Más sencillo que User (no tiene servicios). Úsalo como plantilla para Tax y Zone.

## 4.1 — Value Objects

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 41 | `FamilyName.php` | `app/Family/Domain/ValueObject/` | Valida nombre no vacío, max 255 |

## 4.2 — Entidad

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 42 | `Family.php` | `app/Family/Domain/Entity/` | Entidad con dddCreate, fromPrimitives, toggleActive(), updateName() |

## 4.3 — Interfaz del repositorio

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 43 | `FamilyRepositoryInterface.php` | `app/Family/Domain/Interfaces/` | save, findByUuid, findAll (con filtro active opcional), update, delete |

## 4.4 — Modelo Eloquent

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 44 | `EloquentFamily.php` | `app/Family/Infrastructure/Persistence/Models/` | Modelo con SoftDeletes, tabla `families` |

## 4.5 — Repositorio concreto

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 45 | `EloquentFamilyRepository.php` | `app/Family/Infrastructure/Persistence/Repositories/` | Implementa interfaz, traduce entidad ↔ Eloquent |

## 4.6 — Casos de uso + Responses

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 46 | `CreateFamily.php` | `app/Family/Application/CreateFamily/` | Crea VOs, entidad, persiste |
| 47 | `CreateFamilyResponse.php` | `app/Family/Application/CreateFamily/` | DTO |
| 48 | `ListFamilies.php` | `app/Family/Application/ListFamilies/` | Lista con filtro opcional por active |
| 49 | `ListFamiliesResponse.php` | `app/Family/Application/ListFamilies/` | DTO lista |
| 50 | `GetFamily.php` | `app/Family/Application/GetFamily/` | Busca por UUID |
| 51 | `GetFamilyResponse.php` | `app/Family/Application/GetFamily/` | DTO |
| 52 | `UpdateFamily.php` | `app/Family/Application/UpdateFamily/` | Actualiza nombre y/o active |
| 53 | `UpdateFamilyResponse.php` | `app/Family/Application/UpdateFamily/` | DTO |
| 54 | `ToggleFamilyActive.php` | `app/Family/Application/ToggleFamilyActive/` | Busca, hace toggleActive(), persiste |
| 55 | `ToggleFamilyActiveResponse.php` | `app/Family/Application/ToggleFamilyActive/` | DTO con uuid y nuevo estado active |
| 56 | `DeleteFamily.php` | `app/Family/Application/DeleteFamily/` | Soft delete, sin Response |

## 4.7 — Controladores

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 57 | `CreateFamilyController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | POST /families → 201 |
| 58 | `ListFamiliesController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | GET /families → 200 |
| 59 | `GetFamilyController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | GET /families/{uuid} → 200 |
| 60 | `UpdateFamilyController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | PUT /families/{uuid} → 200 |
| 61 | `ToggleFamilyActiveController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | PATCH /families/{uuid}/toggle-active → 200 |
| 62 | `DeleteFamilyController.php` | `app/Family/Infrastructure/Entrypoint/Http/` | DELETE /families/{uuid} → 204 |

---

# FASE 5 — Dominio TAX

Casi idéntico a Family. La única diferencia es el VO `TaxPercentage`.

## 5.1 — Value Objects

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 63 | `TaxName.php` | `app/Tax/Domain/ValueObject/` | Valida nombre no vacío |
| 64 | `TaxPercentage.php` | `app/Tax/Domain/ValueObject/` | Valida entero entre 0 y 100 |

## 5.2 — Entidad

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 65 | `Tax.php` | `app/Tax/Domain/Entity/` | dddCreate, fromPrimitives, updateName, updatePercentage |

## 5.3 — Interfaz

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 66 | `TaxRepositoryInterface.php` | `app/Tax/Domain/Interfaces/` | save, findByUuid, findAll, update, delete |

## 5.4 — Modelo Eloquent

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 67 | `EloquentTax.php` | `app/Tax/Infrastructure/Persistence/Models/` | Modelo, tabla `taxes` |

## 5.5 — Repositorio

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 68 | `EloquentTaxRepository.php` | `app/Tax/Infrastructure/Persistence/Repositories/` | Implementa interfaz |

## 5.6 — Casos de uso + Responses

| # | Archivo | Ubicación |
|---|---------|-----------|
| 69 | `CreateTax.php` | `app/Tax/Application/CreateTax/` |
| 70 | `CreateTaxResponse.php` | `app/Tax/Application/CreateTax/` |
| 71 | `ListTaxes.php` | `app/Tax/Application/ListTaxes/` |
| 72 | `ListTaxesResponse.php` | `app/Tax/Application/ListTaxes/` |
| 73 | `GetTax.php` | `app/Tax/Application/GetTax/` |
| 74 | `GetTaxResponse.php` | `app/Tax/Application/GetTax/` |
| 75 | `UpdateTax.php` | `app/Tax/Application/UpdateTax/` |
| 76 | `UpdateTaxResponse.php` | `app/Tax/Application/UpdateTax/` |
| 77 | `DeleteTax.php` | `app/Tax/Application/DeleteTax/` |

## 5.7 — Controladores

| # | Archivo | Ubicación |
|---|---------|-----------|
| 78 | `CreateTaxController.php` | `app/Tax/Infrastructure/Entrypoint/Http/` |
| 79 | `ListTaxesController.php` | `app/Tax/Infrastructure/Entrypoint/Http/` |
| 80 | `GetTaxController.php` | `app/Tax/Infrastructure/Entrypoint/Http/` |
| 81 | `UpdateTaxController.php` | `app/Tax/Infrastructure/Entrypoint/Http/` |
| 82 | `DeleteTaxController.php` | `app/Tax/Infrastructure/Entrypoint/Http/` |

---

# FASE 6 — Dominio PRODUCT

Más complejo: tiene relación con Family y Tax. Las respuestas incluyen familia y tax anidados.

## 6.1 — Value Objects

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 83 | `ProductName.php` | `app/Product/Domain/ValueObject/` | Valida nombre no vacío |
| 84 | `Price.php` | `app/Product/Domain/ValueObject/` | Valida entero >= 0 (céntimos) |
| 85 | `Stock.php` | `app/Product/Domain/ValueObject/` | Valida entero >= 0 |

## 6.2 — Entidad

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 86 | `Product.php` | `app/Product/Domain/Entity/` | Guarda familyId y taxId como Uuid. dddCreate, fromPrimitives, toggleActive, updates |

> La entidad almacena `familyId` y `taxId` como `Uuid`. El repositorio resuelve los datos anidados para el Response.

## 6.3 — Interfaz

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 87 | `ProductRepositoryInterface.php` | `app/Product/Domain/Interfaces/` | save, findByUuid, findAll (filtro active y family_uuid), update, delete |

## 6.4 — Modelo Eloquent

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 88 | `EloquentProduct.php` | `app/Product/Infrastructure/Persistence/Models/` | Modelo con relaciones belongsTo family y tax |

## 6.5 — Repositorio

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 89 | `EloquentProductRepository.php` | `app/Product/Infrastructure/Persistence/Repositories/` | Implementa interfaz. Carga relaciones family/tax para respuestas anidadas |

## 6.6 — Casos de uso + Responses

| # | Archivo | Ubicación | Nota |
|---|---------|-----------|------|
| 90 | `CreateProduct.php` | `app/Product/Application/CreateProduct/` | Recibe family_uuid y tax_uuid |
| 91 | `CreateProductResponse.php` | `app/Product/Application/CreateProduct/` | Incluye family y tax anidados |
| 92 | `ListProducts.php` | `app/Product/Application/ListProducts/` | Filtros: active, family_uuid |
| 93 | `ListProductsResponse.php` | `app/Product/Application/ListProducts/` | |
| 94 | `GetProduct.php` | `app/Product/Application/GetProduct/` | |
| 95 | `GetProductResponse.php` | `app/Product/Application/GetProduct/` | Con family y tax anidados |
| 96 | `UpdateProduct.php` | `app/Product/Application/UpdateProduct/` | |
| 97 | `UpdateProductResponse.php` | `app/Product/Application/UpdateProduct/` | |
| 98 | `ToggleProductActive.php` | `app/Product/Application/ToggleProductActive/` | |
| 99 | `ToggleProductActiveResponse.php` | `app/Product/Application/ToggleProductActive/` | |
| 100 | `DeleteProduct.php` | `app/Product/Application/DeleteProduct/` | |

## 6.7 — Controladores

| # | Archivo | Ubicación |
|---|---------|-----------|
| 101 | `CreateProductController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |
| 102 | `ListProductsController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |
| 103 | `GetProductController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |
| 104 | `UpdateProductController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |
| 105 | `ToggleProductActiveController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |
| 106 | `DeleteProductController.php` | `app/Product/Infrastructure/Entrypoint/Http/` |

---

# FASE 7 — Dominio ZONE

Sencillo como Family. La particularidad es que `ListZones` devuelve `tables_count` y `GetZone` devuelve las mesas anidadas.

## 7.1 — Value Objects

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 107 | `ZoneName.php` | `app/Zone/Domain/ValueObject/` | Valida nombre no vacío |

## 7.2 — Entidad

| # | Archivo | Ubicación |
|---|---------|-----------|
| 108 | `Zone.php` | `app/Zone/Domain/Entity/` |

## 7.3 — Interfaz

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 109 | `ZoneRepositoryInterface.php` | `app/Zone/Domain/Interfaces/` | Incluye findByUuidWithTables para devolver mesas anidadas |

## 7.4 — Modelo Eloquent

| # | Archivo | Ubicación |
|---|---------|-----------|
| 110 | `EloquentZone.php` | `app/Zone/Infrastructure/Persistence/Models/` |

## 7.5 — Repositorio

| # | Archivo | Ubicación |
|---|---------|-----------|
| 111 | `EloquentZoneRepository.php` | `app/Zone/Infrastructure/Persistence/Repositories/` |

## 7.6 — Casos de uso + Responses

| # | Archivo | Ubicación | Nota |
|---|---------|-----------|------|
| 112 | `CreateZone.php` | `app/Zone/Application/CreateZone/` | |
| 113 | `CreateZoneResponse.php` | `app/Zone/Application/CreateZone/` | |
| 114 | `ListZones.php` | `app/Zone/Application/ListZones/` | Devuelve tables_count por zona |
| 115 | `ListZonesResponse.php` | `app/Zone/Application/ListZones/` | |
| 116 | `GetZone.php` | `app/Zone/Application/GetZone/` | Devuelve mesas anidadas |
| 117 | `GetZoneResponse.php` | `app/Zone/Application/GetZone/` | Con array de tables |
| 118 | `UpdateZone.php` | `app/Zone/Application/UpdateZone/` | |
| 119 | `UpdateZoneResponse.php` | `app/Zone/Application/UpdateZone/` | |
| 120 | `DeleteZone.php` | `app/Zone/Application/DeleteZone/` | |

## 7.7 — Controladores

| # | Archivo | Ubicación |
|---|---------|-----------|
| 121 | `CreateZoneController.php` | `app/Zone/Infrastructure/Entrypoint/Http/` |
| 122 | `ListZonesController.php` | `app/Zone/Infrastructure/Entrypoint/Http/` |
| 123 | `GetZoneController.php` | `app/Zone/Infrastructure/Entrypoint/Http/` |
| 124 | `UpdateZoneController.php` | `app/Zone/Infrastructure/Entrypoint/Http/` |
| 125 | `DeleteZoneController.php` | `app/Zone/Infrastructure/Entrypoint/Http/` |

---

# FASE 8 — Dominio TABLE

Depende de Zone. La particularidad es el campo calculado `has_open_sale`.

## 8.1 — Value Objects

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 126 | `TableName.php` | `app/Table/Domain/ValueObject/` | Valida nombre no vacío |

## 8.2 — Entidad

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 127 | `Table.php` | `app/Table/Domain/Entity/` | Guarda zoneId como Uuid |

## 8.3 — Interfaz

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 128 | `TableRepositoryInterface.php` | `app/Table/Domain/Interfaces/` | findAll con filtro zone_uuid. Incluye has_open_sale |

## 8.4 — Modelo Eloquent

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 129 | `EloquentTable.php` | `app/Table/Infrastructure/Persistence/Models/` | Relación belongsTo zone |

## 8.5 — Repositorio

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 130 | `EloquentTableRepository.php` | `app/Table/Infrastructure/Persistence/Repositories/` | Calcula has_open_sale consultando orders con status=open |

## 8.6 — Casos de uso + Responses

| # | Archivo | Ubicación | Nota |
|---|---------|-----------|------|
| 131 | `CreateTable.php` | `app/Table/Application/CreateTable/` | Recibe zone_uuid |
| 132 | `CreateTableResponse.php` | `app/Table/Application/CreateTable/` | |
| 133 | `ListTables.php` | `app/Table/Application/ListTables/` | Filtro zone_uuid. Incluye has_open_sale |
| 134 | `ListTablesResponse.php` | `app/Table/Application/ListTables/` | Con zone anidada |
| 135 | `GetTable.php` | `app/Table/Application/GetTable/` | |
| 136 | `GetTableResponse.php` | `app/Table/Application/GetTable/` | Con zone anidada |
| 137 | `UpdateTable.php` | `app/Table/Application/UpdateTable/` | |
| 138 | `UpdateTableResponse.php` | `app/Table/Application/UpdateTable/` | |
| 139 | `DeleteTable.php` | `app/Table/Application/DeleteTable/` | |

## 8.7 — Controladores

| # | Archivo | Ubicación |
|---|---------|-----------|
| 140 | `CreateTableController.php` | `app/Table/Infrastructure/Entrypoint/Http/` |
| 141 | `ListTablesController.php` | `app/Table/Infrastructure/Entrypoint/Http/` |
| 142 | `GetTableController.php` | `app/Table/Infrastructure/Entrypoint/Http/` |
| 143 | `UpdateTableController.php` | `app/Table/Infrastructure/Entrypoint/Http/` |
| 144 | `DeleteTableController.php` | `app/Table/Infrastructure/Entrypoint/Http/` |

---

# FASE 9 — Conectar todo

Estos dos archivos conectan todos los dominios con Laravel.

## 9.1 — Registrar bindings

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 145 | `AppServiceProvider.php` | `app/Providers/` | **MODIFICAR** — añadir un `$this->app->bind()` por cada par interfaz → implementación |

**Bindings a registrar:**

| Interfaz | Implementación |
|----------|----------------|
| `UserRepositoryInterface` | `EloquentUserRepository` |
| `PasswordHasherInterface` | `LaravelPasswordHasher` |
| `FamilyRepositoryInterface` | `EloquentFamilyRepository` |
| `TaxRepositoryInterface` | `EloquentTaxRepository` |
| `ProductRepositoryInterface` | `EloquentProductRepository` |
| `ZoneRepositoryInterface` | `EloquentZoneRepository` |
| `TableRepositoryInterface` | `EloquentTableRepository` |

## 9.2 — Registrar rutas

| # | Archivo | Ubicación | Qué hace |
|---|---------|-----------|----------|
| 146 | `api.php` | `routes/` | **MODIFICAR** — registrar todas las rutas apuntando a los controladores |

**Rutas a registrar:**

| Método | Ruta | Controlador |
|--------|------|-------------|
| POST | `/auth/login` | `LoginController` |
| POST | `/auth/logout` | `LogoutController` |
| GET | `/auth/me` | `MeController` |
| GET | `/users` | `ListUsersController` |
| POST | `/users` | `CreateUserController` |
| GET | `/users/{uuid}` | `GetUserController` |
| PUT | `/users/{uuid}` | `UpdateUserController` |
| DELETE | `/users/{uuid}` | `DeleteUserController` |
| GET | `/families` | `ListFamiliesController` |
| POST | `/families` | `CreateFamilyController` |
| GET | `/families/{uuid}` | `GetFamilyController` |
| PUT | `/families/{uuid}` | `UpdateFamilyController` |
| PATCH | `/families/{uuid}/toggle-active` | `ToggleFamilyActiveController` |
| DELETE | `/families/{uuid}` | `DeleteFamilyController` |
| GET | `/taxes` | `ListTaxesController` |
| POST | `/taxes` | `CreateTaxController` |
| GET | `/taxes/{uuid}` | `GetTaxController` |
| PUT | `/taxes/{uuid}` | `UpdateTaxController` |
| DELETE | `/taxes/{uuid}` | `DeleteTaxController` |
| GET | `/products` | `ListProductsController` |
| POST | `/products` | `CreateProductController` |
| GET | `/products/{uuid}` | `GetProductController` |
| PUT | `/products/{uuid}` | `UpdateProductController` |
| PATCH | `/products/{uuid}/toggle-active` | `ToggleProductActiveController` |
| DELETE | `/products/{uuid}` | `DeleteProductController` |
| GET | `/zones` | `ListZonesController` |
| POST | `/zones` | `CreateZoneController` |
| GET | `/zones/{uuid}` | `GetZoneController` |
| PUT | `/zones/{uuid}` | `UpdateZoneController` |
| DELETE | `/zones/{uuid}` | `DeleteZoneController` |
| GET | `/tables` | `ListTablesController` |
| POST | `/tables` | `CreateTableController` |
| GET | `/tables/{uuid}` | `GetTableController` |
| PUT | `/tables/{uuid}` | `UpdateTableController` |
| DELETE | `/tables/{uuid}` | `DeleteTableController` |

---

# Resumen total

| Fase | Dominio | Archivos | Acumulado |
|------|---------|----------|-----------|
| 1 | Shared VOs | 3 | 3 |
| 2 | Seeders | 8 | 11 |
| 3 | User | 29 | 40 |
| 4 | Family | 22 | 62 |
| 5 | Tax | 20 | 82 |
| 6 | Product | 24 | 106 |
| 7 | Zone | 19 | 125 |
| 8 | Table | 19 | 144 |
| 9 | Provider + Rutas | 2 (modificar) | 146 |

---

# Orden de trabajo dentro de cada dominio

Siempre el mismo:

```
1. VOs  →  2. Entidad  →  3. Interfaz repo  →  4. Modelo Eloquent  →  5. Repo concreto
                                                                              ↓
8. Ruta  ←  7. Controlador  ←  6. Caso de uso + Response  ←────────────────────
                                                                              ↓
                                                            9. Binding en AppServiceProvider
```

No saltes pasos: cada archivo necesita al anterior.
