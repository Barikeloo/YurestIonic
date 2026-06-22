import { computed, inject, Injectable, Signal, signal } from '@angular/core';
import { BehaviorSubject, firstValueFrom, Observable, of } from 'rxjs';
import { catchError, finalize, switchMap, take, tap } from 'rxjs/operators';
import { TpvService, TpvCashSession, TpvCashSessionListItem, TpvCashSessionSummary, TpvCashMovement } from '../services/tpv.service';
import { CajaState } from '../../../core/enums/caja-state.enum';
import { CashSessionStatusService } from '../../../core/services/cash-session-status.service';

export interface LastClosedData {
  id: string;
  opened_by_user_id: string | null;
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
}

export interface OrphanSessionData {
  id: string;
  opened_by_user_id: string | null;
  opened_at: string;
  device_id: string;
}

export interface OpenSessionPayload {
  device_id: string;
  opened_by_user_id: string | null;
  initial_amount_cents: number;
  notes?: string;
}

export interface CloseSessionPayload {
  cash_session_id: string;
  closed_by_user_id: string;
  final_amount_cents: number;
  discrepancy_reason?: string;
}

export interface RegisterMovementPayload {
  cash_session_id: string;
  type: string;
  reason_code: string;
  amount_cents: number;
  user_id: string;
  description?: string;
}

export interface StartClosingPayload {
  cash_session_id: string;
}

export interface CancelClosingPayload {
  cash_session_id: string;
}

@Injectable()
export class CajaSessionFacade {
  private readonly tpvService = inject(TpvService);
  private readonly cashSessionStatus = inject(CashSessionStatusService);

  private readonly _state = signal<CajaState>(CajaState.PRE_APERTURA);
  private readonly _activeSession = signal<TpvCashSession | null>(null);
  private readonly _loading = signal<boolean>(true);
  private readonly _lastClosed = signal<LastClosedData | null>(null);
  private readonly _orphanSession = signal<OrphanSessionData | null>(null);
  private readonly _sessionSummary = signal<TpvCashSessionSummary | null>(null);
  private readonly _isClosingInProgress = signal<boolean>(false);

  public readonly state: Signal<CajaState> = this._state.asReadonly();
  public readonly activeSession: Signal<TpvCashSession | null> = this._activeSession.asReadonly();
  public readonly loading: Signal<boolean> = this._loading.asReadonly();
  public readonly lastClosed: Signal<LastClosedData | null> = this._lastClosed.asReadonly();
  public readonly orphanSession: Signal<OrphanSessionData | null> = this._orphanSession.asReadonly();
  public readonly sessionSummary: Signal<TpvCashSessionSummary | null> = this._sessionSummary.asReadonly();
  public readonly isClosingInProgress: Signal<boolean> = this._isClosingInProgress.asReadonly();

  public readonly isActive: Signal<boolean> = computed(() => this._state() === CajaState.ACTIVA);
  public readonly isPreApertura: Signal<boolean> = computed(() => this._state() === CajaState.PRE_APERTURA);
  public readonly isArqueo: Signal<boolean> = computed(() => this._state() === CajaState.ARQUEO);
  public readonly isHistorico: Signal<boolean> = computed(() => this._state() === CajaState.HISTORICO);

  public setState(value: CajaState): void {
    this._state.set(value);
  }

  public setActiveSession(value: TpvCashSession | null): void {
    this._activeSession.set(value);
    this.cashSessionStatus.setOpen(value?.status === 'open');
  }

  public setLoading(value: boolean): void {
    this._loading.set(value);
  }

  public setLastClosed(value: LastClosedData | null): void {
    this._lastClosed.set(value);
  }

  public setOrphanSession(value: OrphanSessionData | null): void {
    this._orphanSession.set(value);
  }

  public setSessionSummary(value: TpvCashSessionSummary | null): void {
    this._sessionSummary.set(value);
  }

  public setClosingInProgress(value: boolean): void {
    this._isClosingInProgress.set(value);
  }

  public loadActiveSession(deviceId: string): Observable<TpvCashSession | null> {
    this.setLoading(true);

    return this.tpvService.getActiveCashSession(deviceId).pipe(
      tap((session) => {
        if (session) {
          this.setActiveSession(session);
          this.setState(CajaState.ACTIVA);
          this.setOrphanSession(null);
        }
      }),
      catchError(() => of(null)),
      finalize(() => this.setLoading(false))
    );
  }

  public loadLastClosedSession(): Observable<{ last_closed: LastClosedData | null; orphan_session: OrphanSessionData | null } | null> {
    return this.tpvService.getLastClosedCashSession().pipe(
      tap((response) => {
        if (response?.last_closed) {
          this.setLastClosed(response.last_closed);
        }
        if (response?.orphan_session) {
          this.setOrphanSession(response.orphan_session);
        }
      }),
      catchError(() => of(null))
    );
  }

  public openSession(payload: OpenSessionPayload): Observable<TpvCashSession> {
    this.setLoading(true);

    return this.tpvService.openCashSession(payload).pipe(
      tap((session) => {
        this.setActiveSession(session);
        this.setState(CajaState.ACTIVA);
      }),
      finalize(() => this.setLoading(false))
    );
  }

  public closeSession(payload: CloseSessionPayload): Observable<unknown> {
    this.setClosingInProgress(true);

    return this.tpvService.closeCashSession(payload).pipe(
      tap(() => {
        this.setActiveSession(null);
        this.setState(CajaState.PRE_APERTURA);
      }),
      finalize(() => this.setClosingInProgress(false))
    );
  }

  public loadSessionSummary(sessionId: string): Observable<TpvCashSessionSummary> {
    return this.tpvService.getCashSessionSummary(sessionId).pipe(
      tap((summary) => {
        this.setSessionSummary(summary);
      })
    );
  }

  public loadClosedSessions(): Observable<{ sessions: TpvCashSessionListItem[] }> {
    return this.tpvService.listCashSessions();
  }

  public registerMovement(payload: RegisterMovementPayload): Observable<unknown> {
    return this.tpvService.registerCashMovement(payload);
  }

  public startClosing(payload: StartClosingPayload): Observable<{ status: string }> {
    return this.tpvService.startClosingCashSession(payload);
  }

  public cancelClosing(payload: CancelClosingPayload): Observable<{ status: string }> {
    return this.tpvService.cancelClosingCashSession(payload);
  }

  public listCashMovements(sessionId: string): Observable<{ movements: Array<{
    uuid: string;
    type: 'in' | 'out';
    reason_code: string;
    amount_cents: number;
    description: string | null;
    user_id: string;
    created_at: string;
  }> }> {
    return this.tpvService.listCashMovements(sessionId);
  }

  public async refreshActiveSession(deviceId: string): Promise<void> {
    try {
      await firstValueFrom(this.loadActiveSession(deviceId));
    } catch (error) {
      console.error('Error refreshing session:', error);
    }
  }

  public reset(): void {
    this._state.set(CajaState.PRE_APERTURA);
    this._activeSession.set(null);
    this._loading.set(false);
    this._lastClosed.set(null);
    this._orphanSession.set(null);
    this._sessionSummary.set(null);
    this._isClosingInProgress.set(false);
  }
}
