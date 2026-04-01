import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';

export interface ManagementRestaurant {
  id: number;
  uuid?: string;
  name: string;
  legalName: string;
  taxId: string;
  email: string;
  status: 'active';
  users: number;
  zones: number;
  products: number;
}

@Component({
  selector: 'app-restaurant-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './restaurant-list.component.html',
  styleUrls: ['./restaurant-list.component.scss'],
})
export class RestaurantListComponent {
  @Input() restaurants: ManagementRestaurant[] = [];
  @Input() selectedRestaurantId: number = 0;
  @Output() selectRestaurant = new EventEmitter<number>();

  isActive(restaurantId: number): boolean {
    return this.selectedRestaurantId === restaurantId;
  }

  onSelect(restaurantId: number): void {
    this.selectRestaurant.emit(restaurantId);
  }
}
