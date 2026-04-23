import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CardComponent } from '../card/card.component';

export interface MethodBreakdown {
  [key: string]: number;
}

@Component({
  selector: 'app-method-bar',
  templateUrl: './method-bar.component.html',
  styleUrls: ['./method-bar.component.scss'],
  imports: [CommonModule, CardComponent],
  standalone: true,
})
export class MethodBarComponent {
  @Input() methods: MethodBreakdown = {};

  private methodColors: { [key: string]: string } = {
    cash: '#1a9e5a',
    card: '#0077cc',
    bizum: '#ff4d4d',
    transfer: '#7a7a7a',
    gift: '#a0a0a0',
  };

  private methodLabels: { [key: string]: string } = {
    cash: 'Efectivo',
    card: 'Tarjeta',
    bizum: 'Bizum',
    transfer: 'Transferencia',
    gift: 'Cheque regalo',
  };

  public getMethodColor(key: string): string {
    return this.methodColors[key] || '#5a5a5a';
  }

  public getMethodLabel(key: string): string {
    return this.methodLabels[key] || key;
  }

  public getTotal(): number {
    return Object.values(this.methods).reduce((a, b) => a + b, 0);
  }

  public getPercentage(value: number): number {
    const total = this.getTotal();
    return total > 0 ? Math.round((value / total) * 100) : 0;
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }
}
