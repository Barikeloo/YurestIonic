import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';
import { catchError } from 'rxjs/operators';
import { Router } from '@angular/router';

@Injectable()
export class InterceptorProvider implements HttpInterceptor {

  constructor(private readonly router: Router) {}

  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    return next.handle(this.setHeader(request)).pipe(
      catchError((error) => {
        if (error.status === 401) {
          const url = request.url;
          if (url.includes('/auth/me') || url.includes('/superadmin/me')) {
            return throwError(() => error);
          }

          void this.router.navigate(['/login'], {
            queryParams: { returnUrl: this.router.url },
          });
        }
        return throwError(() => error);
      }),
    );
  }

  private setHeader(request: HttpRequest<any>): HttpRequest<any> {
    const deviceId = this.getOrCreateDeviceId();

    return request.clone({
      withCredentials: true,
      setHeaders: {
        Accept: 'application/json',
        'Accept-Language': 'es',
        'X-Device-Id': deviceId,
      }
    });
  }

  private getOrCreateDeviceId(): string {
    const storageKey = 'tpv_device_id';
    const existing = localStorage.getItem(storageKey);

    if (existing && existing.trim() !== '') {
      return existing;
    }

    const generated = typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `dev-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

    localStorage.setItem(storageKey, generated);

    return generated;
  }

}
