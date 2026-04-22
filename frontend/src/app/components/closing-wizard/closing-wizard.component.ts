import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NumpadComponent } from '../numpad/numpad.component';
import { AmountDisplayComponent } from '../amount-display/amount-display.component';
import { BtnComponent } from '../btn/btn.component';
import { CardComponent } from '../card/card.component';
import { KpiCardComponent } from '../kpi-card/kpi-card.component';
import { BadgeComponent } from '../badge/badge.component';

export interface ZReportData {
  tickets: number;
  diners: number;
  gross: number;
  discounts: number;
  net: number;
  cash: number;
  card: number;
  bizum: number;
  invitation: number;
  invitations: number;
  invValue: number;
  cancellations: number;
  tipsCard: number;
  initial: number;
  movIn: number;
  movOut: number;
}

export interface ClosingStep {
  n: number;
  l: string;
}

@Component({
  selector: 'app-closing-wizard',
  templateUrl: './closing-wizard.component.html',
  styleUrls: ['./closing-wizard.component.scss'],
  imports: [
    CommonModule,
    FormsModule,
    NumpadComponent,
    AmountDisplayComponent,
    BtnComponent,
    CardComponent,
    KpiCardComponent,
    BadgeComponent,
  ],
  standalone: true,
})
export class ClosingWizardComponent {
  @Input() isOpen = false;
  @Input() restaurantName = '';
  @Input() deviceName = '';
  @Input() operatorName = '';
  @Input() expectedAmount = 0;
  @Input() zData: ZReportData | null = null;
  @Output() closeModal = new EventEmitter<void>();
  @Output() completeClosing = new EventEmitter<{
    countedAmount: number;
    discrepancyReason?: string;
  }>();

  public step = 1;
  public counted = 0;
  public reason = '';
  public steps: ClosingStep[] = [
    { n: 1, l: 'Contar' },
    { n: 2, l: 'Justificar' },
    { n: 3, l: 'Revisar Z' },
  ];

  public reasons = [
    'Error en el conteo',
    'Cambio no registrado',
    'Propina no declarada',
    'Ticket anulado',
    'Otro',
  ];

  public get discrepancy(): number {
    return this.counted - this.expectedAmount;
  }

  public get hasDiscrepancy(): boolean {
    return this.counted > 0 && Math.abs(this.discrepancy) > 0;
  }

  public get discrepancyColor(): string {
    return this.discrepancy < 0 ? '#ff4d4d' : '#1a9e5a';
  }

  public onClose(): void {
    this.closeModal.emit();
    this.reset();
  }

  public goNext(): void {
    if (this.step === 1) {
      this.step = this.hasDiscrepancy ? 2 : 3;
    } else if (this.step === 2) {
      this.step = 3;
    } else if (this.step === 3) {
      this.completeClosing.emit({
        countedAmount: this.counted,
        discrepancyReason: this.reason || undefined,
      });
      this.closeModal.emit();
      this.reset();
    }
  }

  public goPrevious(): void {
    if (this.step === 2) {
      this.step = 1;
    } else if (this.step === 3) {
      this.step = this.hasDiscrepancy ? 2 : 1;
    }
  }

  public onCountedChange(value: number): void {
    this.counted = value;
  }

  private reset(): void {
    this.step = 1;
    this.counted = 0;
    this.reason = '';
  }

  public formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }

  public formatNum(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
}
