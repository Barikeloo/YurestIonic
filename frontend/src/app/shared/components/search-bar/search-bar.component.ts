import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-search-bar',
  standalone: true,
  imports: [FormsModule],
  templateUrl: './search-bar.component.html',
  styleUrls: ['./search-bar.component.scss'],
})
export class SearchBarComponent {
  @Input() value = '';
  @Input() placeholder = 'Buscar...';
  @Input() resultsCount: number | null = null;
  @Input() totalCount: number | null = null;
  @Input() ariaLabel = 'Buscar';

  @Output() valueChange = new EventEmitter<string>();

  public onInput(next: string): void {
    this.value = next;
    this.valueChange.emit(next);
  }

  public clear(): void {
    if (this.value === '') return;
    this.value = '';
    this.valueChange.emit('');
  }

  public get showCount(): boolean {
    return this.value.trim() !== '' && this.resultsCount !== null && this.totalCount !== null;
  }
}
