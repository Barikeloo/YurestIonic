import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { PrinterConfigItem, PrinterConfigService, UpsertPrinterPayload } from '../../../../services/printer-config.service';

export interface PrinterRow {
  uuid?: string;
  name: string;
  ip: string;
  port: number;
  paperWidth: number;
  enabled: boolean;
  isDefault: boolean;
  zoneUuid: string | null;
}

export interface PrinterFormData {
  name: string;
  ip: string;
  port: number;
  paperWidth: number;
  enabled: boolean;
  isDefault: boolean;
  zoneUuid: string | null;
}

export interface OperationResult {
  ok: boolean;
  error?: string;
  message?: string;
}

const EMPTY_FORM: PrinterFormData = {
  name: '',
  ip: '',
  port: 9100,
  paperWidth: 80,
  enabled: true,
  isDefault: false,
  zoneUuid: null,
};

@Injectable()
export class GestionPrintersFacade {
  private readonly service = inject(PrinterConfigService);

  private readonly _printers = signal<PrinterRow[]>([]);
  private readonly _selectedIndex = signal<number>(-1);
  private readonly _formData = signal<PrinterFormData>({ ...EMPTY_FORM });
  private readonly _isSaving = signal<boolean>(false);
  private readonly _isTesting = signal<boolean>(false);
  private readonly _isLoading = signal<boolean>(false);

  public readonly printers: Signal<PrinterRow[]> = this._printers.asReadonly();
  public readonly selectedIndex: Signal<number> = this._selectedIndex.asReadonly();
  public readonly formData: Signal<PrinterFormData> = this._formData.asReadonly();
  public readonly isSaving: Signal<boolean> = this._isSaving.asReadonly();
  public readonly isTesting: Signal<boolean> = this._isTesting.asReadonly();
  public readonly isLoading: Signal<boolean> = this._isLoading.asReadonly();

  public readonly selectedPrinter: Signal<PrinterRow | null> = computed(() => {
    const index = this._selectedIndex();
    const list = this._printers();
    return index >= 0 && index < list.length ? list[index] : null;
  });

  public async load(): Promise<void> {
    this._isLoading.set(true);
    try {
      const items = await firstValueFrom(this.service.list());
      this._printers.set(items.map(this.toRow));
      this.syncFormFromIndex();
    } finally {
      this._isLoading.set(false);
    }
  }

  public clear(): void {
    this._printers.set([]);
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

  public updateForm<K extends keyof PrinterFormData>(key: K, value: PrinterFormData[K]): void {
    this._formData.update((current) => ({ ...current, [key]: value }));
  }

  public async save(): Promise<OperationResult> {
    const form = this._formData();

    if (!form.name.trim()) return { ok: false, error: 'Indica el nombre de la impresora.' };
    if (!form.ip.trim()) return { ok: false, error: 'Indica la dirección IP.' };

    const payload: UpsertPrinterPayload = {
      name: form.name.trim(),
      ip: form.ip.trim(),
      port: form.port,
      paper_width: form.paperWidth,
      enabled: form.enabled,
      is_default: form.isDefault,
      zone_uuid: form.zoneUuid || null,
    };

    this._isSaving.set(true);
    try {
      const selected = this.selectedPrinter();

      if (selected?.uuid) {
        const updated = await firstValueFrom(this.service.update(selected.uuid, payload));
        this._printers.update((list) =>
          list.map((p) => (p.uuid === selected.uuid ? this.toRow(updated) : p)),
        );
        this.syncFormFromIndex();
        return { ok: true, message: 'Impresora actualizada.' };
      }

      const created = await firstValueFrom(this.service.create(payload));
      const newList = [...this._printers(), this.toRow(created)];
      this._printers.set(newList);
      this._selectedIndex.set(newList.length - 1);
      this.syncFormFromIndex();
      return { ok: true, message: 'Impresora creada.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo guardar la impresora.' };
    } finally {
      this._isSaving.set(false);
    }
  }

  public async deleteSelected(): Promise<OperationResult> {
    const printer = this.selectedPrinter();
    if (!printer?.uuid) return { ok: false, error: 'No hay impresora seleccionada.' };

    try {
      await firstValueFrom(this.service.remove(printer.uuid));
      const newList = this._printers().filter((p) => p.uuid !== printer.uuid);
      this._printers.set(newList);
      this._selectedIndex.set(newList.length > 0 ? 0 : -1);
      this.syncFormFromIndex();
      return { ok: true, message: `Impresora "${printer.name}" eliminada.` };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo eliminar la impresora.' };
    }
  }

  public async testPrinter(): Promise<OperationResult> {
    const printer = this.selectedPrinter();
    if (!printer?.uuid) return { ok: false, error: 'No hay impresora seleccionada.' };

    this._isTesting.set(true);
    try {
      await firstValueFrom(this.service.test(printer.uuid));
      return { ok: true, message: 'Página de prueba enviada correctamente.' };
    } catch (error) {
      return { ok: false, error: error instanceof Error ? error.message : 'No se pudo conectar con la impresora.' };
    } finally {
      this._isTesting.set(false);
    }
  }

  private toRow(item: PrinterConfigItem): PrinterRow {
    return {
      uuid:       item.uuid,
      name:       item.name,
      ip:         item.ip,
      port:       item.port,
      paperWidth: item.paper_width,
      enabled:    item.enabled,
      isDefault:  item.is_default,
      zoneUuid:   item.zone_uuid,
    };
  }

  private syncFormFromIndex(): void {
    const printer = this.selectedPrinter();
    if (printer) {
      this._formData.set({
        name:       printer.name,
        ip:         printer.ip,
        port:       printer.port,
        paperWidth: printer.paperWidth,
        enabled:    printer.enabled,
        isDefault:  printer.isDefault,
        zoneUuid:   printer.zoneUuid,
      });
    } else {
      this._formData.set({ ...EMPTY_FORM });
    }
  }
}
