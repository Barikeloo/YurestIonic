# Plan de Implementación — Autoservicio QR

> **Estado:** Planificación — pendiente de desarrollo  
> **Versión:** 2.0 — actualizado 2026-06-19  
> **Cambios v2:** El comensal abre la mesa (no el camarero). Sistema de rondas de envío con control total del comensal sobre cuándo recibe cada parte del pedido.

---

## 1. Visión general

El módulo de **Autoservicio QR** permite a los comensales gestionar completamente su experiencia desde el móvil: llegan, escanean el QR de la mesa, la abren ellos mismos indicando cuántos son, piden a su ritmo, controlan cuándo quieren recibir cada parte del pedido (bebidas ahora, comida cuando quieran), y solicitan la cuenta cuando terminan. El camarero recibe notificaciones en tiempo real en el TPV y solo interviene para servir, cobrar y atender necesidades especiales.

### Principios de diseño

- **El comensal tiene el control** — Abre la mesa, decide el ritmo, elige cuándo recibir cada ronda.
- **El camarero como apoyo** — Recibe todo en el TPV en tiempo real; su rol se vuelve más servicio y menos toma de pedidos.
- **Sin fricción** — No requiere app, no requiere registro obligatorio. Escanear y listo.
- **Integración nativa** — Todo va al mismo `Order` del TPV. No hay sistema paralelo.
- **Fidelización opcional** — El comensal puede registrarse para acumular puntos.

---

## 2. Flujo completo

```
EL COMENSAL LLEGA Y ESCANEA EL QR DE LA MESA
               │
               ▼
  ┌────────────────────────────────────┐
  │ ¿Hay pedido activo en esta mesa?  │
  └────────────────────────────────────┘
         │                     │
        NO                    SÍ
         │                     │
         ▼                     ▼
  APERTURA DE MESA        UNIRSE A SESIÓN
  ────────────────        ──────────────
  1. "¿Cuántas personas    Entra directo
     sois?" (stepper)      a la carta
  2. Nombre + email        como nueva
     (opcional, loyalty)   sesión individual
  3. "Abrir mesa →"
  4. TPV notifica:
     "Mesa 3 abierta
      vía QR · 4 pax"
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
  id            CHAR(36) PRIMARY KEY,
  table_id      CHAR(36) NOT NULL UNIQUE,
  restaurant_id CHAR(36) NOT NULL,
  token         VARCHAR(64) NOT NULL UNIQUE,
  created_at    TIMESTAMP NOT NULL,
  updated_at    TIMESTAMP NOT NULL,
  FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

-- Sesión de comensal (un dispositivo = una sesión)
CREATE TABLE guest_sessions (
  id                 CHAR(36) PRIMARY KEY,
  table_qr_token_id  CHAR(36) NOT NULL,
  order_id           CHAR(36) NULL,            -- vinculado al Order activo
  restaurant_id      CHAR(36) NOT NULL,
  session_token      VARCHAR(64) NOT NULL UNIQUE,
  guest_name         VARCHAR(100) NULL,
  guest_email        VARCHAR(255) NULL,
  guest_phone        VARCHAR(30) NULL,
  loyalty_profile_id CHAR(36) NULL,
  opened_table       BOOLEAN NOT NULL DEFAULT FALSE, -- este dispositivo abrió la mesa
  diners_count       SMALLINT NULL,                  -- solo si opened_table = true
  check_requested_at TIMESTAMP NULL,
  created_at         TIMESTAMP NOT NULL,
  expires_at         TIMESTAMP NOT NULL,
  FOREIGN KEY (table_qr_token_id) REFERENCES table_qr_tokens(id)
);

-- Rondas de envío (cada vez que el comensal confirma una tanda de productos)
CREATE TABLE guest_order_rounds (
  id               CHAR(36) PRIMARY KEY,
  guest_session_id CHAR(36) NOT NULL,
  order_id         CHAR(36) NOT NULL,
  restaurant_id    CHAR(36) NOT NULL,
  round_number     SMALLINT NOT NULL,          -- 1, 2, 3... por sesión
  label            VARCHAR(100) NULL,          -- "Bebidas", "Entrantes"... (libre)
  submitted_at     TIMESTAMP NOT NULL,
  FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id)
);

-- Perfil de fidelización
CREATE TABLE loyalty_profiles (
  id               CHAR(36) PRIMARY KEY,
  restaurant_id    CHAR(36) NOT NULL,
  name             VARCHAR(100) NOT NULL,
  email            VARCHAR(255) NULL,
  phone            VARCHAR(30) NULL,
  points           INT NOT NULL DEFAULT 0,
  total_spent_cents BIGINT NOT NULL DEFAULT 0,
  visits_count     INT NOT NULL DEFAULT 0,
  last_visit_at    TIMESTAMP NULL,
  created_at       TIMESTAMP NOT NULL,
  updated_at       TIMESTAMP NOT NULL,
  UNIQUE (restaurant_id, email),
  UNIQUE (restaurant_id, phone)
);

-- Historial de visitas del perfil de fidelización
CREATE TABLE loyalty_visits (
  id                 CHAR(36) PRIMARY KEY,
  loyalty_profile_id CHAR(36) NOT NULL,
  restaurant_id      CHAR(36) NOT NULL,
  order_id           CHAR(36) NOT NULL,
  points_earned      INT NOT NULL DEFAULT 0,
  amount_cents       BIGINT NOT NULL DEFAULT 0,
  visited_at         TIMESTAMP NOT NULL,
  FOREIGN KEY (loyalty_profile_id) REFERENCES loyalty_profiles(id)
);
```

### 4.2 Modificaciones en tablas existentes

```sql
-- Alérgenos en productos
ALTER TABLE products ADD COLUMN allergens JSON NULL;
-- ["gluten","crustaceos","huevo","pescado","cacahuetes","soja",
--  "lacteos","frutos_cascara","apio","mostaza","sesamo",
--  "sulfitos","altramuces","moluscos"]

-- Origen y contexto de guest en order_lines
ALTER TABLE order_lines ADD COLUMN origin ENUM('tpv','guest') NOT NULL DEFAULT 'tpv';
ALTER TABLE order_lines ADD COLUMN guest_session_id CHAR(36) NULL;
ALTER TABLE order_lines ADD COLUMN guest_name VARCHAR(100) NULL;
ALTER TABLE order_lines ADD COLUMN guest_round_id CHAR(36) NULL;  -- a qué ronda pertenece
ALTER TABLE order_lines ADD COLUMN send_status ENUM('pending','sent') NOT NULL DEFAULT 'sent';
-- 'pending': el comensal lo tiene en carrito pero no ha enviado aún
-- 'sent': enviado a cocina/barra
```

> **Nota sobre `send_status`:** Las líneas con `send_status = 'pending'` existen en el backend pero la cocina/barra NO las ve todavía. Solo se "activan" cuando el comensal confirma la ronda. Esto permite que el comensal construya su pedido completo sin presión y lo envíe en el momento que quiera.

### 4.3 Dominio backend: `GuestOrder`

```
App/GuestOrder/
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
│   │   └── LoyaltyPoints.php
│   └── Interfaces/
│       ├── TableQrTokenRepositoryInterface.php
│       ├── GuestSessionRepositoryInterface.php
│       ├── GuestOrderRoundRepositoryInterface.php
│       └── LoyaltyProfileRepositoryInterface.php
├── Application/
│   ├── GetTableStatus/           (estado mesa: sin pedido / abierta / cobrando)
│   ├── OpenTableByGuest/         (comensal abre la mesa → crea Order)
│   ├── JoinGuestSession/         (comensal se une a mesa ya abierta)
│   ├── GetCatalogForGuest/       (carta completa: productos, menús, alérgenos)
│   ├── SavePendingLines/         (guardar en carrito backend, no enviar)
│   ├── SubmitGuestRound/         (enviar ronda → cocina ve las líneas)
│   ├── RequestCheck/             (pedir la cuenta)
│   └── GetGuestSessionSummary/   (resumen de lo pedido en esta sesión)
└── Infrastructure/
    ├── Persistence/
    └── Entrypoint/Http/
        ├── Public/               (sin auth)
        └── Admin/                (admin/supervisor)
```

---

## 5. API — Endpoints

### 5.1 Endpoints públicos (sin autenticación, solo token de mesa)

#### Estado y apertura

| Método | URL | Descripción |
|---|---|---|
| `GET`  | `/public/table/{token}` | Estado de la mesa + info del restaurante |
| `POST` | `/public/table/{token}/open` | El comensal abre la mesa |
| `POST` | `/public/table/{token}/session` | Unirse a mesa ya abierta |
| `GET`  | `/public/table/{token}/catalog` | Carta completa (familias, productos, menús) |

#### Pedido y rondas

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/public/table/{token}/cart/save` | Guardar líneas pendientes (sin enviar a cocina) |
| `GET`  | `/public/table/{token}/cart` | Ver el carrito pendiente del comensal |
| `POST` | `/public/table/{token}/cart/submit-round` | Enviar ronda → cocina/barra recibe |
| `GET`  | `/public/table/{token}/my-orders` | Historial de rondas de esta sesión |
| `POST` | `/public/table/{token}/request-check` | Pedir la cuenta |

---

#### Detalle de payloads clave

**`GET /public/table/{token}` — Respuesta**
```json
{
  "restaurant": { "name": "Bar Manolo", "logo_url": "...", "primary_color": "#FF4D4D" },
  "table": { "name": "Mesa 3", "zone": "Terraza" },
  "order_status": "none",        // "none" | "open" | "to_charge"
  "active_sessions_count": 0     // cuántos comensales están ya conectados
}
```

**`POST /public/table/{token}/open` — Body (el comensal abre la mesa)**
```json
{
  "session_token": "abc123",   // UUID generado en localStorage del dispositivo
  "diners_count": 4,
  "guest_name": "Carlos",      // opcional
  "guest_email": "carlos@email.com"  // opcional (loyalty)
}
```

**`POST /public/table/{token}/session` — Body (unirse a mesa ya abierta)**
```json
{
  "session_token": "xyz456",
  "guest_name": "María",
  "guest_email": "maria@email.com"
}
```

**`POST /public/table/{token}/cart/save` — Body (guardar en carrito, no enviar)**
```json
{
  "session_token": "abc123",
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
> Las líneas se crean con `send_status = 'pending'`. La cocina NO las ve.

**`POST /public/table/{token}/cart/submit-round` — Body (enviar una ronda)**
```json
{
  "session_token": "abc123",
  "line_ids": ["uuid1", "uuid2", "uuid3"],  // qué líneas enviar en esta ronda
  "round_label": "Bebidas"                   // opcional, libre
}
```
> Las líneas indicadas cambian a `send_status = 'sent'`. Se crea un `GuestOrderRound`. Se emite evento Reverb al TPV.

### 5.2 Endpoints admin (autenticados)

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/api/admin/tables/{id}/qr-token` | Generar/regenerar token QR |
| `GET`  | `/api/admin/tables/{id}/qr-token` | Token actual + URL + QR image |
| `GET`  | `/api/admin/orders/{id}/guest-sessions` | Sesiones activas y sus rondas |
| `GET`  | `/api/admin/loyalty` | Lista de perfiles de fidelización |
| `GET`  | `/api/admin/loyalty/{id}` | Detalle + historial de visitas |

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
├── [0] ESTADO DE MESA
│     ┌──────────────────────────────────────┐
│     │  Sin pedido activo                   │
│     │  "¡Bienvenidos a Mesa 3!"            │
│     │  "¿Cuántas personas sois?"           │
│     │  [  −  ]  [ 4 ]  [  +  ]            │
│     │  Nombre: [_______________]           │
│     │  Email:  [_______________] (loyalty) │
│     │  [Abrir mesa y empezar a pedir →]    │
│     └──────────────────────────────────────┘
│     ─ o ─
│     ┌──────────────────────────────────────┐
│     │  Mesa ya abierta (otros ya pidieron) │
│     │  "¡Únete a Mesa 3!"                  │
│     │  Nombre: [_______________]           │
│     │  [Unirme y ver la carta →]           │
│     └──────────────────────────────────────┘
│     ─ o ─
│     ┌──────────────────────────────────────┐
│     │  Mesa en cobro                       │
│     │  "Esta mesa está siendo cerrada."    │
│     │  "Consulta a tu camarero."           │
│     └──────────────────────────────────────┘
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
│     ─────────────────────────────────────────
│     FAB flotante: [🛒 Carrito · 3 items · 7,50€]  ← siempre visible
│
├── [2] DETALLE DE PRODUCTO
│     Foto grande (full-width)
│     Nombre + precio base
│     Descripción
│     Alérgenos: [🌾 Gluten] [🥛 Lácteos] (iconos con tooltip)
│     ─────────────────────────────────────────
│     VARIANTE (si tiene):
│       ○ 33cl — 2,00€
│       ● 50cl — 2,80€  ← seleccionado
│     ─────────────────────────────────────────
│     MODIFICADORES (si tiene):
│       ☑ Con hielo (+0,00€)
│       ☐ Sin gas (+0,00€)
│       ☐ Extra limón (+0,50€)
│     ─────────────────────────────────────────
│     Nota: [_________________________]
│     ─────────────────────────────────────────
│     [  −  ] [ 1 ] [  +  ]
│     [Añadir al carrito · 2,80€]  ← suma variantes + mods
│
├── [3] CONFIGURAR MENÚ
│     "Menú del día · 12,00€"
│     ─────────────────────────────────────────
│     PRIMER PLATO (elige 1):
│       ○ Sopa del día
│       ○ Ensalada mixta
│       ● Crema de verduras  ← seleccionada
│     ─────────────────────────────────────────
│     SEGUNDO PLATO (elige 1):
│       ● Lomo a la plancha (+0,00€)
│       ○ Bacalao al horno (+2,00€)  ← suplemento
│     ─────────────────────────────────────────
│     POSTRE (elige 1):
│       ○ Flan
│       ● Fruta del tiempo
│     ─────────────────────────────────────────
│     Total: 14,00€  (base + suplemento bacalao)
│     [Añadir al carrito · 14,00€]
│
├── [4] CARRITO
│     ┌─────────────────────────────────────────────────────┐
│     │ PENDIENTE DE ENVIAR                                 │
│     │ ─────────────────────────────────────────────────── │
│     │ ☑ 2× Coca-Cola 33cl ····················· 5,00€    │
│     │ ☑ 1× Cerveza 50cl ························ 2,80€   │
│     │ ☐ 2× Hamburguesa ························ 18,00€   │  ← no marcada
│     │ ☐ 1× Menú del día ······················ 14,00€   │  ← no marcada
│     └─────────────────────────────────────────────────────┘
│     
│     Instrucción visual: "Marca lo que quieres recibir ahora.
│                          Lo demás lo envías cuando quieras."
│     
│     Ronda actual: [☑ 2× Coca-Cola + ☑ 1× Cerveza · 7,80€]
│     Etiqueta (opcional): [Bebidas___________]
│     
│     [Enviar esta ronda →]   ← solo los marcados van a cocina
│     [Guardar todo para después]  ← guarda sin enviar
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
      RONDA 1 · Bebidas · hace 12 min
        ✓ 2× Coca-Cola 33cl ··········· 5,00€
        ✓ 1× Cerveza 50cl ············· 2,80€
      ─────────────────────────────────────────
      CARRITO PENDIENTE:
        ⏳ 2× Hamburguesa ············ 18,00€
        ⏳ 1× Menú del día ··········· 14,00€
        [Enviar ahora →]
      ─────────────────────────────────────────
      Total pedido hoy: 7,80€ (+ 32,00€ pendiente)
      
      [════════════════════════════════════]
      [        🧾 PEDIR LA CUENTA         ]   ← botón prominente
      [════════════════════════════════════]
      
      (Si tiene loyalty)
      ⭐ Estás acumulando puntos en esta visita
      Tienes 47 puntos · Ganarás ~4 puntos hoy
```

### 6.3 Estructura de componentes Angular

```
src/app/public/guest-order/
├── guest-order.routes.ts
├── guest-order.page.ts            (orquestador de estado)
├── services/
│   ├── guest-order.service.ts     (HTTP + estado de mesa)
│   └── guest-cart.service.ts      (carrito local en señal)
├── components/
│   ├── table-status/              (pantalla 0: abrir/unirse/cerrada)
│   │   ├── open-table-form/       (stepper comensales + identificación)
│   │   └── join-session-form/
│   ├── menu-catalog/              (pantalla 1: carta completa)
│   │   ├── family-tabs/
│   │   └── product-grid/
│   ├── product-detail/            (pantalla 2: detalle con vars+mods)
│   ├── menu-configurator/         (pantalla 3: selección de secciones)
│   ├── guest-cart/                (pantalla 4: carrito con selector de ronda)
│   │   ├── cart-item/
│   │   └── round-selector/        (checkboxes "enviar ahora" / "guardar")
│   ├── round-confirmed/           (pantalla 5: ronda enviada + pendientes)
│   └── order-history/             (pantalla 6: mis pedidos + pedir cuenta)
└── pipes/
    └── allergen-icon.pipe.ts
```

### 6.4 Estado del carrito (cliente — Angular signals)

```typescript
interface CartLine {
  localId: string;               // UUID local, antes de sync con backend
  backendLineId?: string;        // ID en la BD cuando se guardó como pending
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

// Señal global del carrito
readonly cart = signal<CartLine[]>([]);
readonly pendingToSend = computed(() => cart().filter(l => l.sendStatus !== 'sent'));
readonly sentLines    = computed(() => cart().filter(l => l.sendStatus === 'sent'));
readonly cartTotal    = computed(() => pendingToSend().reduce((s, l) => s + l.unitPrice * l.quantity, 0));
```

El carrito persiste en `localStorage` bajo la clave `guest_cart_{sessionToken}`.

---

## 7. Integración con el TPV

### 7.1 Notificación de apertura por comensal

Cuando el comensal abre la mesa vía QR, el backend:
1. Crea el `Order` en el dominio `Order` existente.
2. Emite el evento `TableOpenedByGuest` → `OrderCreated`.
3. El suscriptor Reverb notifica al canal del restaurante.
4. En el TPV, la mesa aparece como **ocupada** igual que si la abriera el camarero.
5. Badge especial en el panel: **"⚡ Abierta vía QR · 4 pax · Carlos"**.

### 7.2 Notificación de ronda enviada

Cuando el comensal confirma una ronda:
1. Las líneas con `send_status='pending'` pasan a `send_status='sent'`.
2. Se emite `GuestRoundSubmitted` → `OrderLinesAdded` (evento existente).
3. Reverb notifica al TPV.
4. El camarero ve las nuevas líneas con badge **"⚡ Guest · Carlos"** y el label de la ronda.
5. El sonido/vibración del TPV alerta al camarero (si está configurado).

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
│ 2  Hamburguesa (en cocina) ········ 18,00€  │
│    ⚡ Carlos · Ronda 2 · Platos              │
└──────────────────────────────────────────────┘
```

### 7.4 Modal QR en el panel de mesa

En el panel lateral del TPV (vista mesa), nuevo botón **"QR Autoservicio"**:

```
┌────────────────────────────────────────────────┐
│  QR Autoservicio · Mesa 3                      │
│                                                │
│  ┌──────────────────────────────────────────┐  │
│  │  ██████  ████  ████  ██████              │  │
│  │  ██  ██  ████  ████  ██  ██  (QR code)   │  │
│  │  ██████  ████  ████  ██████              │  │
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
2. Emite evento Reverb al canal del restaurante.
3. En el TPV: badge rojo en el tab de zona + notificación en el panel de mesa:
   ```
   🧾 Carlos pide la cuenta — Mesa 3
   ```
4. El camarero gestiona el cobro desde el TPV normalmente.

---

## 8. Sistema de rondas — Mecánica detallada

### 8.1 Estados de una línea de pedido guest

```
LOCAL ──→ PENDING ──→ SENT
  │           │          │
  │           │          └── Cocina/barra lo ve y prepara
  │           └── Guardado en backend, comensal puede editar/eliminar
  └── Solo en localStorage del dispositivo (no sincronizado)
```

**Cuándo avanza de estado:**
- `local → pending`: el comensal pulsa "Guardar carrito" (sincroniza con backend pero no envía)
- `local → sent`: el comensal marca el item y pulsa "Enviar esta ronda" (va directo a sent)
- `pending → sent`: el comensal envía una ronda que incluye ese item

### 8.2 Reglas de negocio

- Las líneas `pending` NO son visibles para la cocina en ningún momento.
- El comensal puede eliminar líneas `pending` pero NO las `sent` (ya están en preparación).
- Si la mesa pasa a estado `to_charge`, las líneas `pending` se cancelan automáticamente y el comensal recibe un aviso.
- El camarero puede ver en el TPV qué líneas están aún en `pending` del comensal (información útil para servir).
- Un comensal puede tener múltiples rondas. No hay límite de rondas.

### 8.3 UX del selector de ronda en el carrito

El carrito muestra todos los items pendientes. El comensal puede:

1. **Seleccionar items individualmente** (checkboxes) y pulsar "Enviar selección".
2. **Usar acciones rápidas**: "Enviar todo", "Enviar solo bebidas" (auto-detecta por familia).
3. **Etiquetar la ronda** (campo libre opcional): "Bebidas", "Entrantes", "Para mi solo"...

---

## 9. Sistema de fidelización

### 9.1 Mecánica MVP

- Al identificarse con email o teléfono → se crea o recupera `LoyaltyProfile`.
- **Reconocimiento automático**: si el email/teléfono coincide con una visita anterior, se vincula y se saluda por nombre con el saldo de puntos actual.
- **Acumulación**: 1 punto por euro gastado. Se calculan al cerrar el pedido en el TPV.
- **Pantalla "Mis puntos"** en la carta del comensal: saldo actual + estimación de puntos de esta visita.

### 9.2 Pantalla de identificación (pantalla 0, sub-formulario)

```
¡Bienvenidos!
─────────────────────────────────────────
Nombre *
[__________________________]

Email o teléfono  (para ganar puntos)
[__________________________]

  🌟 Con tu email acumulas puntos en cada visita
     y accedes a ofertas exclusivas

[Continuar sin identificarme]        [Continuar →]
```

Si el email/teléfono ya existe:
```
¡Bienvenido de nuevo, Carlos! 🎉
Tienes 127 puntos acumulados.
[Continuar como Carlos →]
```

### 9.3 Roadmap de fidelización (post-MVP)

- Canjear puntos por descuentos
- Niveles de cliente (Bronce/Plata/Oro) con beneficios
- Campañas ("2× puntos los lunes")
- Historial de visitas visible para el comensal
- Notificaciones push (si el comensal da permiso)

---

## 10. Alérgenos

### 10.1 Los 14 alérgenos de la UE (Reglamento 1169/2011)

| Código | Nombre ES | Icono |
|---|---|---|
| `gluten` | Gluten (trigo, cebada, centeno...) | 🌾 |
| `crustaceos` | Crustáceos | 🦀 |
| `huevo` | Huevo | 🥚 |
| `pescado` | Pescado | 🐟 |
| `cacahuetes` | Cacahuetes | 🥜 |
| `soja` | Soja | 🫘 |
| `lacteos` | Lácteos | 🥛 |
| `frutos_cascara` | Frutos de cáscara | 🌰 |
| `apio` | Apio | 🌿 |
| `mostaza` | Mostaza | 🌱 |
| `sesamo` | Sésamo | 🌻 |
| `sulfitos` | Dióxido de azufre y sulfitos | 🍷 |
| `altramuces` | Altramuces | 🫛 |
| `moluscos` | Moluscos | 🦑 |

### 10.2 En el backoffice (edición de producto)

Nueva sección "Alérgenos" con 14 checkboxes con icono y nombre. Guardados como JSON array.

### 10.3 En la carta del comensal

- Iconos pequeños de alérgenos debajo de cada producto en la grid.
- Tap en un icono → tooltip con el nombre completo.
- **Filtro de alérgenos** en la carta: "Ocultar productos con [selector de alérgenos]". Útil para celíacos, alérgicos, etc.

---

## 11. Diseño UI/UX — Principios

- **Mobile-first obligatorio**: diseñado para pantalla de 375–430px, sin scroll horizontal.
- **Máximo 3 taps** para pedir un producto simple (carta → detalle → añadir).
- **Carrito siempre visible**: FAB flotante con total y contador de items.
- **Sin ambigüedad en el sistema de rondas**: texto claro "Esto va a cocina AHORA" vs "Guardar para más tarde".
- **Feedback inmediato**: animaciones de confirmación, toasts de éxito.
- **Branding del restaurante**: color primario, logo, nombre en el header.
- **Sin tiempos de espera**: la carta se cachea localmente; solo el submit necesita red.
- **Accesibilidad**: contraste mínimo AA, tamaño de fuente mínimo 16px en móvil.

---

## 12. Seguridad

| Riesgo | Mitigación |
|---|---|
| Token predecible | 32 bytes aleatorios (256 bits de entropía), sin patrón |
| Abrir mesa sin ser cliente | El QR físico está en la mesa; solo quien está en el local lo ve |
| Spam de pedidos | Rate limiting 30 req/min por IP en endpoints públicos |
| Pedido a mesa cerrada | Verificar `Order.status === 'open'` en cada submit |
| Datos de fidelización | Email/teléfono solo accesibles con auth admin; nunca en logs |
| Sesión no expirada después de pagar | `expires_at` automático: 24h o al cerrar el order (el que llegue antes) |
| `pending` lines huérfanas | Cleanup job: cancelar `pending` si el order lleva >24h sin actividad |

---

## 13. Roadmap — Orden de implementación

### Sprint 1 — Infraestructura base (backend)
- [ ] Migración: `table_qr_tokens`
- [ ] Migración: `guest_sessions` (con `diners_count`, `opened_table`)
- [ ] Migración: `guest_order_rounds`
- [ ] Dominio `GuestOrder`: entidades + repositorios
- [ ] Endpoint `POST /api/admin/tables/{id}/qr-token` (generar token)
- [ ] Endpoint `GET /public/table/{token}` (estado de mesa)
- [ ] Auto-creación de QR token al crear una mesa

### Sprint 2 — Apertura de mesa por el comensal (backend)
- [ ] Endpoint `POST /public/table/{token}/open` (abre mesa, crea Order)
- [ ] Endpoint `POST /public/table/{token}/session` (unirse a mesa ya abierta)
- [ ] Evento `TableOpenedByGuest` → Reverb → TPV
- [ ] ALTER `order_lines`: añadir `origin`, `guest_session_id`, `guest_name`, `guest_round_id`, `send_status`
- [ ] Endpoint `GET /public/table/{token}/catalog` (carta completa con alérgenos)

### Sprint 3 — Sistema de pedidos y rondas (backend)
- [ ] Endpoint `POST /public/table/{token}/cart/save` (guardar pending)
- [ ] Endpoint `GET /public/table/{token}/cart` (carrito actual)
- [ ] Endpoint `POST /public/table/{token}/cart/submit-round` (enviar ronda)
- [ ] Evento `GuestRoundSubmitted` → Reverb → TPV
- [ ] Endpoint `GET /public/table/{token}/my-orders` (historial)
- [ ] Endpoint `POST /public/table/{token}/request-check` (pedir cuenta)
- [ ] Evento `CheckRequestedByGuest` → Reverb → TPV
- [ ] Cleanup job: cancelar `pending` lines en orders cerrados

### Sprint 4 — Alérgenos (backend + backoffice)
- [ ] ALTER `products`: añadir columna `allergens JSON`
- [ ] Actualizar CRUD de productos en backoffice Angular con 14 checkboxes

### Sprint 5 — Carta digital guest (frontend)
- [ ] Ruta pública `/s/:token` (sin AuthGuard)
- [ ] `GuestOrderService` + `GuestCartService`
- [ ] Pantalla 0: estado de mesa (abrir / unirse / cerrada)
  - [ ] Formulario apertura (stepper comensales + identificación)
  - [ ] Formulario unirse
- [ ] Pantalla 1: carta completa
  - [ ] Tabs por familia con icono y color
  - [ ] Grid de productos con foto, precio, alérgenos
  - [ ] FAB carrito flotante
- [ ] Pantalla 2: detalle de producto (variantes + modificadores)
- [ ] Pantalla 3: configurador de menú
- [ ] Pantalla 4: carrito con selector de ronda
  - [ ] Checkboxes "enviar ahora" / "guardar"
  - [ ] Label de ronda opcional
  - [ ] Botón "Enviar esta ronda"
- [ ] Pantalla 5: confirmación de ronda + pendientes restantes
- [ ] Pantalla 6: mis pedidos + botón pedir cuenta

### Sprint 6 — Integración TPV
- [ ] Modal QR en panel lateral de mesa (TPV)
  - [ ] QR generado (librería `qrcode` npm o server-side)
  - [ ] Indicador de sesiones activas en tiempo real
  - [ ] Botón "Regenerar QR"
- [ ] Badge "⚡ Guest · [nombre] · [ronda]" en líneas de pedido del TPV
- [ ] Indicador "Abierta vía QR" en la tarjeta de mesa
- [ ] Alerta "pedir la cuenta" vía Reverb en el panel de mesa

### Sprint 7 — Fidelización MVP
- [ ] Migración: `loyalty_profiles` + `loyalty_visits`
- [ ] Endpoints admin de consulta de perfiles
- [ ] Reconocimiento automático al identificarse (email/teléfono)
- [ ] Acumulación de puntos al cerrar pedido en TPV
- [ ] Vista "Mis puntos" en carta del comensal

### Sprint 8 — Calidad
- [ ] Tests PHPUnit: dominio GuestOrder + endpoints públicos
- [ ] Tests E2E Playwright: flujo completo apertura → pedido → ronda → cuenta
- [ ] Rate limiting en rutas públicas
- [ ] Caching de la carta en cliente (evitar recarga en cada navegación)
- [ ] README + AUTOSERVICIO_PLAN actualizado con implementación real

---

## 14. Métricas de éxito

| Métrica | Objetivo |
|---|---|
| % pedidos vía autoservicio | > 60% de las mesas con QR activado |
| Tiempo medio apertura → primer pedido | < 3 minutos |
| Promedio de rondas por visita | ≥ 2 |
| Tasa de identificación (loyalty) | > 40% de los comensales |
| Perfiles loyalty creados/semana | Aumenta 20% semana a semana |
| Satisfacción camarero (encuesta) | Reducción de idas a mesa para tomar pedido |

---

*Documento vivo — actualizar al completar cada sprint.*
