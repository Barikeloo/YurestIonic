import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface ZoneItem {
  id: string;
  name: string;
  created_at: string;
  updated_at: string;
}

interface CreateZonePayload {
  name: string;
}

interface UpdateZonePayload {
  name: string;
}

@Injectable({
  providedIn: 'root',
})
export class ZoneService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de zonas.';

  public listZones(): Observable<ZoneItem[]> {
    return this.get<ZoneItem[]>('/admin/zones');
  }

  public getZone(id: string): Observable<ZoneItem> {
    return this.get<ZoneItem>(`/admin/zones/${id}`);
  }

  public createZone(payload: CreateZonePayload): Observable<ZoneItem> {
    return this.post<ZoneItem>('/admin/zones', payload);
  }

  public updateZone(id: string, payload: UpdateZonePayload): Observable<ZoneItem> {
    return this.put<ZoneItem>(`/admin/zones/${id}`, payload);
  }

  public deleteZone(id: string): Observable<void> {
    return this.delete<void>(`/admin/zones/${id}`);
  }
}
