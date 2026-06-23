import { computed, inject, Injectable, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ZoneItem, ZoneService } from '../../../../services/zone.service';
import { TableItem, TableLayout, TableService } from '../../../../services/table.service';
import { ZoneLayoutService, SaveZoneLayoutTable } from '../../../../services/zone-layout.service';
import { ToastService } from '../../../../core/services/toast.service';

export type SizePreset = 'S' | 'M' | 'L';

export interface LocalTable {
  id: string;
  name: string;
  zoneId: string;
  posX: number | null;
  posY: number | null;
  width: number;
  height: number;
  shape: 'rect' | 'circle';
}

export const SIZE_PRESETS: Record<SizePreset, Record<'rect' | 'circle', { w: number; h: number }>> = {
  S: { rect: { w: 80,  h: 60  }, circle: { w: 60,  h: 60  } },
  M: { rect: { w: 110, h: 80  }, circle: { w: 90,  h: 90  } },
  L: { rect: { w: 150, h: 110 }, circle: { w: 120, h: 120 } },
};

const GRID_SNAP   = 40;
const CANVAS_W    = 1200;
const CANVAS_H    = 800;

function snap(value: number): number {
  return Math.round(value / GRID_SNAP) * GRID_SNAP;
}

function snapByCenter(rawTopLeft: number, size: number): number {
  return snap(rawTopLeft + size / 2) - size / 2;
}

function fromTableItem(t: TableItem): LocalTable {
  const l     = t.layout;
  const shape = (l?.shape ?? 'rect') as 'rect' | 'circle';
  let   w     = l?.width  ?? SIZE_PRESETS.M[shape].w;
  let   h     = l?.height ?? SIZE_PRESETS.M[shape].h;

  if (shape === 'circle') {
    const dim = Math.max(w, h);
    w = dim;
    h = dim;
  }

  const rawX = l?.pos_x ?? null;
  const rawY = l?.pos_y ?? null;
  return {
    id:     t.id,
    name:   t.name,
    zoneId: t.zone_id,
    posX:   rawX !== null ? snapByCenter(rawX, w) : null,
    posY:   rawY !== null ? snapByCenter(rawY, h) : null,
    width:  w,
    height: h,
    shape,
  };
}

@Injectable()
export class GestionZonesFloorFacade {
  private readonly zoneService   = inject(ZoneService);
  private readonly tableService  = inject(TableService);
  private readonly layoutService = inject(ZoneLayoutService);
  private readonly toastService  = inject(ToastService);

  private readonly _zone    = signal<ZoneItem | null>(null);
  private readonly _tables  = signal<LocalTable[]>([]);
  private readonly _selected = signal<string | null>(null);
  private readonly _loading  = signal(true);
  private readonly _saving   = signal(false);
  private readonly _isDirty  = signal(false);

  readonly zone     = this._zone.asReadonly();
  readonly tables   = this._tables.asReadonly();
  readonly loading  = this._loading.asReadonly();
  readonly saving   = this._saving.asReadonly();
  readonly isDirty  = this._isDirty.asReadonly();
  readonly selectedId = this._selected.asReadonly();

  readonly placedTables       = computed(() => this._tables().filter(t => t.posX !== null));
  readonly unpositionedTables = computed(() => this._tables().filter(t => t.posX === null));
  readonly selectedTable      = computed(() => this._tables().find(t => t.id === this._selected()) ?? null);

  async loadZone(zoneId: string): Promise<void> {
    this._loading.set(true);
    try {
      const [zone, tables] = await Promise.all([
        firstValueFrom(this.zoneService.getZone(zoneId)),
        firstValueFrom(this.tableService.listTablesByZone(zoneId)),
      ]);
      this._zone.set(zone);
      this._tables.set(tables.map(fromTableItem));
      this._isDirty.set(false);
    } finally {
      this._loading.set(false);
    }
  }

  selectTable(id: string | null): void {
    this._selected.set(id);
  }

  updatePosition(id: string, posX: number, posY: number): void {
    this.patchTable(id, { posX, posY });
    this._isDirty.set(true);
  }

  updateGeometry(id: string, posX: number, posY: number, width: number, height: number): void {
    this.patchTable(id, { posX, posY, width, height });
    this._isDirty.set(true);
  }

  placeOnCanvas(id: string): void {
    const t = this._tables().find(t => t.id === id);
    if (!t) return;
    const placed = this.placedTables();
    const offset = placed.length * GRID_SNAP * 2;
    const rawX = Math.min(100 + offset, CANVAS_W - t.width);
    const rawY = 100;
    const posX = Math.max(0, Math.min(CANVAS_W - t.width,  snapByCenter(rawX, t.width)));
    const posY = Math.max(0, Math.min(CANVAS_H - t.height, snapByCenter(rawY, t.height)));
    this.patchTable(id, { posX, posY });
    this._isDirty.set(true);
  }

  removeFromCanvas(id: string): void {
    this.patchTable(id, { posX: null, posY: null });
    if (this._selected() === id) this._selected.set(null);
    this._isDirty.set(true);
  }

  changeShape(id: string, shape: 'rect' | 'circle'): void {
    const t = this._tables().find(t => t.id === id);
    if (!t) return;
    let newW: number, newH: number;
    if (shape === 'circle') {
      const dim = Math.max(t.width, t.height);
      newW = dim; newH = dim;
    } else {
      const presets: SizePreset[] = ['S', 'M', 'L'];
      let best: SizePreset = 'M';
      let bestDiff = Infinity;
      for (const p of presets) {
        const diff = Math.abs(SIZE_PRESETS[p].circle.w - t.width);
        if (diff < bestDiff) { bestDiff = diff; best = p; }
      }
      newW = SIZE_PRESETS[best].rect.w;
      newH = SIZE_PRESETS[best].rect.h;
    }
    this.patchTable(id, { shape, width: newW, height: newH });
    this._isDirty.set(true);
  }

  changeSize(id: string, preset: SizePreset): void {
    const t = this._tables().find(t => t.id === id);
    if (!t) return;
    const size = SIZE_PRESETS[preset][t.shape];
    this.patchTable(id, { width: size.w, height: size.h });
    this._isDirty.set(true);
  }

  async createAndPlace(name: string, shape: 'rect' | 'circle', preset: SizePreset): Promise<void> {
    const zoneId = this._zone()?.id;
    if (!zoneId || !name.trim()) return;

    const size = SIZE_PRESETS[preset][shape];
    const created = await firstValueFrom(this.tableService.createTable({ zone_id: zoneId, name: name.trim() }));

    const placed = this.placedTables();
    const offset = placed.length * GRID_SNAP * 2;
    const posX = snapByCenter(Math.min(100 + offset, CANVAS_W - size.w), size.w);
    const posY = snapByCenter(100, size.h);

    const local: LocalTable = {
      id: created.id, name: created.name, zoneId,
      posX, posY, width: size.w, height: size.h, shape,
    };
    this._tables.update(list => [...list, local]);
    this._selected.set(created.id);
    this._isDirty.set(true);
  }

  reorderTables(orderedIds: string[]): void {
    const idSet = new Set(orderedIds);
    this._tables.update(list => {
      const map      = new Map(list.map(t => [t.id, t]));
      const reordered = orderedIds.map(id => map.get(id)!).filter(Boolean);
      const rest      = list.filter(t => !idSet.has(t.id));
      return [...reordered, ...rest];
    });
    this._isDirty.set(true);
  }

  async deleteTable(id: string): Promise<void> {
    await firstValueFrom(this.tableService.deleteTable(id));
    this._tables.update(list => list.filter(t => t.id !== id));
    if (this._selected() === id) this._selected.set(null);
    this._isDirty.set(true);
  }

  renameTable(id: string, name: string): void {
    if (!name.trim()) return;
    this.patchTable(id, { name: name.trim() });
    this._isDirty.set(true);
  }

  async save(): Promise<boolean> {
    const zoneId = this._zone()?.id;
    if (!zoneId) return false;
    this._saving.set(true);
    try {
      const payload: SaveZoneLayoutTable[] = this.placedTables().map(t => ({
        uuid:   t.id,
        pos_x:  t.posX!,
        pos_y:  t.posY!,
        width:  t.width,
        height: t.height,
        shape:  t.shape,
      }));
      await this.layoutService.saveZoneLayout(zoneId, payload);
      this._isDirty.set(false);
      return true;
    } catch {
      return false;
    } finally {
      this._saving.set(false);
    }
  }

  private patchTable(id: string, patch: Partial<LocalTable>): void {
    this._tables.update(list => list.map(t => t.id === id ? { ...t, ...patch } : t));
  }

}
