import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface TaxItem {
  id: string;
  name: string;
  percentage: number;
  created_at: string;
  updated_at: string;
}

interface CreateTaxPayload {
  name: string;
  percentage: number;
}

interface UpdateTaxPayload {
  name: string;
  percentage: number;
}

@Injectable({
  providedIn: 'root',
})
export class TaxService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public listTaxes(): Observable<TaxItem[]> {
    return this.http
      .get<TaxItem[]>(`${this.baseUrl}/admin/taxes`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createTax(payload: CreateTaxPayload): Observable<TaxItem> {
    return this.http
      .post<TaxItem>(`${this.baseUrl}/admin/taxes`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateTax(id: string, payload: UpdateTaxPayload): Observable<TaxItem> {
    return this.http
      .put<TaxItem>(`${this.baseUrl}/admin/taxes/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteTax(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/taxes/${id}`, { withCredentials: true })
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

    return 'No se pudo completar la peticion de impuestos.';
  }
}
