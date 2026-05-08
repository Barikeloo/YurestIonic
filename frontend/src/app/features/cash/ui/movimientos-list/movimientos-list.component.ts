import { Component, Input, Output, EventEmitter } from '@angular/core';

import { CardComponent } from '../../../../shared/components/card/card.component';
import { BtnComponent } from '../../../../shared/components/btn/btn.component';
import { CashMovementType } from '../../../../core/enums/cash-movement-type.enum';

export interface CashMovement {
  id: string;
  type: CashMovementType;
  reason: string;
  time: string;
  user: string;
  amount: number;
}

@Component({
  selector: 'app-movimientos-list',
  templateUrl: './movimientos-list.component.html',
  styleUrls: ['./movimientos-list.component.scss'],
  imports: [CardComponent, BtnComponent],
  standalone: true,
})
export class MovimientosListComponent {
  protected readonly CashMovementType = CashMovementType;

  @Input() movements: CashMovement[] = [];

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }
}
