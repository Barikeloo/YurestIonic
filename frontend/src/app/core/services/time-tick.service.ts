import { DestroyRef, inject, Injectable, signal } from '@angular/core';

const TICK_INTERVAL_MS = 30_000;

@Injectable({ providedIn: 'root' })
export class TimeTickService {
  private readonly _nowMs = signal<number>(Date.now());

  public readonly nowMs = this._nowMs.asReadonly();

  constructor() {
    const intervalId = setInterval(() => this._nowMs.set(Date.now()), TICK_INTERVAL_MS);

    inject(DestroyRef).onDestroy(() => clearInterval(intervalId));
  }
}
