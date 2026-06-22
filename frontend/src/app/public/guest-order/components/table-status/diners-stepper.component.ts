import { Component, input, output } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-diners-stepper',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="stepper">
      <button class="step-btn" (click)="decrement()" [disabled]="value() <= 1">−</button>
      <span class="step-value">{{ value() }}</span>
      <button class="step-btn" (click)="increment()" [disabled]="value() >= 99">+</button>
    </div>
  `,
  styles: [`
    .stepper {
      display: flex;
      align-items: center;
      gap: 0;
      background: #fff;
      border: 1.5px solid #e0e0e0;
      border-radius: 12px;
      overflow: hidden;
      width: fit-content;
      margin: 0 auto;
    }
    .step-btn {
      background: none;
      border: none;
      font-size: 24px;
      font-weight: 400;
      width: 52px;
      height: 52px;
      cursor: pointer;
      color: var(--guest-primary, #ff4d4d);
      transition: background 0.15s;
      &:hover:not(:disabled) { background: #f5f5f5; }
      &:disabled { opacity: 0.3; cursor: default; }
    }
    .step-value {
      min-width: 48px;
      text-align: center;
      font-size: 22px;
      font-weight: 600;
      color: #111;
      border-left: 1.5px solid #e0e0e0;
      border-right: 1.5px solid #e0e0e0;
      padding: 0 8px;
      line-height: 52px;
    }
  `],
})
export class DinersStepperComponent {
  readonly value = input.required<number>();
  readonly valueChange = output<number>();

  increment(): void {
    if (this.value() < 99) this.valueChange.emit(this.value() + 1);
  }

  decrement(): void {
    if (this.value() > 1) this.valueChange.emit(this.value() - 1);
  }
}
