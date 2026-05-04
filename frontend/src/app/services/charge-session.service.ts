import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ChargeSession {
  id: string;
  order_id: string;
  diners_count: number;
  total_cents: number;
  paid_cents: number;
  remaining_cents: number;
  suggested_per_diner_cents: number;
  status: 'active' | 'completed' | 'cancelled';
  paid_diner_numbers: number[];
  created_at: string;
  updated_at: string;
}

export interface CreateChargeSessionRequest {
  order_id: string;
  opened_by_user_id: string;
  diners_count?: number;
  remaining_cents?: number; // Deuda real (total - pagos previos)
}

export interface RecordPaymentRequest {
  payment_method: 'cash' | 'card' | 'bizum' | 'voucher' | 'invitation' | 'other';
  opened_by_user_id: string;
  closed_by_user_id: string;
  device_id: string;
  /** Etiqueta visual del comensal. Opcional: en cobro libre puede omitirse. */
  diner_number?: number;
  /** Importe libre. Si no se envía, el backend aplica la cuota equitativa sobre la deuda restante. */
  amount_cents?: number;
}

export interface RecordPaymentResponse {
  payment_id: string;
  charge_session_id: string;
  diner_number: number | null;
  amount_cents: number;
  payment_method: string;
  status: string;
  session_paid_diners_count: number;
  session_status: string;
  session_remaining_cents: number;
  is_session_complete: boolean;
}

export interface UpdateDinersRequest {
  diners_count: number;
}

export interface UpdateDinersResponse {
  id: string;
  diners_count: number;
  suggested_per_diner_cents: number;
  total_cents: number;
  status: string;
}

export interface CancelChargeSessionRequest {
  cancelled_by_user_id: string;
  reason?: string;
}

export interface CancelChargeSessionResponse {
  id: string;
  status: string;
  paid_diners_count: number; // Backend aún usa count en lugar de array
  session_paid_cents: number;
  warning_message: string | null;
  paid_diners: number[]; // Backend envía array de números
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
   * Obtener la sesión de cobro vigente para una orden (la más reciente,
   * sin filtrar por status). El cliente lee `status` y decide cómo actuar.
   */
  getCurrentChargeSession(orderId: string): Observable<ChargeSession> {
    return this.http.get<ChargeSession>(`${this.apiUrl}/tpv/charge-sessions/current`, {
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
