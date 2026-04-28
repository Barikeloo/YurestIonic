import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ChargeSession {
  id: string;
  order_id: string;
  diners_count: number;
  total_cents: number;
  amount_per_diner: number;
  paid_diners_count: number;
  remaining_amount: number;
  status: 'active' | 'completed' | 'cancelled';
  paid_diners: PaidDiner[];
}

export interface PaidDiner {
  diner_number: number;
  amount_cents: number;
  payment_method: string;
  paid_at: string;
}

export interface CreateChargeSessionRequest {
  order_id: string;
  opened_by_user_id: string;
  diners_count?: number;
}

export interface RecordPaymentRequest {
  diner_number: number;
  payment_method: 'cash' | 'card' | 'bizum' | 'other';
}

export interface RecordPaymentResponse {
  id: string;
  diner_number: number;
  amount_cents: number;
  payment_method: string;
  session_paid_diners_count: number;
  is_session_complete: boolean;
  session_status: string;
}

export interface UpdateDinersRequest {
  diners_count: number;
}

export interface UpdateDinersResponse {
  id: string;
  diners_count: number;
  amount_per_diner: number;
  status: string;
}

export interface CancelChargeSessionRequest {
  cancelled_by_user_id: string;
  reason?: string;
}

export interface CancelChargeSessionResponse {
  id: string;
  status: string;
  paid_diners_count: number;
  warning_message: string | null;
}

@Injectable({
  providedIn: 'root'
})
export class ChargeSessionService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  /**
   * Crear o recuperar una sesión de cobro para una orden
   */
  createChargeSession(request: CreateChargeSessionRequest): Observable<ChargeSession> {
    return this.http.post<ChargeSession>(`${this.apiUrl}/tpv/charge-sessions`, request);
  }

  /**
   * Obtener la sesión de cobro activa para una orden
   */
  getActiveChargeSession(orderId: string): Observable<ChargeSession> {
    return this.http.get<ChargeSession>(`${this.apiUrl}/tpv/charge-sessions/active`, {
      params: { order_id: orderId }
    });
  }

  /**
   * Registrar un pago de un comensal
   */
  recordPayment(sessionId: string, request: RecordPaymentRequest): Observable<RecordPaymentResponse> {
    return this.http.post<RecordPaymentResponse>(
      `${this.apiUrl}/tpv/charge-sessions/${sessionId}/payments`,
      request
    );
  }

  /**
   * Actualizar el número de comensales (solo si no hay pagos)
   */
  updateDiners(sessionId: string, request: UpdateDinersRequest): Observable<UpdateDinersResponse> {
    return this.http.put<UpdateDinersResponse>(
      `${this.apiUrl}/tpv/charge-sessions/${sessionId}/diners`,
      request
    );
  }

  /**
   * Cancelar la sesión de cobro
   */
  cancelChargeSession(
    sessionId: string,
    request: CancelChargeSessionRequest
  ): Observable<CancelChargeSessionResponse> {
    return this.http.post<CancelChargeSessionResponse>(
      `${this.apiUrl}/tpv/charge-sessions/${sessionId}/cancel`,
      request
    );
  }
}
