import { Component, computed, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { LocalTable, SIZE_PRESETS, SizePreset } from '../../facades/gestion-zones-floor.facade';

@Component({
  selector: 'app-floor-panel',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './floor-panel.component.html',
  styleUrls: ['./floor-panel.component.scss'],
})
export class FloorPanelComponent {
  readonly unpositioned  = input.required<LocalTable[]>();
  readonly selectedTable = input<LocalTable | null>(null);
  readonly isSaving      = input<boolean>(false);
  readonly isDirty       = input<boolean>(false);
  readonly zoomLevel     = input<number>(1);

  readonly addTable         = output<{ name: string; shape: 'rect' | 'circle'; preset: SizePreset }>();
  readonly placeOnCanvas    = output<string>();
  readonly removeFromCanvas = output<string>();
  readonly nameChanged      = output<{ id: string; name: string }>();
  readonly shapeChanged     = output<{ id: string; shape: 'rect' | 'circle' }>();
  readonly sizeChanged      = output<{ id: string; preset: SizePreset }>();
  readonly zoomIn           = output<void>();
  readonly zoomOut          = output<void>();
  readonly zoomReset        = output<void>();
  readonly save             = output<void>();

  protected newName   = signal('');
  protected newShape  = signal<'rect' | 'circle'>('rect');
  protected newPreset = signal<SizePreset>('M');

  protected readonly zoomPercent = computed(() => Math.round(this.zoomLevel() * 100));
  protected readonly presets: SizePreset[] = ['S', 'M', 'L'];

  protected presetLabel(preset: SizePreset, shape: 'rect' | 'circle'): string {
    const s = SIZE_PRESETS[preset][shape];
    return shape === 'circle' ? `ø${s.w}` : `${s.w}×${s.h}`;
  }

  protected presetIconSize(preset: SizePreset): { w: number; h: number } {
    const scale = { S: 0.14, M: 0.18, L: 0.22 };
    const k = scale[preset];
    const s = SIZE_PRESETS[preset]['rect'];
    return { w: Math.round(s.w * k), h: Math.round(s.h * k) };
  }

  protected currentPreset(t: LocalTable): SizePreset {
    for (const key of this.presets) {
      const s = SIZE_PRESETS[key][t.shape];
      if (s.w === t.width && s.h === t.height) return key;
    }
    return 'M';
  }

  protected onAddTable(): void {
    const name = this.newName().trim();
    if (!name) return;
    this.addTable.emit({ name, shape: this.newShape(), preset: this.newPreset() });
    this.newName.set('');
  }

  protected setNewShape(s: 'rect' | 'circle'): void { this.newShape.set(s); }
  protected setNewPreset(p: SizePreset): void        { this.newPreset.set(p); }

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
