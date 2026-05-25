import { computed, inject, Injectable, Signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { AuthService, AuthUser, UserRole } from './auth.service';

const KNOWN_ROLES: ReadonlySet<UserRole> = new Set<UserRole>(['admin', 'supervisor', 'operator']);

function normalizeRole(value: string | undefined | null): UserRole {
  if (value && (KNOWN_ROLES as ReadonlySet<string>).has(value)) {
    return value as UserRole;
  }

  return 'operator';
}

@Injectable({ providedIn: 'root' })
export class PermissionsService {
  private readonly authService = inject(AuthService);

  private readonly currentUser: Signal<AuthUser | null | undefined> = toSignal(
    this.authService.currentUser$,
  );

  public readonly role: Signal<UserRole> = computed(() =>
    normalizeRole(this.currentUser()?.role),
  );

  public readonly isAdmin: Signal<boolean> = computed(() => this.role() === 'admin');
  public readonly isSupervisor: Signal<boolean> = computed(() => this.role() === 'supervisor');
  public readonly isOperator: Signal<boolean> = computed(() => this.role() === 'operator');

  // Convenience: any role at supervisor level or above
  public readonly isSupervisorOrAbove: Signal<boolean> = computed(
    () => this.isAdmin() || this.isSupervisor(),
  );

  // ── Order-specific capabilities ───────────────
  public readonly canCancelOrders: Signal<boolean> = this.isSupervisorOrAbove;
  public readonly canReopenOrders: Signal<boolean> = this.isSupervisorOrAbove;
  public readonly canDeleteOrders: Signal<boolean> = this.isSupervisorOrAbove;
  public readonly canMarkAsCharged: Signal<boolean> = computed(() => true);
}
