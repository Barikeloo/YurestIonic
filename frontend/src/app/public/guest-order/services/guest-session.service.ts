import { Injectable } from '@angular/core';

const SESSION_KEY_PREFIX = 'guest_session_';

@Injectable({ providedIn: 'root' })
export class GuestSessionService {
  getSessionToken(qrToken: string): string | null {
    return localStorage.getItem(`${SESSION_KEY_PREFIX}${qrToken}`);
  }

  saveSessionToken(qrToken: string, sessionToken: string): void {
    localStorage.setItem(`${SESSION_KEY_PREFIX}${qrToken}`, sessionToken);
  }

  clearSession(qrToken: string): void {
    localStorage.removeItem(`${SESSION_KEY_PREFIX}${qrToken}`);
  }

  generateSessionToken(): string {
    const bytes = new Uint8Array(32);
    crypto.getRandomValues(bytes);
    return Array.from(bytes)
      .map((b) => b.toString(16).padStart(2, '0'))
      .join('');
  }

  generateIdempotencyKey(): string {
    return crypto.randomUUID();
  }
}
