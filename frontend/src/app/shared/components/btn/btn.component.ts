import { Component, Input } from '@angular/core';
import { BtnVariant } from '../../../core/enums/btn-variant.enum';
import { BtnSize } from '../../../core/enums/btn-size.enum';

export { BtnVariant, BtnSize };

@Component({
  selector: 'app-btn',
  templateUrl: './btn.component.html',
  styleUrls: ['./btn.component.scss'],
  imports: [],
  standalone: true,
})
export class BtnComponent {
  protected readonly BtnVariant = BtnVariant;
  protected readonly BtnSize = BtnSize;

  @Input() variant: BtnVariant = BtnVariant.FILL;
  @Input() color = '#ff4d4d';
  @Input() size: BtnSize = BtnSize.MD;
  @Input() disabled = false;
  @Input() block = false;
}
