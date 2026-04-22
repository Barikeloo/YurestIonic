import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NumpadComponent } from '../numpad/numpad.component';
import { AmountDisplayComponent } from '../amount-display/amount-display.component';
import { BtnComponent } from '../btn/btn.component';

@Component({
  selector: 'app-open-cash-modal',
  templateUrl: './open-cash-modal.component.html',
  styleUrls: ['./open-cash-modal.component.scss'],
  imports: [CommonModule, FormsModule, NumpadComponent, AmountDisplayComponent, BtnComponent],
  standalone: true,
})
export class OpenCashModalComponent {
  @Input() isOpen = false;
  @Input() availableUsers: Array<{ id: string; name: string; initials: string }> = [];
  @Output() closeModal = new EventEmitter<void>();
  @Output() openCash = new EventEmitter<{
    userId: string;
    initialAmountCents: number;
    notes?: string;
  }>();

  public selectedUserId: string | null = null;
  public initialAmountCents = 15000;
  public notes = '';
  public showNote = false;

  public onClose(): void {
    this.closeModal.emit();
    this.resetForm();
  }

  public formatCents(cents: number): string {
    return (cents / 100).toFixed(2);
  }

  public onSubmit(): void {
    if (this.selectedUserId && this.initialAmountCents >= 0) {
      this.openCash.emit({
        userId: this.selectedUserId,
        initialAmountCents: this.initialAmountCents,
        notes: this.notes || undefined,
      });
      this.resetForm();
    }
  }

  public selectUser(userId: string): void {
    this.selectedUserId = userId;
  }

  public toggleNote(): void {
    this.showNote = !this.showNote;
  }

  public onAmountChange(value: number): void {
    this.initialAmountCents = value;
  }

  private resetForm(): void {
    this.selectedUserId = null;
    this.initialAmountCents = 15000;
    this.notes = '';
    this.showNote = false;
  }
}
