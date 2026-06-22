import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GuestOrderFacade } from '../../facades/guest-order.facade';
import { IdentityMode } from '../../models/guest-session.models';
import { DinersStepperComponent } from './diners-stepper.component';
import { IdentitySelectorComponent } from './identity-selector.component';

@Component({
  selector: 'app-table-status',
  standalone: true,
  imports: [CommonModule, DinersStepperComponent, IdentitySelectorComponent],
  templateUrl: './table-status.component.html',
  styleUrls: ['./table-status.component.scss'],
})
export class TableStatusComponent {
  protected readonly facade = inject(GuestOrderFacade);

  protected readonly dinersCount = signal(2);
  protected readonly identityMode = signal<IdentityMode | null>(null);
  protected readonly guestName = signal('');
  protected readonly showNameInput = signal(false);
  protected readonly showLoginForm = signal(false);
  protected readonly showRegisterForm = signal(false);
  protected readonly loginEmail = signal('');
  protected readonly loginPassword = signal('');
  protected readonly registerName = signal('');
  protected readonly registerEmail = signal('');
  protected readonly registerPassword = signal('');

  setDiners(n: number): void {
    this.dinersCount.set(n);
  }

  selectIdentity(mode: IdentityMode): void {
    this.identityMode.set(mode);
    this.showNameInput.set(mode === 'named');
    this.showLoginForm.set(mode === 'registered' && !this.facade.customerData());
    this.showRegisterForm.set(false);
    if (mode === 'anonymous') this.guestName.set('');
  }

  switchToRegister(): void {
    this.showLoginForm.set(false);
    this.showRegisterForm.set(true);
  }

  switchToLogin(): void {
    this.showRegisterForm.set(false);
    this.showLoginForm.set(true);
  }

  submitLogin(): void {
    this.facade.loginAccount({ email: this.loginEmail(), password: this.loginPassword() });
  }

  submitRegister(): void {
    this.facade.registerAccount({
      name: this.registerName(),
      email: this.registerEmail(),
      password: this.registerPassword(),
    });
  }

  openTable(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.openTable({
      dinersCount: this.dinersCount(),
      identityMode: mode,
      guestName: mode !== 'anonymous' ? (this.guestName() || undefined) : undefined,
      customerAuthToken: mode === 'registered' ? this.facade.takePendingCustomerAuthToken() : undefined,
    });
  }

  joinSession(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.joinSession({
      identityMode: mode,
      guestName: mode !== 'anonymous' ? (this.guestName() || undefined) : undefined,
      customerAuthToken: mode === 'registered' ? this.facade.takePendingCustomerAuthToken() : undefined,
    });
  }

  openAsAnonymous(): void {
    this.identityMode.set('anonymous');
    this.facade.openTable({ dinersCount: this.dinersCount(), identityMode: 'anonymous' });
  }

  joinAsAnonymous(): void {
    this.identityMode.set('anonymous');
    this.facade.joinSession({ identityMode: 'anonymous' });
  }
}
