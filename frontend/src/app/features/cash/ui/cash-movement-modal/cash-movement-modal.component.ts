
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { NumpadComponent } from '../../../../shared/components/numpad/numpad.component';
import { AmountDisplayComponent } from '../../../../shared/components/amount-display/amount-display.component';
import { BtnComponent } from '../../../../shared/components/btn/btn.component';
import { SegmentComponent, SegmentOption } from '../../../../shared/components/segment/segment.component';
import { CashMovementType } from '../../../../core/enums/cash-movement-type.enum';
import { CashMovementReason } from '../../../../core/enums/cash-movement-reason.enum';

export interface MovementReason {
  value: CashMovementReason;
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
  imports: [FormsModule, NumpadComponent, AmountDisplayComponent, BtnComponent, SegmentComponent],
  standalone: true,
})
export class CashMovementModalComponent {
  protected readonly CashMovementType = CashMovementType;

  @Input() isOpen = false;
  @Output() closeModal = new EventEmitter<void>();
  @Output() registerMovement = new EventEmitter<{
    type: CashMovementType;
    reasonCode: CashMovementReason;
    amountCents: number;
    description?: string;
  }>();

  public type: CashMovementType = CashMovementType.IN;
  public reasonCode: CashMovementReason = CashMovementReason.CHANGE_REFILL;
  public amountCents = 0;
  public description = '';

  public typeOptions: SegmentOption[] = [
    { value: CashMovementType.IN, label: '↑ Entrada' },
    { value: CashMovementType.OUT, label: '↓ Salida' },
  ];

  public reasons: Record<CashMovementType, MovementReason[]> = {
    [CashMovementType.IN]: [
      { value: CashMovementReason.CHANGE_REFILL, label: 'Reposición cambio' },
      { value: CashMovementReason.TIP_DECLARED, label: 'Propina declarada' },
      { value: CashMovementReason.ADJUSTMENT, label: 'Ajuste' },
      { value: CashMovementReason.OTHER, label: 'Otro' },
    ],
    [CashMovementType.OUT]: [
      { value: CashMovementReason.SANGRIA, label: 'Sangría al banco' },
      { value: CashMovementReason.SUPPLIER_PAYMENT, label: 'Pago proveedor' },
      { value: CashMovementReason.TIP_DECLARED, label: 'Propina camarero' },
      { value: CashMovementReason.ADJUSTMENT, label: 'Ajuste' },
      { value: CashMovementReason.OTHER, label: 'Otro' },
    ],
  };

  public get currentReasons(): MovementReason[] {
    return this.reasons[this.type];
  }

  public get accentColor(): string {
    return this.type === CashMovementType.IN ? '#1a9e5a' : '#ff4d4d';
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
    this.type = value as CashMovementType;
    this.reasonCode = this.type === CashMovementType.IN ? CashMovementReason.CHANGE_REFILL : CashMovementReason.SANGRIA;
  }

  public selectReason(code: CashMovementReason): void {
    this.reasonCode = code;
  }

  public onAmountChange(value: number): void {
    this.amountCents = value;
  }

  private resetForm(): void {
    this.type = CashMovementType.IN;
    this.reasonCode = CashMovementReason.CHANGE_REFILL;
    this.amountCents = 0;
    this.description = '';
  }

  public formatCents(cents: number): string {
    return (cents / 100).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }
}
