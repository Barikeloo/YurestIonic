import { Component, computed, input, output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { CdkDragDrop, CdkDropList, CdkDrag, CdkDragHandle, CdkDragPlaceholder, moveItemInArray } from '@angular/cdk/drag-drop';
import { LocalTable, SIZE_PRESETS, SizePreset } from '../../facades/gestion-zones-floor.facade';

@Component({
  selector: 'app-floor-panel',
  standalone: true,
  imports: [FormsModule, CdkDropList, CdkDrag, CdkDragHandle, CdkDragPlaceholder],
  templateUrl: './floor-panel.component.html',
  styleUrls: ['./floor-panel.component.scss'],
})
export class FloorPanelComponent {
  // ── Inputs ──────────────────────────────────────────────────────────────
  readonly placedTables  = input.required<LocalTable[]>();
  readonly unpositioned  = input.required<LocalTable[]>();
  readonly selectedTable = input<LocalTable | null>(null);
  readonly isSaving      = input<boolean>(false);
  readonly isDirty       = input<boolean>(false);
  readonly zoomLevel     = input<number>(1);

  // ── Outputs ─────────────────────────────────────────────────────────────
  readonly openAddModal      = output<void>();
  readonly tableSelected     = output<string | null>();
  readonly layerOrderChanged = output<string[]>();
  readonly placeOnCanvas     = output<string>();
  readonly removeFromCanvas  = output<string>();
  readonly nameChanged       = output<{ id: string; name: string }>();
  readonly shapeChanged      = output<{ id: string; shape: 'rect' | 'circle' }>();
  readonly sizeChanged       = output<{ id: string; preset: SizePreset }>();
  readonly zoomIn            = output<void>();
  readonly zoomOut           = output<void>();
  readonly zoomReset         = output<void>();
  readonly save              = output<void>();

  protected readonly zoomPercent = computed(() => Math.round(this.zoomLevel() * 100));
  protected readonly presets: SizePreset[] = ['S', 'M', 'L'];

  // ── Layer drag & drop ────────────────────────────────────────────────────
  protected onLayerDrop(event: CdkDragDrop<LocalTable[]>): void {
    if (event.previousIndex === event.currentIndex) return;
    const ids = this.placedTables().map(t => t.id);
    moveItemInArray(ids, event.previousIndex, event.currentIndex);
    this.layerOrderChanged.emit(ids);
  }

  // ── Size/shape helpers ───────────────────────────────────────────────────
  protected presetLabel(preset: SizePreset, shape: 'rect' | 'circle'): string {
    const s = SIZE_PRESETS[preset][shape];
    return shape === 'circle' ? `ø${s.w}` : `${s.w}×${s.h}`;
  }

  protected currentPreset(t: LocalTable): SizePreset {
    for (const key of this.presets) {
      const s = SIZE_PRESETS[key][t.shape];
      if (s.w === t.width && s.h === t.height) return key;
    }
    return 'M';
  }

  // ── Selected table actions ────────────────────────────────────────────────
  protected onChangeShape(shape: 'rect' | 'circle'): void {
    const t = this.selectedTable();
    if (!t || t.shape === shape) return;
    this.shapeChanged.emit({ id: t.id, shape });
  }

  protected onSizePreset(preset: SizePreset): void {
    const t = this.selectedTable();
    if (!t) return;
    this.sizeChanged.emit({ id: t.id, preset });
  }

  protected onRemove(): void {
    const t = this.selectedTable();
    if (!t) return;
    this.removeFromCanvas.emit(t.id);
  }
}
