import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent, IonSpinner } from '@ionic/angular/standalone';
import { take } from 'rxjs';
import { AuthService } from '../../../services/auth.service';
import { RestaurantModalComponent } from '../../../components/modals/restaurant-modal/restaurant-modal.component';
import { UserModalComponent } from '../../../components/modals/user-modal/user-modal.component';

interface Restaurant {
    uuid: string;
    name: string;
    legal_name: string;
    tax_id: string;
    email: string;
}

interface CompanyGroup {
    tax_id: string;
    legalName: string;
    restaurants: Restaurant[];
}

@Component({
    selector: 'app-developer-dashboard',
    templateUrl: './developer-dashboard.page.html',
    styleUrls: ['./developer-dashboard.page.scss'],
    imports: [CommonModule, IonContent, IonSpinner, RestaurantModalComponent, UserModalComponent],
})
export class DeveloperDashboardPage implements OnInit {
    public companies: CompanyGroup[] = [];
    public isLoading: boolean = true;
    public error: string | null = null;

    // Restaurant modal
    public restaurantModalOpen = false;
    public restaurantModalData: any = { mode: 'create' };

    // User modal
    public userModalOpen = false;
    public userModalData: any = { mode: 'list', restaurantUuid: '', restaurantName: '' };

    constructor(
        private readonly authService: AuthService,
        private readonly router: Router,
    ) { }

    ngOnInit(): void {
        this.loadRestaurants();
    }

      public openRestaurantModal(mode: 'create' | 'edit', restaurant?: any, presetTaxId?: string): void {
        this.restaurantModalData = {
            mode,
            restaurant: restaurant ? { ...restaurant } : undefined,
                presetTaxId,
        };
        this.restaurantModalOpen = true;
    }

    public closeRestaurantModal(): void {
        this.restaurantModalOpen = false;
    }

    public onRestaurantSaved(): void {
        this.loadRestaurants();
    }

    public openUserModal(restaurantUuid: string, restaurantName: string): void {
        this.userModalData = { mode: 'list', restaurantUuid, restaurantName };
        this.userModalOpen = true;
    }

    public deleteRestaurant(restaurant: Restaurant): void {
        const confirmed = confirm(`Vas a eliminar "${restaurant.name}". Esta accion no se puede deshacer. ¿Continuar?`);

        if (!confirmed) {
            return;
        }

        this.authService.deleteRestaurant(restaurant.uuid).pipe(take(1)).subscribe({
            next: () => {
                this.loadRestaurants();
            },
            error: (error) => {
                this.error = error instanceof Error ? error.message : 'Error al eliminar restaurante';
            },
        });
    }

    public closeUserModal(): void {
        this.userModalOpen = false;
    }

    public onUserSaved(): void {
        // Reload users for the current restaurant
    }

    public logout(): void {
        this.authService.superAdminLogout().pipe(take(1)).subscribe({
            next: () => {
                this.router.navigateByUrl('/');
            },
        });
    }

    private loadRestaurants(): void {
        this.isLoading = true;
        this.error = null;

        this.authService
            .getSuperAdminRestaurants()
            .pipe(take(1))
            .subscribe({
                next: (restaurants) => {
                    this.companies = this.groupByTaxId(restaurants);
                    this.isLoading = false;
                },
                error: (error) => {
                    this.error = error instanceof Error ? error.message : 'Error al cargar restaurantes';
                    this.isLoading = false;
                },
            });
    }

    private groupByTaxId(restaurants: Restaurant[]): CompanyGroup[] {
        const grouped = new Map<string, Restaurant[]>();

        restaurants.forEach((restaurant) => {
            const taxId = restaurant.tax_id || 'Sin asignar';
            if (!grouped.has(taxId)) {
                grouped.set(taxId, []);
            }
            grouped.get(taxId)!.push(restaurant);
        });

        return Array.from(grouped.entries())
            .map(([taxId, restaurants]) => {
                // Use legal_name from first restaurant in group (all should have same tax_id)
                const firstRestaurant = restaurants[0];
                let companyLegalName = 'Compañía';

                // If all restaurants in the group are from the same company, extract legal name pattern
                if (firstRestaurant && firstRestaurant.legal_name) {
                    // For companies with one restaurant, use the full legal name
                    if (restaurants.length === 1) {
                        companyLegalName = firstRestaurant.legal_name;
                    } else {
                        // For companies with multiple restaurants, find common part of legal names
                        const legalNames = restaurants.map(r => r.legal_name);
                        const commonStart = legalNames[0].split(' ')[0]; // Get first word as company identifier
                        companyLegalName = commonStart;
                    }
                }

                return {
                    tax_id: taxId,
                    legalName: companyLegalName,
                    restaurants: restaurants.sort((a, b) => a.name.localeCompare(b.name)),
                };
            })
            .sort((a, b) => a.tax_id.localeCompare(b.tax_id));
    }
}
