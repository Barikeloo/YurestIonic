import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-amount-display',
  templateUrl: './amount-display.component.html',
  styleUrls: ['./amount-display.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class AmountDisplayComponent {
  @Input() cents = 0;
  @Input() label?: string;
  @Input() large = false;
  @Input() color?: string;

  public get int(): number {
    return Math.floor(this.cents / 100);
  }

  public get dec(): string {
    return String(this.cents % 100).padStart(2, '0');
  }
}
