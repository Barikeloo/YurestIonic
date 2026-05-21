import { Component, computed, inject, input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { GestionMenusFacade, MenuFilter, MenuRow } from '../../../pages/core/gestion/facades/gestion-menus.facade';
import { ToastService } from '../../../core/services/toast.service';
import { SearchBarComponent } from '../../../shared/components/search-bar/search-bar.component';
import { TaxOption, FamilyOption } from '../products-management/products-management.component';
import { MenuProductOption } from '../../../services/menu.service';

const ISO_DAY_LABELS: Array<{ bit: number; short: string }> = [
  { bit: 1 << 0, short: 'L' },
  { bit: 1 << 1, short: 'M' },
  { bit: 1 << 2, short: 'X' },
  { bit: 1 << 3, short: 'J' },
  { bit: 1 << 4, short: 'V' },
  { bit: 1 << 5, short: 'S' },
  { bit: 1 << 6, short: 'D' },
];

@Component({
  selector: 'app-menus-management',
  standalone: true,
  imports: [FormsModule, SearchBarComponent],
  templateUrl: './menus-management.component.html',
  styleUrls: ['./menus-management.component.scss'],
})
export class MenusManagementComponent {
  public readonly facade = input.required<GestionMenusFacade>();
  public readonly products = input.required<MenuProductOption[]>();
  public readonly taxes = input.required<TaxOption[]>();
  public readonly families = input.required<FamilyOption[]>();

  protected readonly toastService = inject(ToastService);
  private readonly router = inject(Router);

  public readonly filters: Array<{ value: MenuFilter; label: string }> = [
    { value: 'all', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
    { value: 'archived', label: 'Archivados' },
  ];

  public readonly menus = computed(() => this.facade().filteredMenus());
  public readonly totalCount = computed(() => this.facade().menus().length);

  public readonly isAvailableNow = computed<Map<string, boolean>>(() => {
    const map = new Map<string, boolean>();
    const now = new Date();
    for (const menu of this.facade().menus()) {
      map.set(menu.id, this.computeAvailableNow(menu, now));
    }
    return map;
  });

  public onFilterChange(filter: MenuFilter): void {
    this.facade().setFilter(filter);
  }

  public onSearchChange(value: string): void {
    this.facade().setSearch(value);
  }

  public onCreate(): void {
    this.router.navigateByUrl('/app/gestion/menus/nuevo');
  }

  public onEdit(menu: MenuRow): void {
    if (menu.archived) {
      this.toastService.presentWarning('Los menús archivados no pueden editarse.');
      return;
    }
    this.router.navigateByUrl(`/app/gestion/menus/${menu.id}/editar`);
  }

  public async onToggleActive(menu: MenuRow, event: Event): Promise<void> {
    event.stopPropagation();
    if (menu.archived) return;

    const result = await this.facade().toggleActive(menu.id, !menu.active);
    if (result.ok) {
      this.toastService.presentSuccess(result.message ?? 'Estado actualizado.');
    } else {
      this.toastService.presentError(result.error ?? 'No se pudo cambiar el estado.');
    }
  }

  public async onArchive(menu: MenuRow, event: Event): Promise<void> {
    event.stopPropagation();
    if (menu.archived) return;

    const confirmed = window.confirm(`¿Archivar el menú "${menu.name}"?`);
    if (!confirmed) return;

    const result = await this.facade().archive(menu.id);
    if (result.ok) {
      this.toastService.presentSuccess(result.message ?? 'Menú archivado.');
    } else {
      this.toastService.presentError(result.error ?? 'No se pudo archivar.');
    }
  }

  public toEuro(cents: number): string {
    return `${((cents || 0) / 100).toFixed(2).replace('.', ',')}€`;
  }

  public daysShort(bitmask: number): string {
    if (bitmask === 0) return '—';
    if (bitmask === 0b1111111) return 'Todos los días';
    return ISO_DAY_LABELS.filter((d) => (bitmask & d.bit) !== 0).map((d) => d.short).join(' ');
  }

  public formatTimeRange(from: string | null, to: string | null): string {
    if (!from || !to) return 'Todo el día';
    return `${from.slice(0, 5)} – ${to.slice(0, 5)}`;
  }

  public formatValidity(menu: MenuRow): string {
    if (!menu.validityFrom && !menu.validityTo) return 'Siempre vigente';
    if (menu.validityFrom && menu.validityTo) return `${menu.validityFrom} → ${menu.validityTo}`;
    if (menu.validityFrom) return `Desde ${menu.validityFrom}`;
    return `Hasta ${menu.validityTo}`;
  }

  public sectionsSummary(menu: MenuRow): string {
    const sections = menu.sections.length;
    const items = menu.sections.reduce((acc, s) => acc + s.items.length, 0);
    return `${sections} ${sections === 1 ? 'sección' : 'secciones'} · ${items} items`;
  }

  private computeAvailableNow(menu: MenuRow, now: Date): boolean {
    if (!menu.active || menu.archived) return false;

    if (menu.validityFrom) {
      const from = new Date(menu.validityFrom);
      if (now < from) return false;
    }
    if (menu.validityTo) {
      const to = new Date(menu.validityTo);
      to.setHours(23, 59, 59, 999);
      if (now > to) return false;
    }

    const isoDay = ((now.getDay() + 6) % 7) + 1; // 1=Mon..7=Sun
    const bit = 1 << (isoDay - 1);
    if ((menu.availableDays & bit) === 0) return false;

    if (menu.availableFromTime && menu.availableToTime) {
      const hms = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
      const from = menu.availableFromTime.length === 5 ? `${menu.availableFromTime}:00` : menu.availableFromTime;
      const to = menu.availableToTime.length === 5 ? `${menu.availableToTime}:00` : menu.availableToTime;
      if (hms < from || hms >= to) return false;
    }

    return true;
  }
}
