import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface TableItem {
  id: string;
  zone_id: string;
  name: string;
  created_at: string;
  updated_at: string;
  merged_table_group_id?: string;
}

interface CreateTablePayload {
  zone_id: string;
  name: string;
}

interface UpdateTablePayload {
  zone_id: string;
  name: string;
}

interface MergeTablesPayload {
  table_ids: string[];
}

interface UnmergeTablesPayload {
  group_id: string;
}

interface MergeTablesResponse {
  group_id: string;
  merged_table_ids: string[];
}

interface UnmergeTablesResponse {
  unmerged_table_ids: string[];
}

@Injectable({
  providedIn: 'root',
})
export class TableService extends BaseApiService {
  protected override readonly defaultErrorMessage = 'No se pudo completar la peticion de mesas.';

  public listTables(): Observable<TableItem[]> {
    return this.get<TableItem[]>('/admin/tables');
  }

  public createTable(payload: CreateTablePayload): Observable<TableItem> {
    return this.post<TableItem>('/admin/tables', payload);
  }

  public updateTable(id: string, payload: UpdateTablePayload): Observable<TableItem> {
    return this.put<TableItem>(`/admin/tables/${id}`, payload);
  }

  public deleteTable(id: string): Observable<void> {
    return this.delete<void>(`/admin/tables/${id}`);
  }

  public mergeTables(tableIds: string[]): Observable<MergeTablesResponse> {
    return this.post<MergeTablesResponse>('/api/tpv/tables/merge', { table_ids: tableIds });
  }

  public unmergeTables(groupId: string): Observable<UnmergeTablesResponse> {
    return this.post<UnmergeTablesResponse>('/api/tpv/tables/unmerge', { group_id: groupId });
  }
}
