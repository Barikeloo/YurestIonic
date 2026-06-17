# MEJORA 4 — Diseño interactivo del salón (plano)

**Estado:** ⬜ Pendiente  
**Fecha inicio:** —  
**Objetivo:** Dar a cada mesa una posición y forma reales dentro de su zona, de modo que el TPV muestre un plano visual en lugar de (o además de) la cuadrícula actual.

---

## 1. Visión de producto

### Backoffice — Editor del plano
- Por cada zona el admin ve un **canvas** con cuadrícula.
- Las mesas aparecen como elementos arrastrables y redimensionables.
- Se puede cambiar la forma de cada mesa (rectángulo o círculo).
- Un botón **"Guardar layout"** persiste todas las posiciones de la zona a la vez.
- Las mesas recién creadas (sin posición) aparecen en un panel lateral y se arrastran al canvas.

### TPV — Vista plano
- En la pantalla de mesas aparece un **toggle** "Lista / Plano".
- La vista Plano renderiza el canvas con posición real de cada mesa.
- El color de cada mesa refleja su estado: libre · ocupada · pendiente de cobro · fusionada.
- Al pulsar una mesa se sigue el mismo flujo que en la vista lista.
- Si una mesa no tiene posición asignada (layout `null`) aparece en una fila inferior fuera del plano.

---

## 2. Modelo de datos

### 2.1 Espacio de coordenadas

Se usa un **canvas lógico de 1200 × 800 unidades** (independiente del tamaño de pantalla).  
Valores guardados como enteros. El frontend escala al tamaño del contenedor con `viewBox`.

| Campo      | Tipo     | Default  | Descripción                          |
|------------|----------|----------|--------------------------------------|
| `pos_x`    | int      | `null`   | Esquina superior izquierda, eje X    |
| `pos_y`    | int      | `null`   | Esquina superior izquierda, eje Y    |
| `width`    | int      | `null`   | Ancho en unidades lógicas            |
| `height`   | int      | `null`   | Alto en unidades lógicas             |
| `shape`    | enum     | `'rect'` | `'rect'` o `'circle'`                |

- `pos_x IS NULL` → la mesa no tiene posición asignada en el plano (estado permitido).
- Para `shape = 'circle'` se usa `width` como diámetro; `height` se ignora en render.
- Tamaño por defecto al soltar del panel lateral: `width = 100, height = 60`.
- **No hay rotación en V1** (se puede añadir en V2 como `rotation INT DEFAULT 0`).

### 2.2 Auto-grid para mesas existentes

La migración **no asignará** posiciones automáticas; deja `pos_x = NULL`.  
El admin posiciona las mesas en el editor. En TPV, las mesas sin posición se muestran en la fila "Sin posición" debajo del canvas (mismo comportamiento que hoy).

---

## 3. Backend

### 3.1 Migración

```
2026_XX_XX_000000_add_layout_to_tables_table.php
```

```php
$table->integer('pos_x')->nullable()->default(null);
$table->integer('pos_y')->nullable()->default(null);
$table->integer('width')->nullable()->default(null);
$table->integer('height')->nullable()->default(null);
$table->string('shape', 10)->default('rect');
```

### 3.2 Value Object — `TableLayout`

`App\Tables\Domain\ValueObject\TableLayout`

```php
final readonly class TableLayout
{
    public function __construct(
        public int $posX,
        public int $posY,
        public int $width,
        public int $height,
        public string $shape, // 'rect' | 'circle'
    ) {}

    public static function create(int $posX, int $posY, int $width, int $height, string $shape): self;
    // Validaciones:
    //   posX, posY: 0–1200 / 0–800
    //   width, height: 20–600
    //   shape: in ['rect', 'circle']
}
```

### 3.3 Entidad `Table` — cambios

- Añadir `private ?TableLayout $layout` al constructor.
- Nuevo método `layout(): ?TableLayout`.
- Nuevo método `updateLayout(TableLayout $layout): void` (dispara `TableLayoutUpdated` event).
- `fromPersistence()` recibe los campos de layout y construye el VO si `pos_x !== null`.

### 3.4 Caso de uso — `SaveZoneLayout`

`App\Tables\Application\SaveZoneLayout\SaveZoneLayout`

Recibe un array de `{ table_uuid, pos_x, pos_y, width, height, shape }` para una zona entera.  
Guarda en batch (una sola transacción). Es idempotente.

```
SaveZoneLayoutCommand
  └── zoneId: string
  └── tables: SaveZoneLayoutTableDto[]
          └── uuid, posX, posY, width, height, shape
```

**Por qué batch y no por mesa:** en el editor se mueven múltiples mesas y guardar todo a la vez es una UX más natural (botón "Guardar") y reduce el número de peticiones.

### 3.5 HTTP — endpoint

```
PUT /api/admin/zones/{zoneId}/layout
```

Body:
```json
{
  "tables": [
    { "uuid": "...", "pos_x": 100, "pos_y": 50, "width": 120, "height": 70, "shape": "rect" }
  ]
}
```

`SaveZoneLayoutRequest` valida:
- `tables` → array, requerido.
- `tables.*.uuid` → uuid, requerido.
- `tables.*.pos_x` / `pos_y` → integer, 0–1200 / 0–800.
- `tables.*.width` / `height` → integer, 20–600.
- `tables.*.shape` → in: `['rect', 'circle']`.

Respuesta `200 { "saved": N }`.

### 3.6 API existente — ampliar responses

Los endpoints de TPV (`GET /api/tpv/tables`) y admin (`GET /api/admin/tables`) deben devolver los campos de layout para que el frontend los pinte.

Añadir al resource/DTO de tabla:

```json
"layout": { "pos_x": 100, "pos_y": 50, "width": 120, "height": 70, "shape": "rect" }
// o null si no tiene posición
```

---

## 4. Frontend — Backoffice (editor)

### 4.1 Integración en gestión

En la pantalla de gestión de zonas, cada zona tendrá una pestaña adicional **"Plano"** junto a la lista de mesas.

### 4.2 Componente `ZoneFloorEditorComponent`

```
frontend/src/app/components/gestion/zones-management/zone-floor-editor/
├── zone-floor-editor.component.ts
├── zone-floor-editor.component.html
└── zone-floor-editor.component.scss
```

**Canvas SVG** (no Canvas 2D — SVG es más fácil de gestionar con Angular y accesible):
- `<svg viewBox="0 0 1200 800">` con `preserveAspectRatio="xMidYMid meet"`.
- Fondo con cuadrícula de 40×40 unidades (líneas `stroke-dasharray`).
- Cada mesa es un `<rect>` o `<circle>` dentro del SVG.

**Drag con eventos del ratón / puntero** (`pointerdown`, `pointermove`, `pointerup`):
- Snap a grid de 20 unidades (divisor de 40).
- Al iniciar drag: registrar offset cursor → esquina mesa.
- Al soltar: actualizar el signal local con la nueva posición.

**Resize con handle** (opcional V1, puede posponerse):
- Pequeño cuadrado en esquina inferior-derecha; arrastrar cambia `width/height`.

**Panel lateral — mesas sin posición:**
- Lista de fichas; drag-from-list → al soltar en el canvas asigna posición.

**Botón "Guardar layout":**
- Llama a `PUT /api/admin/zones/{id}/layout` con el estado actual.
- Feedback toast de éxito / error.

**Sin librería externa** de drag-and-drop (no CDK para SVG — CDK no funciona bien con SVG foreignObject). Usar eventos nativos de puntero.

### 4.3 Service — `ZoneLayoutService`

```
frontend/src/app/services/zone-layout.service.ts
```

```typescript
saveZoneLayout(zoneId: string, tables: TableLayoutDto[]): Observable<void>
```

---

## 5. Frontend — TPV (vista plano)

### 5.1 Toggle Lista/Plano

En `mesas.page.html`, encima de las pestañas de zona, añadir:

```html
<div class="view-toggle">
  <button [class.active]="viewMode() === 'list'" (click)="viewMode.set('list')">Lista</button>
  <button [class.active]="viewMode() === 'floor'" (click)="viewMode.set('floor')">Plano</button>
</div>
```

`viewMode = signal<'list' | 'floor'>('list')` en `MesasPage`.  
Persiste en `localStorage` (el usuario no quiere cambiar el modo cada vez que entra).

### 5.2 Componente `FloorPlanComponent`

```
frontend/src/app/features/tables/ui/floor-plan/
├── floor-plan.component.ts
├── floor-plan.component.html
└── floor-plan.component.scss
```

Inputs:
- `tables: TableWithStatus[]`
- `(tableClick): EventEmitter<TableWithStatus>`

Mismo SVG `viewBox="0 0 1200 800"`. Cada mesa:
- `<rect>` o `<circle>` con `fill` según estado:
  - Libre → `var(--color-surface-alt)` (gris claro)
  - Ocupada → `#FF9800` (naranja)
  - Pendiente de cobro → `#F44336` (rojo)
  - Fusionada → `#1A6FE8` (azul)
- Texto centrado con el nombre de la mesa y número de comensales.
- `cursor: pointer` + `(click)` → emite `tableClick`.

**Mesas sin posición:** se renderizan en una fila de fichas debajo del SVG (misma card que en vista lista, pero más compacta).

### 5.3 Cambios en `TpvTableItem` y `TableWithStatus`

Añadir campo opcional al interface:

```typescript
export interface TpvTableItem {
  id: string;
  name: string;
  zone_id: string;
  merged_table_group_id?: string | null;
  layout?: { pos_x: number; pos_y: number; width: number; height: number; shape: 'rect' | 'circle' } | null;
}
```

---

## 6. UX / UI — decisiones de diseño

| Decisión | Elección | Motivo |
|---|---|---|
| Espacio de coordenadas | 1200×800 lógico, SVG viewBox | Independiente de resolución, escalado automático |
| Tecnología canvas | SVG (no Canvas 2D) | Integración natural con Angular, accesible, fácil de estilizar con CSS |
| Drag en editor | Eventos de puntero nativos | CDK no funciona con SVG; librería externa es over-engineering |
| Grid snap | 20 unidades | Suficiente precisión; evita alineaciones caóticas |
| Guardar | Batch por zona | Una acción de usuario, una petición; no auto-save (confuso en editor) |
| Forma mesa | `rect` o `circle` | Cubre el 95% de los casos reales; sin rotación en V1 |
| Mesas sin posición | Visibles bajo el canvas | No se pierde ninguna mesa; el admin las posiciona cuando quiera |
| Toggle en TPV | `localStorage` | El modo elegido persiste entre sesiones |
| Default view | Lista | No rompe el flujo actual; el plano es una mejora opt-in |

---

## 7. Tests

### Unit (PHPUnit + Mockery)

- `TableLayoutTest` — valida bounds, shape inválido lanza excepción.
- `SaveZoneLayoutTest` — persiste N mesas en batch, ignora mesas de otra zona.

### Feature (RefreshDatabase)

- `ZoneLayoutTest`:
  - `PUT /admin/zones/{id}/layout` con payload válido → 200, persiste en DB.
  - Payload con `pos_x` fuera de rango → 422 con error `tables.0.pos_x`.
  - Mesa de otra zona en el payload → ignorada o 422 (a decidir: ignorar es más tolerante).
- `TableListIncludesLayoutTest`:
  - `GET /tpv/tables` devuelve campo `layout` en cada mesa.

### E2E (Playwright) — opcional, baja prioridad

- Admin abre editor, arrastra mesa, guarda → recarga y posición persiste.
- TPV cambia a vista Plano, ve mesa ocupada en naranja, hace clic → abre orden.

---

## 8. Fases de implementación

- [ ] **Fase 1 — Migración y dominio**  
  Migración `add_layout_to_tables`, VO `TableLayout`, entidad `Table` actualizada, tests unit del VO.

- [ ] **Fase 2 — Backend application + HTTP**  
  `SaveZoneLayout` use case, `SaveZoneLayoutCommand`, controller, request, tests feature.  
  Ampliar `GET /tpv/tables` y `GET /admin/tables` para devolver `layout`.

- [ ] **Fase 3 — Frontend backoffice (editor)**  
  `ZoneFloorEditorComponent`, `ZoneLayoutService`, integración en gestión de zonas.

- [ ] **Fase 4 — Frontend TPV (vista plano)**  
  `FloorPlanComponent`, toggle Lista/Plano en `MesasPage`, actualizar `TpvTableItem`.

- [ ] **Fase 5 — Tests e2e + pulido**  
  Playwright smoke tests opcionales; ajustes CSS para tablet 10".

---

## 9. Riesgos y notas

- **Mesas ya existentes sin posición:** la migración deja `pos_x = NULL`. El backend y el frontend deben tratar `layout = null` como estado válido en todos los sitios. No hay rotura de datos.
- **Resize en V1:** puede descartarse para reducir la complejidad del editor. Las mesas tendrán tamaño fijo editable solo con un campo numérico en el panel lateral (más sencillo de implementar y testear).
- **Tablet en TPV:** el SVG viewBox escala automáticamente, pero las zonas de toque (mesas pequeñas) deben tener un mínimo de 48px renderizados.
- **Sin conflictos con fusión de mesas:** `mergedTableGroupId` no se toca en esta mejora. La vista plano muestra la mesa fusionada en su posición como un elemento único.
