import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-badge',
  templateUrl: './badge.component.html',
  styleUrls: ['./badge.component.scss'],
  imports: [],
  standalone: true,
})
export class BadgeComponent {
  @Input() text = '';
  @Input() color = '#ff4d4d';
  @Input() fill = false;
}
