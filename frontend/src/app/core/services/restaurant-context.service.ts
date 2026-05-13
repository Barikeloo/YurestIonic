import { Injectable, signal } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class RestaurantContextService {
  private readonly _selectedRestaurantUuid = signal<string | null>(null);

  public readonly selectedRestaurantUuid = this._selectedRestaurantUuid.asReadonly();

  public setRestaurantContext(uuid: string): void {
    this._selectedRestaurantUuid.set(uuid);
  }

  public clearRestaurantContext(): void {
    this._selectedRestaurantUuid.set(null);
  }
}
