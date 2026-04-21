import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';

export interface TpvFamilyItem {
  id: string;
  name: string;
  active: boolean;
}

export interface TpvProductItem {
  id: string;
  name: string;
  price: number;
  family_id: string;
  tax_id: string;
  active: boolean;
}

export interface TpvZoneItem {
  id: string;
  name: string;
}

export interface TpvTableItem {
  id: string;
  name: string;
  zone_id: string;
}

export interface TpvTaxItem {
  id: string;
  name: string;
  percentage: number;
}

export interface TpvOrder {
  id: string;
  table_id: string;
  status: 'open' | 'to-charge' | 'cancelled' | 'invoiced';
  diners: number;
  opened_at: string;
  opened_by_user_id: string;
  closed_at?: string | null;
  closed_by_user_id?: string | null;
  total: number;
}

export interface TpvOrderLine {
  id: string;
  product_id: string;
  product_name: string | null;
  quantity: number;
  price: number;
  tax_percentage: number;
}

export interface TpvSale {
  id: string;
  order_id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  ticket_number: number | null;
  value_date: string;
  total: number;
}

interface AddLinePayload {
  order_id: string;
  product_id: string;
  user_id: string;
  quantity: number;
  price: number;
  tax_percentage: number;
}

interface UpdateOrderPayload {
  diners?: number;
  action?: 'mark-to-charge' | 'close' | 'cancel';
  closed_by_user_id?: string;
}

@Injectable({
  providedIn: 'root',
})
export class TpvService {
  private readonly baseUrl: string = environment.apiUrl;

  constructor(private readonly http: HttpClient) {}

  // ============================================
  // Catálogo (solo lectura, listados ligeros)
  // ============================================

  public listFamilies(): Observable<TpvFamilyItem[]> {
    return this.http
      .get<TpvFamilyItem[]>(`${this.baseUrl}/tpv/families`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listProducts(): Observable<TpvProductItem[]> {
    return this.http
      .get<TpvProductItem[]>(`${this.baseUrl}/tpv/products`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listZones(): Observable<TpvZoneItem[]> {
    return this.http
      .get<TpvZoneItem[]>(`${this.baseUrl}/tpv/zones`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listTables(): Observable<TpvTableItem[]> {
    return this.http
      .get<TpvTableItem[]>(`${this.baseUrl}/tpv/tables`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listTaxes(): Observable<TpvTaxItem[]> {
    return this.http
      .get<TpvTaxItem[]>(`${this.baseUrl}/tpv/taxes`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  // ============================================
  // Órdenes (transaccional)
  // ============================================

  public createOrder(payload: { table_id: string; opened_by_user_id: string; diners: number }): Observable<TpvOrder> {
    return this.http
      .post<TpvOrder>(`${this.baseUrl}/tpv/orders`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listOrders(): Observable<TpvOrder[]> {
    return this.http
      .get<TpvOrder[]>(`${this.baseUrl}/tpv/orders`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getOrder(id: string): Observable<TpvOrder> {
    return this.http
      .get<TpvOrder>(`${this.baseUrl}/tpv/orders/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateOrder(id: string, payload: UpdateOrderPayload): Observable<TpvOrder> {
    return this.http
      .put<TpvOrder>(`${this.baseUrl}/tpv/orders/${id}`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteOrder(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/tpv/orders/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public addOrderLine(payload: AddLinePayload): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/tpv/orders/lines`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteOrderLine(lineId: string): Observable<void> {
    return this.http
      .delete<void>(`${this.baseUrl}/tpv/orders/lines/${lineId}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getOrderLines(orderId: string): Observable<TpvOrderLine[]> {
    return this.http
      .get<TpvOrderLine[]>(`${this.baseUrl}/tpv/orders/${orderId}/lines`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  // ============================================
  // Ventas (transaccional)
  // ============================================

  public createSale(payload: { order_id: string; opened_by_user_id: string; closed_by_user_id: string }): Observable<TpvSale> {
    return this.http
      .post<TpvSale>(`${this.baseUrl}/tpv/sales`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listSales(): Observable<TpvSale[]> {
    return this.http
      .get<TpvSale[]>(`${this.baseUrl}/tpv/sales`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listUsers(deviceId: string, restaurantUuid?: string): Observable<{ users: any[] }> {
    const params: Record<string, string> = { device_id: deviceId };
    if (restaurantUuid) {
      params['restaurant_uuid'] = restaurantUuid;
    }
    return this.http
      .get<{ users: any[] }>(`${this.baseUrl}/auth/quick-users`, {
        withCredentials: true,
        params,
      })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getSale(id: string): Observable<TpvSale> {
    return this.http
      .get<TpvSale>(`${this.baseUrl}/tpv/sales/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public deleteSale(id: string): Observable<unknown> {
    return this.http
      .delete(`${this.baseUrl}/tpv/sales/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public addSaleLine(payload: AddLinePayload): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/tpv/sales/lines`, payload, { withCredentials: true })
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

    return 'No se pudo completar la petición del TPV.';
  }
}
