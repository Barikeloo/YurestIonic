import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface AuditAlertApi {
  uuid: string;
  audit_log_uuid: string | null;
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
  public listAlerts(): Observable<AuditAlertsResponse> {
    return this.get<AuditAlertsResponse>('/admin/audit-alerts');
  }

  public markAsRead(uuid: string): Observable<{ ok: boolean }> {
    return this.post<{ ok: boolean }>(`/admin/audit-alerts/${uuid}/read`);
  }

  public markAllAsRead(): Observable<{ ok: boolean; marked: number }> {
    return this.post<{ ok: boolean; marked: number }>(`/admin/audit-alerts/read-all`);
  }
}
