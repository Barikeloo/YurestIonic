import { Component, EventEmitter, Input, Output } from '@angular/core';

export type ConfirmModalVariant = 'default' | 'danger';

@Component({
  selector: 'app-confirm-modal',
  templateUrl: './confirm-modal.component.html',
  styleUrls: ['./confirm-modal.component.scss'],
})
export class ConfirmModalComponent {
  @Input() public isOpen = false;
  @Input() public title = '¿Confirmar acción?';
  @Input() public message = '';
  @Input() public confirmLabel = 'Confirmar';
  @Input() public cancelLabel = 'Cancelar';
  @Input() public variant: ConfirmModalVariant = 'default';
  @Input() public loading = false;

  @Output() public confirm = new EventEmitter<void>();
  @Output() public cancel = new EventEmitter<void>();

  public onConfirm(): void {
    if (this.loading) return;
    this.confirm.emit();
  }

  public onCancel(): void {
    if (this.loading) return;
    this.cancel.emit();
  }

  public onOverlayClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
      this.onCancel();
    }
  }
}
