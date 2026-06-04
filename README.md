# YurestIonic — TPV Profesional para Hostelería

> **Versión:** Demo funcional — Mayo 2026  
> **Stack:** Laravel 12 (backend) + Angular 19 + Ionic (frontend)  
> **Arquitectura:** DDD + Hexagonal + Multi-tenant  
> **Licencia:** Propietaria  

---

## Índice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Guía de Despliegue](#2-guía-de-despliegue)
   - [2.1 Prerrequisitos del sistema](#21-prerrequisitos-del-sistema)
   - [2.2 Clonar el repositorio](#22-clonar-el-repositorio)
   - [2.3 Configurar variables de entorno](#23-configurar-variables-de-entorno)
   - [2.4 Levantar la infraestructura](#24-levantar-la-infraestructura)
   - [2.5 Instalar dependencias y preparar la base de datos](#25-instalar-dependencias-y-preparar-la-base-de-datos)
   - [2.6 Cargar datos de demostración](#26-cargar-datos-de-demostración)
   - [2.7 Verificar que todo funciona](#27-verificar-que-todo-funciona)
   - [2.8 Comandos de operación diaria](#28-comandos-de-operación-diaria)
   - [2.9 Reinicio desde cero](#29-reinicio-desde-cero-reset-completo)
3. [Testing](#3-testing)
   - [3.1 Backend — PHPUnit](#31-backend--phpunit)
   - [3.2 Frontend — unit](#32-frontend--unit)
   - [3.3 E2E — Playwright contra stack real](#33-e2e--playwright-contra-stack-real)
4. [Datos de demostración](#4-datos-de-demostración)
5. [Guía de uso de la aplicación](#5-guía-de-uso-de-la-aplicación)
   - [5.1 Vinculación de dispositivo](#51-vinculación-de-dispositivo)
   - [5.2 Login y selección de rol](#52-login-y-selección-de-rol)
   - [5.3 Backoffice — Gestión del negocio](#53-backoffice--gestión-del-negocio)
   - [5.4 TPV — Flujo de venta paso a paso](#54-tpv--flujo-de-venta-paso-a-paso)
   - [5.5 Cobro dividido — Casuística avanzada](#55-cobro-dividido--casuística-avanzada)
   - [5.6 Caja — Sesiones de turno y Z-Report](#56-caja--sesiones-de-turno-y-z-report)
   - [5.7 Dashboard de finanzas (prototipo)](#57-dashboard-de-finanzas-prototipo)
   - [5.8 Panel de Desarrollador (SuperAdmin)](#58-panel-de-desarrollador-superadmin--gestión-de-la-plataforma)
   - [5.9 Editor de Menús — Combos y menú del día](#59-editor-de-menús--combos-y-menú-del-día)
   - [5.10 Registro de Auditoría](#510-registro-de-auditoría)
6. [Características implementadas](#6-características-implementadas)
7. [Arquitectura](#7-arquitectura)
   - [7.1 Stack tecnológico](#71-stack-tecnológico)
   - [7.2 Patrón arquitectónico — DDD + Hexagonal](#72-patrón-arquitectónico--ddd--hexagonal)
   - [7.3 Dominios implementados](#73-dominios-implementados)
   - [7.4 Flujo de una petición](#74-flujo-de-una-petición-arquitectura-en-acción)
   - [7.5 Decisiones técnicas clave](#75-decisiones-técnicas-clave)
   - [7.6 Seguridad](#76-seguridad)
8. [Estructura del repositorio](#8-estructura-del-repositorio)
9. [API REST — Endpoints principales](#9-api-rest--endpoints-principales)
10. [Flujo de desarrollo recomendado](#10-flujo-de-desarrollo-recomendado)
11. [Documentación adicional](#11-documentación-adicional)
12. [Próximos pasos y roadmap técnico](#12-próximos-pasos-y-roadmap-técnico)
13. [Notas para el despliegue en producción](#13-notas-para-el-despliegue-en-producción)

---

## 1. Resumen Ejecutivo

**YurestIonic** es un sistema TPV (Terminal Punto de Venta) completo diseñado para el sector hostelero. Gestiona la operativa diaria de un restaurante desde la configuración del negocio hasta el cierre fiscal del turno, pasando por la toma de pedidos en salón, la división de cuenta por comensales y el cobro con múltiples métodos de pago.

El producto está pensado para desplegarse en tabletas táctiles como dispositivo principal de los camareros, con autenticación por PIN para acceso rápido, sincronización de estado de mesas en tiempo real entre terminales, y una arquitectura backend preparada para escalar horizontalmente a múltiples restaurantes bajo un mismo despliegue (multi-tenancy).

### Alcance actual

- **Backoffice completo** — Gestión de familias, productos, impuestos, zonas, mesas, usuarios y roles.
- **Menús (combos / menú del día)** — Editor para definir productos compuestos por secciones con reglas `min/max` de elecciones, suplementos opcionales por item, vigencia por fechas, días de la semana y franja horaria.
- **Front de venta (TPV)** — Flujo real de mesa → pedido → cobro → cierre, optimizado para táctil.
- **División de cuenta** — 3 estrategias: partes iguales, asignación por líneas, por comensal.
- **Cierre de caja** — Sesiones de turno, movimientos de caja, arqueo y generación de Z-Report con hash de integridad.
- **Dashboard de finanzas** — Prototipo funcional con métricas de ventas por período, producto estrella y evolución de ingresos.
- **Registro de Auditoría** — Traza inmutable de todas las operaciones críticas del negocio: 72 eventos instrumentados (auth, pedidos, caja, ventas, catálogo, mesas, configuración, restaurante). Hash SHA-256 encadenado por restaurante, detección de anomalías (ráfaga de PIN fallidos, descuadre de caja), alertas in-app, filtros server-side con paginación por cursor, live tail y vistas guardadas. Solo accesible para `admin`.
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

> Este paso es **obligatorio** para la primera puesta en marcha. Crea varios restaurantes de demo (incluido *Bar Manolo*) y los usuarios de prueba documentados en la sección 4.

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
docker compose exec api php artisan audit:archive-old --older-than-days=90 --dry-run  # Vista previa del archivado de audit logs >90 días
docker compose exec api php artisan audit:archive-old --older-than-days=90            # Ejecutar archivado (mueve a archived_at, nunca borra)
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

## 3. Testing

El proyecto se valida con tres suites complementarias: unitarios e integración del backend, tests de frontend, y end-to-end con Playwright contra el stack real (Docker + backend + frontend + MySQL seedeado). En conjunto suman **808 tests verdes** que cubren desde invariantes de dominio hasta el flujo completo TPV y todo el ciclo de retención de auditoría.

| Suite | Tests | Cómo correr |
|---|---|---|
| Backend (PHPUnit) | **784** (128 de auditoría) | `make test` |
| Frontend (Karma/Jasmine) | unit | `make test-frontend` |
| E2E (Playwright contra backend real) | **24** | `make test-e2e` |

### 3.1 Backend — PHPUnit

```bash
make test                                                # toda la suite
docker compose exec api php artisan test --filter=ChargeSessionEntityTest
docker compose exec api php artisan test --filter=AuditRetentionLifecycleTest
```

- 784 tests en verde, 0 deprecation warnings.
- **Unit**: entidades de dominio, Value Objects, validaciones de invariantes, cálculos (`AmountPerDiner`, hash de integridad del audit log), use cases con mocks (`GetArchivedAuditStats`, `ExportAuditEvents`, `ListAuditEvents`, `ArchiveOldAuditLogs`, `VerifyAuditChain`, `GetAuditEvent`, + CRUD `AuditSavedView`) y formatters byte-a-byte (`CsvAuditExportFormatter`, `NdjsonAuditExportFormatter`).
- **Feature**: endpoints HTTP con base de datos en contenedor, autenticación, permisos, casos non-happy path (404, 409, 422, 403), y el **lifecycle test de retención** (`AuditRetentionLifecycleTest`) que recorre archive → stats → export → verify chain en una sola historia para detectar regresiones en los bordes entre piezas.
- **Auditoría**: 128 tests específicos (28 feature + 84 unit domain + 16 unit `AuditSavedView`) que cubren listado con cursor, categorías, severidad, búsqueda, exportación CSV/NDJSON, archivado masivo, estadísticas de retención, verificación de cadena SHA-256, detector de anomalías (auth burst, caja mismatch), alertas y vistas guardadas.

### 3.2 Frontend — unit

```bash
make test-frontend
```

- Karma/Jasmine sobre componentes Angular.
- Enfoque en validación de formularios y lógica de cálculo de totales.

### 3.3 E2E — Playwright contra stack real

Los tests E2E ejecutan **flujos completos contra el sistema real** (Angular + Laravel + MySQL en Docker). Antes de cada suite se ejecuta `SaonaDemoSeeder` para dejar el estado conocido (Bar Manolo + 6 empleados con PIN + catálogo + 28 mesas). Para los tests de auditoría se usa además `RetentionDemoSeeder` + `audit:archive-old` para poblar el corpus archivado.

#### Quickstart

```bash
make start              # arranca Docker (api en :8000, frontend en :4200)
make test-e2e           # corre la suite completa (≈3 min, 24 tests)
make test-e2e-report    # abre el reporte HTML en http://localhost:9323
```

#### Targets disponibles

| Target | Para qué |
|---|---|
| `make test-e2e` | Suite completa |
| `make test-e2e-headed` | Ver el navegador mientras corre |
| `make test-e2e-ui` | Modo interactivo de Playwright |
| `make test-e2e-videos` | Fuerza vídeo + trace + screenshot en cada test (para demos / debug) |
| `make test-e2e-fast` | Salta el reseed (iteración rápida en desarrollo) |
| `make test-e2e-report` | Abre el HTML report del último run |

#### Cobertura actual

El plan E2E se desarrolló en 7 fases incrementales (detalle en [`PLAN_E2E.md`](PLAN_E2E.md)):

| Fase | Flujo cubierto | Tests |
|---|---|---|
| 1 | Vinculación de dispositivo (admin login + selección restaurante + quick-users) | 6 |
| 2 | Login PIN (operator/supervisor) + logout | 10 |
| 3 | Caja: apertura con fondo, movimiento de entrada, cierre con Z, cancelar cierre | 2 |
| 4 | Flujo central TPV: mesa → comanda → cerrar cuenta → cobro efectivo → mesa libre | 1 |
| 5 | Auditoría: el admin verifica los eventos generados por el flujo | 1 |
| 6 | Hardening: Makefile targets + README + troubleshooting | — |
| 7 | Auditoría — Historico: KPIs, chart, presets, export CSV, deep-link al registro vivo | 5 |

Para el detalle de qué hay cubierto y qué no (cobros variantes, modificadores requeridos, split bills, transferencias, etc.) ver [`PLAN_E2E.md`](PLAN_E2E.md). El fixture de auditoría usa `seedAndArchiveRetentionDemo()` definido en `frontend/e2e/support/audit.ts`, que ejecuta `RetentionDemoSeeder` + `audit:archive-old` + `cache:clear`.

#### Diseño de proyectos

`playwright.config.ts` define tres proyectos con dependencias para evitar conflictos sobre el estado compartido:

| Project | Specs | Paralelismo |
|---|---|---|
| `stateful` | `cash/**`, `tpv/**`, `audit/**` | serial (`workers: 1`, `fullyParallel: false`) |
| `chromium` | `auth/**`, `smoke/**` | paralelo, depende de `stateful` |
| `mobile-chrome` | `auth/**`, `smoke/**` | paralelo (Pixel 7), depende de `stateful` |

Los specs mutativos corren primero en `stateful` porque `cash_sessions` es único por restaurante+device y los tests de auditoría dependen de `RetentionDemoSeeder`. Los read-only (`auth`, `smoke`) corren después en paralelo cross-browser.

#### Ver vídeos y traces del flujo

Por defecto solo se guardan vídeos cuando un test falla. Para grabarlos siempre (útil para demos y para revisar visualmente lo que hace cada test):

```bash
make test-e2e-videos
make test-e2e-report
```

Cada test enseña **vídeo + screenshot + trace** en el HTML report. El **botón "Trace"** abre un timeline interactivo con el DOM, peticiones de red y consola en cada paso — es la herramienta más útil para debug.

#### Variables de entorno principales

Las más usadas (la lista completa está en [`frontend/e2e/README.md`](frontend/e2e/README.md)):

| Variable | Default | Para qué |
|---|---|---|
| `E2E_SKIP_SEED` | `unset` | Salta el reseed (iteración rápida) |
| `E2E_VIDEO` | `retain-on-failure` | `on` para grabar vídeo siempre |
| `E2E_TRACE` | `on-first-retry` | `on` para trace siempre |
| `E2E_BASE_URL` | `http://localhost:4200` | El backend solo permite CORS desde `localhost` (no `127.0.0.1`) |
| `E2E_SKIP_WEB_SERVER` | `unset` | Salta el `ng serve` que Playwright lanzaría — actívalo si Docker ya está corriendo |

---

## 4. Datos de demostración

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

## 5. Guía de uso de la aplicación

### 5.1 Vinculación de dispositivo

La primera vez que accedes a la aplicación desde un dispositivo nuevo, debes **vincularlo** a un restaurante antes de poder operar. Este paso es obligatorio y se realiza una sola vez por dispositivo.

#### Flujo de vinculación

1. **Pantalla de bienvenida** — Al abrir http://localhost:4200 por primera vez en el dispositivo, verás la pantalla de inicio con dos opciones: **"Vincular dispositivo"** y **"Acceder como desarrollador"**.
2. **Autenticación del administrador** — Toca **"Vincular dispositivo"** e introduce el **email y contraseña** del usuario con rol `admin` del restaurante que quieres vincular.
   > Solo los usuarios con rol `admin` pueden vincular dispositivos. Si introduces credenciales de otro rol, el sistema mostrará un error.
3. **Selección del restaurante** — Tras validar las credenciales, el sistema muestra la lista de restaurantes asociados a ese administrador. Selecciona el restaurante que quieres vincular al dispositivo.
4. **Confirmación** — El restaurante seleccionado se guarda de forma persistente en el almacenamiento local del dispositivo (`localStorage`). A partir de este momento, todas las operaciones del TPV estarán asociadas a ese restaurante.
5. **Acceso al login** — Una vez vinculado, el dispositivo redirige automáticamente a la pantalla de login para que los operadores puedan iniciar sesión con email/contraseña o PIN.

> Si el dispositivo ya está vinculado, la pantalla de bienvenida redirige automáticamente al login sin mostrar el selector.

### 5.2 Login y selección de rol

Al entrar en http://localhost:4200 verás la pantalla de login. Puedes autenticarte de dos formas:

1. **Email + Contraseña** — Para administradores y supervisores que gestionan el backoffice.
2. **Acceso rápido (PIN)** — Para camareros que operan el TPV. Más rápido en el día a día con tabletas compartidas.

### 5.3 Backoffice — Gestión del negocio

Desde el menú lateral, accede a **"Gestión"**. Esta sección está restringida a roles `admin` y `supervisor`.

- **Familias** — Categorías del catálogo. Ej: Bebidas, Entrantes, Carnes. Se pueden activar/desactivar sin borrarlas.
- **Productos** — Alta de artículos con nombre, precio, impuesto, familia, imagen y stock. Cada producto puede tener **modificadores** (ej: "sin cebolla", "doble queso", "extra de salsa") que se registran como notas en la línea de pedido.
- **Menús** — Productos compuestos por secciones que el comensal personaliza al pedir (combos, menú del día). Ver detalle en [4.9 Editor de Menús](#49-editor-de-menús--combos-y-menú-del-día).
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

### 5.4 TPV — Flujo de venta paso a paso

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

### 5.5 Cobro dividido — Casuística avanzada

El sistema soporta combinaciones de métodos dentro de una misma sesión de cobro (`ChargeSession`), con las siguientes reglas de negocio:

- **Si inicias por "Partes iguales"** — El sistema **bloquea** el cambio a "Por líneas" o "Por comensal" para evitar inconsistencias contables. Sigues con partes iguales hasta cubrir la deuda.
- **Si inicias por "Por líneas"** — Puedes asignar productos a comensales, cobrar uno a uno, y si queda un **restante** (gastos comunes no asignados), cambias a "Partes iguales" para dividir lo pendiente.
- **Toggle "Incluir comensales ya pagados"** — En el modo "Partes iguales", determina si la deuda restante se divide entre **todos** los comensales (incluso los que ya pagaron sus líneas) o solo entre los **pendientes**.
- **Pagos mixtos** — Un mismo comensal puede pagar parte en efectivo y parte con tarjeta. El sistema valida que la suma de pagos coincida con el total.

### 5.6 Caja — Sesiones de turno y Z-Report

- **Apertura de caja** — Al inicio del turno, el operador (o admin) abre una sesión de caja introduciendo el **fondo inicial** en efectivo.
- **Durante el turno** — Todos los cobros (`Sale`) y movimientos de caja (`CashMovement`: entradas de cambio, pagos a proveedores, sangrías, propinas) quedan vinculados a la sesión activa del dispositivo.
- **Arqueo** — Al cerrar, el sistema compara el efectivo contado (`final_amount_cents`) contra el efectivo teórico (`expected_amount_cents = fondo inicial + ventas en efectivo + entradas - salidas`). Si hay discrepancia, se exige un motivo.
- **Z-Report** — Tras confirmar el arqueo, el sistema genera el informe Z con:
  - Ventas totales desglosadas por método de pago (efectivo, tarjeta, Bizum, vale, invitación).
  - Movimientos de caja detallados.
  - Propinas declaradas.
  - Discrepancia detectada y justificación.
  - **Hash SHA-256** encadenado con el Z anterior para garantizar la integridad fiscal de la secuencia.

### 5.7 Dashboard de finanzas (prototipo)

Accesible desde el menú lateral para roles `admin` y `supervisor`. Muestra:

- **Ventas por período** — Selector de rango de fechas con gráfica de evolución diaria.
- **Producto estrella** — Artículo más vendido en el período seleccionado.
- **Ticket medio** — Importe promedio por venta.
- **Métodos de pago** — Distribución porcentual (pie chart) de efectivo vs tarjeta vs otros.
- **Top camareros** — Ranking de operadores por volumen de ventas.

> Estado actual: **Prototipo funcional**. Los datos no son reales, pero la interfaz está lista para integrar datos reales en el futuro.

### 5.8 Panel de Desarrollador (SuperAdmin) — Gestión de la plataforma

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

### 5.9 Editor de Menús — Combos y menú del día

Un **menú** es un producto compuesto: un nombre comercial (p. ej. *Menú del día*) con un precio base que el comensal personaliza al pedir eligiendo un producto por cada **sección** definida por el restaurador. El sistema lo modela como un dominio propio (`Menu` → `MenuSection` → `MenuItem`) y, al añadirlo a una comanda, se persiste como una sola línea de orden con las elecciones denormalizadas en JSON, lo que permite reconstruir lo que pidió cada comensal incluso si los productos del catálogo cambian después.

#### Modelo conceptual

```
Menu (cabecera)
└── MenuSection × N        (ej: "Primer plato", "Segundo plato", "Postre")
    ├── min_choices / max_choices  (reglas de elección por sección)
    └── MenuItem × N        (ej: "Sopa", "Ensalada", "Lomo")
        ├── product_id        (referencia al catálogo)
        ├── variant_id?       (opcional: forzar una variante concreta)
        └── extra_price       (suplemento sobre el precio base del menú)
```

#### Propiedades de la cabecera del menú

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | texto | Nombre comercial mostrado en TPV y en el ticket. |
| `description` | texto | Descripción libre opcional (uso interno y marketing). |
| `price` | céntimos | Precio base del menú (PVP con IVA incluido). Los `extra_price` de los items se suman al cobrar. |
| `tax_id` | UUID | Tipo de IVA aplicado al menú completo. |
| `validity_from` / `validity_to` | fecha (opcional) | Rango de vigencia. Fuera de él, el menú no se ofrece. |
| `available_days` | bitmask (7 bits) | Días de la semana en los que se ofrece (`L M X J V S D`). |
| `available_from_time` / `available_to_time` | hora `HH:mm` (opcional) | Franja horaria dentro del día (por ejemplo, sólo en comidas). |
| `active` | booleano | Permite ocultarlo del TPV temporalmente sin perder la configuración. |
| `archived` | booleano | Eliminación lógica. Los menús archivados no se editan ni se sirven. |

#### Propiedades de cada sección

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | texto | Título visible en el modal del TPV (p. ej. "Primer plato"). |
| `min_choices` | entero ≥ 0 | Mínimo de productos que el comensal debe elegir para confirmar. |
| `max_choices` | entero ≥ 1 | Máximo permitido (1 → comportamiento radio, >1 → selección múltiple). |
| `position` | entero | Orden en que se muestra la sección dentro del menú. |
| `items[]` | lista | Productos elegibles para esa sección (ver tabla siguiente). |

> Casos típicos: `min=1, max=1` ("Elige 1, obligatorio"), `min=0, max=1` ("Opcional"), `min=1, max=2` ("Elige 1 o 2").

#### Propiedades de cada item de sección

| Campo | Tipo | Descripción |
|---|---|---|
| `product_id` | UUID | Producto del catálogo ofrecido en esta sección. |
| `variant_id` | UUID nullable | Si se fija, el comensal recibe esa variante; si es null, podrá elegir entre las variantes activas del producto. |
| `extra_price` | céntimos (≥ 0) | Suplemento sumado al precio del menú si el comensal elige este item (p. ej. `+2,00€` por solomillo). |
| `position` | entero | Orden de aparición dentro de la sección. |

#### Flujo del restaurador — Crear / editar un menú

1. **Gestión → Menús** muestra la lista de menús del restaurante con su estado (Activo / Inactivo / Archivado), días, franja y un resumen "*N secciones · M items*".
2. **"Nuevo menú"** abre el editor de pantalla completa con tres bloques:
   - **Cabecera**: nombre, descripción, precio, IVA, vigencia, días y franja horaria.
   - **Secciones**: tarjetas reordenables (drag & drop) con su nombre y reglas `min/max`.
   - **Catálogo lateral**: lista de productos activos filtrable por familia y búsqueda. Cada producto se arrastra a la sección destino para añadirlo como `MenuItem`.
3. Dentro de cada item se puede fijar **variante** y **suplemento** (`extra_price`). El editor valida en tiempo real que cada sección tenga al menos un item y que `min_choices ≤ max_choices ≤ nº items`.
4. **Guardar** persiste el menú completo en una transacción (cabecera + secciones + items). El estado por defecto es **activo**.
5. Desde el listado se puede **activar/desactivar** con un toggle (sin borrar nada) o **archivar** definitivamente (las órdenes pasadas mantienen su referencia al `menu_id`).

#### Flujo del camarero — Añadir un menú a una comanda

1. En la página de **Comanda**, junto a la pestaña *Productos* aparece la pestaña **Menús**.
2. El grid muestra cards de los menús **activos y disponibles ahora** (validez, día, franja).
3. Al tocar un menú se abre el **modal de configuración** con todas las secciones y sus items.
4. Por cada sección, el camarero selecciona el/los productos pedidos por el comensal (la UI fuerza el cumplimiento de `min_choices`/`max_choices`). Para cada elección, si el producto tiene variantes o modificadores, se ofrecen como chips dentro del mismo modal.
5. Opcionalmente se añade una **nota** libre (alergias, "sin guarnición", etc.).
6. **El precio total** del menú se calcula en tiempo real: `precio base + Σ extra_price + Σ modificadores`.
7. Al confirmar, la línea **entra al cart local** (sección *Sin enviar*) junto a los productos sueltos. La comanda se manda al backend al pulsar **Enviar comanda**, no antes.

#### Persistencia en la orden y en la venta

- En `order_lines`, las líneas de menú no tienen `product_id` (`NULL`). En su lugar guardan `menu_id`, `menu_name` (snapshot del nombre) y `menu_selections` (JSON con `section_name`, `product_id`, `product_name`, `variant_id`, `variant_name`, `modifiers[]` y `extra_price` por cada elección del comensal).
- Al cobrar, se genera una `SaleLine` por línea de menú con `product_id = NULL`. Para la trazabilidad fiscal, la `OrderLine` original (que sí incluye las elecciones denormalizadas) queda vinculada vía `order_line_id`. El ticket final reconstruye el desglose del menú a partir de `menu_selections`.
- Toda esta denormalización es deliberada: si mañana se renombra un producto del catálogo, los tickets antiguos siguen mostrando el nombre con el que el comensal lo pidió.

#### Reglas y límites actuales

- Un menú debe tener **al menos una sección** y cada sección **al menos un item** para guardarse.
- Los menús archivados no se editan ni se ofrecen en el TPV, pero conservan integridad para órdenes históricas.
- El TPV no filtra todavía por `available_days` ni franja horaria en cliente (todos los menús activos se listan); el filtrado horario está disponible en el modelo y se aplicará en una iteración próxima.
- Las líneas de menú no se pueden fraccionar entre comensales por línea desde el split por líneas; sí participan del split por partes iguales.

---

### 5.10 Registro de Auditoría

Accesible desde el menú lateral en **"Auditoría"** (solo usuarios con rol `admin`). Es la traza operativa completa e inmutable del restaurante: quién hizo qué, cuándo, desde qué dispositivo, y qué cambió. Consta de **dos vistas principales**: el **Registro vivo** (eventos del turno activos) y el **Histórico** (panel de datos archivados con métricas de retención).

#### Qué se audita

El sistema instrumenta **72 tipos de eventos** distribuidos en 9 categorías: `auth`, `order`, `caja`, `sale`, `table`, `catalog`, `config`, `restaurant` y `system`. Ejemplos:

| Categoría | Eventos representativos |
|---|---|
| **Auth** | Login con email (éxito o fallo), login con PIN (éxito o fallo), vinculación de dispositivo, cambio de contraseña |
| **Pedidos** | Apertura, modificación de comensales, añadir producto/menú, borrar línea, transferencia, reapertura, marcado para cobro, eliminación |
| **Caja** | Apertura de turno, inicio de cierre, cancelación de cierre, cierre con Z-Report, movimiento de caja |
| **Ventas** | Creación de ticket, cancelación, nota de abono, sesión de cobro, pago registrado, líneas asignadas, modificación de comensales, cierre, ticket final, añadir línea |
| **Catálogo** | Alta/baja/modificación de productos, familias, menús, modificadores y variantes; cambio de precio |
| **Mesas** | Creación, modificación, unión, desunión, eliminación |
| **Config** | Alta/baja/modificación de usuarios, impuestos |
| **Restaurant** | Creación y modificación de restaurante |

#### Registro vivo (`/registro-auditoria`)

- **KPIs superiores** — Total de eventos hoy, críticos, usuarios activos y tiempo desde el último evento.
- **Tabs por categoría** — 9 pestañas (`Todo`, `Pedidos`, `Caja`, `Ventas`, `Mesas`, `Catálogo`, `Acceso`, `Config`, `Sistema`). El contador de cada tab refleja los eventos ya cargados.
- **Chips inteligentes** — Filtros rápidos de una pulsación: "Solo críticos", "Mis acciones", "Última hora", "Movimientos de caja", "Cancelaciones", "Reaperturas", "Fallos de acceso", "Transferencias".
- **Filtros avanzados** — Severidad (info / warning / danger / critical / success), usuario, dispositivo y rango de fechas. Todos aplican en servidor con debounce.
- **Búsqueda** — Campo de texto libre con `LIKE` en `action`, `summary` e `entity_id` (mínimo 2 caracteres).
- **Scroll infinito** — Paginación por cursor opaco (`next_cursor`). Cada lote carga hasta 50 eventos.
- **Live tail** — Toggle "Live". Cada 5 segundos consulta eventos nuevos posteriores al primero visible y los inserta en la cabecera de la lista.
- **Mostrar histórico** — Toggle que incluye eventos archivados en el listado (envía `include_archived=1`). Al activarlo, aparece un banner informativo sobre la política de retención (90 días activo → archivado → conservación legal indefinida).
- **Exportar** — Botón que descarga los eventos visibles (según filtros actuales) en formato **CSV** (RFC-4180, UTF-8 BOM, CRLF, compatible con Excel). Al exportar se registra un meta-evento `audit.exported` con el recuento y filtros usados.
- **Drawer de detalle** — Al tocar un evento se abre un panel lateral con:
  - Metadata completa (acción, entidad, usuario, dispositivo, IP, sesión).
  - Diferencia estructurada (`before → after`) cuando la acción modifica datos.
  - Payload JSON completo con botón de copiar al portapapeles.
  - **Hash de integridad SHA-256** con indicador visual "Verificado". Si el hash no coincide con la cadena recalculada, se muestra advertencia.

#### Histórico de retención (`/registro-auditoria/historico`)

Panel independiente que muestra el **corpus archivado** del restaurante. Se accede como una pestaña diferenciada dentro de Auditoría:

- **KPIs de retención** — 4 tarjetas: total archivado, rango temporal (primer y último evento archivado), mes pico y media mensual.
- **Gráfico de barras mensual** — Distribución de eventos archivados mes a mes con barras verticales y etiqueta de recuento.
- **Filtros por rango temporal** — Presets (`Todo el histórico`, `Último año`, `Último trimestre`, `Último mes`) y selector de fechas personalizado con botón "Aplicar". Los presets activos muestran un banner informativo y el botón se marca visualmente.
- **Exportación** — Menú desplegable con dos formatos:
  - **CSV** (RFC-4180, UTF-8 BOM, CRLF) — compatible con Excel.
  - **NDJSON** (una línea JSON por evento) — ideal para integraciones y pipelines de datos.
  - La exportación incluye archivados y queda registrada en la propia auditoría como meta-evento.
- **Deep-link** — Botón "Abrir registro con histórico" que navega al registro vivo con el parámetro `?historico=1`, activando automáticamente el toggle "Mostrar histórico".
- **Estado vacío** — Cuando el filtro no produce resultados, se muestra un mensaje con la política de retención: 90 días activo → archivado → conservación legal indefinida.
- **Estados de carga y error** — Skeleton cards durante carga y panel de error con botón de reintentar.

#### Vistas guardadas

En la barra de filtros del registro vivo hay un dropdown "Vistas" con opciones predefinidas (Críticos del turno, Mis reaperturas, Cuadres con discrepancia, Fallos de acceso 24h) y la capacidad de **guardar la configuración actual de filtros** con un nombre personalizado. Las vistas persisten en la base de datos por restaurante (tabla `audit_saved_views`) y se pueden eliminar desde el mismo dropdown. CRUD completo: crear, listar, aplicar, eliminar.

#### Alertas de anomalías

El detector de anomalías marca automáticamente eventos sospechosos:

- **`auth_failed_burst`** — 3 o más intentos de PIN fallidos del mismo usuario en 5 minutos.
- **`caja_mismatch`** — Cierre de caja con descuadre (diferencia entre efectivo contado y teórico).

Cada anomalía genera una **alerta in-app** accesible desde el icono de campana en la barra superior. Muestra un badge con el número de alertas no leídas. Al tocar una alerta, el sistema:
1. La marca como leída.
2. Si la alerta está vinculada a un evento de auditoría, carga ese evento en el drawer y hace scroll suave hasta él con un efecto visual de pulso.

El polling de alertas es cada 30 segundos. También disponible `POST /api/admin/audit-alerts/read-all` para marcar todas como leídas.

#### Verificación de cadena de integridad

`GET /api/admin/audit-log/verify` recorre **todos los eventos** del restaurante (activos y archivados) y para cada uno:

1. Reconstruye el hash SHA-256 a partir de `prevHash + uuid + restaurantUuid + createdAt + actionSlug + entityType + entityId + userUuid + summary + canonicalJSON(metadata) + canonicalJSON(before) + canonicalJSON(after)`.
2. Compara con el `integrity_hash` almacenado.
3. Si algún eslabón no coincide, lo reporta como evento corrupto.

Devuelve `is_valid`, `total_events`, `verified_count` y el listado de eventos rotos.

#### Cómo acceder

```
1. Iniciar sesión con un usuario admin (ej: Manolo / PIN 1234)
2. En el menú lateral, tocar "Auditoría"
3. Se carga el registro con los eventos de hoy por defecto
4. Usar tabs, chips, filtros o búsqueda para navegar
5. Tocar cualquier evento para ver su detalle completo
6. Guardar una combinación de filtros como "Vista" para recuperarla luego
7. Explorar el Histórico para ver KPIs de retención, gráfico mensual y exportar archivados
```

> **Nota:** La auditoría no tiene interfaz de escritura. Los eventos se insertan automáticamente desde los casos de uso del backend tras cada operación exitosa (o fallo, en el caso de login PIN erróneo). El hash de integridad se calcula dentro de una transacción con `FOR UPDATE` sobre la cadena del restaurante, garantizando secuencialidad.

#### Retención de audit logs

El sistema implementa una política de retención basada en **archivado lógico** (nunca borrado físico) alineada con la legislación española:

| Período | Estado | Comportamiento en UI/API |
|---------|--------|--------------------------|
| Día 0 – 90 | Activo (`archived_at IS NULL`) | Visible por defecto. Aparece en el listado estándar. |
| Día 90 – 6 años | Archivado (`archived_at` con fecha) | Solo visible para `admin` activando el toggle **"Mostrar histórico"** (envía `include_archived=1`) o desde el panel **Histórico**. Incluido en la verificación de cadena de hash. |
| +6 años | Archivado | Misma política que 90d–6a. Se conserva indefinidamente. |

**Fundamento legal:**
- **Código de Comercio (Art. 30):** obligación de conservar libros, correspondencia y justificantes durante **6 años**.
- **LGT (Art. 66):** prescripción del derecho de la Administración para determinar la deuda tributaria — **4 años**.
- **TicketBAI / VeriFactu:** exigen conservación íntegra de todos los registros de facturación durante el período legal aplicable.

**Nunca se borran registros.** El comando `audit:archive-old` mueve eventos antiguos a estado archivado (establece `archived_at`) pero nunca ejecuta `DELETE`. Esto garantiza trazabilidad histórica completa y cumplimiento normativo sin pérdida de datos.

**Comando de archivado programado:**

```bash
# Vista previa (no modifica nada)
docker compose exec api php artisan audit:archive-old --older-than-days=90 --dry-run

# Archivado real (mueve a archived_at)
docker compose exec api php artisan audit:archive-old --older-than-days=90

# Filtrar por restaurante específico
docker compose exec api php artisan audit:archive-old --restaurant-uuid=<uuid>
```

El comando acepta `--restaurant-uuid` para restringir el alcance y `--dry-run` para simulación. Está registrado en el scheduler semanal (`bootstrap/app.php`) para ejecutarse automáticamente cada domingo a las 3:00 AM. Al archivar, emite un meta-evento `audit.archived` con el resumen de cuántos registros se archivaron por restaurante.

**Seeders de demostración:**
- `AuditLogSeeder` — 50 eventos por restaurante con templates realistas (últimos 7 días) y cadena de hash real.
- `RetentionDemoSeeder` — 40 eventos backdated (365–95 días) + 5 recientes para Bar Manolo, usado por los tests E2E del panel histórico. Idempotente (limpia y reinserta).

---

## 6. Características implementadas

### Hitos del proyecto

| Hito | Estado | Alcance |
|---|---|---|
| **Hito 1 — Modelo de datos** | 100% | 20+ migraciones, 14 tablas principales, soft deletes, índices optimizados, shard key `restaurant_id` en todas las entidades. |
| **Hito 2 — API REST Backoffice** | 100% | CRUD completo de familias, impuestos, productos, zonas, mesas y usuarios. Auth dual (email/password + PIN de 4 dígitos). SuperAdmin con gestión multi-restaurante. |
| **Hito 3 — Interfaz Backoffice** | 100% | Panel de gestión con ~1.600 líneas de componentes Angular. Formularios reactivos, validación en tiempo real, toasts de confirmación. |
| **Hito 4 — Front de Venta (TPV)** | 100% | Flujo completo: mesas → apertura → pedido → cobro → cierre. Soporte para pagos parciales, división de cuenta (3 modos), y cierre de caja con Z-Report. |
| **Hito 5 — Informes (Dashboard)** | 40% | Prototipo funcional con métricas clave. Pendiente: exportación a PDF/Excel, filtros avanzados, predicciones. |
| **Hito 6 — Auditoría y trazabilidad** | 100% | Registro de Auditoría con 72 slugs instrumentados, cadena de hash SHA-256, detección de anomalías, alertas in-app, vistas guardadas, paginación por cursor, live tail, exportación CSV/NDJSON, panel **Histórico** con KPIs de retención, gráfico mensual, presets de rango temporal y deep-link al registro vivo. Archivado por antigüedad (90d → `archived_at`, retención legal 6 años, nunca borrado) y toggle "Mostrar histórico" con `include_archived=1`. Solo acceso `admin`. Verificación de cadena con `GET /api/admin/audit-log/verify`. |
| **Hito 7 — Mejoras operativas** | 80% | Roles, PIN, quick access, vinculación de dispositivo, multi-tenancy, productos con modificadores. |

### Funcionalidades detalladas

| Módulo | Feature | Descripción técnica |
|---|---|---|
| **Auth** | Login dual | Sistema de autenticación con JWT que soporta tanto email+password como PIN numérico de 4 dígitos. |
| **Auth** | Quick Access | Lista de usuarios frecuentes en la pantalla de PIN para acceso inmediato sin escribir credenciales. |
| **Auth** | Roles y permisos | 3 roles (`admin`, `supervisor`, `operator`) con guardas de navegación (`CanActivate`) en el frontend y middlewares de autorización en el backend. |
| **Producto** | Modificadores | Cada producto puede tener opciones de personalización que se almacenan en `order_lines.notes` (ej: "sin cebolla", "extra queso"). |
| **Producto** | Stock | Control de inventario básico con decremento automático al cerrar venta. |
| **Menú** | Editor visual | Editor drag & drop de menús con secciones reordenables, catálogo lateral filtrable y validación en tiempo real de reglas `min/max`. |
| **Menú** | Vigencia y disponibilidad | Cabecera con `validity_from/to`, bitmask de `available_days` y franja `available_from_time/available_to_time` para activar el menú sólo en su ventana real. |
| **Menú** | Suplementos por item | Cada `MenuItem` puede llevar un `extra_price` que se suma al precio base del menú si el comensal lo elige. |
| **Menú** | Snapshot de elecciones | La línea de orden de un menú guarda en JSON el `menu_name`, los `menu_selections` y sus variantes/modificadores, garantizando que los tickets antiguos no se "rompan" al renombrar productos del catálogo. |
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
| **Auditoría** | Exportación CSV/NDJSON | Stream de eventos (activos o archivados) en CSV (RFC-4180, UTF-8 BOM, CRLF) o NDJSON (una línea JSON por evento). Se registra meta-evento `audit.exported`. |
| **Auditoría** | Panel Histórico | KPIs de retención (total archivado, rango temporal, mes pico, media mensual), gráfico de barras mensual, presets de rango temporal y selector de fechas personalizado. |
| **Auditoría** | Deep-link histórico | Botón "Abrir registro con histórico" que navega al registro vivo con `?historico=1` activando el toggle "Mostrar histórico". |
| **Auditoría** | Verificación de cadena | `GET /api/admin/audit-log/verify` que recorre todos los eventos (activos + archivados) y verifica la integridad SHA-256 de cada eslabón. |
| **Auditoría** | Traza inmutable | 72 eventos instrumentados en 9 categorías. Cada evento almacena `before/after`, metadata, IP y device. Hash SHA-256 encadenado por restaurante para garantizar integridad. |
| **Auditoría** | Detección de anomalías | Reglas server-side: `auth_failed_burst` (≥3 fallos PIN en 5 min) y `caja_mismatch` (descuadre en cierre). Se marcan en el evento y generan alerta. |
| **Auditoría** | Alertas in-app | Tabla `audit_alerts` con notificaciones por anomalía. Dropdown con badge de no leídas, navegación directa al evento vinculado, polling 30s. |
| **Auditoría** | Vistas guardadas | Persistencia de combinaciones de filtros por restaurante (`audit_saved_views`). CRUD completo: crear, listar, aplicar, eliminar. |
| **Auditoría** | Live tail | Polling cada 5s para insertar eventos nuevos en la cabecera de la lista sin perder el scroll. |
| **Auditoría** | Paginación por cursor | Cursor opaco base64 sobre `(created_at DESC, id DESC)` para evitar desplazamiento de páginas ante inserts concurrentes. |
| **Auditoría** | Archivado por antigüedad | Comando `audit:archive-old` mueve eventos >90 días a `archived_at`. Semanal en scheduler. Emite meta-evento `audit.archived`. Nunca borra. |
| **Auditoría** | Toggle histórico | Flag `include_archived=1` en la UI para que el admin vea eventos archivados. Banner informativo con la política de retención. |
| **Auditoría** | Verificación de cadena con archivados | `GET /api/admin/audit-log/verify` lee también filas archivadas. La cadena SHA-256 sigue siendo íntegra tras archivar. |
| **Dispositivo** | Identificación única | El interceptor HTTP genera y envía `X-Device-Id` (UUID v4 persistente en `localStorage` o `environment.devDeviceId`). Backend captura device + IP en cada evento de auditoría. |
| **Multi-tenant** | Shard key | `restaurant_id` en todas las tablas. Un solo backend sirve a N restaurantes con aislamiento de datos. |
| **SuperAdmin** | Gestión de plataforma | Dominio separado para crear restaurantes y gestionar la infraestructura global. |
| **PDA** | Prototipo de interfaz | Diseño preliminar de la PDA (Punto de Atención Digital) para operadores de sala. Incompleto; se desarrollará en fase posterior. |

---

## 7. Arquitectura

### 7.1 Stack tecnológico

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

### 7.2 Patrón arquitectónico — DDD + Hexagonal

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

### 7.3 Dominios implementados

| Dominio | Entidades principales | Responsabilidad |
|---|---|---|
| `Shared` | `Uuid`, `DomainDateTime`, `Email` | Value Objects reutilizables entre dominios |
| `User` | `User`, `UserName`, `PasswordHash`, `UserRole`, `Pin` | Gestión de empleados del restaurante |
| `SuperAdmin` | `SuperAdmin`, `Restaurant` (gestión) | Administración de la plataforma multi-tenant |
| `Restaurant` | `Restaurant`, `RestaurantName` | Datos fiscales y de contacto del negocio |
| `Family` | `Family`, `FamilyName` | Categorías del catálogo |
| `Product` | `Product`, `Price`, `Stock` | Artículos del menú con precio, impuesto, imagen y modificadores |
| `Menu` | `Menu`, `MenuSection`, `MenuItem`, `MenuValidity`, `MenuAvailability` | Productos compuestos con secciones, reglas de elección y ventana de disponibilidad |
| `Tax` | `Tax`, `TaxPercentage` | Tipos de IVA aplicables |
| `Zone` | `Zone`, `ZoneName` | Salones del local |
| `Table` | `Table`, `TableName` | Mesas físicas con soporte de agrupación |
| `Order` | `Order`, `OrderLine`, `DinerNumber` | Pedidos abiertos (estado mutable hasta el cierre) |
| `Sale` | `Sale`, `SaleLine`, `SalePayment` | Documentos fiscales inmutables (ticket, factura, nota de abono) |
| `Cash` | `CashSession`, `CashMovement`, `ZReportHash` | Sesiones de caja, arqueo e informes fiscales |
| `Audit` | `AuditLog`, `AuditEventCatalog`, `AnomalyDetector`, `AuditChainHasher` | Traza inmutable de operaciones con hash encadenado y detección de anomalías |
| `AuditSavedView` | `AuditSavedView` | Persistencia de combinaciones de filtros del Registro de Auditoría |
| `ChargeSession` | `ChargeSession`, `ChargeSessionPayment`, `AmountPerDiner` | División de cuenta por comensales |

### 7.4 Flujo de una petición (arquitectura en acción)

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

### 7.5 Decisiones técnicas clave

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

## 7.6 Seguridad

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
- **Registro de Auditoría con hash encadenado** — Cada evento de auditoría almacena `integrity_hash = SHA-256(prev_hash + uuid + restaurant_id + created_at + action + entity_type + entity_id + user_id + summary + before + after + metadata)`. La cadena es **por restaurante** y se computa dentro de una transacción con `SELECT ... FOR UPDATE`, garantizando secuencialidad. Endpoint `GET /api/admin/audit-log/verify` recalcula la cadena entera y reporta filas rotas.
- **Identificación de dispositivo** — El interceptor HTTP del frontend genera un `X-Device-Id` UUID persistente en `localStorage` y lo envía en cada petición. El backend captura este header junto a la IP (`$request->ip()`), permitiendo saber exactamente desde qué terminal y dirección se originó cada operación auditada.
- **Soft deletes** — Nunca se pierde información histórica. Un producto eliminado sigue referenciado en las líneas de venta antiguas.
- **Sin datos sensibles en logs** — Contraseñas, PINs y tokens nunca se registran en los logs de Laravel.

---

## 8. Estructura del repositorio

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
│   │   │   ├── dashboard/                  # Prototipo de finanzas (métricas y gráficas)
│   │   │   └── registro-auditoria/         # Registro de Auditoría: filtros, live tail, drawer, alertas
│   │   │       └── facades/
│   │   │           └── registro-auditoria.facade.ts  # Estado reactivo con Signals
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
│   │   ├── services/                       # Servicios globales (Auth, AuditLog, AuditAlert, Restaurant)
│   │   │   ├── audit-log.service.ts        # API de auditoría: list, get, saved views CRUD
│   │   │   └── audit-alert.service.ts      # API de alertas: list, mark read, mark all read
│   │   ├── core/
│   │   │   └── http/
│   │   │       └── interceptor.ts          # Prefija API URL, JWT, X-Device-Id, X-Restaurant-Id
│   │   ├── providers/
│   │   │   └── interceptor.ts              # Legacy — migrando a core/http/interceptor.ts
│   │   └── guards/                         # CanActivate por rol (admin, supervisor, operator)
│   ├── src/environments/
│   ├── angular.json
│   └── package.json
├── docs/
│   ├── CAJA_DESIGN.md                      # Especificación funcional completa del módulo Caja
│   ├── DOMINIO_TPV.md                      # Reglas de diseño de APIs TPV en hostelería
│   └── registro-auditoria-plan.md          # Plan técnico de implementación del módulo Auditoría (hitos, decisiones, cobertura)
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

## 9. API REST — Endpoints principales

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
GET|POST       /api/management/menus           # Listar / crear menús del restaurante
GET|PUT        /api/management/menus/{uuid}    # Editar cabecera, secciones e items
POST           /api/management/menus/{uuid}/archive # Archivado lógico (no editable)
```

### TPV (requiere sesión autenticada)

```
GET    /api/tpv/zones                # Zonas del restaurante
GET    /api/tpv/tables               # Mesas del restaurante
GET    /api/tpv/families            # Familias activas
GET    /api/tpv/products            # Productos activos
GET    /api/tpv/menus               # Menús activos disponibles para la comanda
POST   /api/tpv/orders              # Crear orden (abrir mesa)
GET    /api/tpv/orders              # Listar órdenes abiertas
GET    /api/tpv/orders/{id}         # Detalle de orden
POST   /api/tpv/orders/lines        # Añadir línea de producto a orden
POST   /api/tpv/orders/menu-lines   # Añadir línea de menú a orden (con sus selecciones)
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

### Auditoría (requiere rol `admin`)

```
GET    /api/admin/audit-log               # Listar eventos (cursor, filtros, since)
GET    /api/admin/audit-log/{uuid}        # Detalle de un evento
GET    /api/admin/audit-log/verify        # Verificar cadena de hash por restaurante
GET    /api/admin/audit-saved-views       # Listar vistas guardadas
POST   /api/admin/audit-saved-views        # Crear vista guardada
PATCH  /api/admin/audit-saved-views/{uuid} # Actualizar vista
DELETE /api/admin/audit-saved-views/{uuid} # Eliminar vista
GET    /api/admin/audit-alerts            # Listar alertas de anomalías + unread_count
POST   /api/admin/audit-alerts/read-all   # Marcar todas las alertas como leídas
POST   /api/admin/audit-alerts/{uuid}/read # Marcar una alerta como leída
```

> Documentación completa de request/response en los controladores de `backend/app/<Dominio>/Infrastructure/Entrypoint/Http/`.

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
| [`MENU_FEATURE_README.md`](./MENU_FEATURE_README.md) | Equipo de desarrollo | Detalle técnico del módulo Menús: estructura del dominio, migraciones, casos de uso, integración con OrderLine/SaleLine y áreas de mejora pendientes |
| [`docs/registro-auditoria-plan.md`](./docs/registro-auditoria-plan.md) | Tech Lead / Arquitecto | Plan técnico completo del módulo Auditoría: decisiones de diseño (A–H), hitos 1–5, cobertura de 45 slugs instrumentados, backend DDD + frontend Signals |

---

## 12. Próximos pasos y roadmap técnico

### Corto plazo (1–2 meses)

1. **Dashboard de finanzas v2** — Evolucionar el prototipo actual a panel completo: exportación PDF/Excel, comparativa intermensual, predicción de stock.
2. **Impresión de tickets** — Integración con impresoras térmicas ESC/POS para ticket de cocina y ticket de cliente.
3. **WebSockets** — Sincronización en tiempo real del estado de mesas entre múltiples tabletas del mismo restaurante.

### Medio plazo (3–6 meses)

4. **Descuentos y promociones** — Descuento por línea (% o importe fijo), descuento global en ticket, cupones y promociones temporales (los menús del día con precio cerrado ya están operativos, ver [4.9](#49-editor-de-menús--combos-y-menú-del-día)).
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
> **Última actualización:** Junio 2026
