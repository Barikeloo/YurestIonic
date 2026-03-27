import { CommonModule } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent, IonSpinner } from '@ionic/angular/standalone';
import { take } from 'rxjs';
import { AuthService } from '../../../services/auth.service';

interface Restaurant {
  uuid: string;
  name: string;
  legal_name: string;
  tax_id: string;
  email: string;
}

interface CompanyGroup {
  tax_id: string;
  restaurants: Restaurant[];
}

@Component({
  selector: 'app-developer-dashboard',
  templateUrl: './developer-dashboard.page.html',
  styleUrls: ['./developer-dashboard.page.scss'],
  imports: [CommonModule, IonContent, IonSpinner],
})
export class DeveloperDashboardPage implements OnInit {
  public companies: CompanyGroup[] = [];
  public isLoading: boolean = true;
  public error: string | null = null;

  constructor(
    private readonly authService: AuthService,
    private readonly router: Router,
  ) {}

  ngOnInit(): void {
    this.loadRestaurants();
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
      .map(([taxId, restaurants]) => ({
        tax_id: taxId,
        restaurants: restaurants.sort((a, b) => a.name.localeCompare(b.name)),
      }))
      .sort((a, b) => a.tax_id.localeCompare(b.tax_id));
  }
}
