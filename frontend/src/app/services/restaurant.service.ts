import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface AdminRestaurantItem {
  uuid: string;
  name: string;
  legal_name: string;
  tax_id: string;
  email: string;
}

export interface AdminRestaurantUser {
  uuid: string;
  name: string;
  email: string;
  role: string;
}

interface AdminRestaurantsResponse {
  data: AdminRestaurantItem[];
}

interface AdminRestaurantUsersResponse {
  users: AdminRestaurantUser[];
}

interface SelectRestaurantContextResponse {
  success: boolean;
  restaurant_id: string;
  name: string;
}

interface UpdateAdminRestaurantPayload {
  name: string;
  legal_name: string;
  tax_id: string;
  email: string;
  password?: string;
}

interface CreateRestaurantUserPayload {
  name: string;
  email: string;
  password: string;
}

interface UpdateRestaurantUserPayload {
  name?: string;
  email?: string;
  password?: string;
}

@Injectable({
  providedIn: 'root',
})
export class RestaurantService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  public getAdminRestaurants(): Observable<AdminRestaurantsResponse> {
    return this.http
      .get<AdminRestaurantsResponse>(`${this.baseUrl}/admin/restaurants`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public selectAdminRestaurantContext(restaurantId: string): Observable<SelectRestaurantContextResponse> {
    return this.http
      .post<SelectRestaurantContextResponse>(
        `${this.baseUrl}/admin/context/restaurant`,
        { restaurant_id: restaurantId },
        { withCredentials: true },
      )
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateAdminRestaurant(restaurantId: string, payload: UpdateAdminRestaurantPayload): Observable<unknown> {
    return this.http
      .put(`${this.baseUrl}/admin/restaurants/${restaurantId}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getRestaurantUsers(restaurantUuid: string): Observable<AdminRestaurantUsersResponse> {
    return this.http
      .get<AdminRestaurantUsersResponse>(`${this.baseUrl}/admin/restaurants/${restaurantUuid}/users`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public createRestaurantUser(restaurantUuid: string, payload: CreateRestaurantUserPayload): Observable<AdminRestaurantUser> {
    return this.http
      .post<AdminRestaurantUser>(`${this.baseUrl}/admin/restaurants/${restaurantUuid}/users`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateRestaurantUser(restaurantUuid: string, userUuid: string, payload: UpdateRestaurantUserPayload): Observable<unknown> {
    return this.http
      .put(`${this.baseUrl}/admin/restaurants/${restaurantUuid}/users/${userUuid}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteRestaurantUser(restaurantUuid: string, userUuid: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/admin/restaurants/${restaurantUuid}/users/${userUuid}`, { withCredentials: true })
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

    return 'No se pudo completar la peticion de restaurantes.';
  }
}
