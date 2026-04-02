import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface TableItem {
  id: string;
  zone_id: string;
  name: string;
  created_at: string;
  updated_at: string;
}

interface CreateTablePayload {
  zone_id: string;
  name: string;
}

interface UpdateTablePayload {
  zone_id: string;
  name: string;
}

@Injectable({
  providedIn: 'root',
})
export class TableService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public listTables(): Observable<TableItem[]> {
    return this.http
      .get<TableItem[]>(`${this.baseUrl}/admin/tables`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createTable(payload: CreateTablePayload): Observable<TableItem> {
    return this.http
      .post<TableItem>(`${this.baseUrl}/admin/tables`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateTable(id: string, payload: UpdateTablePayload): Observable<TableItem> {
    return this.http
      .put<TableItem>(`${this.baseUrl}/admin/tables/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteTable(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/tables/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  private extractErrorMessage(error: HttpErrorResponse): string {
    const payload: unknown = error.error;

    if (payload && typeof payload === 'object') {
      const data = payload as { message?: unknown };
      if (typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
      }
    }

    return 'No se pudo completar la peticion de mesas.';
  }
}
