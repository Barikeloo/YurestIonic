# YurestIonic — TPV Profesional para Hostelería

> **Versión:** Demo funcional — Mayo 2026  
> **Stack:** Laravel 12 (backend) + Angular 19 + Ionic (frontend)  
> **Arquitectura:** DDD + Hexagonal + Multi-tenant  
> **Licencia:** Propietaria  

---

## 1. Resumen Ejecutivo

**YurestIonic** es un sistema TPV (Terminal Punto de Venta) completo diseñado para el sector hostelero. Gestiona la operativa diaria de un restaurante desde la configuración del negocio hasta el cierre fiscal del turno, pasando por la toma de pedidos en salón, la división de cuenta por comensales y el cobro con múltiples métodos de pago.

El producto está pensado para desplegarse en tabletas táctiles como dispositivo principal de los camareros, con autenticación por PIN para acceso rápido, sincronización de estado de mesas en tiempo real entre terminales, y una arquitectura backend preparada para escalar horizontalmente a múltiples restaurantes bajo un mismo despliegue (multi-tenancy).

### Alcance actual

- **Backoffice completo** — Gestión de familias, productos, impuestos, zonas, mesas, usuarios y roles.
- **Front de venta (TPV)** — Flujo real de mesa → pedido → cobro → cierre, optimizado para táctil.
- **División de cuenta** — 3 estrategias: partes iguales, asignación por líneas, por comensal.
- **Cierre de caja** — Sesiones de turno, movimientos de caja, arqueo y generación de Z-Report con hash de integridad.
- **Dashboard de finanzas** — Prototipo funcional con métricas de ventas por período, producto estrella y evolución de ingresos.
- **Panel de desarrollador (SuperAdmin)** — Gestión de la plataforma multi-tenant: alta de restaurantes, administración de superadmins y control global del sistema.
- **Prototipo PDA** — Diseño preliminar de la interfaz de PDA (Punto de Atención Digital) para operadores. Incompleto; se completará en una fase posterior del proyecto.

---

## 2. Guía de Despliegue

Este proyecto se despliega mediante **Docker Compose**. A continuación se detalla el procedimiento paso a paso para levantar un entorno funcional desde cero.

### 2.1 Prerrequisitos del sistema

| Requisito | Versión mínima | Notas |
|---|---|---|
| Docker Engine | 24.x | Con soporte para Compose v2 |
| Docker Compose | 2.20+ | Incluido en Docker Desktop |
| GNU Make | 3.81+ | Para ejecutar los targets del Makefile |
| Git | 2.30+ | Para clonar el repositorio |
| RAM disponible | 4 GB | Recomendado para levantar todos los contenedores |

### 2.2 Clonar el repositorio

```bash
git clone <url-del-repositorio> yurestionic
cd yurestionic
```

### 2.3 Configurar variables de entorno

Copiar el archivo de entorno de ejemplo del backend:

```bash
cp backend/.env.example backend/.env
```

Revisar dentro de `backend/.env` que estas variables sean correctas para tu entorno:

```env
# Aplicación
APP_NAME=YurestIonic
APP_ENV=local
APP_KEY=  # se genera en el paso 2.5 con: php artisan key:generate
APP_URL=http://localhost:8000

# Base de datos (NO modificar DB_HOST=db, es el nombre del servicio Docker)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=training
DB_USERNAME=root
DB_PASSWORD=root

# SuperAdmin (credenciales del panel de desarrollador)
SUPERADMIN_NAME="Platform Superadmin"
SUPERADMIN_EMAIL=superadmin@tpv.local
SUPERADMIN_PASSWORD=superadmin123

# JWT (se genera automáticamente con key:generate, o configurar manualmente)
JWT_SECRET=
JWT_TTL=1440

# Cache y colas (preparado para Redis, no obligatorio en demo)
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# Mail (configurar para notificaciones de recuperación de contraseña)
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS="noreply@yurestionic.local"
MAIL_FROM_NAME="YurestIonic"
```

> **Nota:** No modificar `DB_HOST=db`. El nombre del servicio Docker resuelve internamente a la IP del contenedor MySQL.

### 2.4 Levantar la infraestructura

```bash
make start
```

Esto crea y arranca los siguientes contenedores:

| Contenedor | Puerto host | Servicio interno |
|---|---|---|
| `training_api` | `8000` | Laravel 12 (Nginx + PHP-FPM) |
| `training_frontend` | `4200` | Angular dev server |
| `training_db` | `3406` | MySQL 8 |
| `training_dbgate` | `9051` | Cliente web DbGate (conexión preconfigurada) |

### 2.5 Instalar dependencias y preparar la base de datos

```bash
# Instala dependencias PHP de Composer, ejecuta migraciones y genera APP_KEY
make install
docker compose run --rm api php artisan key:generate
```

### 2.6 Cargar datos de demostración

Ejecuta todos los seeders para tener múltiples restaurantes de prueba con usuarios, productos, zonas, mesas y datos operativos listos para probar:

```bash
docker compose exec api php artisan db:seed
```

> Este paso es **obligatorio** para la primera puesta en marcha. Crea varios restaurantes de demo (incluido *Bar Manolo*) y los usuarios de prueba documentados en la sección 3.

### 2.7 Verificar que todo funciona

Abre en tu navegador:

| URL | Descripción |
|---|---|
| http://localhost:4200 | Interfaz principal (TPV + Backoffice) |
| http://localhost:8000 | API REST (debería devolver JSON de status) |
| http://localhost:9051 | DbGate — conexión **Training MySQL** preconfigurada |

### 2.8 Comandos de operación diaria

```bash
make start           # Iniciar todos los servicios
make stop            # Detener todos los servicios
make restart         # Reiniciar sin reconstruir imágenes
make recreate        # Reconstruir imágenes y levantar (después de cambios en Dockerfile)
make db-migrate      # Ejecutar migraciones pendientes
make test            # Tests unitarios e integración del backend
make test-frontend   # Tests unitarios del frontend (Karma/Jasmine)
make lint            # Formatear PHP con Laravel Pint
make build-frontend  # Build de producción Angular
make logs-backend    # Tail de logs de Laravel en tiempo real
```

### 2.9 Reinicio desde cero (reset completo)

Si necesitas borrar todo y empezar de nuevo:

```bash
make stop
docker compose down -v   # elimina volúmenes (base de datos)
make start
make install
docker compose exec api php artisan db:seed
```

---

## 3. Datos de demostración

Tras ejecutar `php artisan db:seed`, el sistema crea varios restaurantes de demostración con catálogos, zonas, mesas y usuarios operativos. El restaurante principal es **Bar Manolo**. A continuación las credenciales para acceder:

### Usuarios de prueba

| Rol | Nombre | Email | Contraseña | PIN | Permisos destacados |
|---|---|---|---|---|---|
| **SuperAdmin** | Platform Superadmin | `superadmin@tpv.local` | `superadmin123` | — | Gestión global de la plataforma: crear restaurantes, gestionar superadmins, acceso total al sistema |
| **Admin** | Manolo | `barmanolo@gmail.com` | `12345678` | `1234` | Gestión completa del restaurante: usuarios, productos, finanzas, cierre forzado de caja |
| **Supervisor** | María | `maria@saona.com` | `12345678` | `2345` | Gestión de turno, anulaciones, arqueo, no puede crear admins |
| **Operador** | Carlos | `carlos@saona.com` | `12345678` | `3456` | Toma de pedidos, cobro, apertura de mesa |
| **Operador** | Laura | `laura@saona.com` | `12345678` | `4567` | Toma de pedidos, cobro, apertura de mesa |
| **Operador** | Javier | `javier@saona.com` | `12345678` | `5678` | Toma de pedidos, cobro, apertura de mesa |
| **Operador** | Sofía | `sofia@saona.com` | `12345678` | `6789` | Toma de pedidos, cobro, apertura de mesa |

> **Tip de acceso rápido:** En la pantalla de login, toca **"Acceso rápido"** e introduce cualquiera de los PINs anteriores para entrar sin escribir email ni contraseña.

### Datos precargados

- **Familias:** Bebidas, Entrantes, Carnes, Pescados, Postres
- **Productos:** ~8 artículos con precios, impuestos (IVA 21%, 10%, 4%) e imágenes
- **Zonas:** Terraza, Salón, Barra
- **Mesas:** 22 mesas distribuidas por zona
- **Impuestos:** IVA General 21%, Reducido 10%, Superreducido 4%

---

## 4. Guía de uso de la aplicación

### 4.1 Vinculación de dispositivo

La primera vez que accedes a la aplicación desde un dispositivo nuevo, debes **vincularlo** a un restaurante antes de poder operar. Este paso es obligatorio y se realiza una sola vez por dispositivo.

#### Flujo de vinculación

1. **Pantalla de bienvenida** — Al abrir http://localhost:4200 por primera vez en el dispositivo, verás la pantalla de inicio con dos opciones: **"Vincular dispositivo"** y **"Acceder como desarrollador"**.
2. **Autenticación del administrador** — Toca **"Vincular dispositivo"** e introduce el **email y contraseña** del usuario con rol `admin` del restaurante que quieres vincular.
   > Solo los usuarios con rol `admin` pueden vincular dispositivos. Si introduces credenciales de otro rol, el sistema mostrará un error.
3. **Selección del restaurante** — Tras validar las credenciales, el sistema muestra la lista de restaurantes asociados a ese administrador. Selecciona el restaurante que quieres vincular al dispositivo.
4. **Confirmación** — El restaurante seleccionado se guarda de forma persistente en el almacenamiento local del dispositivo (`localStorage`). A partir de este momento, todas las operaciones del TPV estarán asociadas a ese restaurante.
5. **Acceso al login** — Una vez vinculado, el dispositivo redirige automáticamente a la pantalla de login para que los operadores puedan iniciar sesión con email/contraseña o PIN.

> Si el dispositivo ya está vinculado, la pantalla de bienvenida redirige automáticamente al login sin mostrar el selector.

### 4.2 Login y selección de rol

Al entrar en http://localhost:4200 verás la pantalla de login. Puedes autenticarte de dos formas:

1. **Email + Contraseña** — Para administradores y supervisores que gestionan el backoffice.
2. **Acceso rápido (PIN)** — Para camareros que operan el TPV. Más rápido en el día a día con tabletas compartidas.

### 4.3 Backoffice — Gestión del negocio

Desde el menú lateral, accede a **"Gestión"**. Esta sección está restringida a roles `admin` y `supervisor`.

- **Familias** — Categorías del catálogo. Ej: Bebidas, Entrantes, Carnes. Se pueden activar/desactivar sin borrarlas.
- **Productos** — Alta de artículos con nombre, precio, impuesto, familia, imagen y stock. Cada producto puede tener **modificadores** (ej: "sin cebolla", "doble queso", "extra de salsa") que se registran como notas en la línea de pedido.
- **Impuestos** — Configuración de tipos de IVA aplicables.
- **Zonas** — Salones o ambientes del local (Terraza, Salón, Barra).
- **Mesas** — Mesas físicas asignadas a una zona. Soportan agrupación (unión de mesas para grupos grandes).
- **Usuarios** — Alta de empleados con rol (`admin`, `supervisor`, `operator`), email, contraseña y PIN numérico.

#### Cambio de restaurante (Tenant Context)

Los usuarios con rol `admin` pueden operar y visualizar varios restaurantes desde un mismo inicio de sesión. En el panel de **Gestión**, la barra lateral izquierda muestra la lista de restaurantes disponibles. Con un solo click sobre cualquier restaurante:

- El sistema cambia el **contexto activo** (`restaurant_id`) para todas las operaciones subsiguientes.
- La **topbar** actualiza automáticamente el nombre del restaurante seleccionado.
- Se cargan en tiempo real los usuarios, productos, familias, zonas, impuestos y Z-Reports correspondientes a ese tenant.
- El contexto se **persiste** en `localStorage`, por lo que al recargar la página o volver a "Gestión" se mantiene el último restaurante seleccionado.

> Esta funcionalidad permite a un administrador gestionar varios locales (multi-tenant) sin cerrar sesión, alternando entre ellos de forma instantánea.

### 4.4 TPV — Flujo de venta paso a paso

| Paso | Acción | Resultado esperado |
|---|---|---|
| **1. Mesas** | Navega a **"Mesas"**. Verás tabs de zonas y un grid de mesas. | Verde = libre. Rojo/Naranja = ocupada (orden abierta). |
| **2. Abrir mesa** | Toca una mesa libre → selecciona **operador** desde el desplegable o quick access → introduce **número de comensales** con el teclado numérico táctil → confirma **"Abrir mesa"**. | Se crea una `Order` en estado `open`. La mesa pasa a ocupada. |
| **3. Tomar pedido** | En la pantalla de pedido, toca una **familia** (tabs superiores) y luego un **producto** del grid. | El producto se añade al resumen de líneas del lado derecho. Si el producto tiene modificadores, aparecen como opciones seleccionables. |
| **4. Gestionar líneas** | En el resumen: **+1** para incrementar cantidad, **-1** para decrementar (si llega a 0 se elimina), o **papelera** para borrar directamente. | El total se recalcula en tiempo real incluyendo impuestos. |
| **5. Modificadores** | Al añadir ciertos productos (ej: hamburguesa), aparecen opciones de personalización: "sin cebolla", "extra de queso", etc. Estas notas se imprimen en cocina junto a la línea. | Las notas quedan ligadas a la línea de `order_lines` en el campo `notes`. |
| **6. Dividir cuenta (opcional)** | Toca **"Dividir cuenta"**. Se abre el modal con 3 modos: **Partes iguales**, **Por líneas** (arrastra cada producto al comensal), **Por comensal** (asignación directa). | El sistema calcula la cuota de cada comensal. |
| **7. Cobrar** | Toca **"Cobrar"**. Aparece el teclado numérico con el **total exacto** pendiente. Si modificas el importe a una cantidad menor, el sistema detecta automáticamente que es un **pago parcial** y cambia el botón a "Cobrar parcial". | Se genera una `Sale` vinculada a la `Order`. Si es pago total, la orden se cierra. |
| **8. Cerrar** | Tras cobrar, la mesa vuelve a estado **libre** (verde). El ticket queda registrado con número de serie. | Se muestra la confirmación con el número de ticket. |

### 4.5 Cobro dividido — Casuística avanzada

El sistema soporta combinaciones de métodos dentro de una misma sesión de cobro (`ChargeSession`), con las siguientes reglas de negocio:

- **Si inicias por "Partes iguales"** — El sistema **bloquea** el cambio a "Por líneas" o "Por comensal" para evitar inconsistencias contables. Sigues con partes iguales hasta cubrir la deuda.
- **Si inicias por "Por líneas"** — Puedes asignar productos a comensales, cobrar uno a uno, y si queda un **restante** (gastos comunes no asignados), cambias a "Partes iguales" para dividir lo pendiente.
- **Toggle "Incluir comensales ya pagados"** — En el modo "Partes iguales", determina si la deuda restante se divide entre **todos** los comensales (incluso los que ya pagaron sus líneas) o solo entre los **pendientes**.
- **Pagos mixtos** — Un mismo comensal puede pagar parte en efectivo y parte con tarjeta. El sistema valida que la suma de pagos coincida con el total.

### 4.6 Caja — Sesiones de turno y Z-Report

- **Apertura de caja** — Al inicio del turno, el operador (o admin) abre una sesión de caja introduciendo el **fondo inicial** en efectivo.
- **Durante el turno** — Todos los cobros (`Sale`) y movimientos de caja (`CashMovement`: entradas de cambio, pagos a proveedores, sangrías, propinas) quedan vinculados a la sesión activa del dispositivo.
- **Arqueo** — Al cerrar, el sistema compara el efectivo contado (`final_amount_cents`) contra el efectivo teórico (`expected_amount_cents = fondo inicial + ventas en efectivo + entradas - salidas`). Si hay discrepancia, se exige un motivo.
- **Z-Report** — Tras confirmar el arqueo, el sistema genera el informe Z con:
  - Ventas totales desglosadas por método de pago (efectivo, tarjeta, Bizum, vale, invitación).
  - Movimientos de caja detallados.
  - Propinas declaradas.
  - Discrepancia detectada y justificación.
  - **Hash SHA-256** encadenado con el Z anterior para garantizar la integridad fiscal de la secuencia.

### 4.7 Dashboard de finanzas (prototipo)

Accesible desde el menú lateral para roles `admin` y `supervisor`. Muestra:

- **Ventas por período** — Selector de rango de fechas con gráfica de evolución diaria.
- **Producto estrella** — Artículo más vendido en el período seleccionado.
- **Ticket medio** — Importe promedio por venta.
- **Métodos de pago** — Distribución porcentual (pie chart) de efectivo vs tarjeta vs otros.
- **Top camareros** — Ranking de operadores por volumen de ventas.

> Estado actual: **Prototipo funcional**. Los datos no son reales, pero la interfaz está lista para integrar datos reales en el futuro.

### 4.8 Panel de Desarrollador (SuperAdmin) — Gestión de la plataforma

El **Panel de Desarrollador** es una interfaz independiente destinada a los administradores de la plataforma, no al personal del restaurante. Permite gestionar el ecosistema multi-tenant desde un único punto de control.

#### Acceso

En la pantalla de login, despliega el selector de modo de acceso y elige **"SuperAdmin"**. Introduce las credenciales:

| Campo | Valor |
|---|---|
| Email | `superadmin@tpv.local` |
| Contraseña | `superadmin123` |

> **Nota:** El SuperAdmin no tiene PIN de acceso rápido. El acceso por email/contraseña es obligatorio por seguridad.

#### Funcionalidades disponibles

- **Gestión de restaurantes** — Crear nuevos restaurantes (tenants) en la plataforma. Cada restaurante aislado opera con su propio conjunto de usuarios, productos, mesas y ventas gracias al shard key `restaurant_id`.
- **Gestión de superadmins** — Alta, baja y modificación de cuentas de administrador de plataforma.
- **Estadísticas globales** — Visión consolidada de todos los restaurantes activos: número de ventas totales, ingresos agregados y usuarios conectados.
- **Diagnóstico** — Acceso rápido a logs de sistema, estado de contenedores y health-check de la API.

#### Flujo típico: dar de alta un nuevo restaurante

```
1. Entrar al Panel de Desarrollador con superadmin@tpv.local
2. Ir a "Restaurantes" → "Nuevo restaurante"
3. Completar: nombre fiscal, NIF/CIF, email de contacto, contraseña inicial
4. Confirmar → el sistema genera automáticamente el restaurante con su UUID
5. Entregar al cliente sus credenciales de admin para acceder al backoffice
```

> **Importante:** El SuperAdmin no opera dentro de un restaurante concreto. No ve mesas, ni toma pedidos, ni cierra cajas. Su rol es exclusivamente la administración de la plataforma y sus tenants.

---

## 5. Características implementadas

### Hitos del proyecto

| Hito | Estado | Alcance |
|---|---|---|
| **Hito 1 — Modelo de datos** | 100% | 20+ migraciones, 14 tablas principales, soft deletes, índices optimizados, shard key `restaurant_id` en todas las entidades. |
| **Hito 2 — API REST Backoffice** | 100% | CRUD completo de familias, impuestos, productos, zonas, mesas y usuarios. Auth dual (email/password + PIN de 4 dígitos). SuperAdmin con gestión multi-restaurante. |
| **Hito 3 — Interfaz Backoffice** | 100% | Panel de gestión con ~1.600 líneas de componentes Angular. Formularios reactivos, validación en tiempo real, toasts de confirmación. |
| **Hito 4 — Front de Venta (TPV)** | 100% | Flujo completo: mesas → apertura → pedido → cobro → cierre. Soporte para pagos parciales, división de cuenta (3 modos), y cierre de caja con Z-Report. |
| **Hito 5 — Informes (Dashboard)** | 40% | Prototipo funcional con métricas clave. Pendiente: exportación a PDF/Excel, filtros avanzados, predicciones. |
| **Hito 6 — Mejoras operativas** | 80% | Roles, PIN, quick access, vinculación de dispositivo, multi-tenancy, productos con modificadores. |

### Funcionalidades detalladas

| Módulo | Feature | Descripción técnica |
|---|---|---|
| **Auth** | Login dual | Sistema de autenticación con JWT que soporta tanto email+password como PIN numérico de 4 dígitos. |
| **Auth** | Quick Access | Lista de usuarios frecuentes en la pantalla de PIN para acceso inmediato sin escribir credenciales. |
| **Auth** | Roles y permisos | 3 roles (`admin`, `supervisor`, `operator`) con guardas de navegación (`CanActivate`) en el frontend y middlewares de autorización en el backend. |
| **Producto** | Modificadores | Cada producto puede tener opciones de personalización que se almacenan en `order_lines.notes` (ej: "sin cebolla", "extra queso"). |
| **Producto** | Stock | Control de inventario básico con decremento automático al cerrar venta. |
| **Mesa** | Estados visuales | Mesas con 2 estados (libre/ocupada) representados con semáforo de colores en el grid. |
| **Mesa** | Agrupación | Soporte para unir mesas físicas (campo `merged_table_group_id`) y gestionarlas como una sola unidad de cobro. |
| **Pedido** | Líneas mutables | Incremento, decremento y eliminación de líneas en tiempo real antes del cierre. |
| **Pedido** | Snapshot fiscal | Al cerrar la venta, las líneas de `Order` se copian a `sales_lines` con el precio e impuesto vigentes en ese momento (inmutabilidad histórica). |
| **Cobro** | División de cuenta | 3 modos: `equal` (partes iguales), `lines` (asignación tap & place), `diner` (por número de comensal). |
| **Cobro** | ChargeSession | Sesión de cobro persistente en base de datos que mantiene estado entre navegaciones y previene dobles pagos. |
| **Cobro** | Pagos mixtos | Un ticket puede pagarse con múltiples métodos simultáneamente (ej: 30€ tarjeta + 5€ efectivo). |
| **Cobro** | Pagos parciales | El camarero introduce cualquier importe; el sistema detecta si es parcial y permite seguir cobrando el restante. |
| **Cobro** | Bloqueo de método | Una vez iniciado el cobro con un método (equal o lines), la sesión bloquea el cambio para garantizar trazabilidad contable. |
| **Cobro** | Toggle pagados | En modo equal, opción para incluir o excluir comensales que ya pagaron sus líneas en el reparto del restante. |
| **Caja** | Sesiones de turno | Apertura con fondo inicial, registro de ventas y movimientos, cierre con arqueo. |
| **Caja** | Z-Report | Informe fiscal automático con correlativo por restaurante, hash SHA-256 encadenado y discrepancias declaradas. |
| **Caja** | Movimientos | Entradas y salidas de caja categorizadas: cambio de moneda, pago a proveedor, sangría, ajuste, propina. |
| **Ventas** | Cancelación completa | Anulación de una venta con motivo obligatorio, generando registro de auditoría. |
| **Ventas** | Reembolso parcial | Cancelación de líneas individuales de una venta ya cerrada mediante nota de abono (`parent_sale_id`). |
| **Multi-tenant** | Shard key | `restaurant_id` en todas las tablas. Un solo backend sirve a N restaurantes con aislamiento de datos. |
| **SuperAdmin** | Gestión de plataforma | Dominio separado para crear restaurantes y gestionar la infraestructura global. |
| **PDA** | Prototipo de interfaz | Diseño preliminar de la PDA (Punto de Atención Digital) para operadores de sala. Incompleto; se desarrollará en fase posterior. |

---

## 6. Arquitectura

### 6.1 Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| **Backend** | Laravel | 12.x |
| **Lenguaje backend** | PHP | 8.3+ |
| **Base de datos** | MySQL | 8.0 |
| **Cache/Sesión** | Redis | 7.x (preparado, no obligatorio para demo) |
| **Frontend** | Angular | 19.x |
| **Framework UI** | Ionic | 8.x |
| **Lenguaje frontend** | TypeScript | 5.x |
| **Estilos** | SCSS + CSS Variables | — |
| **Contenedores** | Docker + Docker Compose | v2 |
| **Servidor web API** | Nginx + PHP-FPM | — |
| **Servidor web dev** | Angular CLI dev server | — |
| **Testing backend** | PHPUnit | 11.x |
| **Testing frontend** | Karma + Jasmine | — |
| **Linting PHP** | Laravel Pint | — |
| **Cliente DB** | DbGate | (contenedor) |

### 6.2 Patrón arquitectónico — DDD + Hexagonal

El backend sigue estrictamente **Domain-Driven Design** con **Arquitectura Hexagonal** (Ports & Adapters). Cada dominio es un módulo autocontenido que no conoce detalles de framework fuera de su capa `Infrastructure`.

```
App/<Dominio>/
├── Domain/
│   ├── Entity/              # Entidades puras con lógica de negocio
│   │   └── <Entidad>.php    # Método de fábrica dddCreate(), invariantes
│   ├── ValueObject/         # VOs inmutables: constructor privado + create()
│   │   └── <VO>.php         # Validación encapsulada, imposible instanciar estado inválido
│   ├── Exception/           # Excepciones de dominio (invariantes violadas, reglas de negocio)
│   │   └── <Entidad>NotFoundException.php
│   │   └── <Regla>Exception.php
│   └── Interfaces/          # Contratos (repositorios, servicios del dominio)
│       └── <Repo>Interface.php
├── Application/
│   └── <CasoDeUso>/
│       ├── <CasoDeUso>.php          # Orquestador puro: sin referencias a Laravel/HTTP
│       ├── <CasoDeUso>Command.php   # DTO de entrada: encapsula los datos que recibe el caso de uso
│       └── <CasoDeUso>Response.php  # DTO de salida para el controlador
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/            # Eloquent (solo usados desde repositorios)
    │   └── Repositories/    # Implementaciones de las interfaces de dominio
    ├── Services/            # Implementaciones de servicios (hashers, notificadores)
    └── Entrypoint/
        └── Http/
            ├── Requests/    # Form Requests de Laravel: validación de entrada HTTP
            └── <Controller>.php  # Controladores (1 acción = 1 __invoke)
```

### 6.3 Dominios implementados

| Dominio | Entidades principales | Responsabilidad |
|---|---|---|
| `Shared` | `Uuid`, `DomainDateTime`, `Email` | Value Objects reutilizables entre dominios |
| `User` | `User`, `UserName`, `PasswordHash`, `UserRole`, `Pin` | Gestión de empleados del restaurante |
| `SuperAdmin` | `SuperAdmin`, `Restaurant` (gestión) | Administración de la plataforma multi-tenant |
| `Restaurant` | `Restaurant`, `RestaurantName` | Datos fiscales y de contacto del negocio |
| `Family` | `Family`, `FamilyName` | Categorías del catálogo |
| `Product` | `Product`, `Price`, `Stock` | Artículos del menú con precio, impuesto, imagen y modificadores |
| `Tax` | `Tax`, `TaxPercentage` | Tipos de IVA aplicables |
| `Zone` | `Zone`, `ZoneName` | Salones del local |
| `Table` | `Table`, `TableName` | Mesas físicas con soporte de agrupación |
| `Order` | `Order`, `OrderLine`, `DinerNumber` | Pedidos abiertos (estado mutable hasta el cierre) |
| `Sale` | `Sale`, `SaleLine`, `SalePayment` | Documentos fiscales inmutables (ticket, factura, nota de abono) |
| `Cash` | `CashSession`, `CashMovement`, `ZReportHash` | Sesiones de caja, arqueo e informes fiscales |
| `ChargeSession` | `ChargeSession`, `ChargeSessionPayment`, `AmountPerDiner` | División de cuenta por comensales |

### 6.4 Flujo de una petición (arquitectura en acción)

```
┌─────────────┐     HTTP/JSON      ┌─────────────────────────────────────────────┐
│   Angular   │ ────────────────▶ │  Controller (Infrastructure/Entrypoint/Http) │
│   (Ionic)   │                   │  ├── Form Request valida entrada             │
└─────────────┘                   │  ├── Construye Command DTO                  │
                                  │  └── Llama al Caso de Uso (Application)     │
                                  └─────────────────────────────────────────────┘
                                                           │
                                                           ▼
                                  ┌─────────────────────────────────────────────┐
                                  │  Caso de Uso (Application)                    │
                                  │  ├── Recibe Command + RepositoryInterface   │
                                  │  ├── Construye VOs con ::create()           │
                                  │  ├── Crea Entidad con ::dddCreate()         │
                                  │  └── Persiste vía Repositorio (interfaz)   │
                                  └─────────────────────────────────────────────┘
                                                           │
                                                           ▼
                                  ┌─────────────────────────────────────────────┐
                                  │  Repositorio Eloquent (Infrastructure)      │
                                  │  ├── Implementa RepositoryInterface         │
                                  │  ├── Traduce Entidad ↔ Modelo Eloquent     │
                                  │  └── Ejecuta operaciones en MySQL           │
                                  └─────────────────────────────────────────────┘
                                                           │
                                                           ▼
                                  ┌─────────────────────────────────────────────┐
                                  │  Response DTO (Application)                   │
                                  │  └── toArray() serializable para JSON         │
                                  └─────────────────────────────────────────────┘
                                                           │
                                                           ▼
                                  ┌─────────────────────────────────────────────┐
                                  │  Controller devuelve JsonResponse            │
                                  │  └── HTTP 200/201 con datos del Response    │
                                  └─────────────────────────────────────────────┘
```

> **Invariante clave:** El dominio (Entidad, VO, Excepciones) no importa nada de Laravel. El caso de uso no conoce HTTP. El controlador no conoce la base de datos.

---

### 6.5 Decisiones técnicas clave

| Decisión | Justificación | Impacto |
|---|---|---|
| **Separar `Order` (mutable) y `Sale` (inmutable)** | Los pedidos admiten cambios de líneas, cantidades y cancelaciones. Las ventas son documentos fiscales que no se pueden modificar; toda corrección genera una nota de abono nueva. | Cumplimiento fiscal, trazabilidad histórica. |
| **`restaurant_id` como shard key** | Todas las tablas incluyen `restaurant_id`. Prepara el sistema para particionamiento horizontal (sharding) si un restaurante crece desproporcionadamente. | Escalabilidad horizontal sin reescritura. |
| **Login por PIN de 4 dígitos** | En hostelería los camareros comparten tabletas táctiles. Un PIN es más rápido que email+password y reduce errores de tipeo. | UX optimizada para el contexto real de trabajo. |
| **Value Objects con constructor privado** | Patrón `VO::create()` como único punto de entrada. Valida en el momento de la creación. Imposible construir un `Email` con formato inválido o un `Money` negativo. | Calidad del dato, menos bugs de validación dispersos. |
| **Responses como DTOs** | Los casos de uso devuelven objetos `Response` en lugar de la entidad del dominio. El controlador serializa el DTO, no la entidad. | Desacoplamiento: cambios internos en la entidad no rompen la API. |
| **Pagos mixtos como colección** | Modelado como array de `SalePayment` en lugar de un campo `payment_method` único. | Soporta escenarios reales: 30€ tarjeta + 5€ efectivo + vale 10€. |
| **Deuda viva de la mesa** | El sistema nunca recalcula el pasado. Los pagos se registran como eventos append-only que reducen la deuda restante. | Trazabilidad completa, prevención de race conditions en pagos concurrentes. |
| **Soft Deletes en todas las entidades** | Campo `deleted_at` en lugar de `DELETE` físico. Las ventas históricas mantienen referencias válidas a productos o impuestos eliminados. | Integridad referencial histórica, cumplimiento fiscal. |

---

## 6.6 Seguridad

### Autenticación y autorización

- **JWT con expiración** — Tokens de sesión con tiempo de vida configurable. Refresh token no implementado aún (pendiente Hito 6).
- **Dual auth** — Email/password para admins/supervisors, PIN de 4 dígitos para operadores de TPV.
- **Hash de contraseñas** — `bcrypt` via Laravel Hash. El hasher se inyecta por interfaz (`PasswordHasherInterface`) en el caso de uso, nunca se usa `Hash::make()` directamente en el controlador.
- **SuperAdmin aislado** — Dominio separado con tabla propia (`super_admins`). No comparte autenticación ni sesión con los usuarios de restaurante.

### Validación y sanitización

- **Form Requests** — Cada controlador HTTP usa un Form Request de Laravel (`Infrastructure/Entrypoint/Http/Requests/`) que valida y sanitiza la entrada antes de que el caso de uso la vea.
- **Value Objects** — Constructor privado + `create()` garantiza que ningún dato inválido llega a la entidad (email mal formado, dinero negativo, UUID inválido).
- **Excepciones de dominio** — Las invariantes violadas lanzan excepciones específicas (`OrderNotFoundException`, `InvalidCredentialsException`) que el controlador mapea a códigos HTTP semánticos (404, 403, 409, 422).

### Trazabilidad e integridad

- **Z-Report con hash SHA-256** — Cada cierre de caja genera un hash encadenado con el Z anterior. Cualquier manipulación posterior rompe la cadena y es detectable.
- **Soft deletes** — Nunca se pierde información histórica. Un producto eliminado sigue referenciado en las líneas de venta antiguas.
- **Sin datos sensibles en logs** — Contraseñas, PINs y tokens nunca se registran en los logs de Laravel.

---

## 7. Estructura del repositorio

```
yurestionic/
├── backend/
│   ├── app/
│   │   ├── Shared/
│   │   │   └── Domain/ValueObject/        # Uuid, DomainDateTime, Email
│   │   ├── <Dominio>/                     # User, Order, Sale, Cash...
│   │   │   ├── Domain/Entity/
│   │   │   ├── Domain/ValueObject/
│   │   │   ├── Domain/Interfaces/
│   │   │   ├── Application/<CasoDeUso>/
│   │   │   └── Infrastructure/
│   │   │       ├── Persistence/
│   │   │       │   ├── Models/
│   │   │       │   └── Repositories/
│   │   │       ├── Services/
│   │   │       └── Entrypoint/Http/
│   │   └── Providers/
│   │       └── AppServiceProvider.php      # Binding de interfaces a implementaciones
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   │   ├── migrations/                     # 20+ migraciones con soft deletes y FKs
│   │   └── seeders/
│   │       └── SaonaDemoSeeder.php         # Datos de demo (Bar Manolo)
│   ├── routes/
│   │   └── api/                            # auth.php, tpv.php, admin.php, superadmin.php
│   ├── tests/
│   │   ├── Unit/                           # Tests de dominio y VOs
│   │   └── Feature/                        # Tests de integración HTTP
│   ├── .env.example
│   ├── composer.json
│   └── phpunit.xml
├── frontend/
│   ├── src/app/
│   │   ├── pages/core/
│   │   │   ├── login/                      # Pantalla de autenticación (email + PIN)
│   │   │   ├── mesas/                      # Grid de zonas y mesas con semáforo de estado
│   │   │   ├── pedidos/                    # Toma de pedido: catálogo + resumen de líneas
│   │   │   ├── caja/                       # Cobro, split bill, sesiones de caja, Z-Report
│   │   │   ├── gestion/                    # Backoffice CRUD completo
│   │   │   └── dashboard/                  # Prototipo de finanzas (métricas y gráficas)
│   │   ├── features/cash/
│   │   │   ├── ui/
│   │   │   │   ├── split-bill-modal/       # Modal de división de cuenta (3 modos)
│   │   │   │   └── cobrar-modal/         # Teclado numérico de cobro
│   │   │   ├── services/
│   │   │   │   ├── charge-session.service.ts
│   │   │   │   └── tpv.service.ts
│   │   │   └── facades/
│   │   │       └── caja-payment.facade.ts  # Estado reactivo con Signals
│   │   ├── components/                     # Componentes reutilizables (botones, cards, modals)
│   │   ├── services/                       # Servicios globales (Auth, HTTP interceptor)
│   │   ├── providers/
│   │   │   └── interceptor.ts              # Prefija API URL, añade headers JWT
│   │   └── guards/                         # CanActivate por rol (admin, supervisor, operator)
│   ├── src/environments/
│   ├── angular.json
│   └── package.json
├── docs/
│   ├── CAJA_DESIGN.md                      # Especificación funcional completa del módulo Caja
│   └── DOMINIO_TPV.md                      # Reglas de diseño de APIs TPV en hostelería
├── docker-compose.yml                      # 4 servicios: api, frontend, db, dbgate
├── Makefile                                # Comandos de desarrollo y operación
├── README.md                               # Este documento
├── ROADMAP.md                              # Hitos, criterios de evaluación, mejoras
├── DATA_MODEL.md                           # Esquema completo de BD con tipos y relaciones
├── TPV_COBROS_README.md                    # Filosofía del sistema de cobros
├── CHARGE_SESSION_PROGRESS.md              # Progreso del sistema de pago a partes iguales
├── RESUMEN_CAJA_FLUJO.md                   # Estabilización de cobros y UX
└── AGENTS.md                               # Convenciones de código (DDD, VOs, estilo)
```

---

## 8. API REST — Endpoints principales

### Autenticación

```
POST   /api/auth/login              # Email + password → JWT
POST   /api/auth/login-pin          # PIN numérico → JWT
GET    /api/auth/me                 # Usuario autenticado
POST   /api/auth/logout            # Invalidar token
GET    /api/auth/quick-users        # Usuarios con PIN para acceso rápido
```

### Backoffice (requiere rol admin/supervisor)

```
GET|POST       /api/management/families
GET|PUT|DELETE /api/management/families/{uuid}
GET|POST       /api/management/products
GET|PUT|DELETE /api/management/products/{uuid}
GET|POST       /api/management/zones
GET|PUT|DELETE /api/management/zones/{uuid}
GET|POST       /api/management/tables
GET|PUT|DELETE /api/management/tables/{uuid}
GET|POST       /api/management/users
GET|PUT|DELETE /api/management/users/{uuid}
```

### TPV (requiere sesión autenticada)

```
GET    /api/tpv/zones                # Zonas del restaurante
GET    /api/tpv/tables               # Mesas del restaurante
GET    /api/tpv/families            # Familias activas
GET    /api/tpv/products            # Productos activos
POST   /api/tpv/orders              # Crear orden (abrir mesa)
GET    /api/tpv/orders              # Listar órdenes abiertas
GET    /api/tpv/orders/{id}         # Detalle de orden
POST   /api/tpv/orders/lines        # Añadir línea a orden
PUT    /api/tpv/orders/{id}         # Actualizar orden (comensales, etc.)
DELETE /api/tpv/orders/{id}         # Cancelar orden
POST   /api/tpv/sales               # Crear venta (cerrar ticket)
POST   /api/tpv/sales/lines         # Añadir líneas a venta
POST   /api/tpv/sales/{id}/cancel   # Cancelar venta completa
POST   /api/tpv/charge-sessions              # Crear sesión de cobro dividido
GET    /api/tpv/charge-sessions/active        # Sesión activa de una orden
PUT    /api/tpv/charge-sessions/{id}/diners   # Modificar número de comensales
POST   /api/tpv/charge-sessions/{id}/payments # Registrar pago parcial/total
POST   /api/tpv/charge-sessions/{id}/cancel   # Cancelar sesión de cobro
```

### Caja (requiere sesión de caja abierta)

```
POST   /api/tpv/cash-sessions              # Abrir sesión de caja
GET    /api/tpv/cash-sessions/active       # Sesión activa del dispositivo
PUT    /api/tpv/cash-sessions/{id}/close   # Cerrar sesión y generar Z-Report
POST   /api/tpv/cash-sessions/{id}/movements # Registrar movimiento de caja
```

> Documentación completa de request/response en los controladores de `backend/app/<Dominio>/Infrastructure/Entrypoint/Http/`.

---

## 9. Testing

### Backend

```bash
# Ejecutar todos los tests (unitarios + integración)
make test

# Ejecutar un test específico
docker compose exec api php artisan test --filter=ChargeSessionEntityTest
```

- **Tests unitarios:** Cubren entidades de dominio y Value Objects (validaciones, invariantes, cálculos de `AmountPerDiner`).
- **Tests de integración:** Validan endpoints HTTP con base de datos en memoria o contenedor de test.

### Frontend

```bash
# Tests unitarios de Angular
make test-frontend
```

- Tests de componentes con Karma/Jasmine.
- Enfoque en validación de formularios y lógica de cálculo de totales.

---

## 10. Flujo de desarrollo recomendado

### Backend (nueva feature en un dominio)

```
1. Domain/ValueObject/      → Crear VOs necesarios (constructor privado + create())
2. Domain/Entity/            → Crear entidad con dddCreate() e invariantes
3. Domain/Exception/         → Crear excepciones para reglas de negocio violadas
4. Domain/Interfaces/        → Definir contrato del repositorio
5. Application/<CasoDeUso>/  → Caso de uso + Command + Response
6. Infrastructure/Persistence/→ Modelo Eloquent + Repositorio concreto
7. Infrastructure/Services/    → Implementar servicios de dominio (si aplica)
8. Infrastructure/Entrypoint/  → Controller + Form Request
9. routes/api/               → Registrar ruta apuntando al controlador
10. tests/                   → Tests unitarios (dominio) + integración (HTTP)
```

### Reglas de oro

- **Reglas de negocio:** Siempre en la entidad o en el VO. Nunca en el controlador ni en el modelo Eloquent.
- **Persistencia:** El caso de uso recibe `RepositoryInterface` por inyección. La implementación concreta se registra en `AppServiceProvider`.
- **Validación:** Form Request sanitiza la entrada HTTP → Command DTO la transporta al caso de uso → VO valida el dato de dominio.
- **Errores:** Excepciones de dominio se mapean a HTTP en el controlador (404 → NotFound, 409 → Conflict, 422 → Validation).
- **Frontend:** Los servicios (`TpvService`, `ChargeSessionService`) consumen la API. Los `facade` gestionan el estado reactivo con Angular Signals.

---

## 11. Documentación adicional

| Documento | Audiencia | Contenido |
|---|---|---|
| [`ROADMAP.md`](./ROADMAP.md) | Dirección / Producto | Hitos, fechas, criterios de evaluación, mejoras propuestas |
| [`DATA_MODEL.md`](./DATA_MODEL.md) | Tech Lead / Backend | Esquema completo de BD: tipos, FKs, soft deletes, shard key |
| [`TPV_COBROS_README.md`](./TPV_COBROS_README.md) | Equipo de producto | Filosofía del cobro: deuda viva, append-only, no recalcular el pasado |
| [`docs/CAJA_DESIGN.md`](./docs/CAJA_DESIGN.md) | Tech Lead / Arquitecto | Especificación funcional completa del módulo Caja: estados, invariantes, casuística |
| [`backend/FASES_IMPLEMENTADAS.md`](./backend/FASES_IMPLEMENTADAS.md) | CTO / Auditor | Detalle técnico de Fases 3 y 4: multi-payment, CancelSale, Z-Report, hash SHA-256 |
| [`CHARGE_SESSION_PROGRESS.md`](./CHARGE_SESSION_PROGRESS.md) | Equipo de desarrollo | Progreso técnico del sistema de pago a partes iguales |
| [`RESUMEN_CAJA_FLUJO.md`](./RESUMEN_CAJA_FLUJO.md) | Equipo de desarrollo | Resumen de estabilización de cobros, teclado numérico unificado, sincronización de componentes |
| [`AGENTS.md`](./AGENTS.md) | IAs / Nuevos devs | Convenciones de código: DDD, VOs con constructor privado, nomenclatura, estilo PSR-12 |
| [`docs/DOMINIO_TPV.md`](./docs/DOMINIO_TPV.md) | Integradores / Partners | Reglas de diseño de APIs TPV: timestamps por línea, jerarquía de productos, fracciones, series de facturación |
| [`PLAN_SEMANA_CTO.md`](./PLAN_SEMANA_CTO.md) | CTO / Dirección | Plan de presentación: guion de demo, decisiones técnicas a justificar, estructura de la presentación |

---

## 12. Próximos pasos y roadmap técnico

### Corto plazo (1–2 meses)

1. **Dashboard de finanzas v2** — Evolucionar el prototipo actual a panel completo: exportación PDF/Excel, comparativa intermensual, predicción de stock.
2. **Impresión de tickets** — Integración con impresoras térmicas ESC/POS para ticket de cocina y ticket de cliente.
3. **WebSockets** — Sincronización en tiempo real del estado de mesas entre múltiples tabletas del mismo restaurante.

### Medio plazo (3–6 meses)

4. **Descuentos y promociones** — Descuento por línea (% o importe fijo), descuento global en ticket, menús del día con precio cerrado.
5. **Traslado de mesa** — Mover un pedido abierto de una mesa a otra sin perder líneas ni asignaciones.
6. **Reservas** — Calendario de reservas con nombre, teléfono, número de comensales y asignación automática a mesa.
7. **Auditoría inmutable** — Tabla `AuditLog` con trazabilidad completa: quién, qué, cuándo, IP, device. Cumplimiento RGPD y fiscal.

### Largo plazo (6–12 meses)

8. **Cola offline** — Soporte para cobros sin conexión con sincronización automática al recuperar red.
9. **App móvil para cliente** — Carta digital, pedido desde mesa (QR), pago split desde el móvil del comensal.
10. **Integración fiscal** — Adaptadores para TicketBAI (País Vasco) y Veri*Factu (nacional) según normativa.

---

## 13. Notas para el despliegue en producción

Esta sección es orientativa para cuando se migre de demo a producción real:

- **Base de datos:** Migrar de MySQL local a servicio gestionado (AWS RDS, Google Cloud SQL, Azure Database). Habilitar backups automáticos diarios.
- **Almacenamiento de imágenes:** Mover imágenes de productos de disco local a S3-compatible (AWS S3, MinIO, DigitalOcean Spaces).
- **Cache y colas:** Activar Redis para cache de sesiones, rate limiting y colas de jobs (generación de Z-Report pesado).
- **SSL/TLS:** Configurar certificados Let's Encrypt en Nginx. Forzar HTTPS en todas las comunicaciones.
- **Monitoreo:** Integrar Sentry para errores en frontend y backend. Logs centralizados con ELK o Loki.
- **CI/CD:** Pipeline GitHub Actions / GitLab CI que ejecute `make test`, `make lint` y `make build-frontend` antes de mergear a `main`.

---

> **Autor:** Yurest  
> **Repositorio:** YurestIonic  
> **Entorno de desarrollo:** Docker Compose con servicios separados para API, frontend, MySQL y DbGate  
> **Demo local:** http://localhost:4200  
> **Última actualización:** Mayo 2026
