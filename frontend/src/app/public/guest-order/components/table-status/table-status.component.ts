import { Component, computed, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { IdentityMode } from '../../models/guest-session.models';
import { DinersStepperComponent } from './diners-stepper.component';
import { IdentitySelectorComponent } from './identity-selector.component';
import { GuestIconComponent } from '../ui/guest-icon.component';

type AuthStep = 'identity' | 'login' | 'register' | 'confirmed';

@Component({
  selector: 'app-table-status',
  standalone: true,
  imports: [CommonModule, DinersStepperComponent, IdentitySelectorComponent, GuestIconComponent],
  templateUrl: './table-status.component.html',
  styleUrls: ['./table-status.component.scss'],
})
export class TableStatusComponent {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly dinersCount = signal(2);
  protected readonly identityMode = signal<IdentityMode | null>(null);
  protected readonly guestName = signal('');
  protected readonly authStep = signal<AuthStep>('identity');

  protected readonly loginEmail = signal('');
  protected readonly loginPassword = signal('');
  protected readonly registerName = signal('');
  protected readonly registerEmail = signal('');
  protected readonly registerPassword = signal('');

  protected readonly isIdentityStep    = computed(() => this.authStep() === 'identity');
  protected readonly isLoginStep       = computed(() => this.authStep() === 'login');
  protected readonly isRegisterStep    = computed(() => this.authStep() === 'register');
  protected readonly isConfirmedStep   = computed(() => this.authStep() === 'confirmed');

  protected readonly canProceed = computed(() => {
    const mode = this.identityMode();
    if (!mode) return false;
    if (mode === 'registered') return this.authStep() === 'confirmed';
    return true;
  });

  setDiners(n: number): void {
    this.dinersCount.set(n);
  }

  selectIdentity(mode: IdentityMode): void {
    this.identityMode.set(mode);
    if (mode === 'registered') {
      this.authStep.set('login');
    } else {
      this.authStep.set('identity');
    }
    if (mode !== 'named') this.guestName.set('');
  }

  backToIdentity(): void {
    this.authStep.set('identity');
    this.identityMode.set(null);
    this.loginEmail.set('');
    this.loginPassword.set('');
    this.registerName.set('');
    this.registerEmail.set('');
    this.registerPassword.set('');
    this.facade.clearCustomerData();
  }

  switchToRegister(): void {
    this.authStep.set('register');
    this.facade.clearError();
  }

  switchToLogin(): void {
    this.authStep.set('login');
    this.facade.clearError();
  }

  submitLogin(): void {
    this.facade.loginAccount({
      email: this.loginEmail(),
      password: this.loginPassword(),
      onSuccess: () => this.authStep.set('confirmed'),
    });
  }

  submitRegister(): void {
    this.facade.registerAccount({
      name: this.registerName(),
      email: this.registerEmail(),
      password: this.registerPassword(),
      onSuccess: () => this.authStep.set('confirmed'),
    });
  }

  openTable(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.openTable({
      dinersCount: this.dinersCount(),
      identityMode: mode,
      guestName: mode === 'named' ? (this.guestName() || undefined) : undefined,
      customerAuthToken: mode === 'registered' ? this.facade.takePendingCustomerAuthToken() : undefined,
    });
  }

  joinSession(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.joinSession({
      identityMode: mode,
      guestName: mode === 'named' ? (this.guestName() || undefined) : undefined,
      customerAuthToken: mode === 'registered' ? this.facade.takePendingCustomerAuthToken() : undefined,
    });
  }
}
