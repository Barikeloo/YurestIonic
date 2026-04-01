import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

export interface FamilyRow {
  uuid?: string;
  name: string;
  active: boolean;
}

export interface FamilyFormData {
  name: string;
  active: boolean;
}

@Component({
  selector: 'app-families-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './families-management.component.html',
  styleUrls: ['./families-management.component.scss'],
})
export class FamiliesManagementComponent {
  @Input() families: FamilyRow[] = [];
  @Input() formData: FamilyFormData = { name: '', active: true };
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
