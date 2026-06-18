import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { GestionZonesFacade, ZoneRow, ZoneFormData, TableFormData } from '../../../pages/core/gestion/facades/gestion-zones.facade';
import { ToastService } from '../../../core/services/toast.service';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';

@Component({
  selector: 'app-zones-management',
  standalone: true,
  imports: [FormsModule, SearchBarComponent],
  templateUrl: './zones-management.component.html',
  styleUrls: ['./zones-management.component.scss'],
})
export class ZonesManagementComponent {
  public readonly facade = input.required<GestionZonesFacade>();
  protected readonly toastService = inject(ToastService);
  private  readonly router        = inject(Router);

  public readonly zones              = computed(() => this.facade().zones());
  public readonly selectedZone       = computed(() => this.facade().selectedZone());
  public readonly selectedZoneIndex  = computed(() => this.facade().selectedZoneIndex());
  public readonly selectedTableIndex = computed(() => this.facade().selectedTableIndex());
  public readonly zoneFormData       = computed(() => this.facade().zoneFormData());
  public readonly tableFormData      = computed(() => this.facade().tableFormData());
  public readonly isSavingZone       = computed(() => this.facade().isSavingZone());
  public readonly isSavingTable      = computed(() => this.facade().isSavingTable());

  public readonly searchTerm = signal('');

  public readonly filteredZones = computed<ZoneRow[]>(() => {
    const term = this.searchTerm().trim().toLowerCase();
    if (!term) return this.zones();
    return this.zones().filter((z) => z.name.toLowerCase().includes(term));
  });

  isZoneSelected(index: number): boolean {
    return this.selectedZoneIndex() === index;
  }

  isSelectedFiltered(filteredIndex: number): boolean {
    const target = this.filteredZones()[filteredIndex];
    if (!target) return false;
    const realIndex = this.zones().indexOf(target);
    return realIndex >= 0 && realIndex === this.selectedZoneIndex();
  }

  onSelectFiltered(filteredIndex: number): void {
    const target = this.filteredZones()[filteredIndex];
    if (!target) return;
    const realIndex = this.zones().indexOf(target);
    if (realIndex >= 0) this.facade().selectZone(realIndex);
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
  }

  isTableSelected(index: number): boolean {
    return this.selectedTableIndex() === index;
  }

  onCreateZone(): void {
    this.facade().startCreateZone();
  }

  async onDeleteZone(): Promise<void> {
    const result = await this.facade().deleteSelectedZone();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Zona eliminada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar la zona.');
    }
  }

  async onSubmitZone(): Promise<void> {
    const result = await this.facade().saveZone();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Zona guardada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar la zona.');
    }
  }

  onSelectTable(index: number): void {
    this.facade().selectTable(index);
  }

  onCreateTable(): void {
    this.facade().startCreateTable();
  }

  async onDeleteTable(): Promise<void> {
    const result = await this.facade().deleteSelectedTable();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Mesa eliminada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar la mesa.');
    }
  }

  async onSubmitTable(): Promise<void> {
    const result = await this.facade().saveTable();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Mesa guardada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar la mesa.');
    }
  }

  updateZoneForm<K extends keyof ZoneFormData>(key: K, value: ZoneFormData[K]): void {
    this.facade().updateZoneForm(key, value);
  }

  updateTableForm<K extends keyof TableFormData>(key: K, value: TableFormData[K]): void {
    this.facade().updateTableForm(key, value);
  }

  openFloorEditor(): void {
    const uuid = this.selectedZone()?.uuid;
    if (uuid) void this.router.navigate(['/app/gestion/zones', uuid, 'floor']);
  }
}
