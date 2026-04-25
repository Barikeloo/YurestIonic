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
  private hasCompleted = false;

  public ngOnChanges(): void {
    if (this.isOpen) {
      this.hasCompleted = false;
      this.startAutoClose();
    } else {
      this.clearAutoClose();
    }
  }

  public onOverlayClick(): void {
    this.emit();
  }

  private startAutoClose(): void {
    this.clearAutoClose();
    this.autoCloseTimeout = setTimeout(() => this.emit(), 2500);
  }

  private emit(): void {
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

  public ngOnDestroy(): void {
    this.clearAutoClose();
  }
}
