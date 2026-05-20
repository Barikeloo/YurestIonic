
import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionTaxesFacade, TaxRow, TaxFormData } from '../../../pages/core/gestion/facades/gestion-taxes.facade';
import { ToastService } from '../../../core/services/toast.service';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';

@Component({
  selector: 'app-taxes-management',
  standalone: true,
  imports: [FormsModule, SearchBarComponent],
  templateUrl: './taxes-management.component.html',
  styleUrls: ['./taxes-management.component.scss'],
})
export class TaxesManagementComponent {
  public readonly facade = input.required<GestionTaxesFacade>();
  protected readonly toastService = inject(ToastService);

  public readonly taxes = computed(() => this.facade().taxes());
  public readonly formData = computed(() => this.facade().formData());
  public readonly selectedIndex = computed(() => this.facade().selectedIndex());
  public readonly isSaving = computed(() => this.facade().isSaving());

  public readonly searchTerm = signal('');

  // Búsqueda por nombre o por porcentaje (texto), p.ej. "10" encuentra el 10%.
  public readonly filteredTaxes = computed<TaxRow[]>(() => {
    const term = this.searchTerm().trim().toLowerCase();
    if (!term) return this.taxes();

    return this.taxes().filter((t) =>
      t.name.toLowerCase().includes(term) || String(t.percentage).includes(term)
    );
  });

  isSelected(index: number): boolean {
    return this.selectedIndex() === index;
  }

  onSelect(index: number): void {
    this.facade().select(index);
  }

  isSelectedFiltered(filteredIndex: number): boolean {
    const target = this.filteredTaxes()[filteredIndex];
    if (!target) return false;
    const realIndex = this.taxes().indexOf(target);

    return realIndex >= 0 && realIndex === this.selectedIndex();
  }

  onSelectFiltered(filteredIndex: number): void {
    const target = this.filteredTaxes()[filteredIndex];
    if (!target) return;
    const realIndex = this.taxes().indexOf(target);
    if (realIndex >= 0) this.facade().select(realIndex);
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
  }

  onCreate(): void {
    this.facade().startCreate();
  }

  async onDelete(): Promise<void> {
    const result = await this.facade().deleteSelected();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Impuesto eliminado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar el impuesto.');
    }
  }

  async onSubmit(): Promise<void> {
    const result = await this.facade().save();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Impuesto guardado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar el impuesto.');
    }
  }

  updateForm<K extends keyof TaxFormData>(key: K, value: TaxFormData[K]): void {
    this.facade().updateForm(key, value);
  }
}
