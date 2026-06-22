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

  setDiners(n: number): void {
    this.dinersCount.set(n);
  }

  selectIdentity(mode: IdentityMode): void {
    this.identityMode.set(mode);
    this.showNameInput.set(mode === 'named');
    if (mode === 'anonymous') {
      this.guestName.set('');
    }
  }

  openTable(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.openTable({
      dinersCount: this.dinersCount(),
      identityMode: mode,
      guestName: mode !== 'anonymous' ? (this.guestName() || undefined) : undefined,
    });
  }

  joinSession(): void {
    const mode = this.identityMode();
    if (!mode) return;
    this.facade.joinSession({
      identityMode: mode,
      guestName: mode !== 'anonymous' ? (this.guestName() || undefined) : undefined,
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
