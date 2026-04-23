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
  @Input() currentUserId: string | null = null;
  @Output() closeModal = new EventEmitter<void>();
  @Output() openCash = new EventEmitter<{
    userId: string;
    initialAmountCents: number;
    notes?: string;
  }>();

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
    if (!this.currentUserId) return;
    this.openCash.emit({
      userId: this.currentUserId,
      initialAmountCents: this.initialAmountCents,
      notes: this.notes || undefined,
    });
    this.resetForm();
  }

  public toggleNote(): void {
    this.showNote = !this.showNote;
  }

  public onAmountChange(value: number): void {
    this.initialAmountCents = value;
  }

  private resetForm(): void {
    this.initialAmountCents = 15000;
    this.notes = '';
    this.showNote = false;
  }
}
