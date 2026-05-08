import { CommonModule } from '@angular/common';
import { Component, computed, inject, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionUsersFacade, UserRow, UserFormData } from '../../../pages/core/gestion/facades/gestion-users.facade';
import { ToastService } from '../../../core/services/toast.service';

export type UserRole = 'operator' | 'supervisor' | 'admin';

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
  public readonly facade = input.required<GestionUsersFacade>();
  public readonly restaurantUuid = input.required<string>();
  public readonly roleOptions = input.required<RoleOption[]>();
  protected readonly toastService = inject(ToastService);

  public readonly users = computed(() => this.facade().users());
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
    const result = await this.facade().deleteSelected(this.restaurantUuid());
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Usuario eliminado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar el usuario.');
    }
  }

  async onSubmit(): Promise<void> {
    const result = await this.facade().save(this.restaurantUuid());
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Usuario guardado.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar el usuario.');
    }
  }

  updateForm<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
    this.facade().updateForm(key, value);
  }

  getRoleBadgeClass(role: string): string {
    const map: Record<string, string> = {
      operator: 'role-badge-operator',
      supervisor: 'role-badge-supervisor',
      admin: 'role-badge-admin',
    };
    return map[role] ?? '';
  }

  getRoleLabel(role: string): string {
    const map: Record<string, string> = {
      operator: 'Operario',
      supervisor: 'Supervisor',
      admin: 'Admin',
    };
    return map[role] ?? role;
  }

  canDelete(): boolean {
    const user = this.users()[this.selectedIndex()];
    if (!user) {
      return true;
    }
    return user.role !== 'admin';
  }
}
