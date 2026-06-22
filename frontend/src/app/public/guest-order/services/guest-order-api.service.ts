import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  TableStatusResponse,
  OpenTableBody,
  JoinSessionBody,
  SessionResult,
  ValidateSessionResult,
} from '../models/guest-session.models';
import { CatalogResponse } from '../models/guest-catalog.models';
import {
  CartApiLine,
  OrderHistoryLine,
  OrderHistoryResponse,
  RoundResult,
  SubmitRoundBody,
} from '../models/guest-cart.models';

@Injectable({ providedIn: 'root' })
export class GuestOrderApiService {
  private readonly http = inject(HttpClient);
  private readonly base = environment.apiUrl;

  private url(token: string, path = ''): string {
    return `${this.base}/api/public/table/${token}${path}`;
  }

  private guestHeaders(sessionToken?: string): HttpHeaders {
    let headers = new HttpHeaders({ 'Content-Type': 'application/json' });
    if (sessionToken) {
      headers = headers.set('X-Guest-Session', sessionToken);
    }
    return headers;
  }

  getTableStatus(token: string): Observable<TableStatusResponse> {
    return this.http.get<TableStatusResponse>(this.url(token));
  }

  getCatalogVersion(token: string): Observable<{ version: number }> {
    return this.http.get<{ version: number }>(this.url(token, '/catalog/version'));
  }

  getCatalog(token: string): Observable<CatalogResponse> {
    return this.http.get<CatalogResponse>(this.url(token, '/catalog'));
  }

  validateSession(token: string, sessionToken: string): Observable<ValidateSessionResult> {
    return this.http.get<ValidateSessionResult>(
      this.url(token, '/session/validate'),
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  openTable(token: string, body: OpenTableBody): Observable<SessionResult> {
    return this.http.post<SessionResult>(this.url(token, '/open'), body);
  }

  joinSession(token: string, body: JoinSessionBody): Observable<SessionResult> {
    return this.http.post<SessionResult>(this.url(token, '/session'), body);
  }

  savePendingLines(
    token: string,
    sessionToken: string,
    lines: CartApiLine[],
  ): Observable<{ line_ids: string[] }> {
    return this.http.post<{ line_ids: string[] }>(
      this.url(token, '/cart/save'),
      { lines },
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  getCart(
    token: string,
    sessionToken: string,
  ): Observable<{ lines: OrderHistoryLine[]; total_cents: number }> {
    return this.http.get<{ lines: OrderHistoryLine[]; total_cents: number }>(
      this.url(token, '/cart'),
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  submitRound(token: string, sessionToken: string, body: SubmitRoundBody): Observable<RoundResult> {
    return this.http.post<RoundResult>(
      this.url(token, '/cart/submit-round'),
      body,
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  getOrderHistory(token: string, sessionToken: string): Observable<OrderHistoryResponse> {
    return this.http.get<OrderHistoryResponse>(
      this.url(token, '/my-orders'),
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  requestCheck(token: string, sessionToken: string): Observable<{ requested_at: string }> {
    return this.http.post<{ requested_at: string }>(
      this.url(token, '/request-check'),
      {},
      { headers: this.guestHeaders(sessionToken) },
    );
  }

  deletePendingLine(token: string, sessionToken: string, lineId: string): Observable<{ line_id: string }> {
    return this.http.delete<{ line_id: string }>(
      this.url(token, `/cart/line/${lineId}`),
      { headers: this.guestHeaders(sessionToken) },
    );
  }
}
