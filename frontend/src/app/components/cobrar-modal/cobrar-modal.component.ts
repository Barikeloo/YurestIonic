import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CardComponent } from '../card/card.component';
import { BtnComponent } from '../btn/btn.component';
import { ToggleComponent } from '../toggle/toggle.component';
import { NumpadComponent } from '../numpad/numpad.component';
import { AmountDisplayComponent } from '../amount-display/amount-display.component';
import { DinersStatusComponent } from '../diners-status/diners-status.component';

type PaymentMethod = 'cash' | 'card' | 'bizum' | 'mixed' | 'invitation';

export interface OrderLine {
  id?: string;
  name: string;
  price: number;
  diner?: number | null;
}

@Component({
  selector: 'app-cobrar-modal',
  templateUrl: './cobrar-modal.component.html',
  styleUrls: ['./cobrar-modal.component.scss'],
  imports: [CommonModule, FormsModule, CardComponent, BtnComponent, ToggleComponent, NumpadComponent, AmountDisplayComponent, DinersStatusComponent],
  standalone: true,
})
export class CobrarModalComponent implements OnChanges {
  @Input() isOpen = false;
  @Input() total = 0;
  @Input() tableLabel = '';
  @Input() lines: OrderLine[] = [];
  @Input() isPartialPayment = false;
  @Input() isProcessing = false;
  @Input() diners = 0;
  @Input() paidDiners: number[] = [];
  @Output() closeModal = new EventEmitter<void>();
  @Output() confirmPayment = new EventEmitter<{ method: PaymentMethod; amount: number; tip?: number; isManualPartial?: boolean }>();
  @Output() splitBill = new EventEmitter<void>();

  public Math = Math;
  public method: PaymentMethod = 'cash';
  public inputAmount = 0;
  public tip = 0;
  public showTip = false;
  public showFiscal = false;

  /** El importe efectivo a cobrar: nunca será mayor al total */
  public get effectiveAmount(): number {
    return Math.min(this.inputAmount, this.total);
  }

  public get change(): number {
    if (this.method !== 'cash') return 0;
    return this.inputAmount - this.effectiveAmount;
  }

  public get remainingTotal(): number {
    if (this.diners <= 0) return this.total;
    const perDiner = Math.floor(this.total / this.diners);
    return this.total - (this.paidDiners.length * perDiner);
  }

  public ngOnChanges(changes: SimpleChanges): void {
    const justOpened = changes['isOpen'] && this.isOpen && !changes['isOpen'].previousValue;
    const totalChangedWhileOpen = changes['total'] && this.isOpen;
    if (justOpened || totalChangedWhileOpen) {
      this.inputAmount = this.total;
    }
  }

  private methodLabels: { [key: string]: string } = {
    cash: 'Efectivo',
    card: 'Tarjeta',
    bizum: 'Bizum',
    mixed: 'Mixto',
    invitation: 'Invitación',
  };

  public get methods(): Array<{ value: PaymentMethod; label: string; icon: string }> {
    return [
      { value: 'cash', label: 'Efectivo', icon: 'cash' },
      { value: 'card', label: 'Tarjeta', icon: 'card' },
      { value: 'bizum', label: 'Bizum', icon: 'phone' },
      { value: 'mixed', label: 'Mixto', icon: 'mixed' },
      { value: 'invitation', label: 'Invitación', icon: 'gift' },
    ];
  }



  public onClose(): void {
    this.closeModal.emit();
    this.resetForm();
  }

  public onConfirm(): void {
    const tip = this.showTip ? this.tip : 0;
    const amountToPay = this.effectiveAmount;

    if (amountToPay <= 0) {
      alert('El importe a cobrar debe ser mayor a 0.');
      return;
    }

    if (this.method === 'cash') {
      if (this.inputAmount > this.total * 2 && this.total > 0) {
        alert('El importe introducido parece demasiado alto. Por favor, verifíquelo.');
        return;
      }
    }

    if (tip > amountToPay) {
      alert('La propina no puede superar el importe a cobrar.');
      return;
    }

    const isManualPartial = amountToPay < this.total;

    this.confirmPayment.emit({
      method: this.method,
      amount: amountToPay + tip,
      tip: tip > 0 ? tip : undefined,
      isManualPartial: isManualPartial,
    });
    this.resetForm();
  }

  public onSplitBill(): void {
    this.splitBill.emit();
  }

  public onCashGivenChange(value: number): void {
    this.inputAmount = value;
  }

  public setQuickAmount(amount: number): void {
    this.inputAmount = amount;
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  public abs(value: number): number {
    return Math.abs(value);
  }

  private resetForm(): void {
    this.method = 'cash';
    this.inputAmount = 0;
    this.tip = 0;
    this.showTip = false;
    this.showFiscal = false;
  }
}
