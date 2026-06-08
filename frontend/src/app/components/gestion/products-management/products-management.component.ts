
import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionProductsFacade, ProductRow, ProductFormData } from '../../../pages/core/gestion/facades/gestion-products.facade';
import { ProductItem } from '../../../services/product.service';
import { ToastService } from '../../../core/services/toast.service';
import { ToggleComponent } from '../../../shared/components/toggle/toggle.component';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';
import { ProductModifiersModalComponent } from '../product-modifiers-modal/product-modifiers-modal.component';
import { PhotoUploadQrModalComponent, PhotoUploadedEvent } from '../photo-upload-qr-modal/photo-upload-qr-modal.component';

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
  imports: [FormsModule, ToggleComponent, SearchBarComponent, ProductModifiersModalComponent, PhotoUploadQrModalComponent],
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

  public readonly modifiersModalOpen = signal(false);
  public readonly photoQrModalOpen = signal(false);
  public readonly searchTerm = signal('');

  public readonly selectedProduct = computed<ProductRow | null>(() => {
    const index = this.selectedIndex();
    if (index < 0) {
      return null;
    }
    return this.products()[index] ?? null;
  });

  // Búsqueda por nombre del producto y por nombre de su familia, todo lowercase.
  public readonly filteredProducts = computed<ProductRow[]>(() => {
    const term = this.searchTerm().trim().toLowerCase();
    if (!term) return this.products();

    return this.products().filter((p) => {
      const productMatch = p.name.toLowerCase().includes(term);
      const familyMatch = this.getFamilyName(p.family_id).toLowerCase().includes(term);

      return productMatch || familyMatch;
    });
  });

  openModifiers(): void {
    if (!this.selectedProduct()) {
      this.toastService.presentWarning('Selecciona un producto antes de configurar modificadores.');
      return;
    }
    this.modifiersModalOpen.set(true);
  }

  closeModifiers(): void {
    this.modifiersModalOpen.set(false);
  }

  openPhotoQr(): void {
    if (!this.selectedProduct()) {
      this.toastService.presentWarning('Selecciona un producto antes de añadir una foto.');
      return;
    }
    this.photoQrModalOpen.set(true);
  }

  closePhotoQr(): void {
    this.photoQrModalOpen.set(false);
  }

  onPhotoUploaded(event: PhotoUploadedEvent): void {
    this.facade().applyPhoto(event.productId, event.imageSrc);
  }

  onModifiersSaved(updated: ProductItem): void {
    this.facade().applyAllergens(updated.id, updated.allergens);
    this.toastService.presentSuccess('Modificadores guardados.');
  }

  isSelected(index: number): boolean {
    return this.selectedIndex() === index;
  }

  onSelect(index: number): void {
    this.facade().select(index);
  }

  isSelectedFiltered(filteredIndex: number): boolean {
    const target = this.filteredProducts()[filteredIndex];
    if (!target) return false;
    const realIndex = this.products().indexOf(target);

    return realIndex >= 0 && realIndex === this.selectedIndex();
  }

  onSelectFiltered(filteredIndex: number): void {
    const target = this.filteredProducts()[filteredIndex];
    if (!target) return;
    const realIndex = this.products().indexOf(target);
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

