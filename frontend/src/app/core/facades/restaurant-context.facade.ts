import { Injectable, inject } from '@angular/core';
import { RestaurantContextService } from '../services/restaurant-context.service';

@Injectable({
  providedIn: 'root',
})
export class RestaurantContextFacade {
  private readonly restaurantContextService = inject(RestaurantContextService);

  public get selectedRestaurantUuid(): string | null {
    return this.restaurantContextService.selectedRestaurantUuid();
  }

  public setRestaurantContext(uuid: string): void {
    this.restaurantContextService.setRestaurantContext(uuid);
  }

  public clearRestaurantContext(): void {
    this.restaurantContextService.clearRestaurantContext();
  }
}
