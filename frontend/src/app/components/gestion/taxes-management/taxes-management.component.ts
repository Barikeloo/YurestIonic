
import { Component, computed, inject, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionTaxesFacade, TaxRow, TaxFormData } from '../../../pages/core/gestion/facades/gestion-taxes.facade';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-taxes-management',
  standalone: true,
  imports: [FormsModule],
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

  isSelected(index: number): boolean {
    return this.selectedIndex() === index;
  }

  onSelect(index: number): void {
    this.facade().select(index);
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
