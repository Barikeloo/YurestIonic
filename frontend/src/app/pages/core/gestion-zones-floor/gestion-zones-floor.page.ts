import { Component, inject, OnInit, signal } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { GestionZonesFloorFacade, SizePreset } from './facades/gestion-zones-floor.facade';
import { FloorCanvasComponent } from './components/floor-canvas/floor-canvas.component';
import { FloorPanelComponent } from './components/floor-panel/floor-panel.component';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-gestion-zones-floor',
  templateUrl: './gestion-zones-floor.page.html',
  styleUrls: ['./gestion-zones-floor.page.scss'],
  standalone: true,
  imports: [FloorCanvasComponent, FloorPanelComponent],
  providers: [GestionZonesFloorFacade],
})
export class GestionZonesFloorPage implements OnInit {
  protected readonly facade      = inject(GestionZonesFloorFacade);
  private  readonly route        = inject(ActivatedRoute);
  private  readonly router       = inject(Router);
  private  readonly toastService = inject(ToastService);

  protected readonly zoomLevel = signal<number>(1);

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

  goBack(): void {
    if (this.facade.isDirty()) {
      if (!confirm('Hay cambios sin guardar. ¿Salir sin guardar?')) return;
    }
    void this.router.navigate(['/app/gestion']);
  }

  // ── Save ─────────────────────────────────────────────────────────────────
  async onSave(): Promise<void> {
    const ok = await this.facade.save();
    if (ok) {
      this.toastService.presentSuccess('Plano guardado.');
    } else {
      this.toastService.presentError('No se pudo guardar el plano.');
    }
  }

  // ── Canvas events ─────────────────────────────────────────────────────────
  onTableClicked(id: string): void {
    this.facade.selectTable(id);
  }

  onCanvasClicked(): void {
    this.facade.selectTable(null);
  }

  onTableMoved(ev: { id: string; x: number; y: number }): void {
    this.facade.updatePosition(ev.id, ev.x, ev.y);
  }

  onRemoveRequested(id: string): void {
    this.facade.removeFromCanvas(id);
  }

  onTableResized(ev: { id: string; posX: number; posY: number; width: number; height: number }): void {
    this.facade.updateGeometry(ev.id, ev.posX, ev.posY, ev.width, ev.height);
  }

  onTableNameEdited(ev: { id: string; name: string }): void {
    this.facade.renameTable(ev.id, ev.name);
  }

  onTableShapeToggled(id: string): void {
    const t = this.facade.tables().find(t => t.id === id);
    if (!t) return;
    this.facade.changeShape(id, t.shape === 'rect' ? 'circle' : 'rect');
  }

  onZoomChanged(zoom: number): void {
    this.zoomLevel.set(zoom);
  }

  zoomIn(): void {
    this.onZoomChanged(Math.min(2.0, Math.round((this.zoomLevel() + 0.1) * 10) / 10));
  }

  zoomOut(): void {
    this.onZoomChanged(Math.max(0.4, Math.round((this.zoomLevel() - 0.1) * 10) / 10));
  }

  zoomReset(): void {
    this.onZoomChanged(1);
  }

  get zoomPercent(): number {
    return Math.round(this.zoomLevel() * 100);
  }

  // ── Panel events ──────────────────────────────────────────────────────────
  async onAddTable(ev: { name: string; shape: 'rect' | 'circle'; preset: SizePreset }): Promise<void> {
    try {
      await this.facade.createAndPlace(ev.name, ev.shape, ev.preset);
    } catch {
      this.toastService.presentError('No se pudo crear la mesa.');
    }
  }

  onPlaceOnCanvas(id: string): void    { this.facade.placeOnCanvas(id); }
  onRemoveFromCanvas(id: string): void { this.facade.removeFromCanvas(id); }

  onShapeChanged(ev: { id: string; shape: 'rect' | 'circle' }): void {
    this.facade.changeShape(ev.id, ev.shape);
  }

  onSizeChanged(ev: { id: string; preset: SizePreset }): void {
    this.facade.changeSize(ev.id, ev.preset);
  }
}
