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
  pin?: string;
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
  restaurantId?: string;
  restaurantName?: string;
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

interface SuperAdminLoginResponse {
  success: boolean;
  message?: string;
  id?: string;
  name?: string;
  email?: string;
}

interface Restaurant {
  uuid: string;
  name: string;
  legal_name: string;
  tax_id: string;
  email: string;
}

interface GetRestaurantsResponse {
  data: Restaurant[];
}

interface RestaurantUserApi {
  uuid: string;
  name: string;
  email: string;
  role?: string;
  pin?: string;
}

interface GetRestaurantUsersResponse {
  users: RestaurantUserApi[];
}

interface CreateUserResponse {
  restaurant_id: string;
  restaurant_name: string;
  admin_email: string;
  admin_name: string;
  admin_pin?: string;
  message: string;
}

export interface QuickAccessUserResponse {
  user_uuid: string;
  name: string;
  role: string;
  restaurant_uuid: string;
  restaurant_name: string;
  last_login_at: string;
}

interface QuickAccessResponse {
  users: QuickAccessUserResponse[];
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
    const deviceId = this.getOrCreateDeviceId();

    return this.http
      .post<LoginResponse>(
        `${this.authBaseUrl}/login`,
        {
          email,
          password,
          device_id: this.getDeviceId(),
        },
        { withCredentials: true },
      )
      .pipe(
        map((response: LoginResponse) => {
          if (!response.success || !response.id || !response.name || !response.email) {
            const message: string = response.message ?? 'No se pudo iniciar sesión.';

            throw new Error(message);
          }

          return {
            id: response.id,
            name: response.name,
            email: response.email,
            role: response.role ?? 'operator',
            restaurantId: response.restaurantId,
            restaurantName: response.restaurantName,
          };
        }),
        tap((user: AuthUser) => {
          this.currentUserSubject.next(user);
        }),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public loginForDeviceLink(email: string, password: string): Observable<AuthUser> {
    return this.http
      .post<LoginResponse>(
        `${this.authBaseUrl}/login-for-device-link`,
        {
          email,
          password,
          device_id: this.getDeviceId(),
        },
        { withCredentials: true },
      )
      .pipe(
        map((response: LoginResponse) => {
          if (!response.success || !response.id || !response.name || !response.email) {
            const message: string = response.message ?? 'No se pudo iniciar sesión.';

            throw new Error(message);
          }

          return {
            id: response.id,
            name: response.name,
            email: response.email,
            role: 'admin',
            restaurantId: response.restaurantId,
            restaurantName: response.restaurantName,
          };
        }),
        tap((user: AuthUser) => {
          this.currentUserSubject.next(user);
        }),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public loginWithPin(userUuid: string, pin: string, deviceId: string): Observable<AuthUser> {
    const resolvedDeviceId = deviceId.trim() !== '' ? deviceId : this.getOrCreateDeviceId();

    return this.http
      .post<LoginResponse>(
        `${this.authBaseUrl}/login-pin`,
        { user_uuid: userUuid, pin, device_id: resolvedDeviceId },
        { withCredentials: true },
      )
      .pipe(
        map((response: LoginResponse) => {
          if (!response.success || !response.id || !response.name || !response.email) {
            const message: string = response.message ?? 'No se pudo iniciar sesion con PIN.';

            throw new Error(message);
          }

          return {
            id: response.id,
            name: response.name,
            email: response.email,
            role: response.role,
            restaurantId: response.restaurantId,
            restaurantName: response.restaurantName,
          };
        }),
        tap((user: AuthUser) => {
          this.currentUserSubject.next(user);
        }),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public getQuickUsers(deviceId: string, restaurantUuid?: string): Observable<QuickAccessUserResponse[]> {
    const resolvedDeviceId = deviceId.trim() !== '' ? deviceId : this.getOrCreateDeviceId();
    const params: Record<string, string> = { device_id: resolvedDeviceId };

    if (restaurantUuid) {
      params['restaurant_uuid'] = restaurantUuid;
    }

    return this.http
      .get<QuickAccessResponse>(`${this.authBaseUrl}/quick-users`, {
        withCredentials: true,
        params,
      })
      .pipe(
        map((response: QuickAccessResponse) => response.users ?? []),
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
    pin?: string,
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
          pin,
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

  public superAdminLogin(email: string, password: string): Observable<void> {
    return this.http
      .post<SuperAdminLoginResponse>(
        `${environment.apiUrl}/superadmin/login`,
        { email, password },
        { withCredentials: true },
      )
      .pipe(
        map((response: SuperAdminLoginResponse) => {
          if (!response.success || !response.id) {
            const message: string = response.message ?? 'No se pudo iniciar sesion.';

            throw new Error(message);
          }
        }),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public superAdminLogout(): Observable<void> {
    return this.http.post(`${environment.apiUrl}/superadmin/logout`, {}, { withCredentials: true }).pipe(
      map(() => undefined),
      catchError((error: unknown) => {
        return throwError(() => error);
      }),
    );
  }

  public getSuperAdminRestaurants(): Observable<Restaurant[]> {
    return this.http
      .get<GetRestaurantsResponse>(`${environment.apiUrl}/admin/restaurants`, { withCredentials: true })
      .pipe(
        map((response: GetRestaurantsResponse) => response.data ?? []),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
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

  public getDeviceId(): string {
    return this.getOrCreateDeviceId();
  }

  private getOrCreateDeviceId(): string {
    const storageKey = 'tpv_device_id';
    const fromStorage = localStorage.getItem(storageKey);

    if (fromStorage && fromStorage.trim() !== '') {
      return fromStorage;
    }

    const generated = typeof crypto !== 'undefined' && 'randomUUID' in crypto
      ? crypto.randomUUID()
      : `dev-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;

    localStorage.setItem(storageKey, generated);

    return generated;
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

  // Restaurant Management (Superadmin)
  public createRestaurant(data: {
    name: string;
    legal_name: string;
    tax_id: string;
    email: string;
    password: string;
    pin?: string;
    company_mode?: 'existing' | 'new';
  }): Observable<Restaurant> {
    return this.http
      .post<{ data: Restaurant }>(
        `${environment.apiUrl}/superadmin/restaurants`,
        data,
        { withCredentials: true },
      )
      .pipe(
        map((response) => response.data ?? {}),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public updateRestaurant(uuid: string, data: {
    name?: string;
    legal_name?: string;
    email?: string;
  }): Observable<Restaurant> {
    return this.http
      .put<{ data: Restaurant }>(
        `${environment.apiUrl}/superadmin/restaurants/${uuid}`,
        data,
        { withCredentials: true },
      )
      .pipe(
        map((response) => response.data ?? {}),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public deleteRestaurant(uuid: string): Observable<void> {
    return this.http
      .delete<void>(
        `${environment.apiUrl}/superadmin/restaurants/${uuid}`,
        { withCredentials: true },
      )
      .pipe(
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  // User Management for Restaurants (Superadmin)
  public getRestaurantUsers(restaurantUuid: string): Observable<AuthUser[]> {
    return this.http
      .get<GetRestaurantUsersResponse>(
        `${environment.apiUrl}/superadmin/restaurants/${restaurantUuid}/users`,
        { withCredentials: true },
      )
      .pipe(
        map((response) =>
          (response.users ?? []).map((user) => ({
            id: user.uuid,
            name: user.name,
            email: user.email,
            role: user.role,
            pin: user.pin,
          })),
        ),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public createRestaurantUser(restaurantUuid: string, data: {
    name: string;
    email: string;
    password: string;
    role: string;
    pin?: string;
  }): Observable<AuthUser> {
    return this.http
      .post<RestaurantUserApi>(
        `${environment.apiUrl}/superadmin/restaurants/${restaurantUuid}/users`,
        data,
        { withCredentials: true },
      )
      .pipe(
        map((response) => ({
          id: response.uuid,
          name: response.name,
          email: response.email,
          role: response.role,
        })),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public updateRestaurantUser(restaurantUuid: string, userUuid: string, data: {
    name?: string;
    email?: string;
    role?: string;
    pin?: string;
  }): Observable<void> {
    return this.http
      .put<{ uuid: string; found: boolean }>(
        `${environment.apiUrl}/superadmin/restaurants/${restaurantUuid}/users/${userUuid}`,
        data,
        { withCredentials: true },
      )
      .pipe(
        map(() => undefined),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public deleteRestaurantUser(restaurantUuid: string, userUuid: string): Observable<void> {
    return this.http
      .delete<void>(
        `${environment.apiUrl}/superadmin/restaurants/${restaurantUuid}/users/${userUuid}`,
        { withCredentials: true },
      )
      .pipe(
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }

  public getAdminRestaurants(): Observable<Restaurant[]> {
    return this.http
      .get<{ data: Restaurant[] }>(`${environment.apiUrl}/admin/restaurants`, { withCredentials: true })
      .pipe(
        map((response) => response.data ?? []),
        catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))),
      );
  }
}