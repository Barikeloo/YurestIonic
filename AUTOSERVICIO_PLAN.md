# Plan de Implementación — Autoservicio QR

> **Estado:** Planificación — pendiente de desarrollo  
> **Fecha:** 2026-06-19  
> **Versión del documento:** 1.0  

---

## 1. Visión general

El módulo de **Autoservicio QR** permite a los comensales de un restaurante pedir desde su propio móvil escaneando un código QR pegado en la mesa. El sistema funciona como una carta digital completa e interactiva, integrada en tiempo real con el TPV existente.

### Principios de diseño

- **Comensal sin fricción** — No requiere app, no requiere registro obligatorio. Solo escanear.
- **Integración nativa** — Los pedidos se añaden al mismo `Order` que el camarero maneja en el TPV. No hay sistema paralelo.
- **Tiempo real** — Cada pedido del comensal aparece en el TPV instantáneamente vía WebSockets (Reverb).
- **Fidelización opcional** — El comensal puede identificarse para acumular puntos/historial. Si no, pide como anónimo.
- **Carta completa** — Todos los productos del catálogo, con fotos, descripciones, alérgenos, variantes, modificadores y menús/combos disponibles.

---

## 2. Flujo completo

```
┌─────────────────────────────────────────────────────────────────────┐
│                         FLUJO DEL CAMARERO                          │
│                                                                     │
│  1. Abre la mesa en el TPV (introduce comensales, etc.)             │
│  2. El QR de la mesa ya está disponible (código permanente)         │
│  3. Opcionalmente muestra el QR al comensal desde el panel lateral  │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         FLUJO DEL COMENSAL                          │
│                                                                     │
│  1. Escanea el QR de la mesa con la cámara del móvil               │
│  2. Se abre /s/{token} en el navegador (sin app, sin login)         │
│  3. Pantalla de identificación (nombre + email/teléfono opcional)   │
│     → Si ya tiene cuenta de fidelización: vincula automáticamente   │
│  4. Ve la carta digital: categorías, productos, fotos, alérgenos    │
│  5. Añade productos al carrito (con variantes y modificadores)       │
│  6. Puede pedir menús del día / combos                              │
│  7. Confirma el pedido → aparece en el TPV en tiempo real           │
│  8. Más tarde puede pedir más cosas (nuevas rondas)                 │
│  9. Cuando quiere pagar, toca "Pedir la cuenta"                     │
│     → El camarero recibe notificación en el TPV                     │
└──────────────────────────┬──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      INTEGRACIÓN TPV                                │
│                                                                     │
│  • Líneas del pedido aparecen en el order igual que si el camarero  │
│    las añadiera manualmente                                          │
│  • Se distinguen con origen "guest" y nombre del comensal           │
│  • Alerta de "Pedir la cuenta" visible en el panel de mesa          │
│  • El cobro sigue siendo gestionado por el camarero en el TPV       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Decisiones técnicas confirmadas

| Decisión | Elección | Motivo |
|---|---|---|
| **QR** | Permanente por mesa | Se imprime una vez, no hay que regenerar en cada turno |
| **Mesa cerrada** | No se puede pedir | El camarero controla cuándo empieza el servicio |
| **Identificación** | Opcional pero incentivada (fidelización) | Sin fricción, pero con beneficios claros |
| **Modificadores** | Completos, igual que en TPV | Experiencia completa para el comensal |
| **Menús/combos** | Sí, con selección de secciones | Mismo catálogo que el TPV |
| **Pedir la cuenta** | El comensal solicita → camarero recibe alerta | No cierra sola, el cobro sigue en TPV |
| **Alérgenos** | Visibles en la carta | Nueva propiedad en `Product` |
| **Fidelización** | Sistema propio (puntos + historial) | Integrado desde el inicio en el modelo de datos |

---

## 4. Modelo de datos

### 4.1 Nuevas tablas

```sql
-- Token QR permanente por mesa
CREATE TABLE table_qr_tokens (
  id            CHAR(36) PRIMARY KEY,
  table_id      CHAR(36) NOT NULL UNIQUE,
  restaurant_id CHAR(36) NOT NULL,
  token         VARCHAR(64) NOT NULL UNIQUE,  -- random, hard to guess
  created_at    TIMESTAMP NOT NULL,
  updated_at    TIMESTAMP NOT NULL,
  FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

-- Sesión de comensal (cada dispositivo que escanea)
CREATE TABLE guest_sessions (
  id                CHAR(36) PRIMARY KEY,
  table_qr_token_id CHAR(36) NOT NULL,
  order_id          CHAR(36) NULL,            -- NULL hasta que hay pedido activo
  restaurant_id     CHAR(36) NOT NULL,
  session_token     VARCHAR(64) NOT NULL UNIQUE, -- identifica el dispositivo
  guest_name        VARCHAR(100) NULL,
  guest_email       VARCHAR(255) NULL,
  guest_phone       VARCHAR(30) NULL,
  loyalty_profile_id CHAR(36) NULL,           -- vinculado si tiene cuenta
  check_requested_at TIMESTAMP NULL,          -- cuando pide la cuenta
  created_at        TIMESTAMP NOT NULL,
  expires_at        TIMESTAMP NOT NULL,       -- TTL de la sesión (fin del día)
  FOREIGN KEY (table_qr_token_id) REFERENCES table_qr_tokens(id)
);

-- Perfil de fidelización
CREATE TABLE loyalty_profiles (
  id              CHAR(36) PRIMARY KEY,
  restaurant_id   CHAR(36) NOT NULL,
  name            VARCHAR(100) NOT NULL,
  email           VARCHAR(255) NULL,
  phone           VARCHAR(30) NULL,
  points          INT NOT NULL DEFAULT 0,
  total_spent_cents BIGINT NOT NULL DEFAULT 0,
  visits_count    INT NOT NULL DEFAULT 0,
  last_visit_at   TIMESTAMP NULL,
  created_at      TIMESTAMP NOT NULL,
  updated_at      TIMESTAMP NOT NULL,
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
-- Añadir alérgenos a productos
ALTER TABLE products ADD COLUMN allergens JSON NULL;
-- Valor: ["gluten","lactosa","huevo","frutos_secos","soja","marisco","pescado","mostaza","apio","sesamo","sulfitos","altramuces","moluscos"]

-- Añadir origen y comensal a order_lines
ALTER TABLE order_lines ADD COLUMN origin ENUM('tpv', 'guest') NOT NULL DEFAULT 'tpv';
ALTER TABLE order_lines ADD COLUMN guest_session_id CHAR(36) NULL;
ALTER TABLE order_lines ADD COLUMN guest_name VARCHAR(100) NULL;
```

### 4.3 Dominio backend nuevo: `GuestOrder`

```
App/GuestOrder/
├── Domain/
│   ├── Entity/
│   │   ├── TableQrToken.php
│   │   ├── GuestSession.php
│   │   └── LoyaltyProfile.php
│   ├── Event/
│   │   ├── GuestOrderSubmitted.php      (AuditableEvent)
│   │   └── CheckRequested.php           (AuditableEvent)
│   ├── ValueObject/
│   │   ├── GuestSessionToken.php
│   │   └── LoyaltyPoints.php
│   └── Interfaces/
│       ├── TableQrTokenRepositoryInterface.php
│       ├── GuestSessionRepositoryInterface.php
│       └── LoyaltyProfileRepositoryInterface.php
├── Application/
│   ├── GenerateTableQrToken/
│   ├── GetTableForGuest/          (menú + estado de la mesa)
│   ├── StartGuestSession/         (comensal identifica/se registra)
│   ├── SubmitGuestOrderLines/     (añade líneas al order activo)
│   ├── RequestCheck/              (comensal pide la cuenta)
│   └── GetGuestOrderHistory/      (qué ha pedido este dispositivo)
└── Infrastructure/
    ├── Persistence/
    └── Entrypoint/
        └── Http/
            ├── Public/            (sin auth)
            └── Admin/             (auth admin/supervisor)
```

---

## 5. API — Endpoints

### 5.1 Endpoints públicos (sin autenticación)

| Método | URL | Descripción |
|---|---|---|
| `GET`  | `/public/table/{token}` | Estado de la mesa + carta completa del restaurante |
| `POST` | `/public/table/{token}/session` | Iniciar o recuperar sesión de comensal |
| `POST` | `/public/table/{token}/lines` | Añadir líneas al pedido activo |
| `GET`  | `/public/table/{token}/my-orders` | Líneas que este dispositivo ha pedido |
| `POST` | `/public/table/{token}/request-check` | Pedir la cuenta |

#### `GET /public/table/{token}` — Respuesta
```json
{
  "restaurant": { "name": "Bar Manolo", "logo_url": "..." },
  "table": { "name": "Mesa 3", "zone": "Terraza" },
  "order_status": "open",  // "closed" | "open" | "none"
  "menu": {
    "families": [
      {
        "id": "...", "name": "Bebidas", "icon": "...", "color": "#FF4D4D",
        "products": [
          {
            "id": "...", "name": "Coca-Cola", "price": 250,
            "description": "...", "image_url": "...",
            "allergens": ["sulfitos"],
            "variants": [{ "id": "...", "name": "33cl", "price_delta": 0 }],
            "modifiers": [{ "id": "...", "name": "Con hielo", "price": 0 }]
          }
        ]
      }
    ],
    "menus": [
      {
        "id": "...", "name": "Menú del día", "price": 1200,
        "sections": [
          {
            "name": "Primer plato", "min_choices": 1, "max_choices": 1,
            "items": [{ "product_id": "...", "product_name": "Sopa", "extra_price": 0 }]
          }
        ]
      }
    ]
  }
}
```

#### `POST /public/table/{token}/session` — Body
```json
{
  "session_token": "abc123",   // localStorage del dispositivo
  "name": "Carlos",            // opcional
  "email": "carlos@email.com", // opcional (para fidelización)
  "phone": "+34600..."         // opcional
}
```

#### `POST /public/table/{token}/lines` — Body
```json
{
  "session_token": "abc123",
  "lines": [
    {
      "product_id": "...",
      "quantity": 2,
      "variant_id": "...",       // opcional
      "modifier_ids": ["..."],   // opcional
      "notes": "sin hielo"       // opcional
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

### 5.2 Endpoints admin (autenticados)

| Método | URL | Descripción |
|---|---|---|
| `POST` | `/api/admin/tables/{id}/qr-token` | Generar/regenerar token QR |
| `GET`  | `/api/admin/tables/{id}/qr-token` | Obtener token + URL + QR image |
| `GET`  | `/api/admin/orders/{id}/guest-lines` | Ver líneas por comensal en un pedido |
| `GET`  | `/api/admin/loyalty` | Lista de perfiles de fidelización |
| `GET`  | `/api/admin/loyalty/{id}` | Detalle de un perfil |

---

## 6. Frontend — Carta digital (interfaz del comensal)

### 6.1 Ruta

```
/s/{token}          — ruta pública, sin AuthGuard
```

Angular necesita una ruta fuera del módulo de autenticación. La app principal ya tiene `/app/...` bajo auth guard. Se añade una ruta hermana `/s/:token` que carga un módulo independiente sin guard.

### 6.2 Pantallas

```
/s/{token}
├── [Pantalla 0] Estado de carga / mesa cerrada
│     "Tu camarero abrirá la mesa en breve. Escanea de nuevo."
│     Auto-poll cada 8 segundos hasta que order_status = "open"
│
├── [Pantalla 1] Identificación (solo primera vez)
│     - Nombre (obligatorio)
│     - Email o teléfono (opcional, con incentivo: "Gana puntos de fidelización")
│     - Botón "Entrar como invitado" (sin datos)
│
├── [Pantalla 2] Carta
│     - Header: logo + nombre mesa + "Mis pedidos (N)" badge
│     - Tabs horizontales por familia (con icono y color)
│     - Grid de productos (foto, nombre, precio, alérgenos)
│     - Sección "Menús disponibles" al final o en tab propio
│     - FAB carrito flotante con contador
│
├── [Pantalla 3] Detalle de producto
│     - Foto grande
│     - Nombre, descripción, alérgenos con iconos
│     - Selector de variante (radio)
│     - Checkboxes de modificadores (con precio extra)
│     - Nota libre (textarea)
│     - Botón "Añadir al carrito" con precio calculado
│
├── [Pantalla 4] Configurar menú
│     - Nombre del menú, precio base
│     - Por cada sección: lista de productos elegibles (radio o checkbox según min/max)
│     - Precio en tiempo real (base + extras)
│     - Botón "Añadir al carrito"
│
├── [Pantalla 5] Carrito
│     - Lista de líneas con foto, nombre, precio, qty
│     - Editar cantidad o eliminar
│     - Total
│     - Botón "Confirmar pedido"
│
├── [Pantalla 6] Pedido enviado ✓
│     - Confirmación visual
│     - "Seguir pidiendo" → vuelve a Carta
│     - "Ver mis pedidos" → pantalla 7
│
└── [Pantalla 7] Mis pedidos (historial de la sesión)
      - Todo lo que este dispositivo ha pedido en esta visita
      - Estado (en cocina / servido) — futuro
      - Botón "Pedir la cuenta" (prominente)
```

### 6.3 Componentes Angular previstos

```
src/app/public/guest-order/
├── guest-order.routes.ts         (ruta /s/:token)
├── guest-order.page.ts           (orquestador)
├── services/
│   ├── guest-order.service.ts    (HTTP calls + estado)
│   └── guest-cart.service.ts     (carrito local en señal)
├── components/
│   ├── table-status/             (pantalla 0)
│   ├── guest-identification/     (pantalla 1)
│   ├── menu-catalog/             (pantalla 2)
│   ├── product-detail/           (pantalla 3)
│   ├── menu-configurator/        (pantalla 4)
│   ├── guest-cart/               (pantalla 5)
│   ├── order-confirmed/          (pantalla 6)
│   └── order-history/            (pantalla 7)
└── pipes/
    └── allergen-icon.pipe.ts
```

### 6.4 Estado del carrito (cliente)

```typescript
interface CartItem {
  type: 'product' | 'menu';
  productId?: string;
  menuId?: string;
  name: string;
  quantity: number;
  variantId?: string;
  variantName?: string;
  modifierIds: string[];
  modifierNames: string[];
  menuSelections?: MenuSelection[];
  notes?: string;
  unitPrice: number;   // calculado: base + variant_delta + modifiers
}
```

El carrito vive en `localStorage` hasta que se envía o la sesión expira.

---

## 7. Integración TPV

### 7.1 Panel de mesa — QR Autoservicio

En el panel lateral de mesas (vista lista y vista plano), cuando hay una mesa seleccionada:
- Botón "QR Autoservicio" → abre modal con:
  - QR code grande (imagen generada por backend o librería JS)
  - URL del enlace (para compartir manualmente)
  - Botón "Regenerar QR" (invalida el anterior, genera nuevo token)
  - Indicador: "N comensales activos" cuando hay sesiones abiertas

### 7.2 Orden lines con origen guest

Cuando el comensal envía un pedido, las líneas se añaden al `Order` con:
- `origin = 'guest'`
- `guest_name = "Carlos"` (si se identificó)
- Visual en TPV: pequeño badge "⚡ Guest" en la línea + nombre del comensal

### 7.3 Notificación "Pedir la cuenta"

Cuando un comensal pulsa "Pedir la cuenta":
1. El backend marca `guest_sessions.check_requested_at`
2. Se emite un evento Reverb al canal del restaurante
3. En el TPV, aparece una alerta en el panel de la mesa: "Mesa 3 — Carlos pide la cuenta"
4. Badge rojo en el tab de la zona

### 7.4 Fidelización visible en TPV

En el panel de mesa, sección "Comensales":
- Lista de sesiones activas con nombre del comensal
- Si tiene perfil de fidelización: icono de puntos + recuento acumulado

---

## 8. Sistema de fidelización

### 8.1 Mecánica básica (MVP)

- **Registro**: Al identificarse por primera vez con email o teléfono, se crea un `LoyaltyProfile`.
- **Puntos**: 1 punto por cada euro gastado (configurable por restaurante en el futuro).
- **Acumulación**: Al cerrar el pedido en el TPV, si hay sesiones de guest vinculadas, se calculan los puntos y se actualizan los perfiles.
- **Consulta**: El comensal puede ver sus puntos en la pantalla de "Mis pedidos".

### 8.2 Reconocimiento automático

Si el comensal introduce el mismo email o teléfono que en visitas anteriores:
- El backend vincula la nueva sesión al perfil existente
- La carta muestra: "¡Bienvenido de nuevo, Carlos! Tienes 47 puntos."

### 8.3 Futuro (no en MVP)

- Canjear puntos por descuentos
- Niveles (Bronce/Plata/Oro)
- Campañas especiales ("2x puntos los miércoles")
- App de fidelización propia
- Integración con email marketing

---

## 9. Alérgenos

### 9.1 Catálogo estándar (EU Regulation 1169/2011)

```
gluten, crustáceos, huevo, pescado, cacahuetes, soja,
lácteos, frutos_de_cáscara, apio, mostaza, sésamo,
dióxido_de_azufre, altramuces, moluscos
```

### 9.2 Backoffice — edición de alérgenos

En la pantalla de edición de producto del backoffice:
- Sección "Alérgenos" con 14 checkboxes con iconos visuales
- Se guardan como JSON array en `products.allergens`

### 9.3 Carta del comensal — visualización

Cada producto muestra iconos de alérgenos (estándar de la UE, color naranja).
Al hacer tap en un icono, aparece el nombre completo.
Filtro opcional: "Mostrar solo productos sin [alérgeno]".

---

## 10. Diseño UI/UX — Principios para la carta

- **Mobile-first** obligatorio (diners usan su propio móvil)
- **Sin fricción**: máximo 3 taps para pedir un producto simple
- **Offline-friendly**: la carta se puede cachear localmente (service worker futuro)
- **Branding**: usa los colores del restaurante (configurables por tenant)
- **Accesibilidad**: contraste AAA, texto legible sin zoom
- **Idioma**: español por defecto, i18n en roadmap
- Fotos grandes y atractivas (el producto se "vende" visualmente)
- Precios siempre visibles (sin sorpresas)

---

## 11. Seguridad

| Riesgo | Mitigación |
|---|---|
| Token QR predecible | Token de 32 bytes aleatorios (256 bits de entropía) |
| Pedido desde mesa cerrada | Verificación de `Order` activo en cada POST /lines |
| Spam de pedidos | Rate limiting en endpoints públicos (60 req/min por IP) |
| Pedido a mesa de otro restaurante | Token lleva `restaurant_id` embebido, se valida siempre |
| CSRF | Endpoints stateless (JWT de sesión de guest, no cookies) |
| Datos de fidelización | Email/teléfono hasheados para búsqueda, nunca en logs |

---

## 12. Roadmap de implementación — Orden de pasos

### Sprint 1 — Infraestructura base (backend)
1. Migración `table_qr_tokens`
2. Migración `guest_sessions`
3. Dominio `GuestOrder` — entidades + repositorios
4. Endpoint `POST /api/admin/tables/{id}/qr-token`
5. Endpoint `GET /public/table/{token}` (mesa + menú completo)

### Sprint 2 — Pedido del comensal (backend)
6. Migración `order_lines.origin` + `order_lines.guest_session_id`
7. Endpoint `POST /public/table/{token}/session`
8. Endpoint `POST /public/table/{token}/lines`
9. Evento `GuestOrderSubmitted` → Reverb → TPV
10. Endpoint `GET /public/table/{token}/my-orders`
11. Endpoint `POST /public/table/{token}/request-check`
12. Evento `CheckRequested` → Reverb → TPV

### Sprint 3 — Alérgenos (backend + backoffice)
13. Migración `products.allergens`
14. Actualizar CRUD de productos (backoffice Angular) con selector de alérgenos

### Sprint 4 — Carta digital (frontend guest)
15. Ruta pública `/s/:token` en Angular (sin AuthGuard)
16. Servicio `GuestOrderService`
17. Pantalla 0: estado de mesa (mesa cerrada / auto-poll)
18. Pantalla 1: identificación del comensal
19. Pantalla 2: carta completa (familias + productos)
20. Pantalla 3: detalle de producto (variantes + modificadores)
21. Pantalla 4: configurador de menú
22. Pantalla 5: carrito
23. Pantalla 6: confirmación
24. Pantalla 7: mis pedidos + pedir la cuenta

### Sprint 5 — TPV integration
25. Modal QR en panel de mesa del TPV
26. Badge "Guest" en líneas de pedido
27. Alerta "pedir la cuenta" en TPV (Reverb)
28. Indicador de sesiones activas en panel de mesa

### Sprint 6 — Fidelización (MVP)
29. Migración `loyalty_profiles` + `loyalty_visits`
30. Endpoints de consulta/creación de perfil
31. Reconocimiento automático al identificarse
32. Acumulación de puntos al cerrar pedido
33. Vista "Mis puntos" en carta del comensal
34. Vista "Fidelización" en backoffice

### Sprint 7 — Calidad y pulido
35. Tests backend (PHPUnit) — endpoints públicos + dominio
36. Tests E2E (Playwright) — flujo completo guest
37. Optimización de la carta (lazy load fotos, caching)
38. Rate limiting y hardening de seguridad
39. README actualizado

---

## 13. Dependencias y riesgos

| Dependencia | Impacto | Mitigación |
|---|---|---|
| Librería QR en frontend | Sprint 5 | `qrcode` (npm, sin deps) o generación server-side |
| Fotos de productos | Sprint 4 | Sin foto → placeholder con color de familia |
| Menús sin secciones configuradas | Sprint 4 | Filtrar menús sin secciones en el endpoint público |
| Mesa sin QR token | Sprint 5 | Generar token automáticamente al crear la mesa |
| Reverb no disponible | Sprint 5 | Fallback a polling cada 10s en el TPV |
| Rate limiting | Sprint 7 | Laravel rate limiter en el grupo de rutas públicas |

---

## 14. Métricas de éxito

- % de pedidos via autoservicio vs. TPV
- Tiempo medio entre apertura de mesa y primer pedido guest
- Tasa de identificación (% que introduce email/teléfono)
- Perfiles de fidelización creados por semana
- Tiempo promedio del camarero en mesa (debería reducirse)

---

*Documento vivo — actualizar con cada sprint completado.*
