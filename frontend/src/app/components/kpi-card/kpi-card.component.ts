import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { CardComponent } from '../card/card.component';

@Component({
  selector: 'app-kpi-card',
  templateUrl: './kpi-card.component.html',
  styleUrls: ['./kpi-card.component.scss'],
  imports: [CommonModule, CardComponent],
  standalone: true,
})
export class KpiCardComponent {
  @Input() label = '';
  @Input() value = '';
  @Input() sub?: string;
  @Input() color?: string;
  @Input() mono = true;
}
