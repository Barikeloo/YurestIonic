import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  ViewChild,
  computed,
  effect,
  input,
  output,
  signal,
} from '@angular/core';
import { LocalTable } from '../../facades/gestion-zones-floor.facade';

const GRID_SNAP       = 40;
const CANVAS_W        = 1200;
const CANVAS_H        = 800;
const FIT_PAD         = 40;
const MIN_SIZE        = GRID_SNAP;
const HANDLE_R        = 6;
const DRAG_THRESHOLD  = 5;   // SVG units before a pointerdown is considered a drag
const CANVAS_INNER_PAD = 24; // matches .canvas-inner padding

type DragKind = 'move' | 'resize-tl' | 'resize-tr' | 'resize-bl' | 'resize-br';

interface DragState {
  id:          string;
  kind:        DragKind;
  startPt:     { x: number; y: number };
  offsetX:     number;
  offsetY:     number;
  initialGeom: { posX: number; posY: number; width: number; height: number };
  moved:       boolean;
}

function snap(v: number): number { return Math.round(v / GRID_SNAP) * GRID_SNAP; }
function snapByCenter(raw: number, size: number): number { return snap(raw + size / 2) - size / 2; }

@Component({
  selector: 'app-floor-canvas',
  standalone: true,
  templateUrl: './floor-canvas.component.html',
  styleUrls: ['./floor-canvas.component.scss'],
})
export class FloorCanvasComponent implements AfterViewInit, OnDestroy {
  // ── Inputs ──────────────────────────────────────────────────────────────
  readonly tables           = input.required<LocalTable[]>();
  readonly selectedId       = input<string | null>(null);
  readonly zoomLevel        = input<number>(1);
  readonly centerOnTableId  = input<string | null>(null);

  // ── Outputs ─────────────────────────────────────────────────────────────
  readonly tableClicked         = output<string>();
  readonly canvasClicked        = output<void>();
  readonly tableMoved           = output<{ id: string; x: number; y: number }>();
  readonly tableResized         = output<{ id: string; posX: number; posY: number; width: number; height: number }>();
  readonly tableNameEdited      = output<{ id: string; name: string }>();
  readonly tableShapeToggled    = output<string>();
  readonly tableDeleteRequested = output<string>();
  readonly removeRequested      = output<string>();
  readonly zoomChanged          = output<number>();
  readonly addTableRequested    = output<void>();

  @ViewChild('svgEl')      svgRef!:  ElementRef<SVGSVGElement>;
  @ViewChild('canvasWrap') wrapRef!: ElementRef<HTMLDivElement>;

  // ── Internal state ───────────────────────────────────────────────────────
  protected readonly _drag     = signal<DragState | null>(null);
  protected readonly _livePos  = signal<{ x: number; y: number; w: number; h: number } | null>(null);
  protected readonly _editing  = signal<string | null>(null);
  protected readonly _editVal  = signal('');
  protected readonly _tooltip  = signal<{ x: number; y: number; text: string } | null>(null);

  protected readonly svgW = computed(() => Math.round(CANVAS_W * this.zoomLevel()));
  protected readonly svgH = computed(() => Math.round(CANVAS_H * this.zoomLevel()));
  protected readonly placedTables = computed(() => this.tables().filter(t => t.posX !== null));
  protected readonly HANDLE_R = HANDLE_R;

  // Floating menu: position above the selected table in screen coords
  protected readonly floatingMenu = computed(() => {
    const id = this.selectedId();
    if (!id || this._drag()) return null;
    const t = this.placedTables().find(t => t.id === id);
    if (!t) return null;
    const zoom = this.zoomLevel();
    const tw = this.tableW(t);
    const cx = (t.posX! + tw / 2) * zoom + CANVAS_INNER_PAD;
    const ty = t.posY! * zoom + CANVAS_INNER_PAD;
    return { id, table: t, cx, ty };
  });

  private wheelHandler!: (e: WheelEvent) => void;

  constructor() {
    effect(() => {
      const id = this.centerOnTableId();
      if (!id) return;
      requestAnimationFrame(() => this.scrollToTable(id));
    });
  }

  // ── Lifecycle ────────────────────────────────────────────────────────────
  ngAfterViewInit(): void {
    const wrap = this.wrapRef.nativeElement;
    const availW = wrap.clientWidth - FIT_PAD;
    const availH = wrap.clientHeight - FIT_PAD;
    if (availW > 0 && availH > 0) {
      const fit = Math.min(availW / CANVAS_W, availH / CANVAS_H);
      this.zoomChanged.emit(Math.max(0.4, Math.min(1.0, Math.round(fit * 10) / 10)));
    }

    this.wheelHandler = (e: WheelEvent) => {
      if (!e.ctrlKey && !e.metaKey) return;
      e.preventDefault();
      const step = e.deltaY > 0 ? -0.1 : 0.1;
      this.zoomChanged.emit(Math.round(Math.max(0.4, Math.min(2.0, this.zoomLevel() + step)) * 10) / 10);
    };
    wrap.addEventListener('wheel', this.wheelHandler, { passive: false });
  }

  ngOnDestroy(): void {
    this.wrapRef?.nativeElement.removeEventListener('wheel', this.wheelHandler);
  }

  // ── Live geometry helpers ─────────────────────────────────────────────────
  protected tableX(t: LocalTable): number {
    const d = this._drag();
    return d?.id === t.id ? (this._livePos()?.x ?? t.posX!) : t.posX!;
  }
  protected tableY(t: LocalTable): number {
    const d = this._drag();
    return d?.id === t.id ? (this._livePos()?.y ?? t.posY!) : t.posY!;
  }
  protected tableW(t: LocalTable): number {
    const d = this._drag();
    return d?.id === t.id ? (this._livePos()?.w ?? t.width) : t.width;
  }
  protected tableH(t: LocalTable): number {
    const d = this._drag();
    return d?.id === t.id ? (this._livePos()?.h ?? t.height) : t.height;
  }
  protected centerX(t: LocalTable): number { return this.tableW(t) / 2; }
  protected centerY(t: LocalTable): number {
    const base = t.shape === 'circle' ? this.tableW(t) / 2 : this.tableH(t) / 2;
    return base + 4;
  }

  // ── Pointer: table move ──────────────────────────────────────────────────
  protected onTablePointerDown(e: PointerEvent, t: LocalTable): void {
    if (this._editing()) return;
    e.stopPropagation();
    const pt = this.toSvg(e);
    this._drag.set({
      id: t.id, kind: 'move',
      startPt: pt,
      offsetX: pt.x - t.posX!, offsetY: pt.y - t.posY!,
      initialGeom: { posX: t.posX!, posY: t.posY!, width: t.width, height: t.height },
      moved: false,
    });
    this._livePos.set({ x: t.posX!, y: t.posY!, w: t.width, h: t.height });
    this._tooltip.set(null);
    // NO setPointerCapture — handled by threshold in pointerup
  }

  // ── Pointer: resize handles ───────────────────────────────────────────────
  protected onHandlePointerDown(e: PointerEvent, t: LocalTable, corner: string): void {
    e.stopPropagation();
    const pt = this.toSvg(e);
    this._drag.set({
      id: t.id, kind: corner as DragKind,
      startPt: pt, offsetX: 0, offsetY: 0,
      initialGeom: { posX: t.posX!, posY: t.posY!, width: t.width, height: t.height },
      moved: false,
    });
    this._livePos.set({ x: t.posX!, y: t.posY!, w: t.width, h: t.height });
    this.svgRef.nativeElement.setPointerCapture(e.pointerId); // capture only for resize
  }

  // ── Pointer: move ──────────────────────────────────────────────────────────
  protected onSvgPointerMove(e: PointerEvent): void {
    const drag = this._drag();
    if (!drag) return;
    const t = this.tables().find(t => t.id === drag.id);
    if (!t) return;
    const pt  = this.toSvg(e);
    const dx  = pt.x - drag.startPt.x;
    const dy  = pt.y - drag.startPt.y;

    // Mark as moved once threshold exceeded
    if (!drag.moved && (Math.abs(dx) > DRAG_THRESHOLD || Math.abs(dy) > DRAG_THRESHOLD)) {
      this._drag.update(d => d ? { ...d, moved: true } : null);
    }
    if (!drag.moved && Math.abs(dx) <= DRAG_THRESHOLD && Math.abs(dy) <= DRAG_THRESHOLD) return;

    const g = drag.initialGeom;

    if (drag.kind === 'move') {
      const x = Math.max(0, Math.min(CANVAS_W - t.width,  snapByCenter(pt.x - drag.offsetX, t.width)));
      const y = Math.max(0, Math.min(CANVAS_H - t.height, snapByCenter(pt.y - drag.offsetY, t.height)));
      this._livePos.set({ x, y, w: t.width, h: t.height });
      return;
    }

    // Resize
    const isCircle = t.shape === 'circle';
    let newW = g.width, newH = g.height, newX = g.posX, newY = g.posY;
    switch (drag.kind) {
      case 'resize-br':
        newW = snap(Math.max(MIN_SIZE, g.width  + dx));
        newH = isCircle ? newW : snap(Math.max(MIN_SIZE, g.height + dy));
        break;
      case 'resize-bl':
        newW = snap(Math.max(MIN_SIZE, g.width  - dx));
        newH = isCircle ? newW : snap(Math.max(MIN_SIZE, g.height + dy));
        newX = g.posX + (g.width - newW);
        break;
      case 'resize-tr':
        newW = snap(Math.max(MIN_SIZE, g.width  + dx));
        newH = isCircle ? newW : snap(Math.max(MIN_SIZE, g.height - dy));
        newY = isCircle ? g.posY + (g.height - newW) : g.posY + (g.height - newH);
        break;
      case 'resize-tl':
        newW = snap(Math.max(MIN_SIZE, g.width  - dx));
        newH = isCircle ? newW : snap(Math.max(MIN_SIZE, g.height - dy));
        newX = g.posX + (g.width  - newW);
        newY = g.posY + (g.height - (isCircle ? newW : newH));
        break;
    }
    if (isCircle) newH = newW;
    newX = Math.max(0, Math.min(CANVAS_W - newW, newX));
    newY = Math.max(0, Math.min(CANVAS_H - newH, newY));
    this._livePos.set({ x: newX, y: newY, w: newW, h: newH });
  }

  // ── Pointer: table up (fires when pointer releases over the table <g>) ────
  protected onTablePointerUp(e: PointerEvent, t: LocalTable): void {
    const drag = this._drag();
    if (!drag || drag.id !== t.id || drag.kind !== 'move') return;
    e.stopPropagation(); // prevent SVG background up from also firing
    this.finalizeDrag(drag);
  }

  // ── Pointer: SVG background up (drag ended outside table, or resize) ──────
  protected onSvgPointerUp(): void {
    const drag = this._drag();
    if (!drag) {
      // Background click — deselect
      if (this._editing()) { this.commitEdit(); return; }
      this.canvasClicked.emit();
      return;
    }
    this.finalizeDrag(drag);
  }

  // Fires when pointer leaves the whole canvas-wrap (including float-menu area).
  // Only terminates an active drag — never deselects.
  protected onCanvasWrapLeave(): void {
    const drag = this._drag();
    if (!drag) return; // pointer just wandered out, no drag active → do nothing
    this.finalizeDrag(drag);
  }

  private finalizeDrag(drag: DragState): void {
    const pos = this._livePos();
    if (!drag.moved) {
      // Threshold not reached → treat as a simple click (select)
      this.tableClicked.emit(drag.id);
    } else if (drag.kind === 'move') {
      if (pos) this.tableMoved.emit({ id: drag.id, x: pos.x, y: pos.y });
    } else {
      if (pos) this.tableResized.emit({ id: drag.id, posX: pos.x, posY: pos.y, width: pos.w, height: pos.h });
    }
    this._drag.set(null);
    this._livePos.set(null);
  }

  // ── Inline name editing ──────────────────────────────────────────────────
  protected onTableDblClick(e: MouseEvent, t: LocalTable): void {
    e.stopPropagation();
    this.startEdit(t.id, t.name);
  }

  protected startEdit(id: string, name: string): void {
    this._editing.set(id);
    this._editVal.set(name);
    setTimeout(() => {
      const inp = this.svgRef.nativeElement.querySelector<HTMLInputElement>('.inline-edit');
      if (inp) { inp.focus(); inp.select(); }
    }, 30);
  }

  protected onEditInput(e: Event): void { this._editVal.set((e.target as HTMLInputElement).value); }
  protected onEditKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter')  { e.preventDefault(); this.commitEdit(); }
    if (e.key === 'Escape') { this._editing.set(null); }
  }
  protected onEditBlur(): void { this.commitEdit(); }

  private commitEdit(): void {
    const id  = this._editing();
    const val = this._editVal().trim();
    if (id && val) this.tableNameEdited.emit({ id, name: val });
    this._editing.set(null);
  }

  // ── Floating menu actions ─────────────────────────────────────────────────
  protected onMenuRename(t: LocalTable): void   { this.startEdit(t.id, t.name); }
  protected onMenuToggleShape(t: LocalTable): void { this.tableShapeToggled.emit(t.id); }
  protected onMenuRemove(t: LocalTable): void   { this.removeRequested.emit(t.id); }
  protected onMenuDelete(t: LocalTable): void   { this.tableDeleteRequested.emit(t.id); }

  // ── Tooltip ──────────────────────────────────────────────────────────────
  protected onTablePointerEnter(e: PointerEvent, t: LocalTable): void {
    if (this._drag()) return;
    const rect = this.wrapRef.nativeElement.getBoundingClientRect();
    const dims = t.shape === 'circle' ? `ø${t.width}` : `${t.width} × ${t.height}`;
    this._tooltip.set({ x: e.clientX - rect.left + 12, y: e.clientY - rect.top - 36, text: `${t.name}  ·  ${dims}` });
  }
  protected onTablePointerLeave(): void { this._tooltip.set(null); }

  // ── Keyboard ─────────────────────────────────────────────────────────────
  protected onKeyDown(e: KeyboardEvent): void {
    if (e.key !== 'Delete' && e.key !== 'Backspace') return;
    if (this._editing()) return;
    const id = this.selectedId();
    if (id) { e.preventDefault(); this.removeRequested.emit(id); }
  }

  // ── Canvas double-click → request add table ───────────────────────────────
  protected onCanvasDblClick(): void {
    this.addTableRequested.emit();
  }

  // ── Center canvas on a specific table ─────────────────────────────────────
  private scrollToTable(id: string): void {
    if (!this.wrapRef) return;
    const t = this.placedTables().find(t => t.id === id);
    if (!t) return;
    const zoom = this.zoomLevel();
    const wrap = this.wrapRef.nativeElement;
    const cx = (t.posX! + this.tableW(t) / 2) * zoom + CANVAS_INNER_PAD;
    const cy = (t.posY! + this.tableH(t) / 2) * zoom + CANVAS_INNER_PAD;
    wrap.scrollTo({
      left: Math.max(0, cx - wrap.clientWidth  / 2),
      top:  Math.max(0, cy - wrap.clientHeight / 2),
      behavior: 'smooth',
    });
  }

  // ── Coord transform ──────────────────────────────────────────────────────
  private toSvg(e: PointerEvent): { x: number; y: number } {
    const svg = this.svgRef.nativeElement;
    const pt  = svg.createSVGPoint();
    pt.x = e.clientX; pt.y = e.clientY;
    const p = pt.matrixTransform(svg.getScreenCTM()!.inverse());
    return { x: p.x, y: p.y };
  }
}
