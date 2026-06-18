# Mejora 4 v2 — Editor de plano de sala (página dedicada, estilo draw.io)

> Fecha: 2026-06-18  
> Sustituye el editor embebido en el panel de gestión por una página completa.

---

## 1. Visión y objetivo

El editor de plano pasa de ser un componente embebido (tab "Plano") a una **página dedicada** accesible
desde el panel de gestión de zonas. El usuario puede:

- Crear mesas directamente desde el editor (se guardan en la BD con nombre y zona).
- Posicionarlas en el canvas arrastrando.
- Cambiar forma y tamaño con presets.
- Eliminarlas o quitarlas del plano.
- Guardar el layout con un solo botón.

---

## 2. UX / UI — Diseño detallado

### 2.1 Layout de la página

```
┌───────────────────────────────────────────────────────────────────────┐
│  ← Gestión  ·  Terraza  ·  12 mesas                 [● Guardar]      │  56px topbar
├─────────────────────────────────────────────┬─────────────────────────┤
│                                             │                         │
│                                             │  §  Nueva mesa          │
│                                             │  ┌─────────────────────┐│
│                                             │  │ Nombre  [_________] ││
│            SVG CANVAS                       │  │ Forma   [□] [○]     ││
│            (flex: 1, fills space)           │  │ Tamaño  [S] [M] [L] ││
│            Fondo: blanco + dot grid         │  │ [Añadir al plano ↓] ││
│            Snap: 20px                       │  └─────────────────────┘│
│                                             │                         │
│                                             │  §  Mesa 3 ← seleccion. │
│          ┌──────────┐                       │  Forma   [□ → ○]        │
│          │  Mesa 3  │ ← selected            │  Tamaño  [S] [M] [L]    │
│          │  (ring)  │                       │  [Quitar del plano]      │
│          └──────────┘                       │  [🗑 Eliminar mesa]      │
│                                             │                         │
│                                             │  §  Sin posición  (2)   │
│                                             │  Mesa 5     [+ canvas]  │
│                                             │  Mesa 7     [+ canvas]  │
│                                             │                         │
│                                             │  ───────────────────    │
│                                             │  [−]  100%  [+]  [⊡]   │  zoom
└─────────────────────────────────────────────┴─────────────────────────┘
                                               280px fixed panel
```

### 2.2 Topbar

| Elemento | Detalle |
|---|---|
| ← Gestión | `router.navigate(['/app/gestion'])` con guard unsaved |
| Nombre zona | Bold, solo lectura |
| Contador | "12 mesas" (total en la zona) |
| Guardar | Botón rojo; punto `●` naranja mientras haya cambios sin guardar |

### 2.3 Canvas

| Aspecto | Decisión |
|---|---|
| Fondo | Blanco puro + dot grid SVG (cada 20px, punto `r=0.8`, color `#e0e0e0`) |
| Espacio lógico | 1200 × 800 (viewBox, misma base que v1) |
| Snapping | 20px siempre activo |
| Mesa free | fill `#fff`, stroke `#d0d0d0`, shadow suave, text `#1a1a1a` |
| Mesa seleccionada | fill `#fff`, stroke `#ff4d4d` 3px, drop-shadow rojo, ring exterior |
| Mesa dragging | opacity 0.8, cursor grabbing |
| Zoom | CSS `transform: scale(zoom)` sobre `.canvas-wrap`; rango 0.4–2.0 |

### 2.4 Panel derecho — secciones

#### § Nueva mesa
- `input[text]` Nombre — placeholder "Ej: Mesa 1, T-05…"
- Shape toggle: `[□ Rect]` / `[○ Círculo]` — segmented button
- Size presets:
  - **S** — rect 80×60 · circle ø60
  - **M** — rect 110×80 · circle ø90 *(default)*
  - **L** — rect 150×110 · circle ø120
- `[Añadir al plano]` → crea en backend + coloca en canvas automáticamente

#### § Mesa seleccionada *(oculta si no hay selección)*
- Nombre (read-only; editar inline en canvas con doble-click, ver §2.5)
- Shape toggle (cambia local, se persiste al guardar)
- Size presets (mismo que arriba)
- `[Quitar del plano]` → pos_x/pos_y → null, mesa queda "sin posición"
- `[🗑 Eliminar mesa]` → destructivo, pide confirmación inline en el panel

#### § Sin posición
- Lista de mesas de la zona sin coordenadas
- Chip con nombre + botón `[+ plano]`
- Si todas están en el plano: texto vacío "Todas las mesas están posicionadas ✓"

#### § Zoom
- `[−]` / `[+]` (paso 10%) / `100%` (label clickable → reset) / `[⊡ Fit]`

### 2.5 Interacciones de canvas

| Gesto | Acción |
|---|---|
| Click en mesa | Selecciona (resalta + muestra sección en panel) |
| Click en vacío | Deselecciona |
| Drag mesa | Mueve con snap |
| Doble-click en mesa | Activa edición inline del nombre (input SVG `foreignObject`) |
| `Delete` / `Backspace` | Quitar mesa seleccionada del plano |
| `Escape` | Deseleccionar |
| Scroll (con Ctrl) | Zoom in/out |

### 2.6 Dirty state y navegación

- Mientras haya cambios sin guardar: indicador `●` en el botón Guardar.
- Al pulsar ← Gestión o navegar fuera: `CanDeactivate` guard → dialog "¿Salir sin guardar?" con opciones Guardar y salir / Descartar / Cancelar.

---

## 3. Arquitectura frontend

### 3.1 Nueva ruta

```
/app/gestion/zones/:zoneId/floor
```

Mismo patrón que `/app/gestion/menus/:id/editar`. Lazy-loaded.

### 3.2 Estructura de ficheros (nuevos)

```
pages/core/gestion-zones-floor/
  gestion-zones-floor.page.ts        ← página principal
  gestion-zones-floor.page.html
  gestion-zones-floor.page.scss
  facades/
    gestion-zones-floor.facade.ts    ← estado local + llamadas API
  components/
    floor-canvas/
      floor-canvas.component.ts      ← SVG canvas, drag, zoom
      floor-canvas.component.html
      floor-canvas.component.scss
    floor-panel/
      floor-panel.component.ts       ← panel derecho completo
      floor-panel.component.html
      floor-panel.component.scss
```

### 3.3 Cambios en ficheros existentes

| Fichero | Cambio |
|---|---|
| `app.routes.ts` | Añadir ruta `gestion/zones/:zoneId/floor` |
| `zones-management.component.*` | Quitar tab "Plano"; añadir botón "Editar plano →" que navega |
| `ZoneFloorEditorComponent` | **Eliminar** (reemplazado por la nueva página) |

### 3.4 `GestionZonesFloorFacade` — estado

```typescript
// Señales de estado
zone        = signal<ZoneItem | null>(null)
tables      = signal<LocalTable[]>([])      // estado local con pos. editables
selectedId  = signal<string | null>(null)
zoomLevel   = signal<number>(1)
isDirty     = signal<boolean>(false)
isSaving    = signal<boolean>(false)

// Computadas
placedTables      = computed(() => tables().filter(t => t.posX != null))
unpositionedTables = computed(() => tables().filter(t => t.posX == null))
selectedTable     = computed(() => tables().find(t => t.id === selectedId()))

// Acciones
async loadZone(zoneId)          // GET /admin/zones/{id} + GET /admin/tables?zone_id={id}
async createAndPlace(name, shape, size)  // POST /admin/tables → place on canvas
async deleteTable(id)           // DELETE /admin/tables/{id}
removeFromCanvas(id)            // solo localiza pos a null, marca dirty
updatePosition(id, x, y)        // actualiza local, marca dirty
updateShape(id, shape)          // actualiza local, marca dirty
updateSize(id, w, h)            // actualiza local, marca dirty
async save()                    // PUT /admin/zones/{id}/layout
```

### 3.5 `FloorCanvasComponent` — inputs/outputs

```typescript
// Inputs
tables        = input.required<LocalTable[]>()
selectedId    = input<string | null>(null)
zoomLevel     = input<number>(1)

// Outputs
tableSelected     = output<string>()          // id
tableDeselected   = output<void>()
tableMoved        = output<{id, x, y}>()
tableNameEdited   = output<{id, name}>()      // doble-click inline edit
```

### 3.6 `FloorPanelComponent` — inputs/outputs

```typescript
// Inputs
unpositionedTables = input.required<LocalTable[]>()
selectedTable      = input<LocalTable | null>(null)
isSaving           = input<boolean>(false)
isDirty            = input<boolean>(false)
zoomLevel          = input<number>(1)

// Outputs
addTable           = output<{name, shape, size}>()
placeOnCanvas      = output<string>()         // id de mesa sin posición
removeFromCanvas   = output<string>()
deleteTable        = output<string>()
shapeChanged       = output<{id, shape}>()
sizeChanged        = output<{id, w, h}>()
zoomChanged        = output<number>()
zoomFit            = output<void>()
save               = output<void>()
```

---

## 4. Backend — cambios necesarios

El backend de la Fase 1 y 2 original ya está completo. Solo verificar:

| Endpoint | Estado | Notas |
|---|---|---|
| `POST /admin/tables` | ✅ existe | Acepta `{ zone_id, name }` |
| `DELETE /admin/tables/{id}` | ✅ existe | |
| `GET /admin/zones/{id}` | ✅ existe | Para obtener nombre de zona |
| `GET /admin/tables` | ✅ existe | Comprobar si acepta `?zone_id=` |
| `PUT /admin/zones/{id}/layout` | ✅ existe | Batch de posiciones |

**Posible ajuste:** verificar que `GET /admin/tables` permite filtrar por `zone_id`.
Si no, añadir ese query param al controller (cambio mínimo).

---

## 5. Fases de implementación

### Fase A — Ruta + scaffold (1 sesión)
1. Añadir ruta en `app.routes.ts`
2. Crear `GestionZonesFloorPage` (shell vacío con topbar)
3. Crear `GestionZonesFloorFacade` con `loadZone()` + estado base
4. En `ZonesManagementComponent`: cambiar tab "Plano" → botón "Editar plano →"
5. Verificar/añadir filtro `zone_id` en `GET /admin/tables` backend si falta

### Fase B — Canvas (1 sesión)
1. `FloorCanvasComponent` full-height
2. Dot grid SVG en `<defs>`
3. Drag & drop (reutilizar lógica pointer events de v1)
4. Selección + deselección
5. Zoom CSS transform + scroll+Ctrl
6. Delete/Backspace key handler
7. Doble-click inline name edit (SVG `foreignObject`)

### Fase C — Panel derecho (1 sesión)
1. `FloorPanelComponent` estructura completa
2. Form nueva mesa (name + shape + size presets)
3. Sección mesa seleccionada
4. Sección sin posición
5. Controles de zoom

### Fase D — Integración backend (1 sesión)
1. `createAndPlace()` → POST /admin/tables + animar entrada al canvas
2. `deleteTable()` con confirm inline
3. `save()` → PUT layout, limpiar dirty
4. `CanDeactivate` guard (dialog unsaved)
5. Eliminar `ZoneFloorEditorComponent` (ya no se usa)

### Fase E — Polish (½ sesión)
1. Animación nueva mesa (scale in desde el panel)
2. "Fit to canvas" (calcular zoom y offset para que todas las mesas quepan)
3. Toast confirmaciones
4. Estado vacío del canvas con ilustración/mensaje

---

## 6. Lo que NO haremos (fuera de scope MVP)

- Redimensionar mesas arrastrando esquinas (size presets es suficiente)
- Undo/redo (no complejidad de historial)
- Panning del canvas (zoom + drag de mesa es suficiente para 1200×800)
- Export a imagen/PDF
- Múltiples pisos o plantas

---

## 7. Referencia de tipos locales

```typescript
interface LocalTable {
  id: string          // uuid en BD
  name: string
  zoneId: string
  posX: number | null
  posY: number | null
  width: number
  height: number
  shape: 'rect' | 'circle'
  isNew?: boolean     // recién creada, sin refrescar desde servidor
}

type SizePreset = 'S' | 'M' | 'L'

const SIZE_PRESETS: Record<SizePreset, Record<'rect' | 'circle', { w: number; h: number }>> = {
  S: { rect: { w: 80, h: 60 },   circle: { w: 60, h: 60 } },
  M: { rect: { w: 110, h: 80 },  circle: { w: 90, h: 90 } },
  L: { rect: { w: 150, h: 110 }, circle: { w: 120, h: 120 } },
}
```
