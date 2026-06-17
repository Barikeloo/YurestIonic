import { Component, effect, ElementRef, inject, input, signal, computed, ViewChild } from '@angular/core';
import { TableRow } from '../../../../pages/core/gestion/facades/gestion-zones.facade';
import { ZoneLayoutService } from '../../../../services/zone-layout.service';
import { ToastService } from '../../../../core/services/toast.service';

const GRID_SNAP    = 20;
const DEFAULT_W    = 100;
const DEFAULT_H    = 60;
const CANVAS_W     = 1200;
const CANVAS_H     = 800;

interface LocalTable {
  uuid: string;
  name: string;
  posX: number | null;
  posY: number | null;
  width: number;
  height: number;
  shape: 'rect' | 'circle';
}

interface DragState {
  uuid: string;
  offsetX: number;
  offsetY: number;
}

@Component({
  selector: 'app-zone-floor-editor',
  standalone: true,
  templateUrl: './zone-floor-editor.component.html',
  styleUrls: ['./zone-floor-editor.component.scss'],
})
export class ZoneFloorEditorComponent {
  readonly tables  = input.required<TableRow[]>();
  readonly zoneId  = input.required<string>();

  private readonly layoutService = inject(ZoneLayoutService);
  private readonly toastService  = inject(ToastService);

  @ViewChild('svgCanvas') svgRef!: ElementRef<SVGSVGElement>;

  protected readonly _tables   = signal<LocalTable[]>([]);
  protected readonly _dragging = signal<DragState | null>(null);
  protected readonly _saving   = signal(false);

  protected readonly placedTables = computed(() =>
    this._tables().filter(t => t.posX !== null)
  );
  protected readonly unpositionedTables = computed(() =>
    this._tables().filter(t => t.posX === null)
  );

  constructor() {
    effect(() => {
      this._tables.set(
        this.tables().map(t => ({
          uuid:   t.uuid!,
          name:   t.name,
          posX:   t.layout?.pos_x ?? null,
          posY:   t.layout?.pos_y ?? null,
          width:  t.layout?.width  ?? DEFAULT_W,
          height: t.layout?.height ?? DEFAULT_H,
          shape:  (t.layout?.shape as 'rect' | 'circle') ?? 'rect',
        })),
      );
    });
  }

  onAddToCanvas(table: LocalTable): void {
    // Place at a staggered default position to avoid stacking
    const placed = this.placedTables();
    const offset = placed.length * GRID_SNAP * 2;
    const posX = this.snap(Math.min(100 + offset, CANVAS_W - DEFAULT_W));
    const posY = this.snap(100);
    this.patchTable(table.uuid, { posX, posY });
  }

  onRemoveFromCanvas(table: LocalTable, event: Event): void {
    event.stopPropagation();
    this.patchTable(table.uuid, { posX: null, posY: null });
  }

  onToggleShape(table: LocalTable, event: Event): void {
    event.stopPropagation();
    this.patchTable(table.uuid, { shape: table.shape === 'rect' ? 'circle' : 'rect' });
  }

  onTablePointerDown(event: PointerEvent, table: LocalTable): void {
    event.stopPropagation();
    const pt = this.toSvgPoint(event);
    this._dragging.set({
      uuid:    table.uuid,
      offsetX: pt.x - (table.posX ?? 0),
      offsetY: pt.y - (table.posY ?? 0),
    });
    this.svgRef.nativeElement.setPointerCapture(event.pointerId);
  }

  onSvgPointerMove(event: PointerEvent): void {
    const drag = this._dragging();
    if (!drag) return;
    const pt  = this.toSvgPoint(event);
    const tbl = this._tables().find(t => t.uuid === drag.uuid);
    if (!tbl) return;
    const w = tbl.width, h = tbl.height;
    this.patchTable(drag.uuid, {
      posX: this.snap(Math.max(0, Math.min(CANVAS_W - w, pt.x - drag.offsetX))),
      posY: this.snap(Math.max(0, Math.min(CANVAS_H - h, pt.y - drag.offsetY))),
    });
  }

  onSvgPointerUp(): void {
    this._dragging.set(null);
  }

  async onSave(): Promise<void> {
    const placed = this.placedTables();
    if (!this.zoneId()) return;
    this._saving.set(true);
    try {
      await this.layoutService.saveZoneLayout(
        this.zoneId(),
        placed.map(t => ({
          uuid:   t.uuid,
          pos_x:  t.posX!,
          pos_y:  t.posY!,
          width:  t.width,
          height: t.height,
          shape:  t.shape,
        })),
      );
      this.toastService.presentSuccess('Plano guardado.');
    } catch {
      this.toastService.presentError('No se pudo guardar el plano.');
    } finally {
      this._saving.set(false);
    }
  }

  // ── Helpers ──────────────────────────────────────────────────────────────

  private toSvgPoint(event: PointerEvent): { x: number; y: number } {
    const svg = this.svgRef.nativeElement;
    const pt  = svg.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const svgPt = pt.matrixTransform(svg.getScreenCTM()!.inverse());
    return { x: svgPt.x, y: svgPt.y };
  }

  private snap(value: number): number {
    return Math.round(value / GRID_SNAP) * GRID_SNAP;
  }

  private patchTable(uuid: string, patch: Partial<LocalTable>): void {
    this._tables.update(list =>
      list.map(t => t.uuid === uuid ? { ...t, ...patch } : t)
    );
  }
}
