import { Injectable } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface SaveZoneLayoutTable {
  uuid: string;
  pos_x: number;
  pos_y: number;
  width: number;
  height: number;
  shape: 'rect' | 'circle';
}

interface SaveZoneLayoutResponse {
  saved: number;
}

@Injectable({ providedIn: 'root' })
export class ZoneLayoutService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo guardar el plano.';

  saveZoneLayout(zoneId: string, tables: SaveZoneLayoutTable[]): Promise<number> {
    return firstValueFrom(
      this.put<SaveZoneLayoutResponse>(`/admin/zones/${zoneId}/layout`, { tables }),
    ).then(res => res.saved);
  }
}
