import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CardComponent } from '../card/card.component';
import { BtnComponent } from '../btn/btn.component';

export interface CashMovement {
  id: string;
  type: 'in' | 'out';
  reason: string;
  time: string;
  user: string;
  amount: number;
}

@Component({
  selector: 'app-movimientos-list',
  templateUrl: './movimientos-list.component.html',
  styleUrls: ['./movimientos-list.component.scss'],
  imports: [CommonModule, CardComponent, BtnComponent],
  standalone: true,
})
export class MovimientosListComponent {
  @Input() movements: CashMovement[] = [];
  @Output() addEntrada = new EventEmitter<void>();
  @Output() addSalida = new EventEmitter<void>();

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }
}
