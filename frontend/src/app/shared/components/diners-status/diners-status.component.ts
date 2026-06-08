import { Component, Input } from '@angular/core';

export interface DinerPayment {
  number: number;
  paid: boolean;
  amount: number;
}

@Component({
  selector: 'app-diners-status',
  templateUrl: './diners-status.component.html',
  styleUrls: ['./diners-status.component.scss'],
  imports: [],
  standalone: true,
})
export class DinersStatusComponent {
  @Input() diners = 0;
  @Input() total = 0;
  @Input() remainingTotal = 0;
  @Input() paidDiners: number[] = [];
  @Input() compact = false;
  @Input() amountPerDiner: number | null = null;

  @Input() dinerAmounts: Record<number, number> | null = null;

  get paidTotal(): number {

    return Math.max(0, this.total - this.remainingTotal);
  }

  get perDinerAmount(): number {
    if (this.amountPerDiner !== null) return this.amountPerDiner;
    if (this.diners <= 0) return 0;
    return Math.floor(this.total / this.diners);
  }

  get remainingDiners(): number {
    return this.diners - this.paidDiners.length;
  }

  getDinerPayments(): DinerPayment[] {
    const allDiners = Array.from({ length: this.diners }, (_, i) => i + 1);
    return allDiners.map((number) => ({
      number,
      paid: this.paidDiners.includes(number),
      amount: this.dinerAmounts?.[number] ?? this.perDinerAmount,
    }));
  }

  formatCents(cents: number): string {
    if (cents === undefined || cents === null) return '0,00 €';
    const euros = Math.floor(cents / 100);
    const remainingCents = cents % 100;
    return `${euros},${remainingCents.toString().padStart(2, '0')} €`;
  }
}
