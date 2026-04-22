import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

export interface SegmentOption {
  value: string;
  label: string;
}

@Component({
  selector: 'app-segment',
  templateUrl: './segment.component.html',
  styleUrls: ['./segment.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class SegmentComponent {
  @Input() options: SegmentOption[] = [];
  @Input() value = '';
  @Output() valueChange = new EventEmitter<string>();

  public onSelect(optionValue: string): void {
    this.valueChange.emit(optionValue);
  }
}
