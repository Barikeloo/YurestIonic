import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ZoneItem, ZoneService } from '../../../../services/zone.service';
import { TableItem, TableLayout, TableService } from '../../../../services/table.service';

export interface TableRow {
  uuid?: string;
  name: string;
  layout?: TableLayout | null;
}

export interface ZoneRow {
  uuid?: string;
  name: string;
  tables: TableRow[];
}

export interface ZoneFormData {
  name: string;
}

export interface TableFormData {
  name: string;
}

const EMPTY_ZONE_FORM: ZoneFormData = { name: '' };
const EMPTY_TABLE_FORM: TableFormData = { name: '' };

export interface OperationResult {
  ok: boolean;
  error?: string;
  message?: string;
}

@Injectable()
export class GestionZonesFacade {
  private readonly zoneService = inject(ZoneService);
  private readonly tableService = inject(TableService);

  private readonly _zones = signal<ZoneRow[]>([]);
  private readonly _selectedZoneIndex = signal<number>(-1);
  private readonly _selectedTableIndex = signal<number>(-1);
  private readonly _zoneFormData = signal<ZoneFormData>({ ...EMPTY_ZONE_FORM });
  private readonly _tableFormData = signal<TableFormData>({ ...EMPTY_TABLE_FORM });
  private readonly _isSavingZone = signal<boolean>(false);
  private readonly _isSavingTable = signal<boolean>(false);
  private readonly _isLoading = signal<boolean>(false);

  public readonly zones: Signal<ZoneRow[]> = this._zones.asReadonly();
  public readonly selectedZoneIndex: Signal<number> = this._selectedZoneIndex.asReadonly();
  public readonly selectedTableIndex: Signal<number> = this._selectedTableIndex.asReadonly();
  public readonly zoneFormData: Signal<ZoneFormData> = this._zoneFormData.asReadonly();
  public readonly tableFormData: Signal<TableFormData> = this._tableFormData.asReadonly();
  public readonly isSavingZone: Signal<boolean> = this._isSavingZone.asReadonly();
  public readonly isSavingTable: Signal<boolean> = this._isSavingTable.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();

  public readonly selectedZone: Signal<ZoneRow | null> = computed(() => {
    const index = this._selectedZoneIndex();
    const list = this._zones();
    return index >= 0 && index < list.length ? list[index] : null;
  });

  public readonly selectedTable: Signal<TableRow | null> = computed(() => {
    const zone = this.selectedZone();
    const index = this._selectedTableIndex();
    if (!zone || index < 0 || index >= zone.tables.length) {
      return null;
    }
    return zone.tables[index];
  });

  public async load(): Promise<void> {
    this._isLoading.set(true);

    try {
      const [zonesResponse, tablesResponse] = await Promise.all([
        firstValueFrom(this.zoneService.listZones()),
        firstValueFrom(this.tableService.listTables()),
      ]);

      const zones = Array.isArray(zonesResponse) ? zonesResponse : (zonesResponse as any).items || [];
      const tables = Array.isArray(tablesResponse) ? tablesResponse : (tablesResponse as any).items || [];

      const zoneMap = new Map<string, TableRow[]>();
      tables.forEach((table: TableItem) => {
        if (!zoneMap.has(table.zone_id)) {
          zoneMap.set(table.zone_id, []);
        }
        zoneMap.get(table.zone_id)!.push({
          uuid: table.id,
          name: table.name,
          layout: table.layout ?? null,
        });
      });

      const rows: ZoneRow[] = zones.map((zone: ZoneItem) => ({
        uuid: zone.id,
        name: zone.name,
        tables: zoneMap.get(zone.id) || [],
      }));

      this._zones.set(rows);
      this.syncZoneFormFromIndex();
      this.syncTableFormFromIndex();
    } finally {
      this._isLoading.set(false);
    }
  }

  public clear(): void {
    this._zones.set([]);
    this._selectedZoneIndex.set(-1);
    this._selectedTableIndex.set(-1);
    this._zoneFormData.set({ ...EMPTY_ZONE_FORM });
    this._tableFormData.set({ ...EMPTY_TABLE_FORM });
  }

  public selectZone(index: number): void {
    this._selectedZoneIndex.set(index);
    this._selectedTableIndex.set(-1);
    this.syncZoneFormFromIndex();
    this.syncTableFormFromIndex();
  }

  public selectTable(index: number): void {
    this._selectedTableIndex.set(index);
    this.syncTableFormFromIndex();
  }

  public startCreateZone(): void {
    this._selectedZoneIndex.set(-1);
    this._selectedTableIndex.set(-1);
    this._zoneFormData.set({ ...EMPTY_ZONE_FORM });
    this._tableFormData.set({ ...EMPTY_TABLE_FORM });
  }

  public startCreateTable(): void {
    this._selectedTableIndex.set(-1);
    this._tableFormData.set({ ...EMPTY_TABLE_FORM });
  }

  public updateZoneForm<K extends keyof ZoneFormData>(key: K, value: ZoneFormData[K]): void {
    this._zoneFormData.update((current) => ({ ...current, [key]: value }));
  }

  public setZoneForm(data: ZoneFormData): void {
    this._zoneFormData.set({ ...data });
  }

  public updateTableForm<K extends keyof TableFormData>(key: K, value: TableFormData[K]): void {
    this._tableFormData.update((current) => ({ ...current, [key]: value }));
  }

  public setTableForm(data: TableFormData): void {
    this._tableFormData.set({ ...data });
  }

  public async deleteSelectedZone(): Promise<OperationResult> {
    const zone = this.selectedZone();

    if (!zone?.uuid) {
      return { ok: false, error: 'No se puede eliminar: zona sin identificador.' };
    }

    try {
      await firstValueFrom(this.zoneService.deleteZone(zone.uuid));
      const newList = this._zones().filter((candidate) => candidate.uuid !== zone.uuid);
      this._zones.set(newList);
      this._selectedZoneIndex.set(newList.length > 0 ? 0 : -1);
      this._selectedTableIndex.set(-1);
      this.syncZoneFormFromIndex();
      this.syncTableFormFromIndex();

      return { ok: true, message: `Zona "${zone.name}" eliminada.` };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la zona.' };
    }
  }

  public async deleteSelectedTable(): Promise<OperationResult> {
    const table = this.selectedTable();
    const zone = this.selectedZone();

    if (!table?.uuid) {
      return { ok: false, error: 'No se puede eliminar: mesa sin identificador.' };
    }

    if (!zone?.uuid) {
      return { ok: false, error: 'No hay zona seleccionada.' };
    }

    try {
      await firstValueFrom(this.tableService.deleteTable(table.uuid));
      this.replaceZoneTables(zone.uuid, zone.tables.filter((t) => t.uuid !== table.uuid));
      this._selectedTableIndex.set(-1);
      this.syncTableFormFromIndex();

      return { ok: true, message: 'Mesa eliminada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la mesa.' };
    }
  }

  public async deleteZone(uuid: string): Promise<OperationResult> {
    try {
      await firstValueFrom(this.zoneService.deleteZone(uuid));
      const newList = this._zones().filter((candidate) => candidate.uuid !== uuid);
      this._zones.set(newList);
      const currentIndex = this._selectedZoneIndex();
      this._selectedZoneIndex.set(newList.length > 0 ? Math.min(currentIndex, newList.length - 1) : -1);
      this._selectedTableIndex.set(-1);
      this.syncZoneFormFromIndex();
      this.syncTableFormFromIndex();

      return { ok: true, message: 'Zona eliminada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la zona.' };
    }
  }

  public async saveZone(): Promise<OperationResult> {
    const form = this._zoneFormData();
    const name = form.name.trim();

    if (!name) {
      return { ok: false, error: 'Indica el nombre de la zona.' };
    }

    const selected = this.selectedZone();

    this._isSavingZone.set(true);

    try {
      if (selected?.uuid) {
        const updated = await firstValueFrom(this.zoneService.updateZone(selected.uuid, { name }));

        this.replaceZone(selected.uuid, {
          uuid: updated.id,
          name: updated.name,
          tables: selected.tables,
        });

        this.syncZoneFormFromIndex();

        return { ok: true, message: 'Zona actualizada.' };
      }

      const created = await firstValueFrom(this.zoneService.createZone({ name }));

      const newRow: ZoneRow = {
        uuid: created.id,
        name: created.name,
        tables: [],
      };

      const newList = [...this._zones(), newRow];
      this._zones.set(newList);
      this._selectedZoneIndex.set(newList.length - 1);
      this._selectedTableIndex.set(-1);
      this.syncZoneFormFromIndex();
      this.syncTableFormFromIndex();

      return { ok: true, message: 'Zona creada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo guardar la zona.' };
    } finally {
      this._isSavingZone.set(false);
    }
  }

  public async saveTable(): Promise<OperationResult> {
    const form = this._tableFormData();
    const name = form.name.trim();
    const zone = this.selectedZone();

    if (!name) {
      return { ok: false, error: 'Indica el nombre de la mesa.' };
    }

    if (!zone?.uuid) {
      return { ok: false, error: 'No hay zona seleccionada.' };
    }

    const selected = this.selectedTable();

    this._isSavingTable.set(true);

    try {
      if (selected?.uuid) {
        const updated = await firstValueFrom(this.tableService.updateTable(selected.uuid, { zone_id: zone.uuid, name }));

        this.replaceZoneTables(
          zone.uuid,
          zone.tables.map((t) => (t.uuid === selected.uuid ? { uuid: updated.id, name: updated.name } : t)),
        );

        this.syncTableFormFromIndex();

        return { ok: true, message: 'Mesa actualizada.' };
      }

      const created = await firstValueFrom(this.tableService.createTable({ zone_id: zone.uuid, name }));

      const newTable: TableRow = {
        uuid: created.id,
        name: created.name,
      };

      const newTables = [...zone.tables, newTable];
      this.replaceZoneTables(zone.uuid, newTables);
      this._selectedTableIndex.set(newTables.length - 1);
      this.syncTableFormFromIndex();

      return { ok: true, message: 'Mesa creada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo guardar la mesa.' };
    } finally {
      this._isSavingTable.set(false);
    }
  }

  private replaceZone(uuid: string, replacement: ZoneRow): void {
    this._zones.update((current) => current.map((zone) => (zone.uuid === uuid ? replacement : zone)));
  }

  private replaceZoneTables(zoneUuid: string, tables: TableRow[]): void {
    this._zones.update((current) =>
      current.map((zone) => (zone.uuid === zoneUuid ? { ...zone, tables } : zone)),
    );
  }

  private syncZoneFormFromIndex(): void {
    const zone = this.selectedZone();

    if (zone) {
      this._zoneFormData.set({ name: zone.name });
    } else {
      this._zoneFormData.set({ ...EMPTY_ZONE_FORM });
    }
  }

  private syncTableFormFromIndex(): void {
    const table = this.selectedTable();

    if (table) {
      this._tableFormData.set({ name: table.name });
    } else {
      this._tableFormData.set({ ...EMPTY_TABLE_FORM });
    }
  }
}
