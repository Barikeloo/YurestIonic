import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';

export interface ActiveRestaurantContext {
  id?: string;
  name: string;
}

@Injectable({
  providedIn: 'root',
})
export class AppContextService {
  private readonly activeRestaurantSubject = new BehaviorSubject<ActiveRestaurantContext | null>(null);

  public readonly activeRestaurant$: Observable<ActiveRestaurantContext | null> = this.activeRestaurantSubject.asObservable();

  public setActiveRestaurant(context: ActiveRestaurantContext): void {
    this.activeRestaurantSubject.next(context);
  }

  public clearActiveRestaurant(): void {
    this.activeRestaurantSubject.next(null);
  }
}
