import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface AuditAlertApi {
  uuid: string;
  action: string;
  anomaly_kind: string;
  entity_type: string;
  entity_id: string;
  summary: string | null;
  metadata: Record<string, unknown>;
  device_id: string | null;
  read_at: string | null;
  created_at: string;
}

export interface AuditAlertsResponse {
  data: AuditAlertApi[];
  unread_count: number;
}

@Injectable({ providedIn: 'root' })
export class AuditAlertService extends BaseApiService {
  private readonly baseUrl = `${this.apiUrl}/admin/audit-alerts`;

  public listAlerts(): Observable<AuditAlertsResponse> {
    return this.http.get<AuditAlertsResponse>(this.baseUrl, { withCredentials: true });
  }

  public markAsRead(uuid: string): Observable<{ ok: boolean }> {
    return this.http.post<{ ok: boolean }>(`${this.baseUrl}/${uuid}/read`, {}, { withCredentials: true });
  }
}
