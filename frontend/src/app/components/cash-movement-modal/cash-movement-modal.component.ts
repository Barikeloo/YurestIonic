import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NumpadComponent } from '../numpad/numpad.component';
import { AmountDisplayComponent } from '../amount-display/amount-display.component';
import { BtnComponent } from '../btn/btn.component';
import { SegmentComponent, SegmentOption } from '../segment/segment.component';

export interface MovementReason {
  value: string;
  label: string;
}

@Component({
  selector: 'app-cash-movement-modal',
  templateUrl: './cash-movement-modal.component.html',
  styles: [`
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.42);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 300;
      backdrop-filter: blur(3px);
    }

    .modal-content {
      background: var(--white);
      border-radius: var(--radius-2xl);
      width: 90%;
      max-width: 520px;
      max-height: 92vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: var(--shadow-lg);
    }

    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--gray-200);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;

      .modal-title {
        font-size: 17px;
        font-weight: 700;
        font-family: var(--font);
        color: var(--black);
      }

      .close-btn {
        border: none;
        background: none;
        font-size: 24px;
        color: var(--gray-400);
        cursor: pointer;
        line-height: 1;
        padding: 0 2px;
      }
    }

    .modal-body {
      overflow-y: auto;
      flex: 1;
      padding: 24px;
    }

    .reason-section {
      margin-top: 16px;
    }

    .section-label {
      font-size: 11px;
      color: var(--gray-400);
      font-family: var(--font);
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .reason-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .reason-chip {
      padding: 6px 14px;
      border-radius: 20px;
      border: 1.5px solid var(--gray-200);
      background: var(--white);
      color: var(--gray-600);
      font-family: var(--font);
      font-size: 13px;
      font-weight: 400;
      cursor: pointer;

      &.selected {
        font-weight: 600;
      }
    }

    .amount-section {
      margin-top: 16px;
    }

    .amount-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      align-items: start;
    }

    .amount-display-wrapper {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .description-textarea {
      margin-top: 14px;
      width: 100%;
      border-radius: 8px;
      border: 1.5px solid var(--gray-200);
      padding: 8px 10px;
      font-family: var(--font);
      font-size: 13px;
      resize: none;
      height: 60px;
      box-sizing: border-box;
      outline: none;
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding: 14px 32px;
      border-top: 1px solid var(--gray-200);
    }
  `],
  imports: [CommonModule, FormsModule, NumpadComponent, AmountDisplayComponent, BtnComponent, SegmentComponent],
  standalone: true,
})
export class CashMovementModalComponent {
  @Input() isOpen = false;
  @Output() closeModal = new EventEmitter<void>();
  @Output() registerMovement = new EventEmitter<{
    type: string;
    reasonCode: string;
    amountCents: number;
    description?: string;
  }>();

  public type: 'in' | 'out' = 'in';
  public reasonCode = 'change_refill';
  public amountCents = 0;
  public description = '';

  public typeOptions: SegmentOption[] = [
    { value: 'in', label: '↑ Entrada' },
    { value: 'out', label: '↓ Salida' },
  ];

  public reasons: { in: MovementReason[]; out: MovementReason[] } = {
    in: [
      { value: 'change_refill', label: 'Reposición cambio' },
      { value: 'tip_declared', label: 'Propina declarada' },
      { value: 'adjustment', label: 'Ajuste' },
      { value: 'other', label: 'Otro' },
    ],
    out: [
      { value: 'sangria', label: 'Sangría al banco' },
      { value: 'supplier_payment', label: 'Pago proveedor' },
      { value: 'tip_declared', label: 'Propina camarero' },
      { value: 'adjustment', label: 'Ajuste' },
      { value: 'other', label: 'Otro' },
    ],
  };

  public get currentReasons(): MovementReason[] {
    return this.reasons[this.type];
  }

  public get accentColor(): string {
    return this.type === 'in' ? '#1a9e5a' : '#ff4d4d';
  }

  public onClose(): void {
    this.closeModal.emit();
    this.resetForm();
  }

  public onSubmit(): void {
    if (this.reasonCode && this.amountCents >= 0) {
      this.registerMovement.emit({
        type: this.type,
        reasonCode: this.reasonCode,
        amountCents: this.amountCents,
        description: this.description || undefined,
      });
      this.resetForm();
    }
  }

  public onTypeChange(value: string): void {
    this.type = value as 'in' | 'out';
    this.reasonCode = this.type === 'in' ? 'change_refill' : 'sangria';
  }

  public selectReason(code: string): void {
    this.reasonCode = code;
  }

  public onAmountChange(value: number): void {
    this.amountCents = value;
  }

  private resetForm(): void {
    this.type = 'in';
    this.reasonCode = 'change_refill';
    this.amountCents = 0;
    this.description = '';
  }

  public formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }
}
