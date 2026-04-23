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
  @Output() complete = new EventEmitter<void>();

  private autoCloseTimeout: any;

  public ngOnChanges(): void {
    if (this.isOpen) {
      this.startAutoClose();
    } else {
      this.clearAutoClose();
    }
  }

  private startAutoClose(): void {
    this.clearAutoClose();
    this.autoCloseTimeout = setTimeout(() => {
      this.complete.emit();
    }, 2500);
  }

  private clearAutoClose(): void {
    if (this.autoCloseTimeout) {
      clearTimeout(this.autoCloseTimeout);
      this.autoCloseTimeout = null;
    }
  }

  public ngOnDestroy(): void {
    this.clearAutoClose();
  }
}
