
import { Component, computed, inject, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionProductsFacade, ProductRow, ProductFormData } from '../../../pages/core/gestion/facades/gestion-products.facade';
import { ToastService } from '../../../core/services/toast.service';
import { ToggleComponent } from '../../../shared/components/toggle/toggle.component';

export interface TaxOption {
  uuid?: string;
  name: string;
  percentage: number;
}

export interface FamilyOption {
  uuid?: string;
  name: string;
}

@Component({
  selector: 'app-products-management',
  standalone: true,
  imports: [FormsModule, ToggleComponent],
  templateUrl: './products-management.component.html',
  styleUrls: ['./products-management.component.scss'],
})
export class ProductsManagementComponent {
  public readonly facade = input.required<GestionProductsFacade>();
  public readonly families = input.required<FamilyOption[]>();
  public readonly taxes = input.required<TaxOption[]>();
  protected readonly toastService = inject(ToastService);

  public readonly products = computed(() => this.facade().products());
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
      this.toastService.presentSuccess(result.message || 'Producto eliminado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar el producto.');
    }
  }

  async onSubmit(): Promise<void> {
    const result = await this.facade().save();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Producto guardado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar el producto.');
    }
  }

  updateForm<K extends keyof ProductFormData>(key: K, value: ProductFormData[K]): void {
    this.facade().updateForm(key, value);
  }

  toEuroFromCents(cents: number): string {
    return `${((cents || 0) / 100).toFixed(2).replace('.', ',')}€`;
  }

  euroToCents(value: string | number): number {
    const strValue = typeof value === 'number' ? value.toString() : value;
    const normalized = strValue.replace(',', '.');
    const amount = Number.parseFloat(normalized);
    return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
  }

  getFamilyName(familyId: string): string {
    const family = this.families().find((f) => f.uuid === familyId);
    return family?.name ?? 'Sin familia';
  }

  getTaxPercentage(taxId: string): number {
    const tax = this.taxes().find((t) => t.uuid === taxId);
    return tax?.percentage ?? 0;
  }
}

