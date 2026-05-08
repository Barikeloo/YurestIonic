import { Injectable, signal, computed, inject } from '@angular/core';
import { Observable, Subject, takeUntil } from 'rxjs';
import { ChargeSessionService, ChargeSession, RecordPaymentRequest, RecordPaymentResponse, CreateChargeSessionRequest, UpdateDinersRequest, UpdateDinersResponse, CancelChargeSessionRequest, CancelChargeSessionResponse } from '../services/charge-session.service';
import { TpvService, TpvOrder, TpvOrderLine } from '../services/tpv.service';
import { AuthService } from '../../../core/services/auth.service';
import { PaymentState } from '../../../core/enums/payment-state.enum';

@Injectable({
  providedIn: 'root'
})
export class CajaPaymentFacade {
  private readonly chargeSessionService = inject(ChargeSessionService);
  private readonly tpvService = inject(TpvService);
  private readonly authService = inject(AuthService);

  private readonly destroy$ = new Subject<void>();

  // Signals for payment state
  private readonly state = signal<PaymentState>(PaymentState.IDLE);
  private readonly currentChargeSession = signal<{ id: string; amountPerDiner: number } | null>(null);
  private readonly currentDinerNumber = signal<number | null>(null);
  private readonly loadedChargeSession = signal<ChargeSession | null>(null);
  private readonly isProcessingPayment = signal<boolean>(false);
  private readonly error = signal<string | null>(null);

  // Readonly signals for external consumption
  public readonly loading = computed(() => this.state() === PaymentState.LOADING);
  public readonly processing = computed(() => this.state() === PaymentState.PROCESSING);
  public readonly success = computed(() => this.state() === PaymentState.SUCCESS);
  public readonly paymentState = computed(() => this.state());
  public readonly chargeSession = computed(() => this.currentChargeSession());
  public readonly dinerNumber = computed(() => this.currentDinerNumber());
  public readonly session = computed(() => this.loadedChargeSession());
  public readonly isProcessing = computed(() => this.isProcessingPayment());
  public readonly paymentError = computed(() => this.error());

  // Getters for compatibility
  public get currentChargeSessionValue(): { id: string; amountPerDiner: number } | null {
    return this.currentChargeSession();
  }

  public get currentDinerNumberValue(): number | null {
    return this.currentDinerNumber();
  }

  public get loadedChargeSessionValue(): ChargeSession | null {
    return this.loadedChargeSession();
  }

  public get isProcessingPaymentValue(): boolean {
    return this.isProcessingPayment();
  }

  // State setters
  public setState(value: PaymentState): void {
    this.state.set(value);
  }

  public setCurrentChargeSession(value: { id: string; amountPerDiner: number } | null): void {
    this.currentChargeSession.set(value);
  }

  public setCurrentDinerNumber(value: number | null): void {
    this.currentDinerNumber.set(value);
  }

  public setLoadedChargeSession(value: ChargeSession | null): void {
    this.loadedChargeSession.set(value);
  }

  public setIsProcessingPayment(value: boolean): void {
    this.isProcessingPayment.set(value);
  }

  public setError(value: string | null): void {
    this.error.set(value);
  }

  // Payment methods
  public createChargeSession(request: CreateChargeSessionRequest): Observable<ChargeSession> {
    this.setState(PaymentState.LOADING);
    return this.chargeSessionService.createChargeSession(request);
  }

  public getCurrentChargeSession(orderId: string): Observable<ChargeSession> {
    return this.chargeSessionService.getCurrentChargeSession(orderId);
  }

  public recordPayment(sessionId: string, request: RecordPaymentRequest): Observable<RecordPaymentResponse> {
    this.setState(PaymentState.PROCESSING);
    this.setIsProcessingPayment(true);
    return this.chargeSessionService.recordPayment(sessionId, request).pipe(
      takeUntil(this.destroy$)
    );
  }

  public updateDiners(sessionId: string, request: UpdateDinersRequest): Observable<UpdateDinersResponse> {
    return this.chargeSessionService.updateDiners(sessionId, request);
  }

  public cancelChargeSession(sessionId: string, request: CancelChargeSessionRequest): Observable<CancelChargeSessionResponse> {
    return this.chargeSessionService.cancelChargeSession(sessionId, request);
  }

  // Helper methods
  public getDeviceId(): string {
    return this.authService.getDeviceId();
  }

  public getOrder(orderId: string): Observable<TpvOrder> {
    return this.tpvService.getOrder(orderId);
  }

  public getOrderTotal(orderId: string): Observable<{ total_cents: number }> {
    return this.tpvService.getOrderTotal(orderId);
  }

  public getOrderPaidTotal(orderId: string): Observable<{ total_cents: number }> {
    return this.tpvService.getOrderPaidTotal(orderId);
  }

  public getOrderLines(orderId: string): Observable<TpvOrderLine[]> {
    return this.tpvService.getOrderLines(orderId);
  }

  public reset(): void {
    this.setState(PaymentState.IDLE);
    this.setCurrentChargeSession(null);
    this.setCurrentDinerNumber(null);
    this.setLoadedChargeSession(null);
    this.setIsProcessingPayment(false);
    this.setError(null);
  }

  public ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }
}
