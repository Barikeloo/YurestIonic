import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import {
  CreateMenuPayload,
  MenuDto,
  MenuService,
  UpdateMenuPayload,
} from '../../../../services/menu.service';

export interface MenuRow {
  id: string;
  name: string;
  description: string | null;
  taxId: string;
  price: number;
  active: boolean;
  archived: boolean;
  validityFrom: string | null;
  validityTo: string | null;
  availableDays: number;
  availableFromTime: string | null;
  availableToTime: string | null;
  sections: MenuDto['sections'];
}

export type MenuFilter = 'active' | 'inactive' | 'archived' | 'all';

export interface OperationResult {
  ok: boolean;
  error?: string;
  message?: string;
}

const FILTER_TO_QUERY: Record<MenuFilter, { active?: boolean; archived?: boolean }> = {
  active: { active: true, archived: false },
  inactive: { active: false, archived: false },
  archived: { archived: true },
  all: {},
};

const toRow = (dto: MenuDto): MenuRow => ({
  id: dto.id,
  name: dto.name,
  description: dto.description,
  taxId: dto.tax_id,
  price: dto.price,
  active: dto.active,
  archived: dto.archived,
  validityFrom: dto.validity_from,
  validityTo: dto.validity_to,
  availableDays: dto.available_days,
  availableFromTime: dto.available_from_time,
  availableToTime: dto.available_to_time,
  sections: dto.sections,
});

@Injectable()
export class GestionMenusFacade {
  private readonly menuService = inject(MenuService);

  private readonly _menus = signal<MenuRow[]>([]);
  private readonly _filter = signal<MenuFilter>('all');
  private readonly _search = signal<string>('');
  private readonly _isLoading = signal<boolean>(false);
  private readonly _isSaving = signal<boolean>(false);
  private readonly _editingId = signal<string | null>(null);

  public readonly menus: Signal<MenuRow[]> = this._menus.asReadonly();
  public readonly filter: Signal<MenuFilter> = this._filter.asReadonly();
  public readonly search: Signal<string> = this._search.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();
  public readonly isSaving: Signal<boolean> = this._isSaving.asReadonly();
  public readonly editingId: Signal<string | null> = this._editingId.asReadonly();

  public readonly filteredMenus: Signal<MenuRow[]> = computed(() => {
    const term = this._search().trim().toLowerCase();
    let list = this._menus();

    const filter = this._filter();
    if (filter === 'active') {
      list = list.filter((m) => m.active && !m.archived);
    } else if (filter === 'inactive') {
      list = list.filter((m) => !m.active && !m.archived);
    } else if (filter === 'archived') {
      list = list.filter((m) => m.archived);
    }

    if (term) {
      list = list.filter((m) => m.name.toLowerCase().includes(term));
    }

    return list;
  });

  public readonly editingMenu: Signal<MenuRow | null> = computed(() => {
    const id = this._editingId();
    if (id === null) return null;
    return this._menus().find((m) => m.id === id) ?? null;
  });

  public async load(): Promise<void> {
    this._isLoading.set(true);

    try {

      const response = await firstValueFrom(this.menuService.listMenus());
      const items = Array.isArray(response) ? (response as MenuDto[]) : response.data;
      this._menus.set(items.map(toRow));
    } finally {
      this._isLoading.set(false);
    }
  }

  public clear(): void {
    this._menus.set([]);
    this._editingId.set(null);
    this._search.set('');
    this._filter.set('all');
  }

  public setFilter(filter: MenuFilter): void {
    this._filter.set(filter);
  }

  public setSearch(value: string): void {
    this._search.set(value);
  }

  public openEditor(id: string): void {
    this._editingId.set(id);
  }

  public closeEditor(): void {
    this._editingId.set(null);
  }

  public async create(payload: CreateMenuPayload): Promise<OperationResult> {
    this._isSaving.set(true);

    try {
      const created = await firstValueFrom(this.menuService.createMenu(payload));
      this._menus.update((current) => [...current, toRow(created)]);

      return { ok: true, message: `Menú "${created.name}" creado.` };
    } catch (error) {
      return { ok: false, error: this.toMessage(error, 'No se pudo crear el menú.') };
    } finally {
      this._isSaving.set(false);
    }
  }

  public async update(id: string, payload: UpdateMenuPayload): Promise<OperationResult> {
    this._isSaving.set(true);

    try {
      const updated = await firstValueFrom(this.menuService.updateMenu(id, payload));
      this.replaceMenu(id, toRow(updated));

      return { ok: true, message: `Menú "${updated.name}" actualizado.` };
    } catch (error) {
      return { ok: false, error: this.toMessage(error, 'No se pudo guardar el menú.') };
    } finally {
      this._isSaving.set(false);
    }
  }

  public async toggleActive(id: string, nextActive: boolean): Promise<OperationResult> {
    try {
      const updated = await firstValueFrom(
        nextActive ? this.menuService.activateMenu(id) : this.menuService.deactivateMenu(id),
      );
      this.replaceMenu(id, toRow(updated));

      return { ok: true, message: nextActive ? 'Menú activado.' : 'Menú desactivado.' };
    } catch (error) {
      return { ok: false, error: this.toMessage(error, 'No se pudo cambiar el estado.') };
    }
  }

  public async archive(id: string): Promise<OperationResult> {
    try {
      await firstValueFrom(this.menuService.archiveMenu(id));

      this._menus.update((current) =>
        current.map((m) =>
          m.id === id
            ? { ...m, archived: true, active: false }
            : m,
        ),
      );

      return { ok: true, message: 'Menú archivado.' };
    } catch (error) {
      return { ok: false, error: this.toMessage(error, 'No se pudo archivar el menú.') };
    }
  }

  private replaceMenu(id: string, replacement: MenuRow): void {
    this._menus.update((current) => current.map((m) => (m.id === id ? replacement : m)));
  }

  private toMessage(error: unknown, fallback: string): string {
    return error instanceof Error ? error.message : fallback;
  }
}
