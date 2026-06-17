import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface PrinterConfigItem {
  uuid: string;
  name: string;
  ip: string;
  port: number;
  paper_width: number;
  enabled: boolean;
  is_default: boolean;
  zone_uuid: string | null;
}

export interface UpsertPrinterPayload {
  name: string;
  ip: string;
  port: number;
  paper_width: number;
  enabled: boolean;
  is_default: boolean;
  zone_uuid: string | null;
}

@Injectable({
  providedIn: 'root',
})
export class PrinterConfigService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de impresoras.';

  public list(): Observable<PrinterConfigItem[]> {
    return this.get<PrinterConfigItem[]>('/admin/printers');
  }

  public create(payload: UpsertPrinterPayload): Observable<PrinterConfigItem> {
    return this.post<PrinterConfigItem>('/admin/printers', payload);
  }

  public update(uuid: string, payload: UpsertPrinterPayload): Observable<PrinterConfigItem> {
    return this.put<PrinterConfigItem>(`/admin/printers/${uuid}`, payload);
  }

  public remove(uuid: string): Observable<void> {
    return this.delete<void>(`/admin/printers/${uuid}`);
  }

  public test(uuid: string): Observable<{ message: string }> {
    return this.post<{ message: string }>(`/admin/printers/${uuid}/test`, {});
  }
}
