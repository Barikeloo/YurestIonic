import { Component, EventEmitter, Input, OnDestroy, Output, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { firstValueFrom } from 'rxjs';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../core/services/toast.service';

export interface PinAuthResult {
  userId: string;
  userName: string;
  userRole: string;
}

@Component({
  selector: 'app-pin-auth-modal',
  templateUrl: './pin-auth-modal.component.html',
  styleUrls: ['./pin-auth-modal.component.scss'],
  imports: [FormsModule],
  standalone: true,
})
export class PinAuthModalComponent implements OnDestroy {
  @Input() isOpen = false;
  @Input() title = 'Verificación de PIN';
  @Input() subtitle = 'Introduce tu PIN para continuar';
  @Output() closeModal = new EventEmitter<void>();
  @Output() authenticated = new EventEmitter<PinAuthResult>();

  public enteredPin = '';
  public isVerifying = false;
  public showSuccess = false;

  private successTimeout: ReturnType<typeof setTimeout> | null = null;
  private readonly toastService = inject(ToastService);

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
      return;
    }

    if (this.enteredPin.length < 4) {
      this.enteredPin += key;

      if (this.enteredPin.length === 4) {
        void this.verifyPin();
      }
    }
  }

  public clearPin(): void {
    if (this.isVerifying || this.showSuccess) return;
    this.enteredPin = '';
  }

  private async verifyPin(): Promise<void> {
    if (this.enteredPin.length !== 4 || this.isVerifying) return;

    this.isVerifying = true;

    try {
      const currentUser = await firstValueFrom(this.authService.currentUser$);
      if (!currentUser) {
        this.toastService.presentError('No hay sesión activa');
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
        this.toastService.presentError('PIN incorrecto');
        this.enteredPin = '';
      } else {
        const message = err instanceof Error ? err.message : 'Error al verificar';
        this.toastService.presentError(message);
        this.enteredPin = '';
      }
    }
  }

  private reset(): void {
    this.enteredPin = '';
    this.isVerifying = false;
    this.showSuccess = false;
  }
}
