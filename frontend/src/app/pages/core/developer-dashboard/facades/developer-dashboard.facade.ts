import { Injectable, signal, computed, inject } from '@angular/core';
import { Observable, take } from 'rxjs';
import { Router } from '@angular/router';
import { AuthService } from '../../../../core/services/auth.service';

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

@Injectable({
  providedIn: 'root'
})
export class DeveloperDashboardFacade {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  private readonly companies = signal<CompanyGroup[]>([]);
  private readonly isLoading = signal<boolean>(true);
  private readonly error = signal<string | null>(null);

  public readonly dashboardCompanies = computed(() => this.companies());
  public readonly dashboardLoading = computed(() => this.isLoading());
  public readonly dashboardError = computed(() => this.error());

  public get companiesValue(): CompanyGroup[] {
    return this.companies();
  }

  public get isLoadingValue(): boolean {
    return this.isLoading();
  }

  public get errorValue(): string | null {
    return this.error();
  }

  public setCompanies(value: CompanyGroup[]): void {
    this.companies.set(value);
  }

  public setIsLoading(value: boolean): void {
    this.isLoading.set(value);
  }

  public setError(value: string | null): void {
    this.error.set(value);
  }

  public loadRestaurants(): void {
    this.setIsLoading(true);
    this.setError(null);

    this.authService
      .getSuperAdminRestaurants()
      .pipe(take(1))
      .subscribe({
        next: (restaurants) => {
          this.setCompanies(this.groupByTaxId(restaurants));
          this.setIsLoading(false);
        },
        error: (error) => {
          this.setError(error instanceof Error ? error.message : 'Error al cargar restaurantes');
          this.setIsLoading(false);
        },
      });
  }

  public deleteRestaurant(restaurantUuid: string): Observable<void> {
    return this.authService.deleteRestaurant(restaurantUuid).pipe(take(1));
  }

  public logout(): Observable<void> {
    return this.authService.superAdminLogout().pipe(take(1));
  }

  public navigateToHome(): void {
    this.router.navigateByUrl('/');
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
        const firstRestaurant = restaurants[0];
        let companyLegalName = 'Compañía';

        if (firstRestaurant && firstRestaurant.legal_name) {
          if (restaurants.length === 1) {
            companyLegalName = firstRestaurant.legal_name;
          } else {
            const legalNames = restaurants.map(r => r.legal_name);
            const commonStart = legalNames[0].split(' ')[0];
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
