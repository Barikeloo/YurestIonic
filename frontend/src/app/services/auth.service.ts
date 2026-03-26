import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { BehaviorSubject, Observable, of, throwError } from 'rxjs';
import { catchError, map, tap } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role?: string;
  restaurantId?: string;
  restaurantName?: string;
}

interface LoginResponse {
  success: boolean;
  message?: string;
  id?: string;
  name?: string;
  email?: string;
  role?: string;
  restaurant_id?: string;
  restaurant_name?: string;
}

interface GetMeResponse {
  success: boolean;
  message?: string;
  id?: string;
  name?: string;
  email?: string;
  role?: string;
  restaurant_id?: string;
  restaurant_name?: string;
}

interface CreateUserResponse {
  restaurant_id: string;
  restaurant_name: string;
  admin_email: string;
  admin_name: string;
  message: string;
}

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private readonly currentUserSubject: BehaviorSubject<AuthUser | null> = new BehaviorSubject<AuthUser | null>(null);

  public readonly currentUser$: Observable<AuthUser | null> = this.currentUserSubject.asObservable();

  private readonly authBaseUrl: string = `${environment.apiUrl}/auth`;

  constructor(private readonly http: HttpClient) {}

  public login(email: string, password: string): Observable<AuthUser> {
    return this.http
      .post<LoginResponse>(
        `${this.authBaseUrl}/login`,
        { email, password },
        { withCredentials: true },
      )
      .pipe(
        map((response: LoginResponse) => {
          if (!response.success || !response.id || !response.name || !response.email) {
            const message: string = response.message ?? 'No se pudo iniciar sesion.';

            throw new Error(message);
          }

          return {
            id: response.id,
            name: response.name,
            email: response.email,
            role: response.role,
            restaurantId: response.restaurant_id,
            restaurantName: response.restaurant_name,
          };
        }),
        tap((user: AuthUser) => {
          this.currentUserSubject.next(user);
        }),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public getMe(): Observable<AuthUser> {
    return this.http.get<GetMeResponse>(`${this.authBaseUrl}/me`, { withCredentials: true }).pipe(
      map((response: GetMeResponse) => {
        if (!response.success || !response.id || !response.name || !response.email) {
          const message: string = response.message ?? 'Sesion no valida.';

          throw new Error(message);
        }

        return {
          id: response.id,
          name: response.name,
          email: response.email,
          role: response.role,
          restaurantId: response.restaurant_id,
          restaurantName: response.restaurant_name,
        };
      }),
      tap((user: AuthUser) => {
        this.currentUserSubject.next(user);
      }),
      catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
    );
  }

  public register(
    restaurantName: string,
    email: string,
    password: string,
    taxId?: string,
    legalName?: string,
  ): Observable<CreateUserResponse> {
    return this.http
      .post<CreateUserResponse>(
        `${this.authBaseUrl}/register`,
        {
          restaurant_name: restaurantName,
          legal_name: legalName,
          admin_name: `Admin ${restaurantName}`,
          email,
          tax_id: taxId,
          password,
          password_confirmation: password,
        },
        { withCredentials: true },
      )
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public restoreSession(): Observable<AuthUser | null> {
    return this.getMe().pipe(
      catchError(() => {
        this.currentUserSubject.next(null);

        return of(null);
      }),
    );
  }

  public logout(): Observable<void> {
    return this.http.post(`${this.authBaseUrl}/logout`, {}, { withCredentials: true }).pipe(
      tap(() => {
        this.currentUserSubject.next(null);
      }),
      map(() => undefined),
      catchError((error: unknown) => {
        this.currentUserSubject.next(null);

        return throwError(() => error);
      }),
    );
  }

  public hasAuthenticatedUser(): boolean {
    return this.currentUserSubject.value !== null;
  }

  private extractErrorMessage(error: HttpErrorResponse): string {
    const payload: unknown = error.error;

    if (payload && typeof payload === 'object') {
      const data = payload as {
        message?: unknown;
        errors?: Record<string, unknown>;
      };

      if (typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
      }

      if (data.errors && typeof data.errors === 'object') {
        const firstErrorGroup: unknown = Object.values(data.errors)[0];

        if (Array.isArray(firstErrorGroup) && typeof firstErrorGroup[0] === 'string') {
          return firstErrorGroup[0];
        }
      }
    }

    return 'No se pudo completar la peticion.';
  }
}