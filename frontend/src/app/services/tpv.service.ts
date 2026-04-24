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
  diner_number?: number | null;
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

export interface TpvCashSession {
  id: string;
  uuid: string;
  restaurant_id: string;
  device_id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  opened_at: string;
  closed_at: string | null;
  initial_amount_cents: number;
  final_amount_cents: number | null;
  expected_amount_cents: number | null;
  discrepancy_cents: number | null;
  discrepancy_reason: string | null;
  z_report_number: number | null;
  z_report_hash: string | null;
  notes: string | null;
  status: 'open' | 'closing' | 'closed' | 'abandoned';
}

export interface TpvCashMovement {
  id: string;
  uuid: string;
  cash_session_id: string;
  type: 'in' | 'out';
  reason_code: string;
  amount_cents: number;
  description: string | null;
  user_id: string;
  created_at: string;
}

export interface TpvCashSessionSummary {
  initial_amount_cents: number;
  total_sales: number;
  total_cash_payments: number;
  total_card_payments: number;
  total_bizum_payments: number;
  total_other_payments: number;
  total_in_movements: number;
  total_out_movements: number;
  expected_amount: number;
  movements_count: number;
  payments_count: number;
}

export interface TpvCashSessionListItem {
  uuid: string;
  device_id: string;
  opened_by_user_id: string;
  closed_by_user_id: string | null;
  opened_at: string;
  closed_at: string | null;
  initial_amount_cents: number;
  final_amount_cents: number | null;
  expected_amount_cents: number | null;
  discrepancy_cents: number | null;
  discrepancy_reason: string | null;
  z_report_number: number | null;
  status: string;
  tickets: number;
  diners: number;
  gross: number;
  discounts: number;
  invitations: number;
  inv_value: number;
  cancellations: number;
  net: number;
  mov_in: number;
  mov_out: number;
}

interface AddLinePayload {
  order_id: string;
  product_id: string;
  quantity: number;
  diner_number?: number | null;
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

  public createSale(payload: {
    order_id: string;
    opened_by_user_id: string;
    closed_by_user_id: string;
    device_id: string;
    payments: Array<{ method: string; amount_cents: number; metadata?: Record<string, unknown> }>;
    order_line_ids?: string[];
    is_partial_payment?: boolean;
  }): Observable<TpvSale> {
    return this.http
      .post<TpvSale>(`${this.baseUrl}/tpv/sales`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getOrderPaidTotal(orderId: string): Observable<{ total_cents: number }> {
    return this.http
      .get<{ total_cents: number }>(`${this.baseUrl}/tpv/sales/order/${orderId}/paid-total`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getOrderTotal(orderId: string): Observable<{ total_cents: number }> {
    return this.http
      .get<{ total_cents: number }>(`${this.baseUrl}/tpv/orders/${orderId}/total`, { withCredentials: true })
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

  // ============================================
  // Sesiones de Caja
  // ============================================

  public openCashSession(payload: {
    device_id: string;
    opened_by_user_id: string;
    initial_amount_cents: number;
    notes?: string;
  }): Observable<TpvCashSession> {
    return this.http
      .post<TpvCashSession>(`${this.baseUrl}/tpv/cash-sessions`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getActiveCashSession(deviceId: string): Observable<TpvCashSession | null> {
    return this.http
      .get<TpvCashSession | null>(`${this.baseUrl}/tpv/cash-sessions/active`, {
        withCredentials: true,
        params: { device_id: deviceId },
      })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listCashSessions(): Observable<{ sessions: TpvCashSessionListItem[] }> {
    return this.http
      .get<{ sessions: TpvCashSessionListItem[] }>(`${this.baseUrl}/tpv/cash-sessions`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getLastClosedCashSession(): Observable<{
    last_closed: {
      id: string;
      opened_by_user_id: string;
      closed_by_user_id: string | null;
      opened_at: string;
      closed_at: string | null;
      final_amount_cents: number | null;
      discrepancy_cents: number | null;
      discrepancy_reason: string | null;
      z_report_number: number | null;
      operator_name: string | null;
      tickets: number;
      diners: number;
    } | null;
    orphan_session: {
      id: string;
      opened_by_user_id: string;
      opened_at: string;
      device_id: string;
    } | null;
  }> {
    return this.http
      .get<{
        last_closed: {
          id: string;
          opened_by_user_id: string;
          closed_by_user_id: string | null;
          opened_at: string;
          closed_at: string | null;
          final_amount_cents: number | null;
          discrepancy_cents: number | null;
          discrepancy_reason: string | null;
          z_report_number: number | null;
          operator_name: string | null;
          tickets: number;
          diners: number;
        } | null;
        orphan_session: {
          id: string;
          opened_by_user_id: string;
          opened_at: string;
          device_id: string;
        } | null;
      }>(`${this.baseUrl}/tpv/cash-sessions/last-closed`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public getCashSessionSummary(id: string): Observable<TpvCashSessionSummary> {
    return this.http
      .get<TpvCashSessionSummary>(`${this.baseUrl}/tpv/cash-sessions/${id}/summary`, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public registerCashMovement(payload: {
    cash_session_id: string;
    type: string;
    reason_code: string;
    amount_cents: number;
    user_id: string;
    description?: string;
  }): Observable<TpvCashMovement> {
    return this.http
      .post<TpvCashMovement>(`${this.baseUrl}/tpv/cash-movements`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public listCashMovements(cashSessionId: string): Observable<{ movements: Array<{
    uuid: string;
    type: 'in' | 'out';
    reason_code: string;
    amount_cents: number;
    description: string | null;
    user_id: string;
    created_at: string;
  }> }> {
    return this.http
      .get<{ movements: any[] }>(`${this.baseUrl}/tpv/cash-movements`, {
        withCredentials: true,
        params: { cash_session_id: cashSessionId },
      })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public startClosingCashSession(payload: { cash_session_id: string }): Observable<{ id: string; status: string }> {
    return this.http
      .post<{ id: string; status: string }>(`${this.baseUrl}/tpv/cash-sessions/start-closing`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public cancelClosingCashSession(payload: { cash_session_id: string }): Observable<{ id: string; status: string }> {
    return this.http
      .post<{ id: string; status: string }>(`${this.baseUrl}/tpv/cash-sessions/cancel-closing`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public closeCashSession(payload: {
    cash_session_id: string;
    closed_by_user_id: string;
    final_amount_cents: number;
    discrepancy_reason?: string;
  }): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/tpv/cash-sessions/close`, payload, { withCredentials: true })
      .pipe(catchError((error: HttpErrorResponse) => throwError(() => new Error(this.extractErrorMessage(error)))));
  }

  public forceCloseCashSession(payload: { cash_session_id: string; closed_by_user_id: string }): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/tpv/cash-sessions/force-close`, payload, { withCredentials: true })
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
