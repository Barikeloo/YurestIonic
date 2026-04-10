import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { take } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';
import { DeviceStorageService, LinkedRestaurant } from '../../../services/device-storage.service';
import { AppContextService } from '../../../services/app-context.service';

interface Restaurant {
  uuid: string;
  name: string;
  legal_name: string;
  tax_id: string;
  email: string;
}

@Component({
  selector: 'app-link-device-select-restaurant',
  templateUrl: './link-device-select-restaurant.page.html',
  styleUrls: ['./link-device-select-restaurant.page.scss'],
  imports: [CommonModule, IonContent],
})
export class LinkDeviceSelectRestaurantPage {
  public restaurants: Restaurant[] = [];
  public isLoading: boolean = true;
  public errorMessage: string | null = null;
  public adminName: string = '';

  constructor(
    private readonly authService: AuthService,
    private readonly deviceStorageService: DeviceStorageService,
    private readonly appContextService: AppContextService,
    private readonly router: Router,
    private readonly activatedRoute: ActivatedRoute,
  ) {}

  public ionViewWillEnter(): void {
    this.adminName = this.activatedRoute.snapshot.queryParams['adminName'] || 'Administrador';
    this.loadRestaurants();
  }

  public loadRestaurants(): void {
    this.isLoading = true;
    this.errorMessage = null;

    this.authService
      .getAdminRestaurants()
      .pipe(take(1))
      .subscribe({
        next: (restaurants) => {
          this.restaurants = restaurants;
          this.isLoading = false;
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo cargar los restaurantes.';
          this.isLoading = false;
        },
      });
  }

  public selectRestaurant(restaurant: Restaurant): void {
    const linkedRestaurant: LinkedRestaurant = {
      uuid: restaurant.uuid,
      name: restaurant.name,
      legalName: restaurant.legal_name,
      taxId: restaurant.tax_id,
      email: restaurant.email,
    };

    this.deviceStorageService.setLinkedRestaurant(linkedRestaurant);
    this.appContextService.setActiveRestaurant({
      id: restaurant.uuid,
      name: restaurant.name,
    });

    this.router.navigateByUrl('/login');
  }

  public goBack(): void {
    this.router.navigateByUrl('/home');
  }
}
