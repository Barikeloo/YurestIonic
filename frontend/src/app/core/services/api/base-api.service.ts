import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, Observable, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';

export type HttpParamsLike = HttpParams | Record<string, string | number | boolean>;

export abstract class BaseApiService {
  protected readonly apiUrl: string = environment.apiUrl;
  protected readonly http: HttpClient = inject(HttpClient);

  /**
   * Default error message used when the backend response does not include a `message` field.
   * Override in subclasses to provide a more specific fallback.
   */
  protected readonly defaultErrorMessage: string = 'No se pudo completar la peticion.';

  protected get<T>(endpoint: string, params?: HttpParamsLike): Observable<T> {
    return this.http
      .get<T>(this.buildUrl(endpoint), {
        params,
        withCredentials: true,
      })
      .pipe(catchError((error: HttpErrorResponse) => this.handleError(error)));
  }

  protected post<T>(endpoint: string, body: unknown = null): Observable<T> {
    return this.http
      .post<T>(this.buildUrl(endpoint), body, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => this.handleError(error)));
  }

  protected put<T>(endpoint: string, body: unknown = null): Observable<T> {
    return this.http
      .put<T>(this.buildUrl(endpoint), body, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => this.handleError(error)));
  }

  protected patch<T>(endpoint: string, body: unknown = null): Observable<T> {
    return this.http
      .patch<T>(this.buildUrl(endpoint), body, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => this.handleError(error)));
  }

  protected delete<T>(endpoint: string, params?: HttpParamsLike): Observable<T> {
    return this.http
      .delete<T>(this.buildUrl(endpoint), {
        params,
        withCredentials: true,
      })
      .pipe(catchError((error: HttpErrorResponse) => this.handleError(error)));
  }

  private buildUrl(endpoint: string): string {
    if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
      return endpoint;
    }

    const base = this.apiUrl.endsWith('/api') ? this.apiUrl : `${this.apiUrl}/api`;

    return `${base}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;
  }

  private handleError(error: HttpErrorResponse): Observable<never> {
    return throwError(() => new Error(this.extractErrorMessage(error)));
  }

  private extractErrorMessage(error: HttpErrorResponse): string {
    const payload: unknown = error.error;

    if (payload && typeof payload === 'object') {
      const data = payload as { message?: unknown };

      if (typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
      }
    }

    return this.defaultErrorMessage;
  }
}
