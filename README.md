# YurestIonic — TPV Profesional para Hostelería

> **Versión:** Demo funcional — Junio 2026 (actualizado 19-jun-2026)  
> **Stack:** Laravel 12 (backend) + Angular 19 + Ionic (frontend) + Laravel Reverb (WebSockets)  
> **Arquitectura:** DDD + Hexagonal + Multi-tenant + Bus de eventos síncrono  
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
    - [5.11 Subida de foto por QR](#511-subida-de-foto-por-qr)
    - [5.13 Vista de plano de mesas (TPV)](#513-vista-de-plano-de-mesas-tpv)
    - [5.14 Editor de plano de sala (Backoffice)](#514-editor-de-plano-de-sala-backoffice)
    - [5.15 Autoservicio QR — Carta digital para comensales](#515-autoservicio-qr--carta-digital-para-comensales)
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

El producto está pensado para desplegarse en tabletas táctiles como dispositivo principal de los camareros, con autenticación por PIN para acceso rápido, sincronización de estado de mesas en tiempo real entre terminales vía **WebSockets (Laravel Reverb)**, y una arquitectura backend preparada para escalar horizontalmente a múltiples restaurantes bajo un mismo despliegue (multi-tenancy). El backend sigue estrictamente **DDD + Hexagonal** con un **bus de eventos síncrono** que desacopla auditoría, broadcasting y efectos secundarios de la lógica de negocio.

### Alcance actual

- **Bus de eventos síncrono** — `InMemorySyncEventBus` + `EventSubscriber`; auditoría y broadcasting como efectos secundarios desacoplados de los casos de uso.
- **Backoffice completo** — Gestión de familias (con color e icono), productos, impuestos, zonas, mesas, usuarios y roles.
- **Menús (combos / menú del día)** — Editor para definir productos compuestos por secciones con reglas `min/max` de elecciones, suplementos opcionales por item, vigencia por fechas, días de la semana y franja horaria.
- **Front de venta (TPV)** — Flujo real de mesa → pedido → cobro → cierre, optimizado para táctil. **Tiempo real multi-terminal** vía WebSockets: apertura, comanda, cobro, unión/separación de mesas se sincronizan entre dispositivos sin recargar.
- **División de cuenta** — 3 estrategias: partes iguales, asignación por líneas, por comensal.
- **Cierre de caja** — Sesiones de turno, movimientos de caja, arqueo y generación de Z-Report con hash de integridad.
- **Dashboard de finanzas** — Prototipo funcional con métricas de ventas por período, producto estrella y evolución de ingresos.
- **Registro de Auditoría** — Traza inmutable de todas las operaciones críticas del negocio: 72 eventos instrumentados (auth, pedidos, caja, ventas, catálogo, mesas, configuración, restaurante). Hash SHA-256 encadenado por restaurante, detección de anomalías (ráfaga de PIN fallidos, descuadre de caja), alertas in-app, filtros server-side con paginación por cursor, live tail y vistas guardadas. Solo accesible para `admin`.
- **Panel de desarrollador (SuperAdmin)** — Gestión de la plataforma multi-tenant: alta de restaurantes, administración de superadmins y control global del sistema.
- **Módulo Autoservicio QR** *(en desarrollo — Sprint 1 completado)* — Carta digital para comensales: escanean el QR de la mesa con el móvil, abren la mesa ellos mismos, piden por rondas y solicitan la cuenta. El camarero recibe todo en tiempo real en el TPV vía Reverb. Sprint 1 completado: infraestructura de tokens QR, tablas de sesiones guest, endpoint `GET /public/table/{token}` y generación/regeneración de tokens desde el admin. Ver `AUTOSERVICIO_PLAN.md` para el plan completo.
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
| `training_api` | `8000` | Laravel 12 (PHP artisan serve) |
| `training_reverb` | `8080` | Laravel Reverb (servidor WebSocket) |
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

El proyecto se valida con tres suites complementarias: unitarios e integración del backend, tests de frontend, y end-to-end con Playwright contra el stack real (Docker + backend + frontend + MySQL seedeado). En conjunto suman **más de 1.100 tests verdes** que cubren desde invariantes de dominio hasta el flujo completo TPV, tiempo real multi-terminal y todo el ciclo de retención de auditoría.

| Suite | Tests | Cómo correr |
|---|---|---|
| Backend (PHPUnit) | **1054** (167 de auditoría + 17 broadcast + 4 merge/unmerge RT) | `make test` |
| Frontend (Karma/Jasmine) | **29** | `make test-frontend` |
| E2E (Playwright contra backend real) | **29** | `make test-e2e` |

### 3.1 Backend — PHPUnit

```bash
make test                                                # toda la suite
docker compose exec api php artisan test --filter=ChargeSessionEntityTest
docker compose exec api php artisan test --filter=AuditRetentionLifecycleTest
```

- 1054 tests en verde, 0 deprecation warnings.
- **Unit**: entidades de dominio, Value Objects, validaciones de invariantes, cálculos (`AmountPerDiner`, hash de integridad del audit log), use cases con mocks (`GetArchivedAuditStats`, `ExportAuditEvents`, `ListAuditEvents`, `ArchiveOldAuditLogs`, `VerifyAuditChain`, `GetLatestVerifyResult`, `GetAuditEvent`, + CRUD `AuditSavedView`) y formatters byte-a-byte (`CsvAuditExportFormatter`, `NdjsonAuditExportFormatter`).
- **Feature**: endpoints HTTP con base de datos en contenedor, autenticación, permisos, casos non-happy path (404, 409, 422, 403), y el **lifecycle test de retención** (`AuditRetentionLifecycleTest`) que recorre archive → stats → export → verify chain en una sola historia para detectar regresiones en los bordes entre piezas.
- **Auditoría**: 167 tests específicos que cubren listado con cursor, categorías, severidad, búsqueda, exportación CSV/NDJSON, archivado masivo, estadísticas de retención (incluido el desglose por categoría, top usuarios y anomalías del panel histórico), verificación de cadena SHA-256, persistencia de resultado de verificación, detector de anomalías (auth burst, caja mismatch), alertas y vistas guardadas.

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
| 7 | Auditoría — Historico: KPIs, chart, presets, export CSV, deep-link, verify card, categoría y usuario drill-down | 8 |
| 8 | Tiempo real de mesas: abrir mesa en A → B actualiza sin reload; marcar cobrar + cobrar en A → B refleja cambio de estado en ≤8s | 2 |

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

- **Familias** — Categorías del catálogo. Ej: Bebidas, Entrantes, Carnes. Se pueden activar/desactivar sin borrarlas. Cada familia tiene **color** (hex #RRGGBB) e **icono** (set Material) configurables desde el backoffice; el acento de color y el icono se reflejan en las pestañas del TPV para identificación visual rápida.
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

> **Tiempo real multi-terminal:** El estado de las mesas se sincroniza automáticamente entre todos los dispositivos del restaurante vía WebSockets (Laravel Reverb). Cualquier operación —abrir mesa, añadir comanda, marcar para cobrar, cobrar, unir o separar mesas— se refleja en todos los terminales en menos de 2 segundos sin necesidad de recargar la página.

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

### 5.7 Dashboard de finanzas

Accesible desde el menú lateral para roles `admin` y `supervisor`. Consta de 7 tabs con datos reales servidos por el dominio `Reporting` del backend (endpoints propios bajo `/api/admin/reports/`):

- **Resumen** — KPIs del período: ingresos totales, ticket medio, tickets servidos, propinas. Mesas activas en tiempo real. Sparkline 14 días. Top 3 métodos de pago.
- **Ventas** — Tabla paginada de tickets con detalle de líneas, método de pago y anulaciones. Heatmap día×hora de intensidad de ventas. Filtros por estado.
- **Productos** — Ranking de artículos por ingresos con tendencia 14 días (badge "Nuevo" si sin historial), ventas por familia, stock crítico/sin ventas, rendimiento por zona.
- **Empleados** — Cards por empleado con spark de tendencia, panel de detalle con KPIs (ingresos, ticket medio, propinas, descuentos), gráfico de tendencia 14 días. Solo aparecen empleados con ventas en el período seleccionado.
- **Caja** — Vista de sesiones de caja activas y cerradas con resumen de movimientos y arqueo.
- **Impuestos** — Pendiente: desglose IVA por tipo, base imponible, cuota y resumen trimestral (Modelo 303).
- **Informes** — Pendiente: descarga CSV/PDF de todos los reportes anteriores.

El estado reactivo del dashboard se gestiona en `FinanzasFacade` con Angular Signals + `toObservable(_period).pipe(switchMap(...))` para recarga automática al cambiar el período seleccionado. Skeleton loaders en todos los tabs.

> El detalle técnico de implementación y decisiones de diseño está en [`DASHBOARD_FINANZAS_PLAN.md`](./DASHBOARD_FINANZAS_PLAN.md).

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
- **Badge de integridad de cadena** — Bloque destacado entre KPIs y gráfico que invoca `GET /admin/audit-log/verify` bajo demanda y muestra 5 estados visuales: no verificado (gris), verificando (azul con spinner), íntegra (verde), rota (rojo con recuento de corruptos) o error (naranja). El resultado persiste en servidor (`audit_chain_verifications`), accesible vía `GET /admin/audit-log/verify/latest`, para que cualquier inspector o dispositivo vea el estado canónico de la cadena. Punto único de prueba de compliance: un golpe de vista basta para confirmar que el corpus archivado mantiene la cadena SHA-256.
- **Gráfico de barras mensual interactivo** — Distribución de eventos archivados mes a mes con etiqueta de recuento siempre visible encima de la barra. Cada barra es un `<button>` accesible: hover con lift y saturación, focus visible, click → drill-down al registro vivo filtrado por ese mes (`/registro-auditoria?historico=1&dateFrom=YYYY-MM-01&dateTo=YYYY-MM-LL`).
- **Desglose por categoría** — Tarjeta con lista de categorías (auth, order, caja, sale, table, catalog, config, restaurant, system) ordenadas por recuento descendente, cada una con su propia barra horizontal proporcional y paleta de color. Click en una fila → drill-down al registro vivo filtrado por esa categoría con el rango activo del panel.
- **Top usuarios del corpus** — Tarjeta con los 5 usuarios más activos del corpus archivado: rank `#1`–`#5`, avatar con iniciales tintado por rol (`admin` rojo, `supervisor` azul, `operator` verde), nombre, rol y recuento en pill mono. Click → drill-down al registro vivo filtrado por usuario.
- **Filtros por rango temporal** — Presets (`Todo el histórico`, `Último año`, `Último trimestre`, `Último mes`) y selector de fechas personalizado con botón "Aplicar". Todos los widgets del panel (KPIs, chart, desgloses) respetan el rango activo y se recalculan en conjunto.
- **Exportación** — Menú desplegable con dos formatos:
  - **CSV** (RFC-4180, UTF-8 BOM, CRLF) — compatible con Excel.
  - **NDJSON** (una línea JSON por evento) — ideal para integraciones y pipelines de datos.
  - La exportación incluye archivados y queda registrada en la propia auditoría como meta-evento.
- **Deep-link al registro vivo** — Botón "Abrir registro" en el CTA del pie del panel que navega al registro vivo con `?historico=1` + el rango activo del panel como `dateFrom`/`dateTo`. En el registro vivo aparece entonces un **banner morado "Vista del histórico"** con el rango aplicado y un botón "Volver a registro normal" que limpia los filtros y los query params. En modo histórico el **live tail se desactiva automáticamente** (los archivados son inmutables) y el botón se oculta.
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
- `RetentionDemoSeeder` — 40 eventos backdated (365–95 días) + 5 recientes para Bar Manolo, atribuidos en round-robin a los empleados del restaurante para que el widget de "Top usuarios" del panel histórico tenga datos significativos. Usado por los tests E2E del panel histórico. Idempotente (limpia y reinserta).

---

### 5.11 Subida de foto

El sistema ofrece **dos formas** de subir o cambiar la foto de un producto:

1. **Desde el ordenador (backoffice)** — En la pantalla de edición del producto, el botón "Subir foto" abre el selector de archivos del sistema. Puedes seleccionar cualquier imagen del disco. Esta vía no requiere cámara ni QR. La foto se procesa y asigna inmediatamente.
2. **Desde el móvil del cliente (vía QR)** — Para que los clientes del restaurante **aporten fotos de sus consumiciones** escaneando un código QR. La foto se asocia automáticamente al producto mediante un token firmado. Flujo diseñado para usarse desde el móvil del cliente sin autenticación.

#### Subida por QR desde el móvil — Flujo completo

1. **Generación del token** — El restaurador (admin/supervisor) genera un token de subida desde el backoffice para un producto concreto: edición del producto → "Subir foto" → se genera un QR.
2. **Impresión del QR** — El sistema muestra un código QR que el restaurador imprime y coloca en la mesa del cliente.
3. **Escaneo** — El cliente escanea el QR con su móvil (cámara nativa o lector QR).
4. **Validación** — La landing valida que el token no esté expirado (por defecto 10 minutos). El token es **multi-uso**: puede usarse para subir y cambiar la foto tantas veces como se quiera mientras no haya caducado.
5. **Captura** — El input de tipo `file` con el atributo `capture` despliega la cámara del móvil (el navegador solicita **permiso de cámara** al usuario). El flujo incluye:
   - **Cámara** con disparador y vibración háptica (`navigator.vibrate([12])`) al capturar.
   - **Crop** opcional: si la relación de aspecto de la foto es ≤ 1.15:1, se salta el paso de recorte.
   - El botón "Volver" en el crop regresa a la cámara (no a inicio).
   - **Preview "¿Queda bien?"** — tras el crop, se muestra la foto a tamaño completo antes de subirla. "Repetir foto" vuelve a la cámara; "Subir esta" confirma la subida.
   - Si la cámara no está disponible, se abre silenciosamente el selector de archivos del dispositivo. Cuando el input se usa desde un ordenador (sin móvil), el botón de cámara del input muestra directamente el selector de archivos.
6. **Subida** — La foto se envía al backend, donde **se redimensiona** a un máximo de 1080px por lado (`scaleDown`) y **se convierte a WebP** (calidad 85) mediante **Intervention Image**. El archivo original se descarta.
7. **Resultado** — La foto optimizada se asigna al producto y se muestra en tiempo real en el backoffice del restaurador. La pantalla de éxito ofrece el botón **"Cambiar foto"** para volver a la cámara y subir otra versión con el mismo token activo.

**Persistencia del token (TPV):** El modal del TPV guarda el token en `PhotoUploadTokenCacheService` (`providedIn: 'root'`). Si el operador cierra el modal y lo vuelve a abrir —incluso después de navegar a otra pantalla— se reutiliza el token existente mientras le quede más de 15 s de vida, evitando generar un nuevo QR innecesariamente.

#### Optimización de imágenes (server-side)

| Aspecto | Detalle |
|---|---|
| **Librería** | Intervention Image v3 (`intervention/image-laravel`) |
| **Driver** | GD con WebP support (instalado en el contenedor Docker) |
| **Redimensionado** | `scaleDown` a 1080px máx. (proporciones originales) |
| **Formato salida** | WebP calidad 85 (~70–90% menor peso que JPEG) |
| **Disco** | `public` (local) en desarrollo; S3-compatible en producción (`PRODUCT_PHOTOS_DISK`) |
| **URL pública** | Las URLs (`image_src`) se reescriben automáticamente en el frontend para funcionar desde el móvil (reescritura `localhost → window.location.origin` + proxy de Angular) |

#### Endpoints

```
GET  /api/public/photo-upload/{token}     # Validar token y obtener contexto del producto
POST /api/public/photo-upload/{token}     # Subir foto (multipart/form-data)
```

#### Limpieza de tokens expirados

```bash
docker compose exec api php artisan product:delete-expired-photo-upload-tokens
```

---

### 5.12 Impresión en impresora térmica

El sistema puede enviar tickets a **impresoras térmicas ESC/POS** conectadas por red TCP/IP, además de la impresión por ventana del navegador.

#### Configuración de impresoras

Accesible desde **Gestión → Impresoras** (solo administradores). Cada impresora tiene:

| Campo | Descripción |
|---|---|
| **Nombre** | Nombre identificativo (ej: "Cocina", "Barra") |
| **IP** | Dirección IP de la impresora en la red local |
| **Puerto** | Puerto TCP (por defecto 9100) |
| **Ancho de papel** | 58mm (32 caracteres) o 80mm (48 caracteres) |
| **Activa** | Si está habilitada para imprimir |
| **Por defecto** | Impresora que se usará cuando ninguna zona tenga una asignada |
| **Zona** | Opcional. Asigna la impresora a una zona concreta (Terraza, Salón, Barra). Si no se asigna zona, se usa la impresora por defecto del restaurante. |

#### Flujo de impresión

1. **Pre-cuenta** — Desde el TPV, al pulsar "Imprimir pre-cuenta", se envía a la impresora térmica de la zona de la mesa. Si falla, se abre la ventana de impresión del navegador como alternativa.
2. **Ticket de pago** — Al registrar un cobro, el ticket de pago se muestra en la ventana de impresión del navegador.
3. **Ticket final** — Al cerrar la venta, se envía automáticamente el ticket final a la impresora térmica (`PrintOnSaleClosedSubscriber`) y también desde el botón "Imprimir ticket final" en caja. Si la impresora no responde, se abre la ventana de impresión del navegador.

#### Comportamiento multi-zona

Si la mesa pertenece a una zona que tiene una impresora asignada, el ticket se envía a esa impresora. Si la zona no tiene impresora, se usa la impresora marcada como "Por defecto" del restaurante. Si no hay ninguna configurada, se recurre siempre a la ventana de impresión del navegador.

---

### 5.13 Vista de plano de mesas (TPV)

La pantalla de **Mesas** ofrece dos vistas alternativas: la cuadrícula de tarjetas (vista por defecto) y el **plano de sala** en formato SVG escalable. El modo activo se recuerda en `localStorage` entre sesiones.

#### Activar la vista de plano

En la barra superior de la pantalla de Mesas, junto a los tabs de zona, hay un toggle con dos botones: **Lista** (rejilla) y **Plano** (disposición real del salón). Toca **Plano** para activarla.

#### Características del plano de sala

- **Distribución real del salón** — Las mesas aparecen en la posición y con la forma (rectángulo o círculo) que el administrador configuró en el editor de plano. El viewport usa `viewBox="0 0 1200 800"` con `preserveAspectRatio="xMidYMid meet`, ajustándose a cualquier tamaño de pantalla.
- **Colores de estado** — Cada tarjeta refleja el estado operativo en tiempo real:
  - Blanco + borde gris — Mesa libre.
  - Negro — Mesa ocupada con pedido abierto.
  - Azul — Mesa marcada para cobrar (`TO_CHARGE`).
- **Nombre y comensales** — Las tarjetas muestran el nombre de la mesa centrado. Si hay comensales sentados, aparece el recuento debajo del nombre.
- **Badge COBRAR** — Pastilla roja-blanca visible cuando la mesa está pendiente de cobro.
- **Selección** — Tocar una mesa en el plano la selecciona y muestra el panel lateral con el estado del pedido y las acciones disponibles (igual que en la vista lista).
- **Juntar mesas (merge) por arrastre** — Fuera del modo merge, arrastra una tarjeta sobre otra para fusionarlas directamente: aparece un clon visual de la mesa arrastrada que sigue el dedo/cursor. Al soltar sobre una mesa destino, se crea un grupo fusionado. La mesa fusionada se muestra en la posición de la mesa destino con el nombre "Mesa A + Mesa B" en 11 px.
- **Persistencia del ancla de merge** — El ID de la mesa destino (ancla) se persiste en `localStorage` como `mesas_merge_anchors`. Al recargar la página, la tarjeta fusionada sigue apareciendo en la posición correcta. Las anclas obsoletas (grupos ya separados) se limpian automáticamente al cargar.
- **Mesas sin posición** — Las mesas que aún no tienen coordenadas en el plano no se renderizan (no ocupan espacio). Se muestra un aviso con el recuento al pie del SVG.
- **Tiempo real** — El estado del plano se actualiza via WebSockets (Laravel Reverb) igual que la vista lista: apertura, comanda, cobro, merge y unmerge se sincronizan entre terminales sin recargar.

#### Drag-to-merge — flujo detallado

1. Mantén presionado sobre una mesa del plano más de 8 px de desplazamiento para iniciar el arrastre.
2. Aparece un **clon flotante** de la tarjeta (`position: fixed`, tamaño real del elemento SVG en pantalla) que sigue el cursor.
3. Arrastra sobre cualquier otra mesa hasta que su borde se ilumine en verde (estado `fp-drag-target`).
4. Suelta para fusionar. El clon desaparece y la tarjeta fusionada aparece inmediatamente en la posición de la mesa destino.
5. Si sueltas en un espacio vacío, la operación se cancela sin cambios.

---

### 5.14 Editor de plano de sala (Backoffice)

Accesible desde **Gestión → Zonas → [nombre de zona] → Editar plano**. Permite diseñar la distribución visual del salón posicionando, redimensionando y personalizando cada mesa sobre un lienzo SVG.

#### Interfaz del editor

El editor ocupa la pantalla completa y se divide en dos áreas:

- **Lienzo (izquierda)** — Canvas SVG con cuadrícula de puntos (`40 × 40 px`). Aquí se colocan y manipulan las mesas visualmente.
- **Panel de capas (derecha, 380 px)** — Panel inspirado en Photoshop con lista de capas, controles de edición en acordeón y acciones globales.

#### Lienzo SVG

| Función | Cómo usarla |
|---|---|
| **Mover mesa** | Arrastra la mesa con el ratón/dedo. Se engancha al grid (snap 40 px). |
| **Redimensionar** | Selecciona la mesa → aparecen 4 asas en las esquinas. Arrastra cualquiera para cambiar tamaño (mínimo 40 px). Los círculos mantienen proporciones cuadradas. |
| **Seleccionar** | Clic sobre una mesa (sin mover) la selecciona y resalta con anillo rojo + glow. |
| **Deseleccionar** | Clic en el fondo vacío del lienzo. |
| **Renombrar (inline)** | Doble clic sobre una mesa → aparece input editable superpuesto. Confirma con Enter o pierde el foco. |
| **Nueva mesa** | Doble clic en el fondo vacío del lienzo → abre el modal de creación. |
| **Menú flotante** | Con una mesa seleccionada, aparece un menú flotante encima con: Renombrar, Cambiar forma (rect ↔ círculo), Quitar del plano. |
| **Zoom** | `Ctrl/Cmd + scroll` para acercar/alejar (rango 40 %–200 %). También desde los botones del panel. |
| **Eliminar** | `Delete` / `Backspace` con una mesa seleccionada → la quita del plano (pasa a "Sin posición"). |
| **Grid snap** | Todas las posiciones y tamaños se redondean al grid de 40 px para mantener alineación. |

#### Panel de capas

**Header:**
- Título "Mesas" con el recuento de mesas colocadas.
- Botón **"+ Nueva"** — abre el modal de creación de mesa. También aparece con doble clic en el lienzo.

**Lista de capas (estilo Photoshop):**
- Cada mesa colocada en el plano aparece como una fila con:
  - **Handle de 6 puntos** (⠿) para reordenar con drag & drop. El orden determina el z-index en el SVG.
  - **Icono de forma** (□ rect / ○ círculo).
  - **Nombre** de la mesa.
  - **Chevron** (›) que rota 180° al expandir.
- **Clic en una fila** → selecciona la mesa Y la cámara del lienzo hace scroll suave hasta centrarla en pantalla. Si ya estaba seleccionada, vuelve a clic para colapsar el acordeón y deseleccionar.
- **Drag & drop de capas** — CDK DragDrop con animación spring `cubic-bezier(0.34, 1.4, 0.64, 1)` a 420 ms. Eje bloqueado a Y. El reordenado se refleja inmediatamente en el lienzo.

**Acordeón de edición** (aparece deslizándose al seleccionar una fila):
- **Nombre** — Input de texto editable; se aplica al confirmar con Enter o al perder el foco.
- **Forma** — Botones "Rect." / "Círculo" con icono SVG. Cambiar forma preserva el tamaño.
- **Tamaño** — Control segmentado S / M / L (sin dimensiones técnicas; se aplica al instante).
- **Quitar del plano** — Botón danger. La mesa pasa a "Sin posición" (no se elimina).

**Sección "Sin posición":**
- Mesas creadas que aún no tienen coordenadas en el plano. Cada chip tiene un botón `+` que las coloca en el lienzo en la primera posición libre.

**Footer:**
- **Zoom** — Controles `−` / `porcentaje` (click para reset a 100 %) / `+`. Rango 40 %–200 %.
- **Guardar plano** — Botón desactivado (gris) cuando no hay cambios. Se activa en rojo con un punto pulsante animado cuando hay cambios pendientes. Guarda las posiciones, tamaños y formas de todas las mesas colocadas via `PUT /api/admin/zones/{id}/layout`.

#### Modal "Nueva mesa"

Se abre desde el botón "+ Nueva" del panel o con doble clic en el lienzo. Contiene:
- Input de nombre con placeholder ("Ej. Mesa 1, Terraza A…") — Enter confirma.
- Selector de forma: dos botones con icono SVG ("Rectángulo" / "Círculo").
- Segmented control de tamaño: S / M / L.
- Botones "Cancelar" y "Añadir al plano".

Al confirmar, la mesa se crea en el backend y se coloca automáticamente en el lienzo en la primera posición disponible (offset progresivo de 80 px por mesa ya colocada).

#### Guarda de cambios sin guardar

Si intentas salir del editor con cambios sin guardar, el sistema muestra un diálogo de confirmación del navegador (`CanDeactivate` guard Angular). Responde "Salir" para descartar o "Quedarse" para volver y guardar.

---

### 5.15 Autoservicio QR — Carta digital para comensales

> **Estado:** En desarrollo activo — Sprint 1 completado (infraestructura base). La interfaz de comensal y la integración completa con el TPV se implementan en sprints posteriores. Ver [`AUTOSERVICIO_PLAN.md`](AUTOSERVICIO_PLAN.md) para el plan técnico completo.

El módulo de **Autoservicio QR** elimina la fricción del ciclo de pedido: los comensales gestionan toda su experiencia desde el móvil escaneando el código QR de la mesa, sin necesidad de instalar ninguna app.

#### Cómo funciona (visión completa)

```
Comensal llega → escanea QR de la mesa → abre la mesa él mismo
  → navega la carta con alérgenos → añade productos al carrito
  → decide cuándo enviar cada ronda a cocina/barra
  → el camarero ve todo en el TPV en tiempo real (vía Reverb)
  → comensal pulsa "Pedir la cuenta" → camarero cobra desde el TPV
```

#### Modos de identidad del comensal

| Modo | Lo que da | TPV muestra | Puntos/Ofertas |
|---|---|---|---|
| **Anónimo** | Nada | "Anónimo" | — |
| **Solo nombre** | Un nombre libre | "Carlos" | — |
| **Con cuenta** | Email + contraseña | "Carlos ⭐" | Acumula puntos y accede a ofertas del restaurante |

Las cuentas son **por restaurante** (no cross-platform). El comensal se registra la primera vez y en visitas siguientes hace login al escanear el QR para acumular puntos.

#### Sistema de rondas

El comensal controla cuándo recibe cada parte del pedido. Añade productos al carrito sin prisa y decide cuándo enviarlos:

```
Añadimos bebidas → "Enviar bebidas ahora" → llegan a la barra
(tiempo después)
Añadimos entrantes → "Enviar entrantes cuando estéis listos"
(comemos entrantes)
Añadimos platos principales → "Enviar platos"
(terminamos)
→ "Pedir la cuenta"
```

Cada ronda tiene un `idempotency_key` generado en el cliente, por lo que los reintentos por caída de red no crean rondas duplicadas.

#### Estado actual — Sprint 1 completado

| Componente | Estado |
|---|---|
| Migraciones (`table_qr_tokens`, `guest_sessions`, `guest_order_rounds`) | ✅ Aplicadas |
| `products.available` + columnas guest en `order_lines` | ✅ Aplicadas |
| Dominio `GuestOrder`: entidad `TableQrToken`, VOs, interfaces, eventos | ✅ Implementado |
| `POST /api/admin/tables/{uuid}/qr-token` (generar/regenerar token) | ✅ Funcional |
| `GET /public/table/{token}` (estado de mesa + info restaurante) | ✅ Funcional |
| Auto-creación de QR al crear una mesa (via `GuestOrderTableCreatedSubscriber`) | ✅ Funcional |
| CORS para rutas `/public/table/*` | ✅ Configurado |
| Apertura de mesa por el comensal, carta, carrito, rondas, cuenta | 🔄 Próximos sprints |
| Interfaz Angular del comensal (`/s/{token}`) | 🔄 Próximos sprints |
| Integración Reverb en TPV (badges "⚡ Guest") | 🔄 Próximos sprints |
| Sistema de cuentas y fidelización | 🔄 Próximos sprints |

#### Integración con el TPV

Cuando el autoservicio esté completo, el camarero verá en el TPV:

- **Badge "Abierta vía QR · X pax"** en la tarjeta de la mesa.
- **Lines con badge "⚡ Guest · Carlos · Ronda 1"** al recibir pedidos del comensal.
- **Alerta roja "Carlos pide la cuenta — Mesa 3"** cuando el comensal solicita cobrar.
- **Modal QR** en el panel lateral con el QR imprimible, sesiones conectadas en tiempo real y botón regenerar.

#### Variable de entorno necesaria

```env
GUEST_APP_URL=http://localhost:4201   # URL base de la carta digital del comensal
```

---

## 6. Características implementadas

### Hitos del proyecto

| Hito | Estado | Alcance |
|---|---|---|
| **Hito 1 — Modelo de datos** | 100% | 20+ migraciones, 14 tablas principales, soft deletes, índices optimizados, shard key `restaurant_id` en todas las entidades. |
| **Hito 2 — API REST Backoffice** | 100% | CRUD completo de familias, impuestos, productos, zonas, mesas y usuarios. Auth dual (email/password + PIN de 4 dígitos). SuperAdmin con gestión multi-restaurante. |
| **Hito 3 — Interfaz Backoffice** | 100% | Panel de gestión con ~1.600 líneas de componentes Angular. Formularios reactivos, validación en tiempo real, toasts de confirmación. |
| **Hito 4 — Front de Venta (TPV)** | 100% | Flujo completo: mesas → apertura → pedido → cobro → cierre. Soporte para pagos parciales, división de cuenta (3 modos), y cierre de caja con Z-Report. |
| **Hito 5 — Informes (Dashboard)** | 75% | 5 de 7 tabs conectados a datos reales (Resumen, Ventas, Productos, Empleados, Caja). Pendiente: Tab Impuestos (desglose IVA / Modelo 303) y Tab Informes (exportación PDF/CSV). |
| **Hito 6 — Auditoría y trazabilidad** | 100% | Registro de Auditoría con 72 slugs instrumentados, cadena de hash SHA-256, detección de anomalías, alertas in-app, vistas guardadas, paginación por cursor, live tail (auto-off en histórico), exportación CSV/NDJSON, banner contextual al llegar desde histórico, y panel **Histórico** con KPIs de retención, widget de anomalías (incidentes detectados en el corpus), badge de integridad de cadena (5 estados, persistido en servidor), gráfico mensual clickable (drill-down por mes), desglose por categoría con barras horizontales coloreadas, top 5 usuarios con avatares por rol, presets de rango temporal, deep-link contextual. Archivado por antigüedad (90d → `archived_at`, retención legal 6 años, nunca borrado) y toggle "Mostrar histórico" con `include_archived=1`. Solo acceso `admin`. Verificación de cadena con `GET /api/admin/audit-log/verify` y persistencia server-side del resultado vía `GET /api/admin/audit-log/verify/latest`. |
| **Hito 7 — Mejoras operativas** | 100% | Roles, PIN, quick access, vinculación de dispositivo, multi-tenancy, productos con modificadores y subida de foto por QR con optimización server-side. |
| **Hito 6 — Mejoras arquitectónicas** | 100% | **Bus de eventos síncrono:** `InMemorySyncEventBus` + `EventSubscriber` + `AuditableEvent`; todos los módulos (Order, Sale, Cash, Table, Menu, Family…) migrados a `EventBusInterface`. **Personalización de familias:** color (hex) e icono (set Material) en `Family`; picker en backoffice, acento visual en TPV. **Tiempo real de mesas (Reverb):** canal `restaurant.{id}`, 12 eventos broadcast cubiertos (OrderCreated → OrderInvoiced + TablesMerged/Unmerged), `MesasFacade` con `reloadOpenOrders()` + `reloadTables()`; 2/2 E2E Playwright verificado. |
| **Hito 8 — Autoservicio QR** | 12% (Sprint 1/8) | Carta digital para comensales: escanean QR de mesa → piden por rondas → camarero recibe en TPV vía Reverb. Sprint 1 completado: dominio `GuestOrder`, 5 migraciones, token QR por mesa (generación/regeneración), endpoint público `GET /public/table/{token}`, auto-creación al crear mesa vía evento. Sprints 2–8 pendientes (apertura de mesa, carta, rondas, frontend Angular, Reverb, cuentas de cliente). |

### Funcionalidades detalladas

| Módulo | Feature | Descripción técnica |
|---|---|---|
| **Auth** | Login dual | Sistema de autenticación con JWT que soporta tanto email+password como PIN numérico de 4 dígitos. |
| **Auth** | Quick Access | Lista de usuarios frecuentes en la pantalla de PIN para acceso inmediato sin escribir credenciales. |
| **Auth** | Roles y permisos | 3 roles (`admin`, `supervisor`, `operator`) con guardas de navegación (`CanActivate`) en el frontend y middlewares de autorización en el backend. |
| **Producto** | Modificadores | Cada producto puede tener opciones de personalización que se almacenan en `order_lines.notes` (ej: "sin cebolla", "extra queso"). |
| **Producto** | Stock | Control de inventario básico con decremento automático al cerrar venta. |
| **Producto** | Foto por QR | Token firmado + QR para subir y cambiar la foto desde el móvil sin autenticación. Multi-uso hasta expiración (10 min). Preview antes de subir, botón "Cambiar foto" en éxito. Token cacheado en servicio singleton para sobrevivir navegaciones. |
| **Producto** | Optimización server-side | Redimensionado a 1080px máx y conversión a WebP (calidad 85) mediante Intervention Image + GD. Las URLs se reescriben para acceso desde móvil. |
| **Menú** | Editor visual | Editor drag & drop de menús con secciones reordenables, catálogo lateral filtrable y validación en tiempo real de reglas `min/max`. |
| **Menú** | Vigencia y disponibilidad | Cabecera con `validity_from/to`, bitmask de `available_days` y franja `available_from_time/available_to_time` para activar el menú sólo en su ventana real. |
| **Menú** | Suplementos por item | Cada `MenuItem` puede llevar un `extra_price` que se suma al precio base del menú si el comensal lo elige. |
| **Menú** | Snapshot de elecciones | La línea de orden de un menú guarda en JSON el `menu_name`, los `menu_selections` y sus variantes/modificadores, garantizando que los tickets antiguos no se "rompan" al renombrar productos del catálogo. |
| **Mesa** | Estados visuales | Mesas con 2 estados (libre/ocupada) representados con semáforo de colores en el grid. |
| **Mesa** | Agrupación | Soporte para unir mesas físicas (campo `merged_table_group_id`) y gestionarlas como una sola unidad de cobro. |
| **Mesa** | Tiempo real multi-terminal | Canal WebSocket `restaurant.{restaurantId}` vía Laravel Reverb. `MesasFacade` suscrito: `order.status_changed` → `reloadOpenOrders()` (órdenes + líneas), `table.status_changed` → `reloadTables()` (merged_table_group_id). Cobertura: 10 eventos de Order + 2 de Table = 12 eventos totales. |
| **Plano TPV** | Vista de plano SVG | Canvas `viewBox="0 0 1200 800"` con `preserveAspectRatio="xMidYMid meet"`. Mesas con colores de estado (blanco/negro/azul), nombre centrado, badge COBRAR y recuento de comensales. Toggle Lista ↔ Plano persistido en `localStorage`. |
| **Plano TPV** | Drag-to-merge | Arrastre de una mesa sobre otra para fusionarlas directamente desde el plano. Clon flotante de tamaño real (`getBoundingClientRect`) con texto centrado vía `position: absolute`. Ancla de merge persistida en `localStorage` (`mesas_merge_anchors`) para mantener la posición correcta tras recargar. Limpieza automática de anclas obsoletas. |
| **Plano TPV** | Tarjeta fusionada | Mesa fusionada renderizada en la posición y tamaño de la mesa destino (ancla). Nombre compuesto "Mesa A + Mesa B" en 11 px. El grupo se identifica a través del ancla incluso si las mesas provienen de sesiones anteriores. |
| **Editor de plano** | Lienzo SVG interactivo | Canvas `1200×800` con cuadrícula de puntos (40 px). Drag para mover (snap al grid), asas de esquina para redimensionar, selección con anillo rojo + glow, menú flotante contextual sobre la mesa seleccionada, inline rename con doble clic, `Delete`/`Backspace` para quitar del plano. |
| **Editor de plano** | Zoom | `Ctrl/Cmd + scroll` para acercar/alejar. Rango 40 %–200 %. Botones `−` / `%` (reset) / `+` en el panel lateral. Ajuste automático al contenido al cargar (`fit to screen`). |
| **Editor de plano** | Panel de capas | Panel lateral de 380 px estilo Photoshop. Lista de todas las mesas colocadas con handle drag-and-drop (CDK, animación spring 420 ms `cubic-bezier(0.34,1.4,0.64,1)`, eje Y bloqueado). Clic en fila → selecciona + scroll suave del canvas hasta centrar la mesa. |
| **Editor de plano** | Acordeón de edición | Al seleccionar una fila del panel, el área de edición se desliza hacia abajo con animación CSS `grid-template-rows: 0fr → 1fr` (240 ms). Contiene: input de nombre, picker de forma, segmented control S/M/L y botón "Quitar del plano". Colapsa al volver a clicar la fila o al deseleccionar en el lienzo. |
| **Editor de plano** | Modal nueva mesa | Modal flotante con `backdrop-filter: blur` accesible desde el botón "+ Nueva" del panel o con doble clic en el fondo del lienzo. Campos: nombre (Enter confirma), forma (rect/círculo), tamaño (S/M/L). Crea la mesa en el backend y la coloca en el lienzo en la primera posición libre. |
| **Editor de plano** | Guardar plano | `PUT /api/admin/zones/{id}/layout` con la geometría completa de todas las mesas colocadas. Botón con punto animado pulsante cuando hay cambios sin guardar (`isDirty`). Guard `CanDeactivate` que avisa si intentas salir con cambios pendientes. |
| **Editor de plano** | Sin posición | Mesas creadas pero sin coordenadas en el plano listadas en sección dedicada. Botón `+` por mesa para colocarla automáticamente en el lienzo. |
| **Familia** | Color e icono | `FamilyColor` (hex #RRGGBB, validación de formato) + `FamilyIcon` (set permitido de 20+ iconos Material). Selector de swatches y chips en backoffice; acento de color e icono en pestañas de familia del TPV. |
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
| **Auditoría** | Panel Histórico | KPIs de retención (total archivado, rango temporal, mes pico, media mensual), gráfico de barras mensual clickable (drill-down por mes), desglose por categoría con barras horizontales y paleta dedicada, top 5 usuarios del corpus con avatar por rol, widget de anomalías (incidentes detectados con sus etiquetas ES), presets de rango temporal y selector de fechas personalizado. Todos los widgets respetan el rango activo. |
| **Auditoría** | Badge integridad de cadena | Bloque en el panel histórico que invoca el endpoint `verify` bajo demanda y muestra 5 estados (no verificada/verificando/íntegra/rota/error) con recuento de eventos corruptos y timestamp de última verificación. El resultado persiste en servidor (tabla `audit_chain_verifications`) para que cualquier inspector vea el estado canónico. |
| **Auditoría** | Drill-down desde panel | Click en barra mensual, fila de categoría o fila de usuario en el panel histórico → navega al registro vivo con `?historico=1` y los filtros correspondientes (`dateFrom`/`dateTo`/`category`/`userId`). |
| **Auditoría** | Banner "Vista del histórico" | Cuando el registro vivo se abre con `?historico=1`, aparece un banner morado con el rango activo y botón "Volver a registro normal" que limpia los filtros y los query params. El live tail se desactiva automáticamente sobre archivados. |
| **Auditoría** | Deep-link histórico | Botón "Abrir registro" del CTA del panel que navega al registro vivo con `?historico=1` + el rango activo como `dateFrom`/`dateTo` (activa el toggle "Mostrar histórico"). |
| **Auditoría** | Verificación de cadena | `GET /api/admin/audit-log/verify` que recorre todos los eventos (activos + archivados) y verifica la integridad SHA-256 de cada eslabón. |
| **Auditoría** | Persistencia de verificación | El resultado de la verificación se persiste en `audit_chain_verifications` y se consulta vía `GET /admin/audit-log/verify/latest`. Cualquier inspector o dispositivo accede al estado canónico. |
| **Auditoría** | Widget de anomalías | Tarjeta roja en el panel histórico que muestra el total de incidentes detectados en el corpus archivado, con pills por tipo (`auth_failed_burst`, `caja_mismatch`) y etiquetas en español. Incluye CTA "Ver en el registro" que navega al registro vivo filtrado por anomalías. |
| **Auditoría** | Traza inmutable | 72 eventos instrumentados en 9 categorías. Cada evento almacena `before/after`, metadata, IP y device. Hash SHA-256 encadenado por restaurante para garantizar integridad. |
| **Auditoría** | Detección de anomalías | Reglas server-side: `auth_failed_burst` (≥3 fallos PIN en 5 min) y `caja_mismatch` (descuadre en cierre). Se marcan en el evento y generan alerta. |
| **Auditoría** | Alertas in-app | Tabla `audit_alerts` con notificaciones por anomalía. Dropdown con badge de no leídas, navegación directa al evento vinculado, polling 30s. |
| **Auditoría** | Vistas guardadas | Persistencia de combinaciones de filtros por restaurante (`audit_saved_views`). CRUD completo: crear, listar, aplicar, eliminar. |
| **Impresión** | Impresora térmica ESC/POS | Integración con impresoras de red TCP (puerto 9100). La configuración por zona permite asignar una impresora distinta a Terraza, Salón o Barra, con fallback a la impresora por defecto del restaurante. Soporta ticket final, pre-cuenta y página de test. Auto-impresión al cierre de venta mediante `PrintOnSaleClosedSubscriber`. |
| **Impresión** | Impresión multi-zona | Resolución automática: mesa → zona → `printer_config_id` → si no hay, fallback a `is_default=true`. Cada impresora define su ancho de papel (58mm/32 chars o 80mm/48 chars). |
| **Auditoría** | Live tail | Polling cada 5s para insertar eventos nuevos en la cabecera de la lista sin perder el scroll. |
| **Auditoría** | Paginación por cursor | Cursor opaco base64 sobre `(created_at DESC, id DESC)` para evitar desplazamiento de páginas ante inserts concurrentes. |
| **Auditoría** | Archivado por antigüedad | Comando `audit:archive-old` mueve eventos >90 días a `archived_at`. Semanal en scheduler. Emite meta-evento `audit.archived`. Nunca borra. |
| **Auditoría** | Toggle histórico | Flag `include_archived=1` en la UI para que el admin vea eventos archivados. Banner informativo con la política de retención. |
| **Auditoría** | Verificación de cadena con archivados | `GET /api/admin/audit-log/verify` lee también filas archivadas. La cadena SHA-256 sigue siendo íntegra tras archivar. |
| **Dispositivo** | Identificación única | El interceptor HTTP genera y envía `X-Device-Id` (UUID v4 persistente en `localStorage` o `environment.devDeviceId`). Backend captura device + IP en cada evento de auditoría. |
| **Bus de eventos** | Síncrono in-process | `InMemorySyncEventBus` despacha `DomainEvent` a todos los `EventSubscriber` registrados en orden. Suscriptores activos: `AuditEventSubscriber` (auditoría cross-cutting), `TablesBroadcastSubscriber` (10 eventos Order → Reverb), `TablesGroupBroadcastSubscriber` (2 eventos Table → Reverb). Las entidades usan el trait `RecordsEvents`; los eventos cross-aggregate los publica directamente el caso de uso. Los eventos de auditoría implementan `AuditableEvent` con before/after/metadata. |
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
| **WebSockets** | Laravel Reverb | 1.x |
| **Bus de eventos** | InMemorySyncEventBus (in-process) | — |
| **Cache/Sesión** | Redis | 7.x (preparado, no obligatorio para demo) |
| **Frontend** | Angular | 19.x |
| **Framework UI** | Ionic | 8.x |
| **Lenguaje frontend** | TypeScript | 5.x |
| **Cliente WS frontend** | Laravel Echo + Pusher JS | — |
| **Estilos** | SCSS + CSS Variables | — |
| **Contenedores** | Docker + Docker Compose | v2 |
| **Servidor web API** | PHP artisan serve (dev) | — |
| **Servidor web dev** | Angular CLI dev server | — |
| **Testing backend** | PHPUnit | 11.x |
| **Testing E2E** | Playwright | 1.x |
| **Testing frontend** | Karma + Jasmine | — |
| **Linting PHP** | Laravel Pint | — |
| **Procesado de imágenes** | Intervention Image + GD (WebP) | 3.x |
| **Cliente DB** | DbGate | (contenedor) |

### 7.2 Patrón arquitectónico — DDD + Hexagonal

El backend sigue estrictamente **Domain-Driven Design** con **Arquitectura Hexagonal** (Ports & Adapters). Cada dominio es un módulo autocontenido que no conoce detalles de framework fuera de su capa `Infrastructure`.

```
App/<Dominio>/
├── Domain/
│   ├── Entity/              # Entidades puras con lógica de negocio
│   │   └── <Entidad>.php    # Método de fábrica dddCreate(), usa trait RecordsEvents
│   ├── Event/               # Eventos de dominio (implementan DomainEvent o AuditableEvent)
│   │   └── <Entidad><Accion>.php   # Ej: OrderCreated, TablesMerged
│   ├── ValueObject/         # VOs inmutables: constructor privado + create()
│   │   └── <VO>.php         # Validación encapsulada, imposible instanciar estado inválido
│   ├── Exception/           # Excepciones de dominio (invariantes violadas, reglas de negocio)
│   │   └── <Entidad>NotFoundException.php
│   │   └── <Regla>Exception.php
│   └── Interfaces/          # Contratos (repositorios, servicios del dominio)
│       └── <Repo>Interface.php
├── Application/
│   └── <CasoDeUso>/
│       ├── <CasoDeUso>.php          # Orquestador: recibe EventBusInterface, llama eventBus->publish()
│       ├── <CasoDeUso>Command.php   # DTO de entrada: encapsula los datos que recibe el caso de uso
│       └── <CasoDeUso>Response.php  # DTO de salida para el controlador
└── Infrastructure/
    ├── Broadcasting/        # Suscriptores WS: escuchan DomainEvents y disparan Reverb
    │   ├── <Dominio>BroadcastSubscriber.php  # Implementa EventSubscriber
    │   └── <Dominio>StatusChanged.php        # Implementa ShouldBroadcastNow
    ├── Persistence/
    │   ├── Models/            # Eloquent (solo usados desde repositorios)
    │   └── Repositories/    # Implementaciones de las interfaces de dominio
    ├── Services/            # Implementaciones de servicios (hashers, notificadores)
    └── Entrypoint/
        └── Http/
            ├── Requests/    # Form Requests de Laravel: validación de entrada HTTP
            └── <Controller>.php  # Controladores (1 acción = 1 __invoke)
```

**Capa `Shared` — contratos del bus de eventos:**

```
App/Shared/
├── Domain/Event/
│   ├── DomainEvent.php          # Interfaz base: occurredOn()
│   ├── AuditableEvent.php       # Extiende DomainEvent: auditSlug/EntityType/EntityId/Metadata/Before/After
│   └── RecordsEvents.php        # Trait para entidades: recordEvent(), pullDomainEvents()
├── Application/Event/
│   ├── EventBusInterface.php    # publish(DomainEvent ...$events): void
│   └── EventSubscriber.php      # subscribedTo(): list<class-string>, handle(DomainEvent): void
└── Infrastructure/Event/
    └── InMemorySyncEventBus.php # Implementación síncrona: despacha a todos los subscribers en registro
```

**Flujo del bus de eventos:**

```
Caso de uso
  ├── Persiste en repositorio
  ├── Llama $entidad->pullDomainEvents() (si la entidad usa RecordsEvents)
  └── $this->eventBus->publish($evento)
           │
           ├── AuditEventSubscriber    → inserta fila en audit_logs con hash SHA-256
           ├── TablesBroadcastSubscriber → event(OrderStatusChanged) → Reverb → canal WS
           └── TablesGroupBroadcastSubscriber → event(TableStatusChanged) → Reverb → canal WS
```

### 7.3 Dominios implementados

| Dominio | Entidades / Contratos principales | Responsabilidad |
|---|---|---|
| `Shared` | `Uuid`, `DomainDateTime`, `Email`, `Money` (VOs); `DomainEvent`, `AuditableEvent`, `RecordsEvents` (eventos); `EventBusInterface`, `EventSubscriber`, `InMemorySyncEventBus` (bus); `TenantContext` (multi-tenant) | Value Objects, contratos del bus de eventos y contexto de tenant compartidos entre dominios |
| `User` | `User`, `UserName`, `PasswordHash`, `UserRole`, `Pin` | Gestión de empleados del restaurante |
| `SuperAdmin` | `SuperAdmin`, `Restaurant` (gestión) | Administración de la plataforma multi-tenant |
| `Restaurant` | `Restaurant`, `RestaurantName` | Datos fiscales y de contacto del negocio |
| `Family` | `Family`, `FamilyName` | Categorías del catálogo |
| `Product` | `Product`, `Price`, `Stock`, `ProductPhotoUploadToken` | Artículos del menú con precio, impuesto, imagen, modificadores y subida de foto por QR con token firmado |
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
| `Reporting` | `DashboardReadRepository`, `SalesReadRepository`, `ProductsReadRepository`, `FamiliesReadRepository`, `CashReadRepository`, `EmployeesReadRepository`, `TaxReadRepository`, `HeatmapReadRepository`, `ScheduledReport`, `ReportExport` | Informes y dashboard financiero. Módulo **read-model puro** (CQRS): no tiene entidades de escritura ni emite eventos de dominio. Toda la lógica de negocio se limita a generación de PDF/CSV y programación de informes programados. |
| `Printer` | `PrinterConfig` (por zona), `EscPosTicketBuilder`, `NetworkPrinterService`, `PrintPreTicket`, `PrintFinalTicket` | Integración con impresoras térmicas ESC/POS por red TCP. Cada zona puede tener su propia IP de impresora (con fallback a la configurada por defecto del restaurante). Soportes: ticket de cocina, pre-cuenta, ticket de pago y ticket final. |

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
                                  │  ├── Recibe EventBusInterface               │
                                  │  ├── Construye VOs con ::create()           │
                                  │  ├── Crea Entidad con ::dddCreate()         │
                                  │  ├── Persiste vía Repositorio (interfaz)   │
                                  │  └── eventBus->publish($evento)            │
                                  └─────────────────────────────────────────────┘
                                          │                    │
                                          ▼                    ▼
                       ┌─────────────────────┐   ┌──────────────────────────┐
                       │  Repositorio Eloquent│   │  InMemorySyncEventBus    │
                       │  (Infrastructure)    │   │  ├── AuditEventSubscriber│
                       │  Entidad ↔ MySQL     │   │  │   → audit_logs (hash) │
                       └─────────────────────┘   │  ├── TablesBroadcast-    │
                                                  │  │   Subscriber          │
                                                  │  │   → Reverb → WS       │
                                                  │  └── TablesGroupBroadcast│
                                                  │      Subscriber          │
                                                  │      → Reverb → WS       │
                                                  └──────────────────────────┘
                                                           │
                                                           ▼  (WebSocket)
                                  ┌─────────────────────────────────────────────┐
                                  │  MesasFacade (Angular)                       │
                                  │  ├── reloadOpenOrders() ← order.status_changed│
                                  │  └── reloadTables()     ← table.status_changed│
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
| **Bus de eventos síncrono in-process (`InMemorySyncEventBus`)** | Desacopla efectos secundarios (auditoría, broadcast WebSocket) de la lógica del caso de uso sin introducir colas ni infraestructura de mensajería externa. Los eventos se despachan dentro del mismo ciclo de petición HTTP, garantizando consistencia. | `EventBusInterface` + subscribers modulares: añadir un nuevo efecto secundario es registrar un `EventSubscriber` sin tocar el caso de uso. |
| **`AuditableEvent` como interfaz diferenciada** | Los eventos auditables llevan `before`/`after`/`metadata` que la auditoría necesita. No todos los eventos requieren auditoría (ej: broadcast-only events). Separar `DomainEvent` base de `AuditableEvent` permite tener eventos ligeros sin overhead de auditoría. | `AuditEventSubscriber` reacciona solo a `AuditableEvent`; los broadcast subscribers reaccionan a todos los eventos relevantes. |
| **Broadcasting con Reverb sobre el mismo bus** | Los `BroadcastSubscriber` son subscribers más del bus: reciben el evento de dominio y disparan el evento Laravel Broadcast. Así, la lógica de qué canales reciben qué payload está en Infrastructure, no en el dominio. | El canal WebSocket (`restaurant.{id}`) es determinista: siempre llega al restaurante correcto gracias al `restaurantId` que cada evento de dominio lleva. |
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
│   │   │   ├── Domain/
│   │   │   │   ├── ValueObject/           # Uuid, DomainDateTime, Email, Money
│   │   │   │   └── Event/                 # DomainEvent, AuditableEvent, RecordsEvents (trait)
│   │   │   ├── Application/Event/         # EventBusInterface, EventSubscriber
│   │   │   └── Infrastructure/Event/      # InMemorySyncEventBus
│   │   ├── <Dominio>/                     # User, Order, Sale, Cash, Table, Family, Menu...
│   │   │   ├── Domain/
│   │   │   │   ├── Entity/
│   │   │   │   ├── Event/                 # Eventos del dominio (OrderCreated, TablesMerged…)
│   │   │   │   ├── ValueObject/
│   │   │   │   └── Interfaces/
│   │   │   ├── Application/<CasoDeUso>/   # UC + Command + Response
│   │   │   └── Infrastructure/
│   │   │       ├── Broadcasting/          # Subscribers WS + Broadcast events (Reverb)
│   │   │       │   ├── <X>BroadcastSubscriber.php  # Implementa EventSubscriber
│   │   │       │   └── <X>StatusChanged.php         # Implementa ShouldBroadcastNow
│   │   │       ├── Persistence/
│   │   │       │   ├── Models/
│   │   │       │   └── Repositories/
│   │   │       ├── Services/
│   │   │       └── Entrypoint/Http/
│   │   ├── Audit/Application/Subscriber/
│   │   │   └── AuditEventSubscriber.php   # Cross-cutting: audita todos los AuditableEvent
│   │   └── Providers/
│   │       └── AppServiceProvider.php     # Bindings + registro de subscribers en InMemorySyncEventBus
│   ├── bootstrap/
│   ├── config/
│   │   └── reverb.php                     # Configuración del servidor WebSocket
│   ├── database/
│   │   ├── migrations/                    # 20+ migraciones con soft deletes y FKs
│   │   └── seeders/
│   │       └── SaonaDemoSeeder.php        # Datos de demo (Bar Manolo, 6 empleados, 28 mesas)
│   ├── routes/
│   │   └── api/                           # auth.php, tpv.php, admin.php, superadmin.php
│   ├── tests/
│   │   ├── Unit/                          # Tests de dominio, VOs, subscribers
│   │   └── Feature/                       # Tests de integración HTTP
│   ├── .env.example
│   ├── composer.json
│   └── phpunit.xml
├── frontend/
│   ├── src/app/
│   │   ├── pages/core/
│   │   │   ├── login/                      # Pantalla de autenticación (email + PIN)
│   │   │   ├── finanzas/                   # Dashboard de finanzas (métricas, gráficas, tabs)
│   │   │   ├── gestion/                    # Backoffice CRUD completo
│   │   │   ├── gestion-menus-editor/       # Editor de menús drag & drop
│   │   │   └── registro-auditoria/         # Registro de Auditoría + panel Histórico
│   │   ├── features/
│   │   │   ├── tables/                     # Todo lo relacionado con mesas
│   │   │   │   ├── facades/
│   │   │   │   │   └── mesas.facade.ts     # Estado con Signals + suscripción WS Reverb
│   │   │   │   ├── pages/                  # Grid de zonas/mesas
│   │   │   │   └── ui/                     # Mesa card, merge modal
│   │   │   ├── orders/                     # Comanda / pedido
│   │   │   │   ├── facades/
│   │   │   │   │   └── pedido.facade.ts
│   │   │   │   ├── pages/
│   │   │   │   └── components/
│   │   │   └── cash/                       # Caja, cobro, split bill
│   │   │       ├── facades/
│   │   │       │   └── caja-payment.facade.ts
│   │   │       ├── pages/
│   │   │       ├── services/
│   │   │       │   ├── charge-session.service.ts
│   │   │       │   └── tpv.service.ts
│   │   │       └── ui/
│   │   │           ├── split-bill-modal/   # Modal de división de cuenta (3 modos)
│   │   │           └── cobrar-modal/       # Teclado numérico de cobro
│   │   ├── shared/components/              # Componentes UI reutilizables (btn, card, badge, numpad...)
│   │   ├── components/                     # Componentes de gestión backoffice
│   │   ├── services/                       # Servicios globales (Auth, AuditLog, AuditAlert, Table)
│   │   ├── core/
│   │   │   ├── services/
│   │   │   │   ├── auth.service.ts
│   │   │   │   └── echo.service.ts         # Wrapper de Laravel Echo para suscripciones WS Reverb
│   │   │   ├── http/
│   │   │   │   └── interceptor.ts          # Prefija API URL, JWT, X-Device-Id, X-Restaurant-Id
│   │   │   └── facades/
│   │   └── guards/                         # CanActivate por rol (admin, supervisor, operator)
│   ├── e2e/
│   │   ├── specs/                          # Tests Playwright por módulo (auth, tpv, cash, audit, finanzas)
│   │   │   └── tpv/
│   │   │       └── realtime-tables.spec.ts # Tests tiempo real: 2 tests (abrir mesa + cobrar)
│   │   └── support/                        # Helpers reutilizables (auth, cash, tpv, audit, fixtures)
│   ├── src/environments/
│   ├── angular.json
│   └── package.json
├── docs/
│   ├── CAJA_DESIGN.md                      # Especificación funcional completa del módulo Caja
│   ├── DOMINIO_TPV.md                      # Reglas de diseño de APIs TPV en hostelería
│   └── registro-auditoria-plan.md          # Plan técnico de implementación del módulo Auditoría (hitos, decisiones, cobertura)
├── docker-compose.yml                      # 5 servicios: api (:8000), reverb (:8080), frontend (:4200), db (:3406), dbgate (:9051)
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

### Público (sin autenticación)

```
GET    /api/public/photo-upload/{token}      # Validar token y obtener contexto del producto
POST   /api/public/photo-upload/{token}      # Subir foto (multipart/form-data)
```

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
POST           /api/management/tables/merge          # Unir mesas en grupo (crea merged_table_group_id)
POST           /api/management/tables/unmerge        # Separar grupo de mesas
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
GET    /api/tpv/orders/{id}              # Detalle de orden
GET    /api/tpv/orders/{id}/lines        # Líneas de la orden
GET    /api/tpv/orders/{id}/paid-total   # Total ya pagado de la orden (para split bill)
POST   /api/tpv/orders/lines              # Añadir línea de producto a orden
POST   /api/tpv/orders/batch-lines        # Enviar comanda (batch de líneas de una vez)
POST   /api/tpv/orders/menu-lines         # Añadir línea de menú a orden (con sus selecciones)
DELETE /api/tpv/orders/lines/{lineId}     # Eliminar línea de orden
PUT    /api/tpv/orders/{id}               # Actualizar orden (comensales, etc.)
POST   /api/tpv/orders/{id}/mark-to-charge # Marcar orden para cobrar
POST   /api/tpv/orders/{id}/reopen        # Reabrir orden marcada para cobrar
POST   /api/tpv/orders/{id}/transfer      # Trasladar pedido a otra mesa
DELETE /api/tpv/orders/{id}               # Cancelar orden (eliminar)
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

### Reporting / Dashboard (requiere rol `admin` o `supervisor`)

```
GET    /api/admin/reports/summary?period=today|yesterday|week|month    # KPIs + métodos de pago + spark
GET    /api/admin/reports/sales?period=...&page=1&per_page=50          # Lista de tickets paginada
GET    /api/admin/reports/sales/{uuid}                                 # Detalle de un ticket
GET    /api/admin/reports/heatmap?period=...                           # Matriz día×hora de ventas
GET    /api/admin/reports/products?period=...                          # Ranking productos + stock + zonas
GET    /api/admin/reports/employees?period=...                         # Ranking empleados + spark 14d
```

### Auditoría (requiere rol `admin`)

```
GET    /api/admin/audit-log               # Listar eventos (cursor, filtros, since)
GET    /api/admin/audit-log/{uuid}        # Detalle de un evento
GET    /api/admin/audit-log/verify        # Verificar cadena de hash por restaurante
GET    /api/admin/audit-log/verify/latest # Último resultado de verificación persistido
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
1. Domain/ValueObject/       → Crear VOs necesarios (constructor privado + create())
2. Domain/Event/             → Crear evento de dominio (implements DomainEvent o AuditableEvent)
3. Domain/Entity/            → Crear entidad con dddCreate() + trait RecordsEvents si aplica
4. Domain/Exception/         → Crear excepciones para reglas de negocio violadas
5. Domain/Interfaces/        → Definir contrato del repositorio
6. Application/<CasoDeUso>/  → Caso de uso (recibe EventBusInterface) + Command + Response
                               → El UC llama eventBus->publish() o entidad->pullDomainEvents()
7. Infrastructure/Persistence/→ Modelo Eloquent + Repositorio concreto
8. Infrastructure/Services/    → Implementar servicios de dominio (si aplica)
9. Infrastructure/Broadcasting/ → Si el evento debe llegar al frontend vía WS:
                               →  · Crear <X>StatusChanged (ShouldBroadcastNow)
                               →  · Crear <X>BroadcastSubscriber (EventSubscriber)
                               →  · Registrar subscriber en AppServiceProvider
10. Infrastructure/Entrypoint/ → Controller + Form Request
11. routes/api/               → Registrar ruta apuntando al controlador
12. tests/                   → Unit (dominio + subscriber) + Feature (HTTP) + E2E si flujo crítico
```

### Reglas de oro

- **Reglas de negocio:** Siempre en la entidad o en el VO. Nunca en el controlador ni en el modelo Eloquent.
- **Persistencia:** El caso de uso recibe `RepositoryInterface` por inyección. La implementación concreta se registra en `AppServiceProvider`.
- **Validación:** Form Request sanitiza la entrada HTTP → Command DTO la transporta al caso de uso → VO valida el dato de dominio.
- **Errores:** Excepciones de dominio se mapean a HTTP en el controlador (404 → NotFound, 409 → Conflict, 422 → Validation).
- **Eventos de dominio:** El caso de uso llama `eventBus->publish()`. Los subscribers (auditoría, broadcast) reaccionan sin que el UC sepa quién escucha. No usar eventos de Laravel (`Event::dispatch`) directamente en casos de uso; el bus de dominio es el contrato.
- **Broadcast a WS:** Solo a través de `*BroadcastSubscriber` en `Infrastructure/Broadcasting/`. El evento de dominio debe llevar `restaurantId` para poder enrutar al canal correcto (`restaurant.{id}`).
- **Frontend:** Los servicios (`TpvService`, `ChargeSessionService`) consumen la API. Los `facade` gestionan el estado reactivo con Angular Signals. `MesasFacade` mantiene suscripción WebSocket vía `EchoService` para actualizaciones en tiempo real.

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
| [`DASHBOARD_FINANZAS_PLAN.md`](./DASHBOARD_FINANZAS_PLAN.md) | Tech Lead / Backend | Plan técnico del Dashboard de Finanzas: contratos API, decisiones de diseño, estado de fases, deuda técnica |

---

## 12. Próximos pasos y roadmap técnico

### Recientemente completado ✅

- **Bus de eventos síncrono** — `InMemorySyncEventBus` con `AuditEventSubscriber` (cross-cutting auditoría) + `TablesBroadcastSubscriber` + `TablesGroupBroadcastSubscriber`. Todos los módulos migrados.
- **Personalización de familias** — Color (hex) e icono (set Material) en backoffice y TPV.
- **Tiempo real de mesas (Reverb)** — Canal `restaurant.{id}`, 12 eventos cubiertos, `MesasFacade` con `reloadOpenOrders()` + `reloadTables()`. 2/2 E2E Playwright.
- **Traslado de mesa** — `OrderTransferred` event, UC `TransferOrder`, endpoint `POST /api/tpv/orders/{id}/transfer`.
- **Impresión en impresora térmica** — Integración ESC/POS por TCP. Configuración por zona con fallback a impresora por defecto. Backend: `Printer` dominio con `PrintPreTicket`, `PrintFinalTicket`, `PrintOnSaleClosedSubscriber`. Frontend: Gestión → Impresoras con CRUD y test de conexión. Pre-cuenta, ticket de pago y ticket final con fallback a ventana del navegador.

### Corto plazo (1–2 meses)

1. **Dashboard de finanzas — fases pendientes** — Tab Impuestos (desglose IVA por tipo, base imponible y resumen trimestral Modelo 303) y Tab Informes (exportación real a CSV/PDF de los reportes existentes).
2. **Diseño interactivo del salón** — Editor drag & drop de mesas sobre plano (pos_x, pos_y, width, height). Backoffice para editar layout; TPV en modo solo lectura sobre el plano.

### Medio plazo (3–6 meses)

4. **Descuentos y promociones** — Descuento por línea (% o importe fijo), descuento global en ticket, cupones y promociones temporales (los menús del día con precio cerrado ya están operativos).
5. **Reservas** — Calendario de reservas con nombre, teléfono, número de comensales y asignación automática a mesa.
6. **Bus de eventos asíncrono** — Mover listeners no críticos (notificaciones push, stock, sincronización de BI) a jobs `ShouldQueue` con Redis/Horizon.

### Largo plazo (6–12 meses)

7. **Cola offline** — Soporte para cobros sin conexión con sincronización automática al recuperar red.
8. **App móvil para cliente** — Carta digital, pedido desde mesa (QR), pago split desde el móvil del comensal.
9. **Integración fiscal** — Adaptadores para TicketBAI (País Vasco) y Veri*Factu (nacional) según normativa.

---

## 13. Notas para el despliegue en producción

Esta sección es orientativa para cuando se migre de demo a producción real:

- **Base de datos:** Migrar de MySQL local a servicio gestionado (AWS RDS, Google Cloud SQL, Azure Database). Habilitar backups automáticos diarios.
- **Almacenamiento de imágenes:** Mover imágenes de productos de disco local a S3-compatible (AWS S3, MinIO, DigitalOcean Spaces). El driver GD con WebP ya está instalado en el contenedor; el formato de salida es WebP calidad 85 (~70–90% menor peso que JPEG).
- **Cache y colas:** Activar Redis para cache de sesiones, rate limiting y colas de jobs (generación de Z-Report pesado).
- **SSL/TLS:** Configurar certificados Let's Encrypt en Nginx. Forzar HTTPS en todas las comunicaciones.
- **Monitoreo:** Integrar Sentry para errores en frontend y backend. Logs centralizados con ELK o Loki.
- **CI/CD:** Pipeline GitHub Actions / GitLab CI que ejecute `make test`, `make lint` y `make build-frontend` antes de mergear a `main`.

---

> **Autor:** Yurest  
> **Repositorio:** YurestIonic  
> **Entorno de desarrollo:** Docker Compose con servicios separados para API, frontend, MySQL y DbGate  
> **Demo local:** http://localhost:4200  
> **Última actualización:** 17 de junio de 2026
