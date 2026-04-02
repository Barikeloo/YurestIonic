import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

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
export class ZoneService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public listZones(): Observable<ZoneItem[]> {
    return this.http
      .get<ZoneItem[]>(`${this.baseUrl}/admin/zones`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createZone(payload: CreateZonePayload): Observable<ZoneItem> {
    return this.http
      .post<ZoneItem>(`${this.baseUrl}/admin/zones`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateZone(id: string, payload: UpdateZonePayload): Observable<ZoneItem> {
    return this.http
      .put<ZoneItem>(`${this.baseUrl}/admin/zones/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteZone(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/zones/${id}`, { withCredentials: true })
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

    return 'No se pudo completar la peticion de zonas.';
  }
}
