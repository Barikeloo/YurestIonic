import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { FamilyItem, FamilyService } from '../../../../services/family.service';

export interface FamilyRow {
  uuid?: string;
  name: string;
  color: string | null;
  icon: string | null;
  active: boolean;
}

export interface FamilyFormData {
  name: string;
  color: string;
  icon: string;
  active: boolean;
}

const EMPTY_FORM: FamilyFormData = { name: '', color: '', icon: '', active: true };

export interface OperationResult {
  ok: boolean;
  error?: string;
  message?: string;
}

@Injectable()
export class GestionFamiliesFacade {
  private readonly familyService = inject(FamilyService);

  private readonly _families = signal<FamilyRow[]>([]);
  private readonly _selectedIndex = signal<number>(-1);
  private readonly _formData = signal<FamilyFormData>({ ...EMPTY_FORM });
  private readonly _isSaving = signal<boolean>(false);
  private readonly _isLoading = signal<boolean>(false);

  public readonly families: Signal<FamilyRow[]> = this._families.asReadonly();
  public readonly selectedIndex: Signal<number> = this._selectedIndex.asReadonly();
  public readonly formData: Signal<FamilyFormData> = this._formData.asReadonly();
  public readonly isSaving: Signal<boolean> = this._isSaving.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();

  public readonly selectedFamily: Signal<FamilyRow | null> = computed(() => {
    const index = this._selectedIndex();
    const list = this._families();

    return index >= 0 && index < list.length ? list[index] : null;
  });

  public async load(): Promise<void> {
    this._isLoading.set(true);

    try {
      const familiesResponse = await firstValueFrom(this.familyService.listFamilies());
      const families = Array.isArray(familiesResponse) ? familiesResponse : (familiesResponse as any).items || [];
      const rows: FamilyRow[] = families.map((family: FamilyItem) => ({
        uuid: family.id,
        name: family.name,
        color: family.color,
        icon: family.icon,
        active: family.active,
      }));

      this._families.set(rows);
      this.syncFormFromIndex();
    } finally {
      this._isLoading.set(false);
    }
  }

  public clear(): void {
    this._families.set([]);
    this._selectedIndex.set(-1);
    this._formData.set({ ...EMPTY_FORM });
  }

  public select(index: number): void {
    this._selectedIndex.set(index);
    this.syncFormFromIndex();
  }

  public startCreate(): void {
    this._selectedIndex.set(-1);
    this._formData.set({ ...EMPTY_FORM });
  }

  public updateForm<K extends keyof FamilyFormData>(key: K, value: FamilyFormData[K]): void {
    this._formData.update((current) => ({ ...current, [key]: value }));
  }

  public setForm(data: FamilyFormData): void {
    this._formData.set({ ...data });
  }

  public async deleteSelected(): Promise<OperationResult> {
    const family = this.selectedFamily();

    if (!family?.uuid) {
      return { ok: false, error: 'No se puede eliminar: familia sin identificador.' };
    }

    try {
      await firstValueFrom(this.familyService.deleteFamily(family.uuid));
      const newList = this._families().filter((candidate) => candidate.uuid !== family.uuid);
      this._families.set(newList);
      this._selectedIndex.set(newList.length > 0 ? 0 : -1);
      this.syncFormFromIndex();

      return { ok: true, message: `Familia "${family.name}" eliminada.` };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la familia.' };
    }
  }

  public async delete(uuid: string): Promise<OperationResult> {
    try {
      await firstValueFrom(this.familyService.deleteFamily(uuid));
      const newList = this._families().filter((candidate) => candidate.uuid !== uuid);
      this._families.set(newList);
      const currentIndex = this._selectedIndex();
      this._selectedIndex.set(newList.length > 0 ? Math.min(currentIndex, newList.length - 1) : -1);
      this.syncFormFromIndex();

      return { ok: true, message: 'Familia eliminada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la familia.' };
    }
  }

  public async save(): Promise<OperationResult> {
    const form = this._formData();
    const name = form.name.trim();

    if (!name) {
      return { ok: false, error: 'Indica el nombre de la familia.' };
    }

    const color = form.color.trim() || null;
    const icon = form.icon.trim() || null;
    const desiredActive = form.active;
    const selected = this.selectedFamily();

    this._isSaving.set(true);

    try {
      if (selected?.uuid) {
        const updated = await firstValueFrom(this.familyService.updateFamily(selected.uuid, { name, color, icon }));

        const finalFamily = await firstValueFrom(
          desiredActive
            ? this.familyService.activateFamily(updated.id)
            : this.familyService.deactivateFamily(updated.id),
        );

        this.replaceFamily(selected.uuid, {
          uuid: finalFamily.id,
          name: finalFamily.name,
          color: finalFamily.color,
          icon: finalFamily.icon,
          active: finalFamily.active,
        });

        this.syncFormFromIndex();

        return { ok: true, message: 'Familia actualizada.' };
      }

      const created = await firstValueFrom(this.familyService.createFamily({ name, color, icon }));
      const finalFamily = desiredActive
        ? created
        : await firstValueFrom(this.familyService.deactivateFamily(created.id));

      const newRow: FamilyRow = {
        uuid: finalFamily.id,
        name: finalFamily.name,
        color: finalFamily.color,
        icon: finalFamily.icon,
        active: finalFamily.active,
      };

      const newList = [...this._families(), newRow];
      this._families.set(newList);
      this._selectedIndex.set(newList.length - 1);
      this.syncFormFromIndex();

      return { ok: true, message: 'Familia creada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo guardar la familia.' };
    } finally {
      this._isSaving.set(false);
    }
  }

  private replaceFamily(uuid: string, replacement: FamilyRow): void {
    this._families.update((current) =>
      current.map((family) => (family.uuid === uuid ? replacement : family)),
    );
  }

  private syncFormFromIndex(): void {
    const family = this.selectedFamily();

    if (family) {
      this._formData.set({
        name: family.name,
        color: family.color ?? '',
        icon: family.icon ?? '',
        active: family.active,
      });
    } else {
      this._formData.set({ ...EMPTY_FORM });
    }
  }
}
