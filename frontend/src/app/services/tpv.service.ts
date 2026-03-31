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
  familyId: string;
  active: boolean;
}

export interface TpvZoneItem {
  id: string;
  name: string;
}

export interface TpvTableItem {
  id: string;
  name: string;
  zoneId: string;
  seats: number;
}

export interface TpvTaxItem {
  id: string;
  name: string;
  percentage: number;
}

export interface TpvOrder {
  id: string;
  tableId: string;
  status: string;
  total: number;
  createdAt: string;
}

export interface TpvSale {
  id: string;
  orderId?: string;
  status: string;
  total: number;
  createdAt: string;
}

interface AddLinePayload {
  orderId?: string;
  saleId?: string;
  productId: string;
  quantity: number;
  price: number;
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

  public createOrder(payload: { tableId: string }): Observable<TpvOrder> {
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

  public updateOrder(id: string, payload: Partial<TpvOrder>): Observable<TpvOrder> {
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

  public getOrderLines(orderId: string): Observable<unknown[]> {
    return this.http
      .get<unknown[]>(`${this.baseUrl}/tpv/orders/${orderId}/lines`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  // ============================================
  // Ventas (transaccional)
  // ============================================

  public createSale(payload: { orderId?: string }): Observable<TpvSale> {
    return this.http
      .post<TpvSale>(`${this.baseUrl}/tpv/sales`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listSales(): Observable<TpvSale[]> {
    return this.http
      .get<TpvSale[]>(`${this.baseUrl}/tpv/sales`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getSale(id: string): Observable<TpvSale> {
    return this.http
      .get<TpvSale>(`${this.baseUrl}/tpv/sales/${id}`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public updateSale(id: string, payload: Partial<TpvSale>): Observable<TpvSale> {
    return this.http
      .put<TpvSale>(`${this.baseUrl}/tpv/sales/${id}`, payload, { withCredentials: true })
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
