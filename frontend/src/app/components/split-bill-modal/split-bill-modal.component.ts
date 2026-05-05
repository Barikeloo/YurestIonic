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
  @Input() chargeSession: ChargeSession | null = null;
  @Input() remainingCents = 0;
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmSplit = new EventEmitter<{ selectedLines: BillLine[]; diner?: number; amount?: number; isEqualPart?: boolean; chargeSessionId?: string }>();
  @Output() sessionUpdated = new EventEmitter<any>();
  @Output() paymentRecorded = new EventEmitter<{ diner: number; amount: number }>();

  public mode: 'equal' | 'lines' | 'diner' = 'equal';
  public parts = 2;
  public assignedLines: BillLine[] = [];
  private chargeSessionLoaded = false;
  public isLoading = false;
  public error: string | null = null;

  constructor(private chargeSessionService: ChargeSessionService) {}

  public ngOnInit(): void {
    this.assignedLines = [...this.lines];
    this.parts = this.diners;
    if (!this.chargeSession) {
      this.loadChargeSession();
    } else {
      this.chargeSessionLoaded = true;
    }
  }

  public ngOnChanges(changes: SimpleChanges): void {
    if (changes['lines'] || changes['diners'] || changes['paidDiners']) {
      this.assignedLines = [...this.lines];
      this.parts = this.diners;
      console.log('SplitBillModal ngOnChanges - diners:', this.diners, 'paidDiners:', this.paidDiners);
    }

    if (changes['chargeSession'] && this.chargeSession) {
      console.log('SplitBillModal chargeSession updated from parent:', this.chargeSession);
      this.chargeSessionLoaded = true;
    }

    if (changes['orderId'] && !changes['orderId'].firstChange) {
      this.chargeSession = null;
      this.loadChargeSession();
    }
  }

  public get remainingDiners(): number {
    if (this.chargeSession) {
      return this.chargeSession.diners_count - this.chargeSession.paid_diner_numbers.length;
    }
    return this.diners - this.paidDiners.length;
  }

  public get unpaidDinerNumbers(): number[] {
    if (this.chargeSession) {
      const allDiners = Array.from({ length: this.chargeSession.diners_count }, (_, i) => i + 1);
      const paidNumbers = this.chargeSession.paid_diner_numbers;
      return allDiners.filter((d) => !paidNumbers.includes(d));
    }
    const allDiners = Array.from({ length: this.diners }, (_, i) => i + 1);
    return allDiners.filter((d) => !this.paidDiners.includes(d));
  }

  public get equalPart(): number {
    if (this.chargeSession) {
      const unpaidCount = this.remainingDiners;
      if (unpaidCount > 0) {
        const recalculated = Math.floor(this.chargeSession.remaining_cents / unpaidCount);
        return recalculated;
      }
      return this.chargeSession.suggested_per_diner_cents;
    }
    if (this.remainingDiners <= 0) return 0;
    return Math.floor(this.total / this.remainingDiners);
  }

  public get remainder(): number {
    if (this.chargeSession) {
      return this.chargeSession.remaining_cents - (this.equalPart * this.remainingDiners);
    }
    return this.total - this.equalPart * this.remainingDiners;
  }

  public get remainingTotal(): number {
    if (this.chargeSession) {
      return this.chargeSession.remaining_cents;
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

  public get canUpdateDiners(): boolean {
    return this.chargeSession !== null;
  }

  public get minDinersCount(): number {
    if (!this.chargeSession) return 1;
    return Math.max(1, this.chargeSession.paid_diner_numbers.length);
  }

  public decreaseDinersCount(): void {
    if (!this.chargeSession || !this.canUpdateDiners) return;
    const newCount = this.chargeSession.diners_count - 1;
    if (newCount < this.minDinersCount) return;
    this.updateDinersCountOnBackend(newCount);
  }

  public increaseDinersCount(): void {
    if (!this.chargeSession || !this.canUpdateDiners) return;
    const newCount = this.chargeSession.diners_count + 1;
    if (newCount > 20) return;
    this.updateDinersCountOnBackend(newCount);
  }

  private updateDinersCountOnBackend(newCount: number): void {
    if (!this.chargeSession) return;
    this.isLoading = true;
    this.chargeSessionService.updateDiners(this.chargeSession.id, { diners_count: newCount }).subscribe({
      next: () => {
        this.loadChargeSession();
      },
      error: (error) => {
        console.error('Error updating diners count', error);
        this.error = 'No se pudo actualizar el número de comensales';
        this.isLoading = false;
      }
    });
  }

  public chargeEqualPart(dinerNum: number): void {
    const partAmount = this.getDinerAmount(dinerNum);
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

    this.chargeSessionService.getCurrentChargeSession(this.orderId).subscribe({
      next: (session) => {
        console.log('Charge session loaded:', session);
        this.chargeSession = session;
        this.sessionUpdated.emit(session);
        this.syncPaidDinersFromSession();
        this.isLoading = false;
      },
      error: (error) => {
        if (error.status === 404) {
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

  private createChargeSession(): void {
    if (!this.orderId || !this.userId) return;

    this.chargeSessionService.createChargeSession({
      order_id: this.orderId,
      opened_by_user_id: this.userId,
      diners_count: this.diners,
      remaining_cents: this.remainingCents > 0 ? this.remainingCents : undefined,
    }).subscribe({
      next: (session) => {
        console.log('Charge session created:', session);
        this.chargeSession = session;
        this.sessionUpdated.emit(session);
        this.isLoading = false;
      },
      error: (error) => {
        console.error('Error creating charge session:', error);

        this.error = 'Error al crear la sesión de cobro';
        this.isLoading = false;
      },
    });
  }

  private syncPaidDinersFromSession(): void {
    if (!this.chargeSession) return;

    const paidDinerNumbers = this.chargeSession.paid_diner_numbers;
    console.log('Syncing paid diners from session:', paidDinerNumbers);
  }

  public isDinerPaid(dinerNum: number): boolean {
    if (!this.chargeSession) return this.paidDiners.includes(dinerNum);
    return this.chargeSession.paid_diner_numbers.includes(dinerNum);
  }

  public getDinerAmount(dinerNum: number): number {
    if (!this.chargeSession) {
      const unpaidNumbers = this.unpaidDinerNumbers;
      const index = unpaidNumbers.indexOf(dinerNum);

      return this.equalPart + (index === unpaidNumbers.length - 1 ? this.remainder : 0);
    }

    const unpaidNumbers = this.unpaidDinerNumbers;
    const isLastUnpaid = unpaidNumbers.length > 0 && unpaidNumbers[unpaidNumbers.length - 1] === dinerNum;

    if (isLastUnpaid) {
      const othersAmount = this.equalPart * (unpaidNumbers.length - 1);
      const lastAmount = this.chargeSession.remaining_cents - othersAmount;

      return lastAmount;
    }

    return this.equalPart;
  }

  public get isSessionCompleted(): boolean {
    if (!this.chargeSession) return false;
    return this.chargeSession.paid_diner_numbers.length >= this.chargeSession.diners_count;
  }
}
