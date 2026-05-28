import { Component, Input, Output, EventEmitter, ViewChild, ElementRef, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-save-view-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './save-view-modal.component.html',
  styleUrls: ['./save-view-modal.component.scss'],
})
export class SaveViewModalComponent {
  @Input() isOpen = false;
  @Output() close = new EventEmitter<void>();
  @Output() confirm = new EventEmitter<string>();

  @ViewChild('nameInput') nameInputRef?: ElementRef<HTMLInputElement>;

  public viewName = '';
  public error = '';

  public onClose(): void {
    this.viewName = '';
    this.error = '';
    this.close.emit();
  }

  public onConfirm(): void {
    const name = this.viewName.trim();
    if (!name) {
      this.error = 'El nombre es obligatorio';
      this.focusInput();
      return;
    }
    if (name.length > 60) {
      this.error = 'Máximo 60 caracteres';
      this.focusInput();
      return;
    }
    this.error = '';
    this.confirm.emit(name);
    this.viewName = '';
  }

  public onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter') {
      e.preventDefault();
      this.onConfirm();
    }
    if (e.key === 'Escape') {
      this.onClose();
    }
  }

  public focusInput(): void {
    setTimeout(() => this.nameInputRef?.nativeElement?.focus(), 50);
  }
}
