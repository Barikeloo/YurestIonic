import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

export interface TaxRow {
  uuid?: string;
  name: string;
  percentage: number;
}

export interface TaxFormData {
  name: string;
  percentage: number;
}

@Component({
  selector: 'app-taxes-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './taxes-management.component.html',
  styleUrls: ['./taxes-management.component.scss'],
})
export class TaxesManagementComponent {
  @Input() taxes: TaxRow[] = [];
  @Input() formData: TaxFormData = { name: '', percentage: 10 };
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
}
