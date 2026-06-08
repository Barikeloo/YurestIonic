import { CommonModule } from '@angular/common';
import { Component, computed, inject, input, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionUsersFacade, UserRow, UserFormData } from '../../../pages/core/gestion/facades/gestion-users.facade';
import { ToastService } from '../../../core/services/toast.service';
import { UserRole } from '../../../core/enums/user-role.enum';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';

export interface RoleOption {
  value: UserRole;
  label: string;
}

@Component({
  selector: 'app-users-management',
  standalone: true,
  imports: [CommonModule, FormsModule, SearchBarComponent],
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

  public readonly searchTerm = signal('');

  public readonly filteredUsers = computed<UserRow[]>(() => {
    const term = this.searchTerm().trim().toLowerCase();
    if (!term) return this.users();

    return this.users().filter((u) => {
      const nameMatch = u.name.toLowerCase().includes(term);
      const emailMatch = u.email.toLowerCase().includes(term);
      const roleMatch = this.getRoleLabel(u.role).toLowerCase().includes(term);

      return nameMatch || emailMatch || roleMatch;
    });
  });

  isSelected(index: number): boolean {
    return this.selectedIndex() === index;
  }

  onSelect(index: number): void {
    this.facade().select(index);
  }

  isSelectedFiltered(filteredIndex: number): boolean {
    const target = this.filteredUsers()[filteredIndex];
    if (!target) return false;
    const realIndex = this.users().indexOf(target);

    return realIndex >= 0 && realIndex === this.selectedIndex();
  }

  onSelectFiltered(filteredIndex: number): void {
    const target = this.filteredUsers()[filteredIndex];
    if (!target) return;
    const realIndex = this.users().indexOf(target);
    if (realIndex >= 0) this.facade().select(realIndex);
  }

  onSearchChange(value: string): void {
    this.searchTerm.set(value);
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
      [UserRole.OPERATOR]: 'role-badge-operator',
      [UserRole.SUPERVISOR]: 'role-badge-supervisor',
      [UserRole.ADMIN]: 'role-badge-admin',
    };
    return map[role] ?? '';
  }

  getRoleLabel(role: string): string {
    const map: Record<string, string> = {
      [UserRole.OPERATOR]: 'Operario',
      [UserRole.SUPERVISOR]: 'Supervisor',
      [UserRole.ADMIN]: 'Admin',
    };
    return map[role] ?? role;
  }

  canDelete(): boolean {
    const user = this.users()[this.selectedIndex()];
    if (!user) {
      return true;
    }
    return user.role !== UserRole.ADMIN;
  }
}
