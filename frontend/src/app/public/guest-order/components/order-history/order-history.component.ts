import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';

@Component({
  selector: 'app-order-history',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './order-history.component.html',
  styleUrls: ['./order-history.component.scss'],
})
export class OrderHistoryComponent {
  protected readonly facade = inject(GuestOrderFacade);
  protected readonly checkRequested = signal(false);
  protected readonly checkError = signal<string | null>(null);

  requestCheck(): void {
    this.checkError.set(null);
    this.facade.requestCheck();
    this.checkRequested.set(true);
  }
}
