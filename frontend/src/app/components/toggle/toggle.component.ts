import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'app-toggle',
  templateUrl: './toggle.component.html',
  styleUrls: ['./toggle.component.scss'],
  imports: [CommonModule],
  standalone: true,
})
export class ToggleComponent {
  @Input() value = false;
  @Input() color = '#1a9e5a';
  @Output() valueChange = new EventEmitter<boolean>();

  public onToggle(): void {
    this.valueChange.emit(!this.value);
  }
}
