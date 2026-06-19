import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { GestionZonesFloorFacade, SizePreset } from './facades/gestion-zones-floor.facade';
import { HasUnsavedChanges } from '../../../core/guards/can-deactivate.guard';
import { FloorCanvasComponent } from './components/floor-canvas/floor-canvas.component';
import { FloorPanelComponent } from './components/floor-panel/floor-panel.component';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-gestion-zones-floor',
  templateUrl: './gestion-zones-floor.page.html',
  styleUrls: ['./gestion-zones-floor.page.scss'],
  standalone: true,
  imports: [FloorCanvasComponent, FloorPanelComponent, FormsModule],
  providers: [GestionZonesFloorFacade],
})
export class GestionZonesFloorPage implements OnInit, HasUnsavedChanges {
  protected readonly facade      = inject(GestionZonesFloorFacade);
  private  readonly route        = inject(ActivatedRoute);
  private  readonly router       = inject(Router);
  private  readonly toastService = inject(ToastService);

  protected readonly zoomLevel       = signal<number>(1);
  protected readonly centerOnTableId = signal<string | null>(null);

  // ── Add-table modal ───────────────────────────────────────────────────────
  protected addModalOpen    = false;
  protected addModalName    = '';
  protected addModalShape: 'rect' | 'circle' = 'rect';
  protected addModalPreset: SizePreset = 'M';
  protected readonly presets: SizePreset[] = ['S', 'M', 'L'];

  async ngOnInit(): Promise<void> {
    const zoneId = this.route.snapshot.paramMap.get('zoneId');
    if (!zoneId) {
      void this.router.navigate(['/app/gestion']);
      return;
    }
    try {
      await this.facade.loadZone(zoneId);
    } catch {
      this.toastService.presentError('No se pudo cargar la zona.');
      void this.router.navigate(['/app/gestion']);
    }
  }

  hasUnsavedChanges(): boolean { return this.facade.isDirty(); }

  goBack(): void { void this.router.navigate(['/app/gestion']); }

  // ── Save ──────────────────────────────────────────────────────────────────
  async onSave(): Promise<void> {
    const ok = await this.facade.save();
    if (ok) this.toastService.presentSuccess('Plano guardado.');
    else    this.toastService.presentError('No se pudo guardar el plano.');
  }

  // ── Canvas events ─────────────────────────────────────────────────────────
  onTableClicked(id: string): void    { this.facade.selectTable(id); }
  onCanvasClicked(): void             { this.facade.selectTable(null); }
  onTableMoved(ev: { id: string; x: number; y: number }): void {
    this.facade.updatePosition(ev.id, ev.x, ev.y);
  }
  onRemoveRequested(id: string): void { this.facade.removeFromCanvas(id); }
  onTableResized(ev: { id: string; posX: number; posY: number; width: number; height: number }): void {
    this.facade.updateGeometry(ev.id, ev.posX, ev.posY, ev.width, ev.height);
  }
  onTableNameEdited(ev: { id: string; name: string }): void { this.facade.renameTable(ev.id, ev.name); }
  onTableShapeToggled(id: string): void {
    const t = this.facade.tables().find(t => t.id === id);
    if (!t) return;
    this.facade.changeShape(id, t.shape === 'rect' ? 'circle' : 'rect');
  }
  onZoomChanged(zoom: number): void { this.zoomLevel.set(zoom); }

  // Canvas double-click → open add modal
  onAddTableRequested(): void { this.openAddModal(); }

  // ── Panel events ──────────────────────────────────────────────────────────
  onPlaceOnCanvas(id: string): void    { this.facade.placeOnCanvas(id); }
  onRemoveFromCanvas(id: string): void { this.facade.removeFromCanvas(id); }
  onShapeChanged(ev: { id: string; shape: 'rect' | 'circle' }): void {
    this.facade.changeShape(ev.id, ev.shape);
  }
  onSizeChanged(ev: { id: string; preset: SizePreset }): void {
    this.facade.changeSize(ev.id, ev.preset);
  }
  onLayerOrderChanged(ids: string[]): void { this.facade.reorderTables(ids); }

  // Select from layers list + center canvas on it
  onTableSelectedFromPanel(id: string | null): void {
    this.facade.selectTable(id);
    if (id) {
      this.centerOnTableId.set(null);
      setTimeout(() => this.centerOnTableId.set(id), 0);
    }
  }

  // ── Zoom ──────────────────────────────────────────────────────────────────
  zoomIn():    void { this.onZoomChanged(Math.min(2.0, Math.round((this.zoomLevel() + 0.1) * 10) / 10)); }
  zoomOut():   void { this.onZoomChanged(Math.max(0.4, Math.round((this.zoomLevel() - 0.1) * 10) / 10)); }
  zoomReset(): void { this.onZoomChanged(1); }

  // ── Add-table modal ───────────────────────────────────────────────────────
  openAddModal(): void {
    this.addModalName   = '';
    this.addModalShape  = 'rect';
    this.addModalPreset = 'M';
    this.addModalOpen   = true;
  }

  closeAddModal(): void { this.addModalOpen = false; }

  async confirmAddTable(): Promise<void> {
    if (!this.addModalName.trim()) return;
    try {
      await this.facade.createAndPlace(this.addModalName.trim(), this.addModalShape, this.addModalPreset);
      this.addModalOpen = false;
    } catch {
      this.toastService.presentError('No se pudo crear la mesa.');
    }
  }
}
