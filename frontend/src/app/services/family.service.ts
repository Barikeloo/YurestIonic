import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface FamilyItem {
  id: string;
  name: string;
  active: boolean;
  created_at: string;
  updated_at: string;
}

interface CreateFamilyPayload {
  name: string;
}

interface UpdateFamilyPayload {
  name: string;
}

@Injectable({
  providedIn: 'root',
})
export class FamilyService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public listFamilies(): Observable<FamilyItem[]> {
    return this.http
      .get<FamilyItem[]>(`${this.baseUrl}/admin/families`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createFamily(payload: CreateFamilyPayload): Observable<FamilyItem> {
    return this.http
      .post<FamilyItem>(`${this.baseUrl}/admin/families`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateFamily(id: string, payload: UpdateFamilyPayload): Observable<FamilyItem> {
    return this.http
      .put<FamilyItem>(`${this.baseUrl}/admin/families/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public activateFamily(id: string): Observable<FamilyItem> {
    return this.http
      .patch<FamilyItem>(`${this.baseUrl}/admin/families/${id}/activate`, {}, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deactivateFamily(id: string): Observable<FamilyItem> {
    return this.http
      .patch<FamilyItem>(`${this.baseUrl}/admin/families/${id}/deactivate`, {}, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteFamily(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/families/${id}`, { withCredentials: true })
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

    return 'No se pudo completar la peticion de familias.';
  }
}
