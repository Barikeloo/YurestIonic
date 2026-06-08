import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges, inject, HostListener } from '@angular/core';

import { FormsModule } from '@angular/forms';
import { BtnComponent } from '../../../../shared/components/btn/btn.component';
import { NumpadComponent } from '../../../../shared/components/numpad/numpad.component';
import { PaymentMethod } from '../../../../core/enums/payment-method.enum';
import { ToastService } from '../../../../core/services/toast.service';

export interface OrderLineModifier {
  name: string;
  price?: number;
}

export interface OrderLine {
  id?: string;
  name: string;

  price: number;
  diner?: number | null;
  quantity?: number;
  unitPrice?: number;
  variantName?: string | null;

  taxPercentage?: number;
  modifiers?: OrderLineModifier[] | null;
}

interface TaxLine {
  rate: number;
  base: number;
  tax: number;
}

interface MethodOption {
  value: PaymentMethod;
  label: string;
}

type SideOption = 'detail' | 'partial' | 'tip' | 'fiscal' | null;

@Component({
  selector: 'app-cobrar-modal',
  templateUrl: './cobrar-modal.component.html',
  styleUrls: ['./cobrar-modal.component.scss'],
  imports: [FormsModule, BtnComponent, NumpadComponent],
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

  private readonly toastService = inject(ToastService);

  public readonly PaymentMethod = PaymentMethod;
  public readonly Math = Math;

  public method: PaymentMethod = PaymentMethod.CASH;
  public inputAmount = 0;
  public tip = 0;

  public sideOption: SideOption = null;

  public showSecondaryMethods = false;

  public fiscalNif = '';
  public fiscalName = '';
  public fiscalAddress = '';

  public readonly primaryMethods: MethodOption[] = [
    { value: PaymentMethod.CASH, label: 'Efectivo' },
    { value: PaymentMethod.CARD, label: 'Tarjeta' },
    { value: PaymentMethod.BIZUM, label: 'Bizum' },
  ];

  public readonly secondaryMethods: MethodOption[] = [
    { value: PaymentMethod.MIXED, label: 'Mixto' },
    { value: PaymentMethod.INVITATION, label: 'Invitación' },
  ];

  public readonly cashQuickAmounts = [4000, 5000, 6000, 10000, 20000, 50000];
  public readonly tipPresets = [100, 200, 500, 1000];

  public get partialMode(): boolean { return this.sideOption === 'partial'; }
  public get tipMode(): boolean { return this.sideOption === 'tip'; }
  public get fiscalMode(): boolean { return this.sideOption === 'fiscal'; }
  public get showDetail(): boolean { return this.sideOption === 'detail'; }
  public get isWide(): boolean { return this.sideOption !== null; }

  public get effectiveAmount(): number {
    if (this.partialMode) return Math.min(this.inputAmount, this.total);
    return this.total;
  }

  public get change(): number {
    if (this.method !== PaymentMethod.CASH) return 0;
    const cashIn = this.partialMode ? this.inputAmount : (this.inputAmount || this.total);
    return cashIn - this.effectiveAmount;
  }

  public get remainingAfter(): number {
    return Math.max(0, this.total - this.effectiveAmount);
  }

  public get methodLabel(): string {
    return [...this.primaryMethods, ...this.secondaryMethods].find(m => m.value === this.method)?.label ?? '';
  }

  public get isSecondaryActive(): boolean {
    return this.secondaryMethods.some(m => m.value === this.method);
  }

  public get activeMethodIndex(): number {
    if (this.isSecondaryActive) return this.primaryMethods.length;
    const idx = this.primaryMethods.findIndex(m => m.value === this.method);
    return idx >= 0 ? idx : 0;
  }

  public modifiersTotal(line: OrderLine): number {
    if (!line.modifiers) return 0;
    return line.modifiers.reduce((acc, m) => acc + (m.price ?? 0), 0);
  }

  public lineTotal(line: OrderLine): number {
    return line.price + this.modifiersTotal(line);
  }

  public get taxBreakdown(): TaxLine[] {
    const grouped = new Map<number, number>();
    let hasAnyTax = false;
    for (const line of this.lines) {
      if (line.taxPercentage === undefined || line.taxPercentage === null) continue;
      hasAnyTax = true;
      const rate = line.taxPercentage;
      const grossLine = this.lineTotal(line);
      grouped.set(rate, (grouped.get(rate) ?? 0) + grossLine);
    }
    if (!hasAnyTax) return [];

    return Array.from(grouped.entries())
      .sort((a, b) => a[0] - b[0])
      .map(([rate, gross]) => {
        const base = Math.round(gross / (1 + rate / 100));
        const tax = gross - base;
        return { rate, base, tax };
      });
  }

  public get sidePanelTitle(): string {
    switch (this.sideOption) {
      case 'detail': return `Detalle (${this.lines.length} ${this.lines.length === 1 ? 'línea' : 'líneas'})`;
      case 'partial': return 'Cobro parcial';
      case 'tip': return 'Propina';
      case 'fiscal': return 'Factura completa';
      default: return '';
    }
  }

  public get confirmLabel(): string {
    if (this.isProcessing) return 'Procesando…';
    const total = this.effectiveAmount + (this.tipMode ? this.tip : 0);
    const verb = this.method === PaymentMethod.INVITATION ? 'Invitar' : 'Cobrar';
    return `${verb} ${this.formatCents(total)} €`;
  }

  public get canConfirm(): boolean {
    if (this.isProcessing) return false;
    if (this.effectiveAmount <= 0) return false;
    if (this.method === PaymentMethod.CASH && this.partialMode && this.inputAmount < this.effectiveAmount) return false;
    return true;
  }

  public ngOnChanges(changes: SimpleChanges): void {
    const justOpened = changes['isOpen'] && this.isOpen && !changes['isOpen'].previousValue;
    if (justOpened) {
      this.resetForm();
      this.inputAmount = this.total;
    }
    const totalChangedWhileOpen = changes['total'] && this.isOpen && !justOpened;
    if (totalChangedWhileOpen && !this.partialMode) {
      this.inputAmount = this.total;
    }
  }

  @HostListener('document:keydown.escape')
  public onEscape(): void {
    if (this.isOpen) {
      if (this.sideOption !== null) {
        this.sideOption = null;
      } else {
        this.onClose();
      }
    }
  }

  public onClose(): void {
    this.closeModal.emit();
  }

  public selectMethod(method: PaymentMethod): void {
    this.method = method;
    this.showSecondaryMethods = false;

    if (method === PaymentMethod.INVITATION && this.tipMode) {
      this.sideOption = null;
    }
    if (!this.partialMode) {
      this.inputAmount = this.total;
    }
  }

  public toggleSecondaryMethods(): void {
    this.showSecondaryMethods = !this.showSecondaryMethods;
  }

  public toggleSide(opt: Exclude<SideOption, null>): void {
    if (this.sideOption === opt) {
      this.sideOption = null;
      if (opt === 'partial') this.inputAmount = this.total;
      return;
    }

    if (this.sideOption === 'partial' && opt !== 'partial') {
      this.inputAmount = this.total;
    }
    this.sideOption = opt;
    if (opt === 'partial') {
      this.inputAmount = 0;
    }
  }

  public closeSide(): void {
    if (this.partialMode) this.inputAmount = this.total;
    this.sideOption = null;
  }

  public onAmountChange(value: number): void {
    this.inputAmount = value;
  }

  public setQuickAmount(amount: number): void {
    this.inputAmount = amount;
  }

  public setTip(amount: number): void {
    this.tip = this.tip === amount ? 0 : amount;
  }

  public onConfirm(): void {
    const tip = this.tipMode ? this.tip : 0;
    const amountToPay = this.effectiveAmount;

    if (amountToPay <= 0) {
      this.toastService.presentWarning('El importe a cobrar debe ser mayor a 0.');
      return;
    }

    if (this.method === PaymentMethod.CASH && this.partialMode) {
      if (this.inputAmount > this.total * 2 && this.total > 0) {
        this.toastService.presentWarning('El importe entregado parece demasiado alto. Por favor, verifíquelo.');
        return;
      }
      if (this.inputAmount < amountToPay) {
        this.toastService.presentWarning('El importe entregado es inferior al importe a cobrar.');
        return;
      }
    }

    if (tip > amountToPay) {
      this.toastService.presentWarning('La propina no puede superar el importe a cobrar.');
      return;
    }

    const isManualPartial = amountToPay < this.total;

    this.confirmPayment.emit({
      method: this.method,
      amount: amountToPay + tip,
      tip: tip > 0 ? tip : undefined,
      isManualPartial,
    });
  }

  public onSplitBill(): void {
    this.splitBill.emit();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',');
  }

  public abs(value: number): number {
    return Math.abs(value);
  }

  private resetForm(): void {
    this.method = PaymentMethod.CASH;
    this.inputAmount = 0;
    this.tip = 0;
    this.sideOption = null;
    this.showSecondaryMethods = false;
    this.fiscalNif = '';
    this.fiscalName = '';
    this.fiscalAddress = '';
  }
}
