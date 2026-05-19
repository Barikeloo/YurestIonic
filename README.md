# Yurest TPV — Sistema de Terminal Punto de Venta para Hostelería

> **Versión:** Demo funcional — Mayo 2026  
> **Stack:** Laravel 12 (backend) + Angular + Ionic (frontend)  
> **Arquitectura:** DDD + Hexagonal + Multi-tenant  

---

## 1. Qué es este proyecto

Un **TPV completo para hostelería** que permite gestionar la operativa diaria de un restaurante: configuración del negocio, toma de pedidos en salón, cobro dividido por comensales, cierre de caja con reporte Z, y gestión de usuarios con roles.

El sistema está pensado para uso real en tabletas táctiles, con autenticación por PIN para camareros, sincronización de estado de mesas, y una arquitectura preparada para escalar a múltiples restaurantes.

---

## 2. Cómo levantar el proyecto

### Prerrequisitos

- Docker + Docker Compose
- GNU Make

### Pasos (primera vez)

```bash
# 1. Clonar y entrar
cd /Users/yurest/Desktop/Worksito/yurestionic

# 2. Configurar entorno backend
cp backend/.env.example backend/.env

# 3. Levantar contenedores (API, frontend, MySQL, DbGate)
make start

# 4. Instalar dependencias y migrar base de datos
make install

# 5. Generar APP_KEY del backend
docker compose run --rm api php artisan key:generate

# 6. (Opcional) Servir frontend con live reload
make serve-frontend
```

### Servicios disponibles

| Servicio | URL | Descripción |
|---|---|---|
| **Frontend (Angular)** | http://localhost:4200 | Interfaz TPV y backoffice |
| **API (Laravel)** | http://localhost:8000 | API REST |
| **DbGate (MySQL)** | http://localhost:9051 | Cliente web de base de datos (conexión "Training MySQL" preconfigurada) |

### Comandos útiles (Makefile)

```bash
make start           # Levantar contenedores
make stop            # Detener contenedores
make restart         # Reiniciar
make recreate        # Reconstruir imágenes y levantar
make install         # composer install + migraciones
make db-migrate      # Ejecutar migraciones pendientes
make test            # Tests PHPUnit del backend
make test-frontend   # Tests Angular
make lint            # Formatear PHP con Laravel Pint
make build-frontend  # Build de producción Angular
make logs-backend    # Ver logs de Laravel en tiempo real
```

---

## 3. Cómo usar la aplicación (guía rápida)

### 3.1 Login

En http://localhost:4200 puedes acceder con:

| Rol | Email | Contraseña | PIN |
|---|---|---|---|
| **Admin** | `barmanolo@gmail.com` | `12345678` | `1234` |
| **Supervisor** | `maria@saona.com` | `12345678` | `2345` |
| **Operador** | `carlos@saona.com` | `12345678` | `3456` |
| **Operador** | `laura@saona.com` | `12345678` | `4567` |

También puedes usar **"Acceso rápido"** para entrar directamente con PIN sin escribir email.

### 3.2 Backoffice — Gestión del negocio

Accede desde el menú lateral a **"Gestión"**. Desde ahí puedes:

- **Familias** — Crear/editar categorías de productos (Bebidas, Entrantes, etc.)
- **Productos** — Alta de artículos con precio, impuesto, familia, imagen y stock
- **Impuestos** — Configurar tipos de IVA (21%, 10%, 4%)
- **Zonas** — Definir salones del local (Terraza, Salón, Barra)
- **Mesas** — Crear mesas asignadas a zonas
- **Usuarios** — Alta de camareros con rol (admin / supervisor / operator)

> Los seeders de demo (`SaonaDemoSeeder`) crean automáticamente un restaurante "Bar Manolo" con familias, productos, zonas y mesas listos para probar.

### 3.3 TPV — Flujo de venta completo

| Paso | Acción | Resultado |
|---|---|---|
| **1. Mesas** | Ve a **"Mesas"**. Verás las zonas y un grid de mesas. | Mesas libres en verde, ocupadas en rojo/naranja. |
| **2. Abrir mesa** | Toca una mesa libre → elige operador → indica número de comensales con el teclado numérico → **"Abrir mesa"**. | La mesa pasa a ocupada. Se crea una orden. |
| **3. Tomar pedido** | Navega por **familias** (tabs superiores) y toca **productos** para añadirlos. | El producto aparece en el resumen de líneas a la derecha. |
| **4. Gestionar líneas** | En el resumen puedes: **+1** / **-1** / **eliminar** líneas. | Cantidades y total se recalculan en tiempo real. |
| **5. Dividir cuenta (opcional)** | Toca **"Dividir cuenta"**. Puedes repartir por: **partes iguales**, **por líneas** (asignar cada producto a un comensal), o **por comensal**. | El sistema calcula cuánto le corresponde a cada uno. |
| **6. Cobrar** | Toca **"Cobrar"**. Aparece el teclado numérico con el total exacto. Si cambias el importe a menor, se detecta automáticamente como **pago parcial**. | Se genera la venta (ticket). |
| **7. Cerrar** | Confirma el cobro. La mesa vuelve a **libre** (verde). | El ticket queda registrado. Se puede imprimir. |

### 3.4 Cobro dividido avanzado

El sistema soporta **combinación de métodos de pago** dentro de una misma sesión de cobro:

- **Por líneas primero, equal después:** Asigna productos a comensales y cobra uno a uno. Si queda un restante (gastos comunes), cambia a "Partes iguales" y divide lo pendiente entre quienes quedan.
- **Toggle "Incluir comensales ya pagados":** En "Partes iguales", puedes marcar esta opción para dividir la deuda restante entre **todos** los comensales (incluidos los que ya pagaron líneas) o solo entre los **pendientes**.
- **Bloqueo de método:** Si inicias el cobro con "Partes iguales", el sistema **bloquea el cambio** a "Por líneas" o "Por comensal" para evitar inconsistencias contables.

### 3.5 Caja — Cierre de turno

- Abre una **sesión de caja** al inicio del turno (fondo inicial).
- Durante el turno, todos los cobros se vinculan a esa sesión.
- Al cerrar, el sistema genera automáticamente un **Z-Report** con: ventas por método de pago, movimientos de caja (entradas/salidas), propinas, discrepancias y hash de integridad SHA-256.

---

## 4. Features implementadas

### Hitos obligatorios completados

| Hito | Estado | Detalle |
|---|---|---|
| **Hito 1 — Modelo de datos** | 100% | 20+ migraciones, relaciones completas, soft deletes, shard key `restaurant_id` |
| **Hito 2 — API REST Backoffice** | 100% | CRUD completo: familias, impuestos, productos, zonas, mesas, usuarios. Auth email + PIN |
| **Hito 3 — Interfaz Backoffice** | 100% | Gestión funcional completa (~1600 líneas), formularios con validación, navegación clara |
| **Hito 4 — Front de Venta (TPV)** | 100% | Mesas, apertura, pedido, cobro, cierre, tickets. División de cuenta por partes iguales / líneas / comensal |

### Extras implementados (Hito 6 y más)

| Feature | Descripción |
|---|---|
| **Roles de usuario** | `admin`, `supervisor`, `operator`. Cada rol ve y puede hacer cosas distintas |
| **Autenticación por PIN** | Login rápido de 4 dígitos para operadores en tableta |
| **División de cuenta** | 3 modos: partes iguales, por líneas (tap & place), por comensal. Toggle para incluir/excluir pagados |
| **Métodos de pago mixtos** | Efectivo, tarjeta, Bizum, vale, invitación, otros. Múltiples pagos por venta |
| **Cierre de caja + Z-Report** | Reporte fiscal automático al cerrar turno con hash SHA-256 |
| **Pagos parciales manuales** | El camarero introduce cualquier importe; el sistema detecta si es parcial automáticamente |
| **Reembolso de líneas** | Cancelar líneas individuales de una venta ya cerrada con nota de abono |
| **Cancelación de ventas** | Ventas completas cancelables con motivo y trazabilidad |
| **SuperAdmin** | Dominio separado para gestionar la plataforma y todos los restaurantes |
| **Vinculación de dispositivo** | Flujo de device link para asociar tabletas al restaurante |
| **Quick access** | Usuarios frecuentes para acceso rápido en TPV |
| **Multi-tenancy** | `restaurant_id` como shard key en todas las tablas. Un despliegue sirve a múltiples restaurantes |
| **Seeders demo** | `SaonaDemoSeeder` con restaurante, usuarios, familias, productos, zonas y mesas listos para demo |

---

## 5. Arquitectura

### Stack tecnológico

| Capa | Tecnología |
|---|---|
| **Backend** | Laravel 12, PHP 8.3+, MySQL 8 |
| **Frontend** | Angular 19, Ionic, TypeScript, standalone components |
| **Infraestructura** | Docker Compose, Nginx (API), Node (Angular dev server) |
| **Base de datos** | MySQL 8 (puerto 3406 host / 3306 container) |
| **Herramientas** | DbGate (cliente web DB), Laravel Pint (linting), PHPUnit (tests) |

### DDD + Hexagonal

El backend sigue **Domain-Driven Design con arquitectura hexagonal**. Cada dominio es autocontenido:

```
App/<Dominio>/
├── Domain/
│   ├── Entity/         # Entidades puras (dddCreate, reglas de negocio)
│   ├── ValueObject/    # VOs inmutables con constructor privado + create()
│   └── Interfaces/     # Contratos (repositorios, servicios)
├── Application/
│   └── <CasoDeUso>/    # Orquestador + Response DTO
└── Infrastructure/
    ├── Persistence/
    │   ├── Models/       # Eloquent
    │   └── Repositories/ # Implementaciones de interfaces
    ├── Services/         # Implementaciones de servicios (hashers, etc.)
    └── Entrypoint/Http/  # Controladores (1 acción = 1 __invoke)
```

### Dominios principales

| Dominio | Responsabilidad |
|---|---|
| `User` | Gestión de usuarios, login, roles, PIN |
| `SuperAdmin` | Gestión de plataforma y restaurantes |
| `Restaurant` | Datos del negocio |
| `Family` | Categorías de productos |
| `Product` | Catálogo con precio, impuesto, stock |
| `Tax` | Tipos de IVA |
| `Zone` | Salones del local |
| `Table` | Mesas físicas |
| `Order` | Pedidos abiertos (mutable) |
| `Sale` | Ventas cerradas (inmutable fiscal) |
| `Cash` | Sesiones de caja, movimientos, Z-Report |
| `ChargeSession` | División de cuenta por comensales |

### Decisiones técnicas clave

| Decisión | Justificación |
|---|---|
| **Separar `Order` y `Sale`** | Los pedidos abiertos tienen ciclo de vida mutable; las ventas son documentos fiscales inmutables |
| **`restaurant_id` como shard key** | Prepara el sistema para multi-tenancy real y particionamiento horizontal |
| **Login por PIN** | En hostelería los camareros comparten tabletas. Un PIN de 4 dígitos es más rápido que email+password |
| **VOs con constructor privado** | Imposible crear un objeto en estado inválido. Las validaciones se ejecutan siempre |
| **Responses como DTOs** | El controlador no depende de la entidad del dominio para serializar. Cambios internos no rompen la API |
| **Pagos mixtos como colección** | Un ticket puede pagarse con efectivo + tarjeta + Bizum simultáneamente |
| **Deuda viva de la mesa** | El sistema nunca recalcula el pasado. Los pagos reducen la deuda restante; todo es append-only |

---

## 6. Estructura del repositorio

```
yurestionic/
├── backend/                    # Laravel 12 — API REST
│   ├── app/
│   │   ├── Shared/Domain/ValueObject/   # Uuid, DomainDateTime, Email (reutilizables)
│   │   ├── <Dominio>/                   # User, Order, Sale, Cash, ChargeSession...
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   │       └── SaonaDemoSeeder.php      # Demo data
│   ├── routes/api/                      # auth.php, tpv.php, admin.php...
│   └── tests/                           # PHPUnit (unit + integration)
├── frontend/                   # Angular + Ionic
│   └── src/app/
│       ├── pages/core/
│       │   ├── mesas/          # Vista de zonas y mesas
│       │   ├── pedidos/        # Toma de pedido (catálogo + resumen)
│       │   ├── caja/           # Cobro, split bill, cierre de caja
│       │   └── gestion/        # Backoffice CRUD
│       ├── features/cash/      # Servicios y modales de cobro
│       └── services/           # TpvService, AuthService...
├── docs/                       # Documentación técnica adicional
├── Makefile                    # Comandos de desarrollo
└── docker-compose.yml          # Servicios: api, frontend, db, dbgate
```

---

## 7. Documentación adicional

| Documento | Contenido |
|---|---|
| [`ROADMAP.md`](./ROADMAP.md) | Hitos del proyecto, criterios de evaluación, mejoras propuestas |
| [`DATA_MODEL.md`](./DATA_MODEL.md) | Esquema completo de base de datos con tipos y relaciones |
| [`TPV_COBROS_README.md`](./TPV_COBROS_README.md) | Filosofía del sistema de cobros: deuda viva, append-only |
| [`docs/CAJA_DESIGN.md`](./docs/CAJA_DESIGN.md) | Especificación funcional del módulo Caja (casuística completa) |
| [`backend/FASES_IMPLEMENTADAS.md`](./backend/FASES_IMPLEMENTADAS.md) | Detalle de Fases 3 y 4: multi-payment, CancelSale, Z-Report |
| [`CHARGE_SESSION_PROGRESS.md`](./CHARGE_SESSION_PROGRESS.md) | Progreso del sistema de pago a partes iguales |
| [`RESUMEN_CAJA_FLUJO.md`](./RESUMEN_CAJA_FLUJO.md) | Resumen de estabilización de cobros y división de cuenta |
| [`AGENTS.md`](./AGENTS.md) | Convenciones de código para IAs (DDD, VOs, estilo) |

---

## 8. Próximos pasos sugeridos

1. **Informes y dashboard** (Hito 5) — Ventas por día, producto, usuario; gráficas de evolución
2. **Actualización en tiempo real** — WebSockets para sincronizar mesas entre tabletas
3. **Impresión de tickets** — Integración con impresoras térmicas ESC/POS
4. **Descuentos** — Por línea y por ticket total
5. **Traslado de mesa** — Mover un pedido abierto de una mesa a otra
6. **Auditoría inmutable** — `AuditLog` con trazabilidad completa de quién hizo qué y cuándo
7. **Cola offline** — Soporte para cobros sin conexión con sincronización posterior

---

> **Autor:** Yurest  
> **Entorno de desarrollo:** Docker Compose con servicios separados para API, frontend, MySQL y DbGate  
> **Demo lista en:** http://localhost:4200
