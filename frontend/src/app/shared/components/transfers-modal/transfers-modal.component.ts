import { Component, EventEmitter, Input, Output } from '@angular/core';

export interface TransferDisplay {
  id: string;
  fromTable: string;
  toTable: string;
  user: string;
  date: string;
}

@Component({
  selector: 'app-transfers-modal',
  templateUrl: './transfers-modal.component.html',
  styleUrls: ['./transfers-modal.component.scss'],
  standalone: true,
})
export class TransfersModalComponent {
  @Input() public isOpen = false;
  @Input() public loading = false;
  @Input() public transfers: TransferDisplay[] = [];
  @Input() public title = 'Transferencias';
  @Input() public subtitle: string | null = null;
  @Output() public close = new EventEmitter<void>();

  public onOverlayClick(): void {
    this.close.emit();
  }

  public onBoxClick(event: MouseEvent): void {
    event.stopPropagation();
  }
}
