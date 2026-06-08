import { Component, computed, inject, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent, IonSpinner } from '@ionic/angular/standalone';
import { take } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { RestaurantModalComponent } from '../../../components/modals/restaurant-modal/restaurant-modal.component';
import { UserModalComponent } from '../../../components/modals/user-modal/user-modal.component';
import { DeveloperDashboardFacade } from './facades/developer-dashboard.facade';

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
    imports: [IonContent, IonSpinner, RestaurantModalComponent, UserModalComponent],
    providers: [DeveloperDashboardFacade],
})
export class DeveloperDashboardPage implements OnInit {
    protected readonly dashboardFacade = inject(DeveloperDashboardFacade);
    private readonly router = inject(Router);
    private readonly toastService = inject(ToastService);

    public readonly companies = computed(() => this.dashboardFacade.dashboardCompanies());
    public readonly isLoading = computed(() => this.dashboardFacade.dashboardLoading());

    public restaurantModalOpen = false;
    public restaurantModalData: any = { mode: 'create' };

    public userModalOpen = false;
    public userModalData: any = { mode: 'list', restaurantUuid: '', restaurantName: '' };

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

        this.dashboardFacade.deleteRestaurant(restaurant.uuid).subscribe({
            next: () => {
                this.dashboardFacade.loadRestaurants();
            },
            error: (error) => {
                const message = error instanceof Error ? error.message : 'Error al eliminar restaurante';
                this.toastService.presentError(message);
            },
        });
    }

    public closeUserModal(): void {
        this.userModalOpen = false;
    }

    public onUserSaved(): void {
    }

    public logout(): void {
        this.dashboardFacade.logout().subscribe({
            next: () => {
                this.dashboardFacade.navigateToHome();
            },
        });
    }

    private loadRestaurants(): void {
        this.dashboardFacade.loadRestaurants();
    }
}
