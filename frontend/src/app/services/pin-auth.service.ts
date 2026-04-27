import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, firstValueFrom } from 'rxjs';
import { AuthService } from './auth.service';

export interface AuthContext {
  userId: string;
  userName: string;
  userRole: string;
  authenticatedAt: number;
  lastActivityAt: number;
}

export type AuthActionType = 'normal' | 'critical';

export const DEFAULT_SESSION_TIMEOUT_MIN = 5;

@Injectable({
  providedIn: 'root',
})
export class PinAuthService {
  private sessionTimeoutMs = DEFAULT_SESSION_TIMEOUT_MIN * 60 * 1000;

  private authContextSubject = new BehaviorSubject<AuthContext | null>(null);
  public authContext$: Observable<AuthContext | null> = this.authContextSubject.asObservable();

  constructor(private readonly authService: AuthService) {}

  public setSessionTimeout(minutes: number): void {
    this.sessionTimeoutMs = minutes * 60 * 1000;
  }

  public requiresPin(actionType: AuthActionType = 'normal', requiredRole?: string): boolean {
    const context = this.authContextSubject.value;

    if (!context) return true;

    if (actionType === 'critical') {
      if (requiredRole && !this.hasRequiredRole(context.userRole, requiredRole)) {
        return true;
      }
    }

    const now = Date.now();
    const elapsed = now - context.lastActivityAt;

    if (elapsed > this.sessionTimeoutMs) {
      this.clearAuth();
      return true;
    }

    if (requiredRole && !this.hasRequiredRole(context.userRole, requiredRole)) {
      return true;
    }

    this.extendSession();
    return false;
  }

  public async verifyPin(pin: string): Promise<AuthContext> {
    const currentUser = this.authService.currentUserSnapshot;
    if (!currentUser) {
      throw new Error('No hay sesión activa');
    }

    const deviceId = this.authService.getDeviceId();

    await firstValueFrom(
      this.authService.loginWithPin(currentUser.id, pin, deviceId),
    );

    const now = Date.now();
    const context: AuthContext = {
      userId: currentUser.id,
      userName: currentUser.name,
      userRole: currentUser.role ?? 'operator',
      authenticatedAt: now,
      lastActivityAt: now,
    };

    this.authContextSubject.next(context);
    return context;
  }

  public extendSession(): void {
    const context = this.authContextSubject.value;
    if (context) {
      context.lastActivityAt = Date.now();
      this.authContextSubject.next(context);
    }
  }

  public setAuthContext(context: AuthContext): void {
    this.authContextSubject.next(context);
  }

  public clearAuth(): void {
    this.authContextSubject.next(null);
  }

  public getRemainingTime(): number {
    const context = this.authContextSubject.value;
    if (!context) return 0;

    const now = Date.now();
    const elapsed = now - context.lastActivityAt;
    const remaining = this.sessionTimeoutMs - elapsed;

    return Math.max(0, Math.floor(remaining / 1000));
  }

  private hasRequiredRole(userRole: string, requiredRole: string): boolean {
    const roleHierarchy: Record<string, number> = {
      operator: 1,
      supervisor: 2,
      admin: 3,
    };

    const userLevel = roleHierarchy[userRole] ?? 0;
    const requiredLevel = roleHierarchy[requiredRole] ?? 0;

    return userLevel >= requiredLevel;
  }
}

