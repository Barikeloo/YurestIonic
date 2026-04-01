import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ManagementRestaurant } from '../restaurant-list/restaurant-list.component';

export interface RestaurantFormData {
  name: string;
  legalName: string;
  taxId: string;
  email: string;
  password: string;
}

@Component({
  selector: 'app-restaurant-detail',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './restaurant-detail.component.html',
  styleUrls: ['./restaurant-detail.component.scss'],
})
export class RestaurantDetailComponent {
  @Input() restaurant: ManagementRestaurant | null = null;
  @Input() formData: RestaurantFormData = {
    name: '',
    legalName: '',
    taxId: '',
    email: '',
    password: '',
  };
  @Input() isSaving: boolean = false;
  @Output() saveChanges = new EventEmitter<void>();

  onSubmit(): void {
    this.saveChanges.emit();
  }
}
