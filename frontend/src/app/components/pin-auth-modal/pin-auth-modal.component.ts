import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, OnDestroy, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../services/auth.service';

export interface PinAuthResult {
  userId: string;
  userName: string;
  userRole: string;
}

@Component({
  selector: 'app-pin-auth-modal',
  templateUrl: './pin-auth-modal.component.html',
  styleUrls: ['./pin-auth-modal.component.scss'],
  imports: [CommonModule, FormsModule],
  standalone: true,
})
export class PinAuthModalComponent implements OnDestroy {
  @Input() isOpen = false;
  @Input() title = 'Verificación de PIN';
  @Input() subtitle = 'Introduce tu PIN para continuar';
  @Output() closeModal = new EventEmitter<void>();
  @Output() authenticated = new EventEmitter<PinAuthResult>();

  public enteredPin = '';
  public pinError: string | null = null;
  public isVerifying = false;
  public showSuccess = false;

  private successTimeout: ReturnType<typeof setTimeout> | null = null;

  constructor(private readonly authService: AuthService) {}

  public ngOnDestroy(): void {
    if (this.successTimeout) clearTimeout(this.successTimeout);
  }

  get pinDots(): boolean[] {
    return [0, 1, 2, 3].map(i => i < this.enteredPin.length);
  }

  public onClose(): void {
    this.reset();
    this.closeModal.emit();
  }

  public pinPress(key: string): void {
    if (this.isVerifying || this.showSuccess) return;

    if (key === 'del') {
      this.enteredPin = this.enteredPin.slice(0, -1);
      this.pinError = null;
      return;
    }

    if (this.enteredPin.length < 4) {
      this.enteredPin += key;
      this.pinError = null;

      if (this.enteredPin.length === 4) {
        void this.verifyPin();
      }
    }
  }

  public clearPin(): void {
    if (this.isVerifying || this.showSuccess) return;
    this.enteredPin = '';
    this.pinError = null;
  }

  private async verifyPin(): Promise<void> {
    if (this.enteredPin.length !== 4 || this.isVerifying) return;

    this.isVerifying = true;
    this.pinError = null;

    try {
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (!currentUser) {
        this.pinError = 'No hay sesión activa';
        this.enteredPin = '';
        this.isVerifying = false;
        return;
      }

      const deviceId = this.authService.getDeviceId();
      await firstValueFrom(
        this.authService.loginWithPin(currentUser.id, this.enteredPin, deviceId),
      );

      this.showSuccess = true;
      this.isVerifying = false;

      this.successTimeout = setTimeout(() => {
        this.successTimeout = null;
        this.authenticated.emit({
          userId: currentUser.id,
          userName: currentUser.name,
          userRole: currentUser.role ?? 'operator',
        });
        this.reset();
      }, 1500);
    } catch (err) {
      this.isVerifying = false;
      if (err instanceof Error && err.message.includes('PIN')) {
        this.pinError = 'PIN incorrecto';
        this.enteredPin = '';
      } else {
        this.pinError = err instanceof Error ? err.message : 'Error al verificar';
        this.enteredPin = '';
      }
    }
  }

  private reset(): void {
    this.enteredPin = '';
    this.pinError = null;
    this.isVerifying = false;
    this.showSuccess = false;
  }
}
