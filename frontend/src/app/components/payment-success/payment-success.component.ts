import { Component, Input, Output, EventEmitter } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-payment-success',
  templateUrl: './payment-success.component.html',
  styleUrls: ['./payment-success.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class PaymentSuccessComponent {
  @Input() isOpen = false;
  @Input() paymentTicketText: string | null = null;
  @Input() finalTicketText: string | null = null;
  @Input() showFinalTicket = false;
  @Output() complete = new EventEmitter<void>();
  @Output() printPayment = new EventEmitter<void>();
  @Output() printFinal = new EventEmitter<void>();

  private autoCloseTimeout: any;
  private hasCompleted = false;

  public ngOnChanges(): void {
    if (this.isOpen) {
      this.hasCompleted = false;
      if (!this.hasPreview()) {
        this.startAutoClose();
      }
    } else {
      this.clearAutoClose();
    }
  }

  public onOverlayClick(): void {
    this.onCloseClick();
  }

  public onPrintPayment(): void {
    this.printPayment.emit();
  }

  public onPrintFinal(): void {
    this.printFinal.emit();
  }

  private startAutoClose(): void {
    this.clearAutoClose();
    this.autoCloseTimeout = setTimeout(() => this.onCloseClick(), 2500);
  }

  public onCloseClick(): void {
    if (this.hasCompleted) return;
    this.hasCompleted = true;
    this.clearAutoClose();
    this.complete.emit();
  }

  private clearAutoClose(): void {
    if (this.autoCloseTimeout) {
      clearTimeout(this.autoCloseTimeout);
      this.autoCloseTimeout = null;
    }
  }

  private hasPreview(): boolean {
    return !!this.paymentTicketText || !!this.finalTicketText;
  }

  public ngOnDestroy(): void {
    this.clearAutoClose();
  }
}
