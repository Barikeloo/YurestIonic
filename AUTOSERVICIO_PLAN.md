# Plan de Implementación — Autoservicio QR

> **Estado:** En desarrollo — Sprint 1 completado  
> **Versión:** 4.0 — actualizado 2026-06-19  
> **Cambios v4:** Sistema de identidad en tres niveles (anónimo / solo nombre / con cuenta); `customer_accounts` por restaurante con email+contraseña; endpoints de registro y login en flujo QR; pantalla 0 rediseñada con selector de modo; `identity_mode` en guest_sessions; puntos y ofertas exclusivos para cuentas registradas.

---

## 1. Visión general

El módulo de **Autoservicio QR** permite a los comensales gestionar completamente su experiencia desde el móvil: llegan, escanean el QR de la mesa, la abren ellos mismos indicando cuántos son, piden a su ritmo, controlan cuándo quieren recibir cada parte del pedido (bebidas ahora, comida cuando quieran), y solicitan la cuenta cuando terminan. El camarero recibe notificaciones en tiempo real en el TPV y solo interviene para servir, cobrar y atender necesidades especiales.

### Principios de diseño

- **El comensal tiene el control** — Abre la mesa, decide el ritmo, elige cuándo recibir cada ronda.
- **El camarero como apoyo** — Recibe todo en el TPV en tiempo real; su rol se vuelve más servicio y menos toma de pedidos.
- **Sin fricción** — No requiere app, no requiere registro obligatorio. Escanear y listo.
- **Integración nativa** — Todo va al mismo `Order` del TPV. No hay sistema paralelo.
- **Fidelización opcional** — El comensal puede registrarse para acumular puntos.
- **Resiliente** — Opera con conexión intermitente; los datos críticos sobreviven cierres de navegador.

---

## 2. Flujo completo

```
EL COMENSAL LLEGA Y ESCANEA EL QR DE LA MESA
               │
               ▼
  ┌────────────────────────────────────┐
  │ ¿Hay sesión válida en localStorage?│
  └────────────────────────────────────┘
         │                     │
        NO                    SÍ
         │                     │
         ▼                     ▼
  ┌──────────────────┐   VALIDAR SESIÓN con backend
  │ ¿Pedido activo   │        │             │
  │ en esta mesa?    │      VÁLIDA        EXPIRADA
  └──────────────────┘        │             │
       │          │           ▼             ▼
      NO          SÍ     CARTA directa  Pantalla 0
       │          │       (skip paso 0)  con aviso
       ▼          ▼
  APERTURA    UNIRSE A SESIÓN
  DE MESA
  ────────────────────────────────────
  1. "¿Cuántas personas sois?"
  2. Nombre + email (opcional, loyalty)
  3. "Abrir mesa →"
  4. TPV notifica: "Mesa 3 abierta vía QR · 4 pax"
         │
         ▼
  ══════════════════════════════════════════
  EXPERIENCIA DE PEDIDO (múltiples rondas)
  ══════════════════════════════════════════

  ┌─────────────────────────────────────────────────────────┐
  │  RONDA 1 — Bebidas                                      │
  │                                                         │
  │  Comensal navega carta → añade bebidas al carrito       │
  │  "Enviar esta ronda" → bebidas van a cocina/barra       │
  │  TPV ve: "Carlos: 2x Coca-Cola, 1x Agua" [⚡ Guest]    │
  └─────────────────────────────────────────────────────────┘
                          │
                    (tiempo después)
                          │
  ┌─────────────────────────────────────────────────────────┐
  │  RONDA 2 — Comida (el comensal decide cuándo)           │
  │                                                         │
  │  Vuelve a la carta → añade platos                       │
  │  Puede elegir:                                          │
  │    • "Enviar ahora" → va inmediatamente                 │
  │    • "Guardar para después" → queda pendiente           │
  │  Cuando listos: "Enviar platos" → cocina recibe         │
  └─────────────────────────────────────────────────────────┘
                          │
                    (terminan de comer)
                          │
  ┌─────────────────────────────────────────────────────────┐
  │  CIERRE                                                 │
  │                                                         │
  │  Comensal pulsa "Pedir la cuenta"                       │
  │  TPV recibe alerta: "Mesa 3 · Carlos pide la cuenta"    │
  │  Camarero va a cobrar al TPV como siempre               │
  └─────────────────────────────────────────────────────────┘
```

---

## 3. Los dos cambios clave respecto al flujo tradicional

### 3.1 El comensal abre la mesa

En el flujo tradicional el camarero abre la mesa en el TPV. En el autoservicio **el comensal lo hace**:

| Aspecto | Flujo TPV tradicional | Flujo Autoservicio |
|---|---|---|
| ¿Quién abre la mesa? | Camarero | Comensal (desde el QR) |
| ¿Quién indica los comensales? | Camarero | Comensal |
| ¿Cuándo puede pedir? | Cuando el camarero abre | Inmediatamente después de escanear |
| Notificación al TPV | — | "Mesa N abierta vía QR · X pax" |

**Estados del QR al escanear:**

```
Estado A — Mesa sin pedido activo
  → El comensal ve la pantalla de apertura
  → Introduce número de comensales + identificación opcional
  → Pulsa "Abrir mesa" → se crea el Order en el backend
  → Puede empezar a pedir de inmediato

Estado B — Mesa con pedido activo (ya abierta por otro comensal o el camarero)
  → El comensal entra directamente a la carta
  → Se une como nueva sesión en la misma mesa
  → El TPV ve "nuevo comensal conectado: María"

Estado C — Mesa en estado COBRAR (ya se pidió la cuenta)
  → "Esta mesa está siendo cerrada. Consulta a tu camarero."

Estado D — Sesión válida en localStorage (comensal vuelve a la pestaña)
  → El frontend valida el session_token contra el backend
  → Si válido: salta directamente a la carta (sin mostrar pantalla 0)
  → Si expirado: muestra pantalla 0 con aviso "Tu sesión expiró, únete de nuevo"
```

**El camarero NO necesita abrir la mesa** cuando hay autoservicio. Pero puede hacerlo desde el TPV igualmente (para grupos que prefieren el servicio tradicional). Si el camarero abre la mesa antes que el comensal, el QR entra en el Estado B.

### 3.2 El comensal controla cuándo recibe cada parte del pedido

El sistema de **rondas de envío** da control total al comensal:

**Concepto de ronda:**
- El comensal añade productos al carrito sin prisa.
- Cuando quiere recibir algo, lo **marca como "enviar ahora"** y confirma esa ronda.
- Lo que no marca queda en el carrito como **"guardar para después"**.
- Puede hacer tantas rondas como quiera durante la visita.

**Ejemplo real:**
```
Llegamos → Escaneamos QR → Abrimos mesa (4 personas)
↓
Añadimos: 2 cervezas, 1 agua, 1 refresco, 1 ración de croquetas
"Enviar bebidas ahora" → marcamos solo las 4 bebidas
"Confirmar ronda" → 4 bebidas van a la barra
Croquetas quedan en carrito como pendiente
↓
(tomamos las bebidas, empezamos a hablar)
Añadimos más productos: 4 ensaladas, 2 hamburguesas, 2 chuletas
↓
Cuando tenemos hambre: "Ver carrito pendiente"
Marcamos: croquetas + 4 ensaladas → "Enviar entrantes"
Dejamos hamburguesas y chuletas para después
↓
(comemos los entrantes)
↓
"Ver carrito pendiente" → marcamos hamburguesas y chuletas
"Enviar platos principales"
↓
(terminamos de comer)
→ Añadimos 2 postres → "Enviar postres"
→ Cuando terminamos: "Pedir la cuenta"
```

---

## 4. Modelo de datos

### 4.1 Nuevas tablas

```sql
-- Token QR permanente por mesa
CREATE TABLE table_qr_tokens (
  id              CHAR(36) PRIMARY KEY,
  table_id        CHAR(36) NOT NULL UNIQUE,
  restaurant_id   CHAR(36) NOT NULL,
  token           VARCHAR(64) NOT NULL UNIQUE,
  catalog_version INT NOT NULL DEFAULT 1,   -- incrementa en cada cambio de carta
  created_at      TIMESTAMP NOT NULL,
  updated_at      TIMESTAMP NOT NULL,
  FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

-- Sesión de comensal (un dispositivo = una sesión)
CREATE TABLE guest_sessions (
  id                   CHAR(36) PRIMARY KEY,
  table_qr_token_id    CHAR(36) NOT NULL,
  order_id             CHAR(36) NULL,
  restaurant_id        CHAR(36) NOT NULL,
  session_token        VARCHAR(64) NOT NULL UNIQUE,
  identity_mode        ENUM('anonymous','named','registered') NOT NULL DEFAULT 'anonymous',
  -- 'anonymous'  → sin nombre; TPV muestra "Anónimo"
  -- 'named'      → tiene nombre, sin cuenta; TPV muestra el nombre
  -- 'registered' → tiene cuenta customer_accounts; acumula puntos y accede a ofertas
  guest_name           VARCHAR(100) NULL,           -- null si anonymous, nombre si named/registered
  customer_account_id  CHAR(36) NULL,               -- FK a customer_accounts; solo si registered
  opened_table         BOOLEAN NOT NULL DEFAULT FALSE,
  diners_count         SMALLINT NULL,
  check_requested_at   TIMESTAMP NULL,
  created_at           TIMESTAMP NOT NULL,
  expires_at           TIMESTAMP NOT NULL,
  FOREIGN KEY (table_qr_token_id) REFERENCES table_qr_tokens(id),
  FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id) ON DELETE SET NULL
);

-- Rondas de envío
CREATE TABLE guest_order_rounds (
  id               CHAR(36) PRIMARY KEY,
  guest_session_id CHAR(36) NOT NULL,
  order_id         CHAR(36) NOT NULL,
  restaurant_id    CHAR(36) NOT NULL,
  round_number     SMALLINT NOT NULL,          -- 1, 2, 3... por sesión (asignado con FOR UPDATE)
  label            VARCHAR(100) NULL,          -- "Bebidas", "Entrantes"... (libre)
  idempotency_key  VARCHAR(64) NOT NULL UNIQUE,-- evita doble envío por reintento
  submitted_at     TIMESTAMP NOT NULL,
  FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id)
);

-- Cuenta de cliente registrado (por restaurante)
CREATE TABLE customer_accounts (
  id                 CHAR(36) PRIMARY KEY,
  restaurant_id      CHAR(36) NOT NULL,
  name               VARCHAR(100) NOT NULL,
  email              VARCHAR(255) NOT NULL,
  password_hash      VARCHAR(255) NOT NULL,
  email_verified_at  TIMESTAMP NULL,
  points             INT NOT NULL DEFAULT 0,
  total_spent_cents  BIGINT NOT NULL DEFAULT 0,
  visits_count       INT NOT NULL DEFAULT 0,
  last_visit_at      TIMESTAMP NULL,
  created_at         TIMESTAMP NOT NULL,
  updated_at         TIMESTAMP NOT NULL,
  UNIQUE (restaurant_id, email)
);

-- Historial de visitas del cliente registrado
CREATE TABLE customer_visits (
  id                  CHAR(36) PRIMARY KEY,
  customer_account_id CHAR(36) NOT NULL,
  restaurant_id       CHAR(36) NOT NULL,
  order_id            CHAR(36) NOT NULL,
  guest_session_id    CHAR(36) NOT NULL,
  points_earned       INT NOT NULL DEFAULT 0,
  amount_cents        BIGINT NOT NULL DEFAULT 0,
  visited_at          TIMESTAMP NOT NULL,
  FOREIGN KEY (customer_account_id) REFERENCES customer_accounts(id)
);

-- Ofertas del restaurante para clientes registrados
CREATE TABLE customer_offers (
  id             CHAR(36) PRIMARY KEY,
  restaurant_id  CHAR(36) NOT NULL,
  title          VARCHAR(150) NOT NULL,
  description    TEXT NULL,
  discount_type  ENUM('percent','fixed_cents','points_multiplier') NOT NULL,
  discount_value INT NOT NULL,            -- %, céntimos o multiplicador x10
  min_points     INT NOT NULL DEFAULT 0,  -- puntos mínimos para activar la oferta
  valid_from     TIMESTAMP NULL,
  valid_until    TIMESTAMP NULL,
  active         BOOLEAN NOT NULL DEFAULT TRUE,
  created_at     TIMESTAMP NOT NULL,
  updated_at     TIMESTAMP NOT NULL
);
```

### 4.2 Modificaciones en tablas existentes

```sql
-- Alérgenos en productos → ✅ YA EXISTE
-- Migración 2026_05_15_100000_add_allergens_to_products_table.php ya aplicada.
-- Columna: products.allergens JSON NULLABLE
-- Códigos válidos definidos en ProductAllergens::ALLERGENS (14 valores EU)

-- Campo active → ✅ YA EXISTE (products.active BOOLEAN DEFAULT true)
-- Disponibilidad temporal de producto (sin tocar active)
ALTER TABLE products ADD COLUMN available BOOLEAN NOT NULL DEFAULT TRUE;
-- Diferencia con active:
--   active = FALSE  → producto eliminado del catálogo (backoffice lo desactivó permanentemente)
--   available = FALSE → producto temporalmente agotado (staff lo marca en caliente desde TPV)
--   El endpoint GET /catalog filtra: active = TRUE. Devuelve available para que el guest lo vea.

-- catalog_version en table_qr_tokens → ver sección 4.1 (nuevo campo en la tabla)
-- Se incrementa con UPDATE ... SET catalog_version = catalog_version + 1
-- cada vez que se crea/actualiza/desactiva un producto, familia, variante o modificador
-- del restaurante. Permite al cliente detectar si su caché está desactualizada.

-- Origen y contexto de guest en order_lines
ALTER TABLE order_lines ADD COLUMN origin ENUM('tpv','guest') NOT NULL DEFAULT 'tpv';
ALTER TABLE order_lines ADD COLUMN guest_session_id CHAR(36) NULL;
ALTER TABLE order_lines ADD COLUMN guest_name VARCHAR(100) NULL;
ALTER TABLE order_lines ADD COLUMN guest_round_id CHAR(36) NULL;
ALTER TABLE order_lines ADD COLUMN send_status ENUM('pending','sent') NOT NULL DEFAULT 'sent';
-- 'pending': en carrito del comensal; cocina/barra NO lo ve
-- 'sent':    enviado en una ronda; cocina ya lo recibió
```

> **Nota sobre `send_status`:** Las líneas con `send_status = 'pending'` existen en el backend pero la cocina/barra NO las ve todavía. Solo se "activan" cuando el comensal confirma la ronda.

### 4.3 Dominio backend: `GuestOrder`

```
App/GuestOrder/                                   ← directorio ya creado
├── Domain/
│   ├── Entity/
│   │   ├── TableQrToken.php
│   │   ├── GuestSession.php
│   │   ├── GuestOrderRound.php
│   │   └── LoyaltyProfile.php
│   ├── Event/
│   │   ├── TableOpenedByGuest.php        (AuditableEvent)
│   │   ├── GuestSessionStarted.php       (AuditableEvent)
│   │   ├── GuestRoundSubmitted.php       (AuditableEvent)
│   │   └── CheckRequestedByGuest.php     (AuditableEvent)
│   ├── ValueObject/
│   │   ├── GuestSessionToken.php
│   │   ├── GuestRoundLabel.php
│   │   ├── IdempotencyKey.php
│   │   └── LoyaltyPoints.php
│   └── Interfaces/
│       ├── TableQrTokenRepositoryInterface.php
│       ├── GuestSessionRepositoryInterface.php
│       ├── GuestOrderRoundRepositoryInterface.php
│       └── LoyaltyProfileRepositoryInterface.php
├── Application/
│   ├── GetTableStatus/           → estado mesa (none / open / to_charge)
│   ├── OpenTableByGuest/         → comensal abre la mesa → crea Order
│   ├── JoinGuestSession/         → comensal se une a mesa ya abierta
│   ├── ValidateGuestSession/     → valida session_token para recuperación de sesión
│   ├── RegisterCustomerAccount/  → crear cuenta (nombre + email + contraseña)
│   ├── LoginCustomerAccount/     → autenticar → customer_auth_token (15 min)
│   ├── GetCatalogForGuest/       → carta completa: productos, menús, alérgenos, available
│   ├── GetCatalogVersion/        → solo devuelve catalog_version (ligero, para polling)
│   ├── SavePendingLines/         → guardar en carrito backend, no enviar
│   ├── SubmitGuestRound/         → enviar ronda → cocina ve las líneas (idempotente)
│   ├── RequestCheck/             → pedir la cuenta
│   ├── GetGuestSessionSummary/   → resumen de lo pedido + datos de cuenta si registered
│   ├── GetActiveOffers/          → ofertas activas para el cliente registrado
│   └── CreditCustomerPoints/     → acreditar puntos al cerrar pedido (llamado desde TPV)
└── Infrastructure/
    ├── Persistence/
    ├── Broadcasting/             → listeners Reverb
    ├── Console/                  → cleanup jobs
    ├── Subscriber/
    └── Entrypoint/Http/
        ├── Public/               (sin auth, identificados por X-Guest-Session header)
        └── Admin/                (autenticados)
```

---

## 5. API — Endpoints

### 5.1 Autenticación de sesión guest

Los endpoints que operan sobre datos del comensal (carrito, rondas, cuenta) requieren identificar la sesión. Se usa el header HTTP:

```
X-Guest-Session: {session_token}
```

El `session_token` es un UUID generado en `localStorage` del dispositivo en el momento de abrir o unirse a la sesión. No es un Bearer JWT — es el mismo token almacenado en `guest_sessions.session_token`. El backend valida que:

1. El token existe y no ha expirado (`expires_at > NOW()`).
2. El token pertenece a una sesión asociada al `table_qr_token` de la URL.

**Todos los endpoints públicos devuelven CORS** con los headers:
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, OPTIONS
Access-Control-Allow-Headers: Content-Type, X-Guest-Session
```
Configurado en el middleware de Laravel para el grupo de rutas `/public/table/*`.

### 5.2 Formato estándar de error

Todos los errores siguen esta estructura:

```json
{
  "error": {
    "code": "TABLE_ALREADY_OPEN",
    "message": "Esta mesa ya tiene un pedido activo.",
    "details": {}
  }
}
```

| HTTP | `code` | Significado |
|---|---|---|
| 400 | `VALIDATION_ERROR` | Payload inválido |
| 401 | `SESSION_REQUIRED` | Falta header X-Guest-Session |
| 401 | `INVALID_CREDENTIALS` | Email o contraseña incorrectos en login |
| 403 | `SESSION_EXPIRED` | El session_token expiró |
| 403 | `SESSION_MISMATCH` | El token no corresponde a esta mesa |
| 409 | `TABLE_ALREADY_OPEN` | Intento de abrir mesa ya abierta |
| 409 | `TABLE_TO_CHARGE` | Mesa en proceso de cobro, no acepta pedidos |
| 409 | `ROUND_ALREADY_SUBMITTED` | Idempotency key ya usada (ronda ya enviada) |
| 409 | `EMAIL_ALREADY_REGISTERED` | El email ya tiene cuenta en este restaurante |
| 410 | `PRODUCT_UNAVAILABLE` | Producto marcado como no disponible |
| 422 | `PENDING_LINES_ONLY` | submit-round recibió IDs de líneas ya enviadas |
| 429 | `RATE_LIMITED` | Límite de peticiones superado |

**Respuesta 429 completa:**
```json
{
  "error": {
    "code": "RATE_LIMITED",
    "message": "Demasiadas peticiones. Espera un momento.",
    "details": { "retry_after_seconds": 12 }
  }
}
```

### 5.3 Endpoints públicos

#### Identidad del comensal (auth de cuenta)

| Método | URL | Auth | Descripción |
|---|---|---|---|
| `POST` | `/public/table/{token}/auth/register` | — | Crear cuenta en este restaurante |
| `POST` | `/public/table/{token}/auth/login` | — | Iniciar sesión con cuenta existente |

#### Estado y apertura

| Método | URL | Auth | Descripción |
|---|---|---|---|
| `GET`  | `/public/table/{token}` | — | Estado de la mesa + info del restaurante |
| `POST` | `/public/table/{token}/open` | — | El comensal abre la mesa (cualquier modo de identidad) |
| `POST` | `/public/table/{token}/session` | — | Unirse a mesa ya abierta (cualquier modo de identidad) |
| `GET`  | `/public/table/{token}/session/validate` | X-Guest-Session | Validar sesión existente (recuperación) |
| `GET`  | `/public/table/{token}/catalog` | — | Carta completa (familias, productos, menús) |
| `GET`  | `/public/table/{token}/catalog/version` | — | Solo la versión actual del catálogo (polling ligero) |

#### Pedido y rondas

| Método | URL | Auth | Descripción |
|---|---|---|---|
| `POST` | `/public/table/{token}/cart/save` | X-Guest-Session | Guardar líneas pendientes (sin enviar) |
| `GET`  | `/public/table/{token}/cart` | X-Guest-Session | Ver el carrito pendiente |
| `POST` | `/public/table/{token}/cart/submit-round` | X-Guest-Session | Enviar ronda → cocina |
| `GET`  | `/public/table/{token}/my-orders` | X-Guest-Session | Historial de rondas de esta sesión |
| `POST` | `/public/table/{token}/request-check` | X-Guest-Session | Pedir la cuenta |

---

#### Detalle de payloads clave

**`GET /public/table/{token}` — Respuesta**
```json
{
  "restaurant": {
    "name": "Bar Manolo",
    "logo_url": "...",
    "primary_color": "#FF4D4D",
    "locale": "es"
  },
  "table": { "name": "Mesa 3", "zone": "Terraza" },
  "order_status": "none",        // "none" | "open" | "to_charge"
  "active_sessions_count": 0
}
```

**`POST /public/table/{token}/auth/register` — Body**
```json
{
  "name": "Carlos",
  "email": "carlos@email.com",
  "password": "••••••••"
}
```
Respuesta `201`:
```json
{
  "customer": {
    "id": "...",
    "name": "Carlos",
    "email": "carlos@email.com",
    "points": 0,
    "visits_count": 0,
    "active_offers": []
  },
  "customer_auth_token": "eyJ..."  // token corto (15 min) para pasar a /open o /session
}
```

**`POST /public/table/{token}/auth/login` — Body**
```json
{
  "email": "carlos@email.com",
  "password": "••••••••"
}
```
Respuesta `200`:
```json
{
  "customer": {
    "id": "...",
    "name": "Carlos",
    "email": "carlos@email.com",
    "points": 127,
    "visits_count": 8,
    "last_visit_at": "2026-05-10T21:00:00Z",
    "active_offers": [
      { "id": "...", "title": "10% en tu próxima visita", "discount_type": "percent", "discount_value": 10 }
    ]
  },
  "customer_auth_token": "eyJ..."
}
```
> El `customer_auth_token` no es la `session_token` de la mesa. Es un token de corta duración (15 min) que el frontend pasa en el cuerpo de `/open` o `/session` para vincular la cuenta con la sesión. Una vez vinculado, todas las operaciones usan `X-Guest-Session` como siempre.

**`POST /public/table/{token}/open` — Body**

El body varía según el modo de identidad elegido:

```json
// Modo anónimo
{
  "session_token": "abc123",
  "diners_count": 4,
  "identity_mode": "anonymous"
}

// Modo solo nombre
{
  "session_token": "abc123",
  "diners_count": 4,
  "identity_mode": "named",
  "guest_name": "Carlos"
}

// Modo con cuenta (previamente autenticado)
{
  "session_token": "abc123",
  "diners_count": 4,
  "identity_mode": "registered",
  "customer_auth_token": "eyJ..."
}
```

**`POST /public/table/{token}/session` — Body (unirse a mesa ya abierta)**

```json
// Modo anónimo
{
  "session_token": "xyz456",
  "identity_mode": "anonymous"
}

// Modo solo nombre
{
  "session_token": "xyz456",
  "identity_mode": "named",
  "guest_name": "María"
}

// Modo con cuenta
{
  "session_token": "xyz456",
  "identity_mode": "registered",
  "customer_auth_token": "eyJ..."
}
```

**`GET /public/table/{token}/session/validate` — Respuesta**
```json
{
  "valid": true,
  "guest_name": "Carlos",
  "order_status": "open",
  "expires_at": "2026-06-19T23:00:00Z"
}
```
Si la sesión no existe o expiró, devuelve `{ "valid": false }` con HTTP 200 (no es un error, es una consulta de estado).

**`GET /public/table/{token}/catalog` — Respuesta**
```json
{
  "version": 42,
  "families": [
    {
      "id": "...",
      "name": "Bebidas",
      "icon": "local_bar",
      "color": "#2196F3",
      "products": [
        {
          "id": "...",
          "name": "Coca-Cola",
          "description": "...",
          "price_cents": 250,
          "photo_url": "...",
          "allergens": ["sulphites"],
          "active": true,
          "available": true,
          "variants": [...],
          "modifiers": [...]
        }
      ]
    }
  ],
  "menus": [...]
}
```

**`GET /public/table/{token}/catalog/version` — Respuesta**
```json
{ "version": 42 }
```
Ligero. El cliente lo llama cada 60 segundos. Si `version` difiere de la versión en caché, relanza `GET /catalog` completo.

**`POST /public/table/{token}/cart/save` — Body**
```json
{
  "lines": [
    {
      "product_id": "...",
      "quantity": 2,
      "variant_id": "...",
      "modifier_ids": ["..."],
      "notes": "sin hielo"
    },
    {
      "menu_id": "...",
      "quantity": 1,
      "menu_selections": [
        { "section_id": "...", "product_id": "...", "variant_id": null }
      ]
    }
  ]
}
```
> El `session_token` va en el header `X-Guest-Session`, no en el body.  
> Las líneas se crean con `send_status = 'pending'`. La cocina NO las ve.

**`POST /public/table/{token}/cart/submit-round` — Body**
```json
{
  "line_ids": ["uuid1", "uuid2", "uuid3"],
  "round_label": "Bebidas",
  "idempotency_key": "550e8400-e29b-41d4-a716-446655440000"
}
```
> `idempotency_key`: UUID generado por el cliente en el momento de pulsar "Enviar ronda". Si la red falla y el cliente reintenta con la misma key, el backend devuelve la respuesta original sin crear una segunda ronda. Si la key ya fue usada, responde `409 ROUND_ALREADY_SUBMITTED` con los datos de la ronda ya creada.

### 5.4 Endpoints admin (autenticados)

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/api/admin/tables/{id}/qr-token` | Generar/regenerar token QR |
| `GET`  | `/api/admin/tables/{id}/qr-token` | Token actual + URL + QR image |
| `PATCH`| `/api/admin/products/{id}/availability` | Marcar producto disponible/agotado |
| `GET`  | `/api/admin/orders/{id}/guest-sessions` | Sesiones activas y sus rondas |
| `GET`  | `/api/admin/customers` | Lista de cuentas de clientes registrados |
| `GET`  | `/api/admin/customers/{id}` | Detalle + historial de visitas + puntos |
| `GET`  | `/api/admin/offers` | Lista de ofertas activas/inactivas |
| `POST` | `/api/admin/offers` | Crear oferta |
| `PATCH`| `/api/admin/offers/{id}` | Editar / activar / desactivar oferta |
| `DELETE`| `/api/admin/offers/{id}` | Eliminar oferta |

**`PATCH /api/admin/products/{id}/availability` — Body**
```json
{ "available": false }
```
> Al cambiar `available`, el backend incrementa `catalog_version` en `table_qr_tokens` del restaurante y emite el evento Reverb `ProductAvailabilityChanged` a todos los guests conectados a mesas del restaurante.

---

## 6. Frontend — Carta digital (interfaz del comensal)

### 6.1 Ruta Angular

```
/s/{token}   — ruta pública, sin AuthGuard, módulo independiente
```

### 6.2 Árbol de pantallas

```
/s/{token}
│
├── [0] ESTADO DE MESA + SELECTOR DE IDENTIDAD
│
│     ── PASO 1: Estado de la mesa ──────────────────────
│
│     Sub-estado A: SIN PEDIDO ACTIVO
│     ┌──────────────────────────────────────┐
│     │ "¡Bienvenidos a Mesa 3!"             │
│     │ "¿Cuántas personas sois?"            │
│     │ [  −  ]  [ 4 ]  [  +  ]             │
│     │                                      │
│     │ ¿Cómo quieres continuar?             │
│     │                                      │
│     │ ┌──────────────────────────────────┐ │
│     │ │ 👤 Entrar con mi cuenta          │ │  ← registrado
│     │ │    Acumula puntos y ofertas       │ │
│     │ └──────────────────────────────────┘ │
│     │ ┌──────────────────────────────────┐ │
│     │ │ ✏️  Poner solo mi nombre         │ │  ← named
│     │ │    Sin registro, rápido           │ │
│     │ └──────────────────────────────────┘ │
│     │ ┌──────────────────────────────────┐ │
│     │ │ 🚀 Entrar como anónimo           │ │  ← anonymous
│     │ │    Directo a la carta             │ │
│     │ └──────────────────────────────────┘ │
│     └──────────────────────────────────────┘
│
│     ── PASO 2A: Modo "Entrar con mi cuenta" ──────────
│     ┌──────────────────────────────────────┐
│     │ Iniciar sesión                       │
│     │ Email:       [___________________]   │
│     │ Contraseña:  [___________________]   │
│     │              [¿Olvidaste tu clave?]  │
│     │ [Entrar →]                           │
│     │ ─────────────────────────────────── │
│     │ ¿No tienes cuenta?  [Crear cuenta]  │
│     └──────────────────────────────────────┘
│
│     Si login correcto → banner bienvenida:
│     ┌──────────────────────────────────────┐
│     │ ¡Bienvenido de nuevo, Carlos! 🎉     │
│     │ Tienes 127 puntos acumulados         │
│     │ ⭐ Oferta: 10% en tu próxima visita  │
│     │ [Abrir mesa →]                       │
│     └──────────────────────────────────────┘
│
│     Sub-formulario "Crear cuenta":
│     ┌──────────────────────────────────────┐
│     │ Nombre:      [___________________]   │
│     │ Email:       [___________________]   │
│     │ Contraseña:  [___________________]   │
│     │ [Crear cuenta y abrir mesa →]        │
│     └──────────────────────────────────────┘
│
│     ── PASO 2B: Modo "Solo nombre" ───────────────────
│     ┌──────────────────────────────────────┐
│     │ Tu nombre (para que te reconozcamos) │
│     │ [___________________]                │
│     │ [Abrir mesa →]                       │
│     └──────────────────────────────────────┘
│
│     ── PASO 2C: Modo anónimo ─────────────────────────
│     → Sin formulario, pulsar "Entrar como anónimo"
│       abre la mesa directamente
│
│     Sub-estado B: MESA YA ABIERTA (cualquier modo)
│     ┌──────────────────────────────────────┐
│     │ "¡Únete a Mesa 3!"                   │
│     │ (mismo selector de identidad)        │
│     │ [Unirme y ver la carta →]            │
│     └──────────────────────────────────────┘
│
│     Sub-estado C: MESA EN COBRO
│     ┌──────────────────────────────────────┐
│     │ "Esta mesa está siendo cerrada."     │
│     │ "Consulta a tu camarero."            │
│     └──────────────────────────────────────┘
│
│     Sub-estado D: SESIÓN EXPIRADA (recovery)
│     ┌──────────────────────────────────────┐
│     │ "Tu sesión anterior expiró."         │
│     │ "Únete de nuevo para seguir pidiendo"│
│     │ → muestra sub-estado A o B según mesa│
│     └──────────────────────────────────────┘
│
│     ⚠ DETECCIÓN DE CAMBIO EN TIEMPO REAL:
│     Suscripción al canal presence-table.{table_id}.
│     Si mesa pasa "none" → "open": actualiza a B.
│     Fallback: polling GET /table/{token} cada 10s.
│
├── [1] CARTA
│     Header: logo + "Mesa 3 · Carlos" + [🛒 3] badge + [📋 Mis pedidos]
│     ─────────────────────────────────────────
│     Tabs: [ Bebidas ] [ Entrantes ] [ Platos ] [ Postres ] [ Menús ]
│     ─────────────────────────────────────────
│     Grid de productos (2 columnas en móvil):
│       ┌─────────┐  ┌─────────┐
│       │  📷     │  │  📷     │
│       │ Coca-   │  │ Cerveza │
│       │ Cola    │  │         │
│       │ 2,50€   │  │ 2,00€   │
│       │ ⚠ sulf. │  │         │
│       │ [+Add]  │  │ [+Add]  │
│       └─────────┘  └─────────┘
│
│     Producto agotado (available=false):
│       ┌─────────┐
│       │  📷     │
│       │ Croquetas│ ← overlay semitransparente
│       │ AGOTADO │
│       │ 8,00€   │
│       │ [—]     │ ← botón deshabilitado
│       └─────────┘
│
│     FAB flotante: [🛒 Carrito · 3 items · 7,50€]
│
├── [2] DETALLE DE PRODUCTO
│     Foto grande (full-width)
│     Nombre + precio base
│     Descripción
│     Alérgenos: [🌾 Gluten] [🥛 Lácteos] (iconos con tooltip)
│     ─────────────────────────────────────────
│     VARIANTE (si tiene):
│       ○ 33cl — 2,00€
│       ● 50cl — 2,80€
│     MODIFICADORES (si tiene):
│       ☑ Con hielo (+0,00€)
│       ☐ Sin gas (+0,00€)
│       ☐ Extra limón (+0,50€)
│     Nota: [_________________________]
│     [  −  ] [ 1 ] [  +  ]
│     [Añadir al carrito · 2,80€]
│
├── [3] CONFIGURAR MENÚ
│     "Menú del día · 12,00€"
│     PRIMER PLATO (elige 1): ○ Sopa ○ Ensalada ● Crema
│     SEGUNDO PLATO (elige 1): ● Lomo ○ Bacalao (+2,00€)
│     POSTRE (elige 1): ○ Flan ● Fruta
│     Total: 14,00€
│     [Añadir al carrito · 14,00€]
│
├── [4] CARRITO
│     ┌─────────────────────────────────────────────────────┐
│     │ PENDIENTE DE ENVIAR                                 │
│     │ ☑ 2× Coca-Cola 33cl ····················· 5,00€    │
│     │ ☑ 1× Cerveza 50cl ························ 2,80€   │
│     │ ☐ 2× Hamburguesa ························ 18,00€   │
│     │ ☐ 1× Menú del día ······················ 14,00€   │
│     └─────────────────────────────────────────────────────┘
│
│     "Marca lo que quieres recibir ahora.
│      Lo demás lo envías cuando quieras."
│
│     Ronda actual: [☑ 2× Coca-Cola + ☑ 1× Cerveza · 7,80€]
│     Etiqueta (opcional): [Bebidas___________]
│
│     [Enviar esta ronda →]
│     [Guardar todo para después]
│
│     Estado offline:
│     ⚠ "Sin conexión. Tu carrito está guardado.
│        Cuando recuperes internet, enviaremos tu pedido."
│     [Reintentar →]
│
├── [5] RONDA ENVIADA ✓
│     ✅ "¡Tu pedido está en camino!"
│     "Hemos enviado: Bebidas (3 productos)"
│
│     ┌────────────────────────────────────────┐
│     │ AÚN EN TU CARRITO (para después):      │
│     │  • 2× Hamburguesa                      │
│     │  • 1× Menú del día                     │
│     │ [Enviar cuando estés listo →]           │
│     └────────────────────────────────────────┘
│
│     [Seguir añadiendo productos →]
│     [Ver todo lo que he pedido]
│
└── [6] MIS PEDIDOS (historial de la sesión)
      ────────────── MI PEDIDO ──────────────
      RONDA 1 · Bebidas · hace 12 min
        ✓ 2× Coca-Cola 33cl ··········· 5,00€
        ✓ 1× Cerveza 50cl ············· 2,80€
      ─────────────────────────────────────────
      CARRITO PENDIENTE:
        ⏳ 2× Hamburguesa ············ 18,00€
        ⏳ 1× Menú del día ··········· 14,00€
        [Enviar ahora →]
      ─────────────────────────────────────────
      Mi total enviado: 7,80€ (+ 32,00€ pendiente)

      [════════════════════════════════════]
      [        🧾 PEDIR LA CUENTA         ]
      [════════════════════════════════════]

      (Si tiene loyalty)
      ⭐ Estás acumulando puntos en esta visita
      Tienes 47 puntos · Ganarás ~4 puntos hoy
```

### 6.3 Estructura de componentes Angular

```
src/app/public/guest-order/
├── guest-order.routes.ts
├── guest-order.page.ts               (orquestador de estado)
├── services/
│   ├── guest-order.service.ts        (HTTP + estado de mesa)
│   ├── guest-cart.service.ts         (carrito local en señal + retry queue)
│   ├── guest-session.service.ts      (gestión localStorage, recovery, expiración)
│   ├── guest-auth.service.ts         (login, register, customer_auth_token)
│   ├── guest-reverb.service.ts       (suscripciones Reverb: presencia, eventos)
│   └── catalog-cache.service.ts      (caché con versión + polling de versión)
├── components/
│   ├── table-status/
│   │   ├── identity-selector/         (tarjetas anónimo / nombre / cuenta)
│   │   ├── login-form/                (email + contraseña + enlace "crear cuenta")
│   │   ├── register-form/             (nombre + email + contraseña)
│   │   ├── named-form/                (campo nombre solo)
│   │   ├── welcome-back-banner/       (saludo + puntos + oferta activa)
│   │   ├── open-table-form/           (orquesta identity-selector + diners count)
│   │   └── join-session-form/         (misma lógica sin diners count)
│   ├── menu-catalog/
│   │   ├── family-tabs/
│   │   └── product-grid/
│   ├── product-detail/
│   ├── menu-configurator/
│   ├── guest-cart/
│   │   ├── cart-item/
│   │   ├── round-selector/
│   │   └── offline-banner/           (banner estado sin conexión)
│   ├── round-confirmed/
│   └── order-history/
└── pipes/
    └── allergen-icon.pipe.ts
```

### 6.4 Estado del carrito (cliente — Angular signals)

```typescript
interface CartLine {
  localId: string;               // UUID local antes de sync con backend
  backendLineId?: string;        // ID en BD cuando se guardó como pending
  type: 'product' | 'menu';
  name: string;
  quantity: number;
  productId?: string;
  menuId?: string;
  variantId?: string;
  variantName?: string;
  modifierIds: string[];
  modifierNames: string[];
  menuSelections?: GuestMenuSelection[];
  notes?: string;
  unitPrice: number;
  sendStatus: 'local' | 'pending' | 'sent';
  // 'local'   → solo en localStorage, no enviado al backend
  // 'pending' → guardado en backend como send_status='pending', cocina no ve
  // 'sent'    → enviado en una ronda, cocina ya lo recibió
}

interface RetryQueueEntry {
  idempotencyKey: string;
  lineIds: string[];
  roundLabel?: string;
  attemptedAt: string;         // ISO timestamp del intento fallido
}

interface CustomerAccountState {
  id: string;
  name: string;
  email: string;
  points: number;
  visitsCount: number;
  activeOffers: CustomerOffer[];
}

// Señales del carrito y sesión
readonly cart              = signal<CartLine[]>([]);
readonly retryQueue        = signal<RetryQueueEntry[]>([]);
readonly isOffline         = signal<boolean>(false);
readonly identityMode      = signal<'anonymous' | 'named' | 'registered'>('anonymous');
readonly customerAccount   = signal<CustomerAccountState | null>(null);  // null si no registered
readonly pendingToSend     = computed(() => cart().filter(l => l.sendStatus !== 'sent'));
readonly sentLines         = computed(() => cart().filter(l => l.sendStatus === 'sent'));
readonly cartTotal         = computed(() =>
  pendingToSend().reduce((s, l) => s + l.unitPrice * l.quantity, 0)
);
readonly estimatedPoints   = computed(() =>
  Math.floor(cartTotal() / 100)  // 1 punto por euro; solo aplica si registered
);
```

El carrito persiste en `localStorage` bajo la clave `guest_cart_{sessionToken}`.  
El `retryQueue` persiste bajo `guest_retry_{sessionToken}`. Se procesa automáticamente al recuperar la conexión (escuchando el evento `online` del navegador).

---

## 7. Integración con el TPV

### 7.1 Notificación de apertura por comensal

Cuando el comensal abre la mesa vía QR, el backend:
1. Crea el `Order` en el dominio `Order` existente.
2. Emite el evento `TableOpenedByGuest` → `OrderCreated`.
3. El listener Reverb notifica al canal `private-restaurant.{restaurant_id}`.
4. En el TPV, la mesa aparece como **ocupada** igual que si la abriera el camarero.
5. Badge especial en el panel: **"⚡ Abierta vía QR · 4 pax · Carlos"**.

### 7.2 Notificación de ronda enviada

Cuando el comensal confirma una ronda:
1. Las líneas con `send_status='pending'` pasan a `send_status='sent'` (transacción atómica).
2. Se asigna `round_number` con `SELECT MAX(round_number) + 1 FROM guest_order_rounds WHERE guest_session_id = ? FOR UPDATE`.
3. Se emite `GuestRoundSubmitted` → `OrderLinesAdded` (evento existente).
4. Reverb notifica al canal `private-restaurant.{restaurant_id}`.
5. El camarero ve las nuevas líneas con badge **"⚡ Guest · Carlos"** y el label de la ronda.
6. El sonido/vibración del TPV alerta al camarero (si está configurado).

### 7.3 Líneas en el TPV — visualización

```
┌──────────────────────────────────────────────┐
│ PEDIDO - Mesa 3 · Terraza                    │
│                                              │
│ 2  Coca-Cola 33cl ················· 5,00€   │
│    ⚡ Carlos · Ronda 1 · Bebidas             │
│                                              │
│ 1  Cerveza 50cl ··················· 2,80€   │
│    ⚡ Carlos · Ronda 1 · Bebidas             │
│                                              │
│ 2  Hamburguesa ···················· 18,00€  │
│    ⚡ Carlos · Ronda 2 · Platos              │
│                                              │
│ ⏳ 1  Menú del día (pendiente) ···· 14,00€  │  ← pending, visible solo para camarero
│    ⚡ Carlos · en carrito                    │
└──────────────────────────────────────────────┘
```

El camarero puede ver las líneas `pending` del comensal (útil para anticipar servicio), pero estas NO van a la cocina ni al KDS hasta que el comensal las envíe.

### 7.4 Modal QR en el panel de mesa

```
┌────────────────────────────────────────────────┐
│  QR Autoservicio · Mesa 3                      │
│                                                │
│  ┌──────────────────────────────────────────┐  │
│  │  (QR code generado)                      │  │
│  └──────────────────────────────────────────┘  │
│                                                │
│  https://tpv.local/s/a1b2c3d4e5               │
│                                                │
│  👥 2 comensales conectados                   │
│  📋 Carlos · 3 rondas enviadas                │
│  📋 María · 1 ronda enviada                   │
│                                                │
│  [Copiar enlace]   [Regenerar QR]             │
└────────────────────────────────────────────────┘
```

### 7.5 Alerta "Pedir la cuenta"

Cuando un comensal pulsa "Pedir la cuenta":
1. Backend marca `guest_sessions.check_requested_at`.
2. Emite evento Reverb al canal `private-restaurant.{restaurant_id}`.
3. En el TPV: badge rojo en el tab de zona + notificación en el panel de mesa:
   ```
   🧾 Carlos pide la cuenta — Mesa 3
   ```
4. El camarero gestiona el cobro desde el TPV normalmente.

### 7.6 Integración con KDS (Kitchen Display System)

Si el restaurante tiene pantalla de cocina independiente (KDS), las líneas de origen `guest` con `send_status='sent'` deben tratarse **exactamente igual** que las líneas de origen `tpv`. El KDS recibe ya el evento `OrderLinesAdded` (emitido por `GuestRoundSubmitted`), que es el mismo evento que genera el TPV cuando el camarero añade líneas. No requiere trabajo adicional siempre que el KDS esté suscrito a ese evento.

Si el KDS filtra por `origin`, debe incluir tanto `'tpv'` como `'guest'` en su query.

### 7.7 Split de cuenta — visibilidad en TPV

Cada `order_line` tiene `guest_session_id` y `guest_name`. El endpoint `GET /api/admin/orders/{id}/guest-sessions` devuelve el desglose por sesión:

```json
{
  "sessions": [
    {
      "guest_name": "Carlos",
      "session_id": "...",
      "lines_sent": [...],
      "subtotal_cents": 2580
    },
    {
      "guest_name": "María",
      "session_id": "...",
      "lines_sent": [...],
      "subtotal_cents": 1400
    }
  ],
  "total_cents": 3980
}
```

**MVP**: el camarero ve el desglose informativo en el modal de sesiones, pero el cobro se hace sobre el total de la mesa en el TPV como siempre.  
**Post-MVP**: añadir botón "Cobrar por separado" en el flujo de cobro del TPV, que genera una factura por `guest_session_id`.

---

## 8. Reverb — Canales y Payloads de Eventos

### 8.1 Estructura de canales

| Canal | Tipo | Quién suscribe | Propósito |
|---|---|---|---|
| `private-restaurant.{restaurant_id}` | Private | TPV | Todos los eventos del restaurante |
| `private-guest.{session_token}` | Private | Frontend guest | Eventos para esa sesión específica |
| `presence-table.{table_id}` | Presence | TPV + guests en esa mesa | Estado de quién está conectado (opcional, para indicador "X comensales conectados") |

> **Autenticación de canales**: Los canales `private-*` requieren autenticación Reverb. El canal `private-guest.{session_token}` se autentica pasando el `session_token` en el body del auth request; el backend valida que el token exista y no esté expirado.

### 8.2 Eventos: servidor → TPV (`private-restaurant.{restaurant_id}`)

**`TableOpenedByGuest`**
```json
{
  "event": "TableOpenedByGuest",
  "data": {
    "table_id": "...",
    "table_name": "Mesa 3",
    "order_id": "...",
    "guest_session_id": "...",
    "guest_name": "Carlos",
    "diners_count": 4,
    "opened_at": "2026-06-19T20:15:00Z"
  }
}
```

**`GuestSessionJoined`**
```json
{
  "event": "GuestSessionJoined",
  "data": {
    "table_id": "...",
    "order_id": "...",
    "guest_session_id": "...",
    "guest_name": "María",
    "joined_at": "2026-06-19T20:22:00Z"
  }
}
```

**`GuestRoundSubmitted`**
```json
{
  "event": "GuestRoundSubmitted",
  "data": {
    "table_id": "...",
    "table_name": "Mesa 3",
    "order_id": "...",
    "guest_session_id": "...",
    "guest_name": "Carlos",
    "round_id": "...",
    "round_number": 2,
    "round_label": "Bebidas",
    "line_ids": ["uuid1", "uuid2"],
    "submitted_at": "2026-06-19T20:18:00Z"
  }
}
```

**`CheckRequestedByGuest`**
```json
{
  "event": "CheckRequestedByGuest",
  "data": {
    "table_id": "...",
    "table_name": "Mesa 3",
    "order_id": "...",
    "guest_session_id": "...",
    "guest_name": "Carlos",
    "requested_at": "2026-06-19T21:45:00Z"
  }
}
```

### 8.3 Eventos: servidor → guest (`private-guest.{session_token}`)

**`OrderStatusChanged`**
```json
{
  "event": "OrderStatusChanged",
  "data": {
    "new_status": "to_charge",
    "message": "El camarero está preparando tu cuenta."
  }
}
```
> El frontend guest muestra un banner y deshabilita "Enviar ronda" y "Pedir la cuenta".

**`TableClosedByStaff`**
```json
{
  "event": "TableClosedByStaff",
  "data": {
    "message": "Esta mesa ha sido cerrada. Gracias por tu visita."
  }
}
```
> El frontend limpia `localStorage` y muestra pantalla de despedida.

**`ProductAvailabilityChanged`**
```json
{
  "event": "ProductAvailabilityChanged",
  "data": {
    "product_id": "...",
    "available": false
  }
}
```
> El frontend guest actualiza la señal del catálogo en memoria para marcar el producto como agotado sin recargar la página.  
> Si el comensal tiene ese producto en el carrito como `pending`, se muestra un aviso: "El producto X ya no está disponible. Se ha eliminado de tu carrito."

**`CatalogUpdated`**
```json
{
  "event": "CatalogUpdated",
  "data": { "version": 43 }
}
```
> Alternativa al polling de versión. El frontend compara con su versión en caché y recarga si difiere.

**`RoundAcknowledged`**
```json
{
  "event": "RoundAcknowledged",
  "data": {
    "round_id": "...",
    "round_number": 2,
    "idempotency_key": "550e8400-..."
  }
}
```
> Confirmación extra al guest de que la ronda fue recibida (además de la respuesta HTTP). Permite limpiar el `retryQueue`.

### 8.4 Canal presence: `presence-table.{table_id}`

Usado por el TPV para mostrar "X comensales conectados" en el modal QR, y por el guest en la pantalla 0 para detectar cambios de estado de la mesa en tiempo real.

```json
// Datos de presencia de cada miembro
{
  "guest_name": "Carlos",
  "role": "guest",          // "guest" | "staff"
  "joined_at": "2026-06-19T20:15:00Z"
}
```

---

## 9. Sistema de rondas — Mecánica detallada

### 9.1 Estados de una línea de pedido guest

```
LOCAL ──→ PENDING ──→ SENT
  │           │          │
  │           │          └── Cocina/barra/KDS lo ve y prepara
  │           └── Guardado en backend, comensal puede editar/eliminar
  └── Solo en localStorage del dispositivo (no sincronizado)
```

**Cuándo avanza de estado:**
- `local → pending`: el comensal pulsa "Guardar carrito" (sincroniza con backend pero no envía)
- `local → sent`: el comensal marca el item y pulsa "Enviar esta ronda" (va directo a sent)
- `pending → sent`: el comensal envía una ronda que incluye ese item

### 9.2 Reglas de negocio

- Las líneas `pending` NO son visibles para la cocina en ningún momento.
- El comensal puede eliminar líneas `pending` pero NO las `sent` (ya están en preparación).
- Si la mesa pasa a estado `to_charge`, las líneas `pending` se cancelan automáticamente y el comensal recibe un aviso vía Reverb.
- El camarero puede ver en el TPV qué líneas están en `pending` del comensal (información útil para anticipar servicio).
- Un comensal puede hacer múltiples rondas sin límite.
- El `round_number` es secuencial por `guest_session_id`, asignado con `SELECT ... FOR UPDATE` para evitar duplicados concurrentes.

### 9.3 UX del selector de ronda en el carrito

1. **Selección individual** (checkboxes) → pulsar "Enviar selección".
2. **Acciones rápidas**: "Enviar todo", "Enviar solo bebidas" (auto-detecta por familia).
3. **Etiquetar la ronda** (campo libre opcional).

---

## 10. Gestión de sesión del comensal

### 10.1 Ciclo de vida de la sesión

```
CREACIÓN                VALIDEZ              EXPIRACIÓN
─────────               ──────────────       ──────────
open o join       →     expires_at =         • 24h desde created_at
                        MIN(                  • o cuando el Order se cierra
                          created_at + 24h,     (el backend actualiza expires_at
                          order.closed_at       al cerrar el pedido)
                        )
```

### 10.2 Recuperación de sesión (browser close → reopen)

Cuando el comensal cierra la pestaña y vuelve a escanear el QR (o reabre la pestaña), el frontend:

1. Busca `guest_session_{qr_token}` en `localStorage`.
2. Si existe, llama a `GET /public/table/{token}/session/validate` con `X-Guest-Session`.
3. **Válida** → salta directamente a la Carta (pantalla 1). Carga el carrito desde `localStorage` (`guest_cart_{sessionToken}`). En segundo plano, reconcilia el carrito local con `GET /cart` del backend para detectar líneas que llegaron al backend pero no al cliente (o viceversa).
4. **Expirada o no existe** → muestra pantalla 0 sub-estado D ("Tu sesión expiró") + flujo estándar de apertura/unión.

**Reconciliación de carrito:**
```
localStorage lines  ←→  backend pending lines
Si hay líneas en backend pero no en local → añadirlas al cart local como 'pending'
Si hay líneas en local como 'pending' pero no en backend → guardarlas con POST /cart/save
Si hay líneas en local como 'local' → no tocar (solo existen en el dispositivo)
```

### 10.3 Multi-comensal en la misma mesa

**Visibilidad:**
- Cada comensal ve en "Mis pedidos" **solo sus propias rondas** (filtrado por `guest_session_id`).
- El total mostrado en la pantalla 6 es el **total propio**, no el de la mesa.
- No hay pantalla de "pedido conjunto de la mesa" en el MVP (post-MVP opcional).

**Concurrencia de rondas:**
- Dos comensales pueden enviar rondas simultáneamente sin conflicto: cada uno tiene su propio `round_number` secuencial dentro de su sesión.
- El TPV recibe dos eventos `GuestRoundSubmitted` independientes y los muestra como entradas separadas en el pedido.

**Notificaciones TPV al unirse:**
- Cada nuevo comensal que se une emite `GuestSessionJoined` al canal del restaurante.
- El modal QR del TPV muestra en tiempo real cuántos comensales están conectados (canal presence).

### 10.4 Regeneración de QR con sesiones activas

Cuando el camarero pulsa "Regenerar QR" en el TPV:
1. Se crea un nuevo `table_qr_tokens.token` (el anterior queda inválido para nuevos accesos).
2. Las sesiones ya creadas con el token anterior **siguen siendo válidas** hasta su `expires_at` (el session_token es el identificador real de la sesión, no el QR token).
3. Los comensales conectados no pierden su sesión; solo se invalida el enlace QR para que nuevas personas no puedan entrar con el código viejo.
4. La respuesta del endpoint confirma: `{ "regenerated": true, "active_sessions_preserved": 2 }`.

---

## 11. Disponibilidad de producto y caché de carta

### 11.1 Campo `available` vs campo `active`

| Campo | Quién lo controla | Significado | Visibilidad en carta |
|---|---|---|---|
| `active` | Backoffice (admin) | El producto existe en el catálogo | `active=false` → no aparece |
| `available` | Staff desde TPV en caliente | El producto está en stock hoy | `available=false` → aparece como "Agotado" |

El endpoint `GET /catalog` devuelve solo productos con `active=true`. Devuelve `available` para que el guest pueda mostrar el estado de agotado.

### 11.2 Caché del catálogo en el cliente

La carta es el recurso más pesado. Se cachea en `localStorage` bajo la clave `catalog_{qr_token}`:

```typescript
interface CatalogCache {
  version: number;       // catalog_version del backend en el momento de la descarga
  fetchedAt: string;     // ISO timestamp
  data: CatalogResponse; // respuesta completa del GET /catalog
}
```

**Estrategia de invalidación:**

| Mecanismo | Cuándo | Acción |
|---|---|---|
| Polling de versión | Cada 60 segundos (en foreground) | GET /catalog/version → si version > cache.version, recargar catálogo completo |
| Evento Reverb `CatalogUpdated` | Cuando el admin cambia la carta | Recargar catálogo completo inmediatamente |
| Evento Reverb `ProductAvailabilityChanged` | Cuando un producto se agota | Actualizar solo ese producto en memoria (sin recarga completa) |
| TTL de seguridad | Si `fetchedAt` > 30 minutos | Forzar recarga al próximo GET /catalog |

**Producto añadido al carrito que luego se agota:**
1. El guest recibe `ProductAvailabilityChanged` vía Reverb.
2. Si el producto está en su carrito como `pending` → toast: "El producto X ya no está disponible y se eliminó de tu carrito."
3. El frontend elimina la línea de `pending` y llama a `DELETE /cart/line/{id}`.
4. Si está como `local` → mismo aviso al intentar guardar/enviar.
5. El backend valida `available=true` en `SavePendingLines` y `SubmitGuestRound` y devuelve `410 PRODUCT_UNAVAILABLE` si algún producto está agotado en el momento del submit.

---

## 12. Resiliencia offline y manejo de errores

### 12.1 Detección de conectividad

El servicio `GuestCartService` escucha los eventos del navegador:
```typescript
window.addEventListener('online',  () => isOffline.set(false));
window.addEventListener('offline', () => isOffline.set(true));
```

Al recuperar la conexión (`online` event):
1. Procesar el `retryQueue` en orden.
2. Para cada entrada: llamar a `POST /cart/submit-round` con el `idempotency_key` original.
3. Si responde `200 OK` → eliminar del queue, marcar líneas como `sent`.
4. Si responde `409 ROUND_ALREADY_SUBMITTED` → la ronda llegó en el primer intento; eliminar del queue, marcar líneas como `sent`.
5. Si responde otro error → mantener en queue y mostrar toast con botón manual "Reintentar".

### 12.2 Comportamiento por endpoint en caso de error

| Endpoint | Error de red | Acción |
|---|---|---|
| `GET /table/{token}` | Toast "Sin conexión", mostrar última respuesta cacheada | No bloquea UX |
| `GET /catalog` | Usar catálogo del `localStorage` si existe y no ha expirado el TTL | No bloquea UX |
| `POST /cart/save` | Mantener como `local` en localStorage, reintentar al reconectar | No bloquea UX |
| `POST /cart/submit-round` | Añadir al `retryQueue`, mostrar banner offline | Bloquea hasta reconexión |
| `POST /request-check` | Reintentar 2× con backoff exponencial (1s, 3s), luego mostrar error con botón manual | Crítico para UX |

### 12.3 Estados de error en la UI

```
Error de red en submit-round:
  ┌────────────────────────────────────────────┐
  │ ⚠ Sin conexión                             │
  │ Tu pedido está guardado. Lo enviaremos     │
  │ automáticamente cuando recuperes internet. │
  │                           [Reintentar →]   │
  └────────────────────────────────────────────┘

Error 410 PRODUCT_UNAVAILABLE:
  ┌────────────────────────────────────────────┐
  │ ❌ "Croquetas" ya no está disponible        │
  │ Se eliminó de tu carrito.                  │
  │                             [Entendido]    │
  └────────────────────────────────────────────┘

Error 409 TABLE_TO_CHARGE:
  ┌────────────────────────────────────────────┐
  │ 🧾 La cuenta ya está siendo procesada      │
  │ No puedes añadir más productos.            │
  │ Consulta a tu camarero si hay un error.    │
  └────────────────────────────────────────────┘
```

---

## 13. Sistema de fidelización y cuentas

### 13.1 Los tres modos de identidad

| Modo | Lo que da el comensal | TPV muestra | Puntos | Ofertas | Historial |
|---|---|---|---|---|---|
| **Anónimo** | Nada | "Anónimo" | ✗ | ✗ | ✗ |
| **Solo nombre** | Un nombre | "Carlos" | ✗ | ✗ | ✗ |
| **Con cuenta** | Email + contraseña | "Carlos ⭐" | ✓ | ✓ | ✓ |

Los modos anónimo y solo nombre son idénticos en experiencia de pedido. La diferencia es puramente de identificación en el TPV y de acumulación de puntos.

### 13.2 Cuenta de cliente — mecánica

**Registro:**
- Nombre + email + contraseña.
- La cuenta es **por restaurante**: una cuenta en "Bar Manolo" no funciona en "Pizzería Roma".
- No hay verificación de email en el MVP (post-MVP se añade).

**Login:**
- Email + contraseña → `customer_auth_token` (válido 15 min).
- El token se usa una sola vez para abrir/unirse a mesa. Una vez vinculado, todo opera con `X-Guest-Session`.
- No hay "recordar sesión" entre visitas: el comensal hace login cada vez que escanea el QR.

**Reconocimiento en la bienvenida (login):**
```
¡Bienvenido de nuevo, Carlos! 🎉
─────────────────────────────────
⭐ 127 puntos acumulados
   Ganarás ~4 puntos en esta visita

🎁 Tienes una oferta activa:
   10% de descuento en tu próxima visita
   [Ver oferta]

[Abrir mesa →]
```

### 13.3 Acumulación de puntos

- **Ratio**: 1 punto por cada euro gastado (redondeado hacia abajo).
- **Cuándo**: al cerrar el pedido en el TPV, el camarero confirma el cobro y el backend calcula y acredita los puntos.
- **Visibilidad en tiempo real**: la pantalla 6 (Mis pedidos) muestra "Ganarás ~X puntos en esta visita" usando `estimatedPoints` calculado en el cliente.
- Las líneas `pending` (no enviadas) no cuentan para la estimación hasta que se envían.

### 13.4 Ofertas

El restaurante crea ofertas desde el backoffice (admin). Cada oferta tiene:

| Campo | Descripción |
|---|---|
| `title` | Texto corto visible al cliente |
| `description` | Detalle opcional |
| `discount_type` | `percent` / `fixed_cents` / `points_multiplier` |
| `discount_value` | Porcentaje, céntimos fijos o multiplicador (x10 ints, e.g. `20` = x2.0) |
| `min_points` | Puntos mínimos necesarios para ver/activar la oferta |
| `valid_from` / `valid_until` | Ventana temporal (null = sin límite) |

**Flujo de oferta MVP:**
1. La oferta se muestra al cliente en la pantalla de bienvenida (login) y en la pantalla 6 (Mis pedidos).
2. El cliente le enseña la oferta al camarero, quien la aplica manualmente en el TPV durante el cobro.
3. **Post-MVP**: aplicación automática de descuento en el TPV al identificar la cuenta del cliente.

### 13.5 Pantalla "Mis puntos" (dentro de pantalla 6)

Solo visible si `identityMode === 'registered'`:

```
─────────────────────────────────────────
⭐ CUENTA  Carlos
   127 puntos acumulados
   Ganarás ~4 puntos en esta visita

🎁 OFERTAS DISPONIBLES
   ┌──────────────────────────────────┐
   │ 10% de descuento                 │
   │ Muestra esto al camarero al pagar│
   │ Válido hasta 30-jun-2026         │
   └──────────────────────────────────┘

📋 HISTORIAL (últimas visitas)
   12-jun-2026 · 32,50€ · +3 pts
   05-jun-2026 · 18,00€ · +1 pt
   ─────────────────────────────────
```

### 13.6 Dominio backend: entidades de cuenta

```
App/GuestOrder/
└── Application/
    ├── RegisterCustomerAccount/
    ├── LoginCustomerAccount/
    ├── GetCustomerAccountForGuest/   (datos para pantalla "Mis puntos")
    ├── CreditCustomerPoints/         (llamado al cerrar pedido en TPV)
    └── GetActiveOffers/              (ofertas activas para un cliente)
```

### 13.7 Roadmap post-MVP

- Verificación de email al registrarse
- Recuperación de contraseña (email con enlace)
- Canjeo de puntos por descuentos directamente en el TPV
- Niveles de cliente (Bronce / Plata / Oro) con beneficios diferenciados
- Campañas de puntos ("2× puntos los lunes", "3× en tu cumpleaños")
- Notificaciones push opcionales (si el comensal da permiso en el navegador)

---

## 14. Alérgenos

> ✅ **Ya implementado en el backend.** No requiere trabajo adicional en backend ni backoffice.

### 14.1 Estado actual

Los alérgenos están completamente implementados desde `2026-05-15`:

- **Migración**: `2026_05_15_100000_add_allergens_to_products_table.php` → `products.allergens JSON NULLABLE`.
- **Value Object**: `App\Product\Domain\ValueObject\ProductAllergens` con los 14 códigos EU.
- **CRUD**: `CreateProduct`, `UpdateProduct`, `GetProduct`, `ListProducts` ya gestionan alérgenos.
- **Backoffice Angular**: el formulario de producto ya incluye el selector de alérgenos.

### 14.2 Códigos definidos en `ProductAllergens::ALLERGENS`

| Código | Nombre ES | Icono |
|---|---|---|
| `gluten` | Gluten | 🌾 |
| `crustaceans` | Crustáceos | 🦀 |
| `eggs` | Huevo | 🥚 |
| `fish` | Pescado | 🐟 |
| `peanuts` | Cacahuetes | 🥜 |
| `soy` | Soja | 🫘 |
| `dairy` | Lácteos | 🥛 |
| `nuts` | Frutos de cáscara | 🌰 |
| `celery` | Apio | 🌿 |
| `mustard` | Mostaza | 🌱 |
| `sesame` | Sésamo | 🌻 |
| `sulphites` | Dióxido de azufre y sulfitos | 🍷 |
| `lupin` | Altramuces | 🫛 |
| `molluscs` | Moluscos | 🦑 |

### 14.3 Lo único pendiente: exposición en endpoint público

El endpoint `GET /public/table/{token}/catalog` debe incluir `allergens[]` de cada producto (el campo ya existe en el dominio). Solo hay que mapearlo en el Response de `GetCatalogForGuest`.

### 14.4 En la carta del comensal

- Iconos debajo de cada producto en la grid.
- Tap en icono → tooltip con nombre completo en español.
- **Filtro de alérgenos**: "Ocultar productos con [selector]".
- El pipe `allergen-icon.pipe.ts` mapea código → emoji + nombre ES.

---

## 15. Diseño UI/UX — Principios

- **Mobile-first obligatorio**: 375–430px, sin scroll horizontal.
- **Máximo 3 taps** para pedir un producto simple.
- **Carrito siempre visible**: FAB flotante con total y contador.
- **Sin ambigüedad en rondas**: "Esto va a cocina AHORA" vs "Guardar para más tarde".
- **Feedback inmediato**: animaciones de confirmación, toasts de éxito.
- **Branding del restaurante**: color primario, logo, nombre en header.
- **Sin tiempos de espera**: carta cacheada; solo el submit necesita red.
- **Accesibilidad**: contraste mínimo AA, fuente mínima 16px, area táctil mínima 44px, iconos de alérgenos con etiqueta de texto (no solo color).
- **Internacionalización (i18n)**: MVP en español. La arquitectura debe permitir añadir idiomas: textos UI en archivos de traducción (`i18n/es.json`, `i18n/en.json`). Los nombres de productos y familias vienen del backend tal cual (la carta es del restaurante). Para el MVP basta con español; la infraestructura de i18n se monta desde el principio para no refactorizar después.

---

## 16. Seguridad

| Riesgo | Mitigación |
|---|---|
| Token predecible | 32 bytes aleatorios (256 bits), sin patrón |
| Abrir mesa sin ser cliente | El QR físico está en la mesa; solo quien está en el local lo ve |
| Spam de pedidos | Rate limiting 30 req/min por IP en endpoints públicos (respuesta 429 estándar) |
| Pedido a mesa cerrada | Verificar `Order.status === 'open'` en cada submit |
| Datos de fidelización | Email/teléfono solo accesibles con auth admin; nunca en logs |
| Sesión no expirada tras pago | `expires_at` = MIN(24h, order.closed_at) |
| `pending` lines huérfanas | Cleanup job: cancelar `pending` si el order lleva >24h sin actividad |
| Doble envío de ronda | `idempotency_key` UNIQUE en `guest_order_rounds`; respuesta idempotente en 409 |
| Sesión de otro comensal | Middleware valida que `X-Guest-Session` pertenezca al QR token de la URL |
| Regenerar QR invalida sesiones activas | No invalida; solo el token de URL cambia; sesiones existentes siguen por `expires_at` |
| CORS mal configurado | Middleware Laravel aplica CORS en todas las rutas `/public/table/*` con `Allow-Origin: *` y headers permitidos explícitos (`Content-Type`, `X-Guest-Session`) |
| Enumeración de sesiones | `session_token` es opaco (UUID v4); no hay endpoint para listar sesiones sin auth admin |

---

## 17. Roadmap — Orden de implementación

### Sprint 1 — Infraestructura base (backend) ✅ COMPLETADO 2026-06-19
- [x] Migración: `table_qr_tokens` (con `catalog_version`)
- [x] Migración: `guest_sessions` (con `identity_mode`, sin `customer_account_id` hasta Sprint 7)
- [x] Migración: `guest_order_rounds` (con `idempotency_key`)
- [x] Migración: `products.available` (boolean, default true)
- [x] Migración: `order_lines` columnas guest (`origin`, `send_status`, `guest_session_id`, `guest_name`, `guest_round_id`) — migración idempotente (columnas preexistían como char(36))
- [x] Dominio `GuestOrder`: `TableQrToken` entity + `QrToken`/`GuestSessionToken`/`IdentityMode`/`SendStatus` VOs + `TableStatusData` read model + `TableQrTokenRepositoryInterface` + `TableQrTokenNotFoundException`/`TableNotFoundException`
- [x] Endpoint `POST /api/admin/tables/{tableId}/qr-token` (generar/regenerar token QR)
- [x] Endpoint `GET /public/table/{token}` (estado de mesa con throttle 30 req/min)
- [x] Auto-creación de QR token al crear una mesa via `GuestOrderTableCreatedSubscriber`
- [x] Configuración CORS para rutas `/public/table/*` + `GUEST_APP_URL` en allowed origins
- [x] `AppServiceProvider`: binding `TableQrTokenRepositoryInterface`, binding manual `GenerateTableQrToken` (con `guestAppBaseUrl`), subscriber en event bus

> **Notas de implementación:**
> - `order_lines.guest_session_id` y `guest_round_id` son `char(36)` (UUID), no bigint FK — pre-existían en la BD con ese tipo.
> - `GuestOrderTableCreatedSubscriber` usa `DB::table` directamente para evitar `HasTenantScope` de `EloquentZone` (que requiere tenant context inexistente en event handlers).
> - `GUEST_APP_URL` env var necesaria (default: `http://localhost:4201`) para construir la URL del QR.

### Sprint 2 — Apertura de mesa y sesión (backend)
- [ ] Endpoint `POST /public/table/{token}/open`
- [ ] Endpoint `POST /public/table/{token}/session`
- [ ] Endpoint `GET /public/table/{token}/session/validate` (recuperación)
- [ ] Evento `TableOpenedByGuest` → canal Reverb `private-restaurant.{id}`
- [ ] Evento `GuestSessionJoined` → canal Reverb
- [ ] Canal presence `presence-table.{table_id}`
- [ ] ALTER `order_lines`: `origin`, `guest_session_id`, `guest_name`, `guest_round_id`, `send_status`
- [ ] ALTER `products`: columna `available BOOLEAN DEFAULT TRUE`
- [ ] Endpoint `GET /public/table/{token}/catalog` (carta + alérgenos + available)
- [ ] Endpoint `GET /public/table/{token}/catalog/version` (ligero)

### Sprint 3 — Sistema de pedidos y rondas (backend)
- [ ] Endpoint `POST /public/table/{token}/cart/save`
- [ ] Endpoint `GET /public/table/{token}/cart` (con X-Guest-Session)
- [ ] Endpoint `POST /public/table/{token}/cart/submit-round` (con idempotency_key)
- [ ] Lógica `SELECT ... FOR UPDATE` para asignar `round_number`
- [ ] Evento `GuestRoundSubmitted` → `OrderLinesAdded` → Reverb
- [ ] Evento `RoundAcknowledged` → canal `private-guest.{session_token}`
- [ ] Endpoint `GET /public/table/{token}/my-orders`
- [ ] Endpoint `POST /public/table/{token}/request-check`
- [ ] Evento `CheckRequestedByGuest` → Reverb
- [ ] Eventos `OrderStatusChanged` + `TableClosedByStaff` al canal guest
- [ ] Cleanup job: cancelar `pending` en orders >24h sin actividad

### Sprint 4 — Disponibilidad y caché de carta
- [ ] Endpoint `PATCH /api/admin/products/{id}/availability`
- [ ] Incrementar `catalog_version` al cambiar carta (productos, familias, variantes, modificadores)
- [ ] Evento `ProductAvailabilityChanged` → canal `private-guest.{session_token}` de todos los guests de las mesas del restaurante
- [ ] Evento `CatalogUpdated` → canal `private-guest.{session_token}`
- [ ] Validación `available=true` en `SavePendingLines` y `SubmitGuestRound`

### Sprint 5 — Carta digital guest (frontend Angular)
- [ ] Ruta pública `/s/:token` sin AuthGuard
- [ ] `GuestSessionService` (localStorage + recovery)
- [ ] `GuestOrderService` (HTTP)
- [ ] `GuestCartService` (señales + retryQueue + offline detection)
- [ ] `GuestReverbService` (suscripciones canales)
- [ ] `CatalogCacheService` (caché + polling versión + invalidación)
- [ ] Pantalla 0: estado de mesa
  - [ ] Sub-estado A: abrir mesa (stepper + identificación)
  - [ ] Sub-estado B: unirse
  - [ ] Sub-estado C: en cobro
  - [ ] Sub-estado D: sesión expirada
  - [ ] Suscripción presence-table para detectar cambios en tiempo real
  - [ ] Fallback polling cada 10s si Reverb no disponible
- [ ] Pantalla 1: carta completa
  - [ ] Tabs por familia
  - [ ] Grid con alérgenos y estado `available`
  - [ ] FAB carrito flotante
- [ ] Pantalla 2: detalle de producto (variantes + modificadores)
- [ ] Pantalla 3: configurador de menú
- [ ] Pantalla 4: carrito con selector de ronda
  - [ ] Checkboxes enviar/guardar
  - [ ] Label de ronda
  - [ ] Banner offline
  - [ ] Botón "Reintentar" manual
- [ ] Pantalla 5: confirmación de ronda + pendientes restantes
- [ ] Pantalla 6: mis pedidos + pedir cuenta
- [ ] Pipe `allergen-icon.pipe.ts`
- [ ] Filtro de alérgenos en carta
- [ ] i18n: infraestructura de traducción (MVP en español, listo para añadir idiomas)

### Sprint 6 — Integración TPV
- [ ] Modal QR en panel lateral de mesa
  - [ ] QR generado (librería `qrcode` npm o server-side)
  - [ ] Indicador de sesiones activas en tiempo real (canal presence)
  - [ ] Botón "Regenerar QR" (con confirmación)
- [ ] Badge "⚡ Guest · [nombre] · Ronda [N]" en líneas del TPV
- [ ] Visualización de líneas `pending` del comensal en TPV (informativa)
- [ ] Indicador "Abierta vía QR" en tarjeta de mesa
- [ ] Alerta "pedir la cuenta" vía Reverb en panel de mesa
- [ ] Vista de desglose por sesión en `GET /api/admin/orders/{id}/guest-sessions`

### Sprint 7 — Cuentas y fidelización MVP
- [ ] Migración: `customer_accounts`
- [ ] Migración: `customer_visits`
- [ ] Migración: `customer_offers`
- [ ] Dominio `GuestOrder`: `RegisterCustomerAccount`, `LoginCustomerAccount`
- [ ] Dominio `GuestOrder`: `GetActiveOffers`, `CreditCustomerPoints`
- [ ] Endpoint `POST /public/table/{token}/auth/register`
- [ ] Endpoint `POST /public/table/{token}/auth/login`
- [ ] Actualizar `POST /open` y `POST /session` para aceptar `identity_mode` + `customer_auth_token`
- [ ] Selector de identidad en pantalla 0 (3 tarjetas: anónimo / nombre / cuenta)
- [ ] Formulario login (email + contraseña)
- [ ] Formulario registro (nombre + email + contraseña)
- [ ] Formulario "solo nombre"
- [ ] Banner bienvenida de vuelta (puntos + oferta activa)
- [ ] `GuestAuthService` en Angular (login, register, customer_auth_token)
- [ ] Señal `customerAccount` en `GuestSessionService`
- [ ] Sección "Mis puntos" en pantalla 6 (solo si registered)
- [ ] Acumulación de puntos al cerrar pedido en TPV (`CreditCustomerPoints`)
- [ ] Endpoints admin: `GET /customers`, `GET /customers/{id}`
- [ ] Endpoints admin CRUD de ofertas
- [ ] Tests PHPUnit: `RegisterCustomerAccount`, `LoginCustomerAccount`, `CreditCustomerPoints`

### Sprint 8 — Calidad
- [ ] Tests PHPUnit: dominio GuestOrder + endpoints públicos + lógica idempotencia
- [ ] Tests PHPUnit: lógica de cancelación de `pending` al pasar a `to_charge`
- [ ] Tests E2E Playwright: flujo completo apertura → pedido → ronda → cuenta
- [ ] Tests E2E Playwright: flujo recovery (session restore desde localStorage)
- [ ] Tests E2E Playwright: flujo offline (submit-round con red cortada → reconexión → reenvío)
- [ ] Rate limiting en rutas públicas (con respuesta 429 estándar)
- [ ] Tests de componentes Angular: `GuestCartService` (retryQueue, offline)
- [ ] README + AUTOSERVICIO_PLAN actualizado con implementación real

---

## 18. Métricas de éxito

| Métrica | Objetivo |
|---|---|
| % pedidos vía autoservicio | > 60% de las mesas con QR activado |
| Tiempo medio apertura → primer pedido | < 3 minutos |
| Promedio de rondas por visita | ≥ 2 |
| Tasa de uso modo "con cuenta" | > 30% de los comensales |
| Cuentas nuevas creadas/semana | Aumenta 20% semana a semana |
| Tasa de login vs registro | Indica retención (objetivo: >50% login en mesas repetidas) |
| Tasa de recuperación de sesión exitosa | > 90% de los intentos de recovery |
| Tasa de reenvío desde retryQueue completado | > 95% (rondas enviadas tras reconexión) |
| Satisfacción camarero (encuesta) | Reducción de idas a mesa para tomar pedido |

---

*Documento vivo — actualizar al completar cada sprint.*
