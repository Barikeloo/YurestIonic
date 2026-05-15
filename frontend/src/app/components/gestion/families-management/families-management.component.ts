
import { Component, computed, inject, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { GestionFamiliesFacade, FamilyRow, FamilyFormData } from '../../../pages/core/gestion/facades/gestion-families.facade';
import { ToastService } from '../../../core/services/toast.service';
import { ToggleComponent } from '../../../shared/components/toggle/toggle.component';

@Component({
  selector: 'app-families-management',
  standalone: true,
  imports: [FormsModule, ToggleComponent],
  templateUrl: './families-management.component.html',
  styleUrls: ['./families-management.component.scss'],
})
export class FamiliesManagementComponent {
  public readonly facade = input.required<GestionFamiliesFacade>();
  protected readonly toastService = inject(ToastService);

  public readonly families = computed(() => this.facade().families());
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
      this.toastService.presentSuccess(result.message || 'Familia eliminada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar la familia.');
    }
  }

  async onSubmit(): Promise<void> {
    const result = await this.facade().save();
    if (result.ok) {
      this.toastService.presentSuccess(result.message || 'Familia guardada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar la familia.');
    }
  }

  updateForm<K extends keyof FamilyFormData>(key: K, value: FamilyFormData[K]): void {
    this.facade().updateForm(key, value);
  }
}
