import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { BadgeComponent } from '../badge/badge.component';
import { BtnComponent } from '../btn/btn.component';
import { CardComponent } from '../card/card.component';
import { DinersStatusComponent } from '../diners-status/diners-status.component';
import { ChargeSessionService, ChargeSession } from '../../services/charge-session.service';

export interface BillLine {
  id?: string;
  name: string;
  price: number;
  diner?: number | null;
}

@Component({
  selector: 'app-split-bill-modal',
  templateUrl: './split-bill-modal.component.html',
  styleUrls: ['./split-bill-modal.component.scss'],
  imports: [CommonModule, FormsModule, CardComponent, BtnComponent, BadgeComponent, DinersStatusComponent],
  standalone: true,
})
export class SplitBillModalComponent implements OnChanges, OnInit {
  @Input() isOpen = false;
  @Input() total = 0;
  @Input() tableLabel = '';
  @Input() lines: BillLine[] = [];
  @Input() diners = 2;
  @Input() paidDiners: number[] = [];
  @Input() orderId: string | null = null;
  @Input() userId: string | null = null;
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmSplit = new EventEmitter<{ selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }>();
  @Output() paymentRecorded = new EventEmitter<{ diner: number; amount: number }>();

  public mode: 'equal' | 'lines' | 'diner' = 'equal';
  public parts = 2;
  public assignedLines: BillLine[] = [];
  public chargeSession: ChargeSession | null = null;
  public isLoading = false;
  public error: string | null = null;

  constructor(private chargeSessionService: ChargeSessionService) {}

  public ngOnInit(): void {
    this.assignedLines = [...this.lines];
    this.parts = this.diners;
    console.log('SplitBillModal ngOnInit - diners:', this.diners, 'paidDiners:', this.paidDiners);
    this.loadChargeSession();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes['lines'] || changes['diners'] || changes['paidDiners']) {
      this.assignedLines = [...this.lines];
      this.parts = this.diners;
      console.log('SplitBillModal ngOnChanges - diners:', this.diners, 'paidDiners:', this.paidDiners);
    }

    // Si cambia el orderId (nueva mesa), recargamos la sesión de cobro
    if (changes['orderId'] && !changes['orderId'].firstChange) {
      this.chargeSession = null;
      this.loadChargeSession();
    }
  }

  public get remainingDiners(): number {
    if (this.chargeSession) {
      return this.chargeSession.diners_count - this.chargeSession.paid_diners_count;
    }
    return this.diners - this.paidDiners.length;
  }

  public get unpaidDinerNumbers(): number[] {
    const allDiners = Array.from({ length: this.diners }, (_, i) => i + 1);
    if (this.chargeSession) {
      const paidNumbers = this.chargeSession.paid_diners.map((p) => p.diner_number);
      return allDiners.filter((d) => !paidNumbers.includes(d));
    }
    return allDiners.filter((d) => !this.paidDiners.includes(d));
  }

  public get equalPart(): number {
    if (this.chargeSession) {
      return this.chargeSession.amount_per_diner;
    }
    if (this.remainingDiners <= 0) return 0;
    return Math.floor(this.total / this.remainingDiners);
  }

  public get remainder(): number {
    if (this.chargeSession) {
      return this.chargeSession.remaining_amount - (this.equalPart * this.remainingDiners);
    }
    return this.total - this.equalPart * this.remainingDiners;
  }

  public get remainingTotal(): number {
    if (this.chargeSession) {
      return this.chargeSession.remaining_amount;
    }
    return this.total;
  }

  public assignLine(id: string | undefined, diner: number): void {
    if (!id) return;
    this.assignedLines = this.assignedLines.map((l) =>
      l.id === id ? { ...l, diner: l.diner === diner ? null : diner } : l
    );
  }

  public getSubtotal(diner: number): number {
    return this.assignedLines.filter((l) => l.diner === diner).reduce((sum, l) => sum + l.price, 0);
  }

  public getDinerLines(diner: number): BillLine[] {
    return this.assignedLines.filter((l) => l.diner === diner);
  }

  public getSubaccountLines(diner: number): BillLine[] {
    return this.assignedLines.filter((l) => l.diner === diner);
  }

  public getCommonLines(): BillLine[] {
    return this.assignedLines.filter((l) => !l.diner);
  }

  public decreaseParts(): void {
    this.parts = Math.max(2, this.parts - 1);
  }

  public increaseParts(): void {
    this.parts = Math.min(10, this.parts + 1);
  }

  public chargeDiner(diner: number): void {
    const selectedLines = this.assignedLines.filter((l) => l.diner === diner);
    if (selectedLines.length > 0) {
      this.confirmSplit.emit({ selectedLines, diner, chargeSessionId: this.chargeSession?.id });
      this.closeModal.emit();
    }
  }

  public chargeEqualPart(dinerNum: number): void {
    // El monto real lo calculamos con getDinerAmount (gestiona correctamente el último comensal)
    const partAmount = this.getDinerAmount(dinerNum);

    // Para partes iguales emitimos todas las líneas en el primer cobro; en los
    // siguientes el padre ya tiene la orden cargada y gestiona el parcial.
    this.confirmSplit.emit({
      selectedLines: this.paidDiners.length === 0 ? this.assignedLines : [],
      diner: dinerNum,
      amount: partAmount,
      isEqualPart: true,
      chargeSessionId: this.chargeSession?.id,
    });
    this.closeModal.emit();
  }

  public onConfirm(): void {
    this.confirmSplit.emit({ selectedLines: this.assignedLines, chargeSessionId: this.chargeSession?.id });
    this.closeModal.emit();
  }

  public onClose(): void {
    const hasAssignedLines = this.mode === 'lines' && this.assignedLines.some((l) => l.diner != null);
    if (hasAssignedLines && !confirm('Hay líneas asignadas. ¿Cerrar sin cobrar?')) return;
    this.closeModal.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  private formatCentsToEuros(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + ' €';
  }

  /**
   * Cargar o crear la sesión de cobro desde el backend
   */
  public loadChargeSession(): void {
    if (!this.orderId || !this.userId) {
      console.log('No orderId or userId, skipping charge session load');
      return;
    }

    this.isLoading = true;
    this.error = null;

    // Primero intentamos obtener la sesión activa existente
    this.chargeSessionService.getActiveChargeSession(this.orderId).subscribe({
      next: (session) => {
        console.log('Charge session loaded:', session);
        this.chargeSession = session;
        this.syncPaidDinersFromSession();
        this.isLoading = false;
      },
      error: (error) => {
        if (error.status === 404) {
          // No hay sesión activa, creamos una nueva
          this.createChargeSession();
        } else {
          console.error('Error loading charge session:', {
            status: error?.status,
            message: error?.message,
            error: error?.error,
            url: error?.url,
          });
          const detail = error?.error?.message || error?.message || `HTTP ${error?.status}`;
          this.error = `Error al cargar la sesión de cobro (${detail})`;
          this.isLoading = false;
        }
      },
    });
  }

  /**
   * Crear una nueva sesión de cobro
   */
  private createChargeSession(): void {
    if (!this.orderId || !this.userId) return;

    this.chargeSessionService.createChargeSession({
      order_id: this.orderId,
      opened_by_user_id: this.userId,
      diners_count: this.diners,
    }).subscribe({
      next: (session) => {
        console.log('Charge session created:', session);
        this.chargeSession = session;
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Error creating charge session:', error);

        // Backend validation: order already has partial payments
        const err = error?.error || error;
        if (err?.error_code === 'order_has_partial_payments') {
          const amount = this.formatCentsToEuros(err.paid_amount_cents || 0);

          alert(
            `Esta cuenta ya tiene ${amount} cobrados.\n\n` +
            `No se puede dividir a partes iguales mientras existan pagos previos.\n\n` +
            `Opciones:\n` +
            `• Continúa cobrando manualmente\n` +
            `• O cancela los pagos previos`
          );

          this.error = null;
          this.isLoading = false;
          return;
        }

        this.error = 'Error al crear la sesión de cobro';
        this.isLoading = false;
      },
    });
  }

  /**
   * Sincronizar los comensales pagados desde la sesión del backend
   */
  private syncPaidDinersFromSession(): void {
    if (!this.chargeSession) return;

    const paidDinerNumbers = this.chargeSession.paid_diners.map((p) => p.diner_number);
    // Actualizar el input paidDiners (esto debería ser manejado por el componente padre)
    console.log('Syncing paid diners from session:', paidDinerNumbers);
  }

  /**
   * Verificar si un comensal ya pagó según el backend
   */
  public isDinerPaid(dinerNum: number): boolean {
    if (!this.chargeSession) return this.paidDiners.includes(dinerNum);
    return this.chargeSession.paid_diners.some((p) => p.diner_number === dinerNum);
  }

  /**
   * Obtener el monto a pagar para un comensal específico
   */
  public getDinerAmount(dinerNum: number): number {
    if (!this.chargeSession) {
      // Fallback al cálculo local
      const unpaidNumbers = this.unpaidDinerNumbers;
      const index = unpaidNumbers.indexOf(dinerNum);

      return this.equalPart + (index === unpaidNumbers.length - 1 ? this.remainder : 0);
    }

    // El último comensal no pagado paga el resto acumulado
    const unpaidNumbers = this.unpaidDinerNumbers;
    const isLastUnpaid = unpaidNumbers.length > 0 && unpaidNumbers[unpaidNumbers.length - 1] === dinerNum;

    if (isLastUnpaid) {
      // Cuánto queda después de que paguen los demás pendientes excepto este
      const othersAmount = this.chargeSession.amount_per_diner * (unpaidNumbers.length - 1);
      const lastAmount = this.chargeSession.remaining_amount - othersAmount;

      return Math.max(lastAmount, this.chargeSession.amount_per_diner);
    }

    return this.chargeSession.amount_per_diner;
  }

  /**
   * Verificar si todos los comensales han pagado (sesión completada)
   */
  public get isSessionCompleted(): boolean {
    if (!this.chargeSession) return false;
    return this.chargeSession.paid_diners_count >= this.chargeSession.diners_count;
  }
}
