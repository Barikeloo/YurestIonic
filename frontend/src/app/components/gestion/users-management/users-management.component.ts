import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

export type UserRole = 'operator' | 'supervisor' | 'admin';

export interface UserRow {
  uuid?: string;
  name: string;
  role: UserRole;
  email: string;
  pin?: string;
  password?: string;
}

export interface UserFormData {
  name: string;
  email: string;
  role: UserRole;
  pin: string;
  password: string;
}

export interface RoleOption {
  value: UserRole;
  label: string;
}

@Component({
  selector: 'app-users-management',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './users-management.component.html',
  styleUrls: ['./users-management.component.scss'],
})
export class UsersManagementComponent {
  @Input() users: UserRow[] = [];
  @Input() formData: UserFormData = {
    name: '',
    email: '',
    role: 'operator',
    pin: '',
    password: '',
  };
  @Input() selectedIndex: number = 0;
  @Input() isSaving: boolean = false;
  @Input() roleOptions: RoleOption[] = [];
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

  getRoleBadgeClass(role: UserRole): string {
    const map: Record<UserRole, string> = {
      operator: 'role-badge-operator',
      supervisor: 'role-badge-supervisor',
      admin: 'role-badge-admin',
    };
    return map[role] ?? '';
  }

  getRoleLabel(role: UserRole): string {
    const map: Record<UserRole, string> = {
      operator: 'Operario',
      supervisor: 'Supervisor',
      admin: 'Admin',
    };
    return map[role] ?? role;
  }

  canDelete(): boolean {
    const user = this.users[this.selectedIndex];
    if (!user) {
      return true;
    }
    return user.role !== 'admin';
  }
}
