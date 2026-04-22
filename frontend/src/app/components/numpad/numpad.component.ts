import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-numpad',
  templateUrl: './numpad.component.html',
  styleUrls: ['./numpad.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class NumpadComponent {
  @Input() value = 0;
  @Output() valueChange = new EventEmitter<number>();
  public isHolding = false;
  private holdInterval: any;

  public readonly keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'C', '0', '⌫'];

  public onTap(key: string): void {
    if (key === 'C') {
      this.valueChange.emit(0);
    } else if (key === '⌫') {
      this.valueChange.emit(Math.floor(this.value / 10));
    } else {
      const next = this.value * 10 + parseInt(key, 10);
      if (next <= 9999999) {
        this.valueChange.emit(next);
      }
    }
  }

  public onMouseDown(event: Event): void {
    this.isHolding = true;
    event.preventDefault();
  }

  public onMouseUp(event: Event): void {
    this.isHolding = false;
    event.preventDefault();
  }
}
