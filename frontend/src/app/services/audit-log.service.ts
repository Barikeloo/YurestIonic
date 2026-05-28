import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export type AuditCategoryApi =
  | 'order'
  | 'caja'
  | 'sale'
  | 'table'
  | 'catalog'
  | 'auth'
  | 'config'
  | 'system';

export type AuditSeverityApi = 'info' | 'warning' | 'danger' | 'critical' | 'success';

export interface AuditEventApi {
  uuid: string;
  entity_type: string;
  entity_id: string;
  action: string;
  category: AuditCategoryApi;
  severity: AuditSeverityApi;
  summary: string;
  reason: string | null;
  session_id: string | null;
  anomaly_kind: string | null;
  integrity_hash: string;
  prev_hash: string | null;
  metadata: Record<string, unknown>;
  user_id: string | null;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  ip_address: string | null;
  device_id: string | null;
  created_at: string;
}

export interface ListAuditEventsResponse {
  data: AuditEventApi[];
  next_cursor: string | null;
  has_more: boolean;
}

export interface ListAuditEventsFilters {
  category?: AuditCategoryApi;
  severity?: AuditSeverityApi;
  userId?: string;
  deviceId?: string;
  dateFrom?: string;
  dateTo?: string;
  search?: string;
  anomalyOnly?: boolean;
  cursor?: string;
  /** Live tail: returns events newer than this uuid (ascending). */
  since?: string;
}

export interface AuditSavedViewApi {
  uuid: string;
  name: string;
  icon: string | null;
  filters: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export interface ListAuditSavedViewsResponse {
  data: AuditSavedViewApi[];
}

export interface CreateAuditSavedViewPayload {
  name: string;
  icon: string | null;
  filters: Record<string, unknown>;
}

export interface UpdateAuditSavedViewPayload {
  name?: string;
  icon?: string | null;
  filters?: Record<string, unknown>;
}

@Injectable({
  providedIn: 'root',
})
export class AuditLogService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo cargar el registro de auditoría.';

  public list(filters: ListAuditEventsFilters = {}): Observable<ListAuditEventsResponse> {
    const params: Record<string, string | number | boolean> = {};
    if (filters.category) params['category'] = filters.category;
    if (filters.severity) params['severity'] = filters.severity;
    if (filters.userId) params['user_id'] = filters.userId;
    if (filters.deviceId) params['device_id'] = filters.deviceId;
    if (filters.dateFrom) params['date_from'] = filters.dateFrom;
    if (filters.dateTo) params['date_to'] = filters.dateTo;
    if (filters.search) params['q'] = filters.search;
    if (filters.anomalyOnly) params['anomaly_only'] = true;
    if (filters.cursor) params['cursor'] = filters.cursor;
    if (filters.since) params['since'] = filters.since;

    return this.get<ListAuditEventsResponse>('/admin/audit-log', params);
  }

  public getEvent(uuid: string): Observable<AuditEventApi> {
    return this.get<AuditEventApi>(`/admin/audit-log/${uuid}`);
  }

  public listSavedViews(): Observable<ListAuditSavedViewsResponse> {
    return this.get<ListAuditSavedViewsResponse>('/admin/audit-saved-views');
  }

  public createSavedView(payload: CreateAuditSavedViewPayload): Observable<AuditSavedViewApi> {
    return this.post<AuditSavedViewApi>('/admin/audit-saved-views', payload);
  }

  public updateSavedView(uuid: string, payload: UpdateAuditSavedViewPayload): Observable<AuditSavedViewApi> {
    return this.patch<AuditSavedViewApi>(`/admin/audit-saved-views/${uuid}`, payload);
  }

  public deleteSavedView(uuid: string): Observable<void> {
    return this.delete<void>(`/admin/audit-saved-views/${uuid}`);
  }
}
