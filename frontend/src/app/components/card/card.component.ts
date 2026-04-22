import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-card',
  templateUrl: './card.component.html',
  styleUrls: ['./card.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class CardComponent {
  @Input() padding = 14;
  @Input() clickable = true;
}
