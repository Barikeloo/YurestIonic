import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionPrintersFacade, PrinterFormData, PrinterRow } from '../../../pages/core/gestion/facades/gestion-printers.facade';
import { ZoneRow } from '../../../pages/core/gestion/facades/gestion-zones.facade';
import { ToastService } from '../../../core/services/toast.service';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';
import { ToggleComponent } from '../../../shared/components/toggle/toggle.component';

@Component({
  selector: 'app-printers-management',
  standalone: true,
  imports: [FormsModule, SearchBarComponent, ToggleComponent],
  templateUrl: './printers-management.component.html',
  styleUrls: ['./printers-management.component.scss'],
})
export class PrintersManagementComponent {
  public readonly facade = input.required<GestionPrintersFacade>();
  public readonly zones = input<ZoneRow[]>([]);

  protected readonly toastService = inject(ToastService);

  public readonly printers = computed(() => this.facade().printers());
  public readonly selectedPrinter = computed(() => this.facade().selectedPrinter());
  public readonly selectedIndex = computed(() => this.facade().selectedIndex());
  public readonly formData = computed(() => this.facade().formData());
  public readonly isSaving = computed(() => this.facade().isSaving());
  public readonly isTesting = computed(() => this.facade().isTesting());
  public readonly isLoading = computed(() => this.facade().isLoading());

  public readonly searchTerm = signal('');

  public readonly filteredPrinters = computed<PrinterRow[]>(() => {
    const term = this.searchTerm().trim().toLowerCase();
    if (!term) return this.printers();
    return this.printers().filter((p) => p.name.toLowerCase().includes(term));
  });

  public isSelected(filteredIndex: number): boolean {
    const target = this.filteredPrinters()[filteredIndex];
    if (!target) return false;
    const realIndex = this.printers().indexOf(target);
    return realIndex >= 0 && realIndex === this.selectedIndex();
  }

  public onSelectFiltered(filteredIndex: number): void {
    const target = this.filteredPrinters()[filteredIndex];
    if (!target) return;
    const realIndex = this.printers().indexOf(target);
    if (realIndex >= 0) this.facade().select(realIndex);
  }

  public onSearchChange(value: string): void {
    this.searchTerm.set(value);
  }

  public onCreatePrinter(): void {
    this.facade().startCreate();
  }

  public async onDelete(): Promise<void> {
    const printer = this.selectedPrinter();
    if (!printer) {
      this.toastService.presentError('No hay ninguna impresora seleccionada.');
      return;
    }
    if (!window.confirm(`¿Eliminar impresora "${printer.name}"? Esta acción no se puede deshacer.`)) return;

    const result = await this.facade().deleteSelected();
    if (result.ok) {
      this.toastService.presentSuccess(result.message ?? 'Impresora eliminada.');
    } else {
      this.toastService.presentError(result.error ?? 'No se pudo eliminar la impresora.');
    }
  }

  public async onSubmit(): Promise<void> {
    const result = await this.facade().save();
    if (result.ok) {
      this.toastService.presentSuccess(result.message ?? 'Impresora guardada.');
    } else {
      this.toastService.presentError(result.error ?? 'No se pudo guardar la impresora.');
    }
  }

  public async onTest(): Promise<void> {
    const result = await this.facade().testPrinter();
    if (result.ok) {
      this.toastService.presentSuccess(result.message ?? 'Página de prueba enviada.');
    } else {
      this.toastService.presentError(result.error ?? 'No se pudo conectar con la impresora.');
    }
  }

  public updateForm<K extends keyof PrinterFormData>(key: K, value: PrinterFormData[K]): void {
    this.facade().updateForm(key, value);
  }

  public printerSubtitle(printer: PrinterRow): string {
    const parts: string[] = [`${printer.ip}:${printer.port}`];
    if (printer.isDefault) parts.push('Predeterminada');
    if (!printer.enabled) parts.push('Desactivada');
    return parts.join(' · ');
  }

  public zoneNameFor(zoneUuid: string | null): string {
    if (!zoneUuid) return '';
    return this.zones().find((z) => z.uuid === zoneUuid)?.name ?? '';
  }
}
