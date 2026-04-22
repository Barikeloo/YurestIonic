import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

export type BtnVariant = 'fill' | 'outline' | 'ghost' | 'gray' | 'success' | 'danger';
export type BtnSize = 'sm' | 'md' | 'lg';

@Component({
  selector: 'app-btn',
  templateUrl: './btn.component.html',
  styleUrls: ['./btn.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class BtnComponent {
  @Input() variant: BtnVariant = 'fill';
  @Input() color = '#ff4d4d';
  @Input() size: BtnSize = 'md';
  @Input() disabled = false;
  @Input() block = false;
}
