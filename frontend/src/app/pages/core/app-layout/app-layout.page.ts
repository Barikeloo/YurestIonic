import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, interval } from 'rxjs';
import { take } from 'rxjs/operators';
import { AppContextService } from '../../../services/app-context.service';
import { AuthService, AuthUser } from '../../../services/auth.service';
import { RestaurantService } from '../../../services/restaurant.service';

@Component({
  selector: 'app-layout-page',
  templateUrl: './app-layout.page.html',
  styleUrls: ['./app-layout.page.scss'],
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive],
})
export class AppLayoutPage implements OnInit, OnDestroy {
  public currentDateTime: Date = new Date();
  public currentUser: AuthUser | null = null;
  public activeRestaurantName: string = 'Sin restaurante';
  public isAdminUser: boolean = false;

  private timerSubscription?: Subscription;
  private userSubscription?: Subscription;
  private contextSubscription?: Subscription;

  constructor(
    private readonly authService: AuthService,
    private readonly contextService: AppContextService,
    private readonly restaurantService: RestaurantService,
    private readonly router: Router,
  ) {}

  public ngOnInit(): void {
    this.timerSubscription = interval(1000).subscribe(() => {
      this.currentDateTime = new Date();
    });

    this.userSubscription = this.authService.currentUser$.subscribe((user) => {
      this.currentUser = user;
    });

    this.contextSubscription = this.contextService.activeRestaurant$.subscribe((context) => {
      this.activeRestaurantName = context?.name ?? 'Sin restaurante';
    });

    this.authService.restoreSession().pipe(take(1)).subscribe({
      next: () => {
        this.checkAdminAccess();
      },
      error: () => {
        this.currentUser = null;
        this.isAdminUser = false;
      },
    });
  }

  public ngOnDestroy(): void {
    this.timerSubscription?.unsubscribe();
    this.userSubscription?.unsubscribe();
    this.contextSubscription?.unsubscribe();
  }

  public get currentUserInitials(): string {
    if (!this.currentUser?.name) {
      return 'US';
    }

    const parts = this.currentUser.name.trim().split(/\s+/).filter(Boolean);
    const first = parts[0]?.charAt(0) ?? 'U';
    const second = parts[1]?.charAt(0) ?? parts[0]?.charAt(1) ?? 'S';

    return `${first}${second}`.toUpperCase();
  }

  public get topbarDateText(): string {
    const datePart = new Intl.DateTimeFormat('es-ES', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    }).format(this.currentDateTime);

    const timePart = new Intl.DateTimeFormat('es-ES', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(this.currentDateTime);

    return `${datePart} · ${timePart}`;
  }

  public goToCaja(): void {
    this.router.navigateByUrl('/app/caja');
  }

  public logout(): void {
    this.authService.logout().pipe(take(1)).subscribe({
      next: () => {
        this.contextService.clearActiveRestaurant();
        this.isAdminUser = false;
        this.router.navigateByUrl('/login');
      },
      error: () => {
        this.contextService.clearActiveRestaurant();
        this.isAdminUser = false;
        this.router.navigateByUrl('/login');
      },
    });
  }

  private checkAdminAccess(): void {
    this.restaurantService
      .getAdminRestaurants()
      .pipe(take(1))
      .subscribe({
        next: () => {
          this.isAdminUser = true;
        },
        error: () => {
          this.isAdminUser = false;
          if (this.router.url.startsWith('/app/gestion')) {
            this.router.navigateByUrl('/app/mesas');
          }
        },
      });
  }
}
