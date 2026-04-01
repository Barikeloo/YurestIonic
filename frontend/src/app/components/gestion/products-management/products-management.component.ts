import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

export interface ProductRow {
  uuid?: string;
  family_id: string;
  tax_id: string;
  name: string;
  price: number;
  stock: number;
  active: boolean;
}

export interface ProductFormData {
  name: string;
  family_id: string;
  tax_id: string;
  price: string;
  stock: number;
  active: boolean;
}

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
  imports: [CommonModule, FormsModule],
  templateUrl: './products-management.component.html',
  styleUrls: ['./products-management.component.scss'],
})
export class ProductsManagementComponent {
  @Input() products: ProductRow[] = [];
  @Input() families: FamilyOption[] = [];
  @Input() taxes: TaxOption[] = [];
  @Input() formData: ProductFormData = {
    name: '',
    family_id: '',
    tax_id: '',
    price: '',
    stock: 0,
    active: true,
  };
  @Input() selectedIndex: number = 0;
  @Input() isSaving: boolean = false;
  @Output() selectItem = new EventEmitter<number>();
  @Output() createNew = new EventEmitter<void>();
  @Output() deleteSelected = new EventEmitter<void>();
  @Output() saveChanges = new EventEmitter<void>();

  isSelected(index: number): boolean {
    return this.selectedIndex === index;
  }

  onSelect(index: number): void {
    this.selectItem.emit(index);
  }

  onCreate(): void {
    this.createNew.emit();
  }

  onDelete(): void {
    this.deleteSelected.emit();
  }

  onSubmit(): void {
    this.saveChanges.emit();
  }

  toEuroFromCents(cents: number): string {
    return `${((cents || 0) / 100).toFixed(2).replace('.', ',')}€`;
  }

  getFamilyName(familyId: string): string {
    const family = this.families.find(f => f.uuid === familyId);
    return family?.name ?? 'Sin familia';
  }

  getTaxPercentage(taxId: string): number {
    const tax = this.taxes.find(t => t.uuid === taxId);
    return tax?.percentage ?? 0;
  }
}
