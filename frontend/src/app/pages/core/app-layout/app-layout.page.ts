import { CommonModule } from '@angular/common';
import { Component, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, interval } from 'rxjs';
import { filter, take } from 'rxjs/operators';
import { AppContextService } from '../../../services/app-context.service';
import { AuthService, AuthUser } from '../../../services/auth.service';
import { TpvService } from '../../../services/tpv.service';

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
  public isPedidosOrComanda: boolean = false;
  public isCajaOpen: boolean = false;
  public cajaLoaded: boolean = false;

  private timerSubscription?: Subscription;
  private userSubscription?: Subscription;
  private contextSubscription?: Subscription;
  private routerSubscription?: Subscription;

  constructor(
    private readonly authService: AuthService,
    private readonly contextService: AppContextService,
    private readonly tpvService: TpvService,
    private readonly router: Router,
  ) {}

  public ngOnInit(): void {
    this.timerSubscription = interval(1000).subscribe(() => {
      this.currentDateTime = new Date();
    });

    this.userSubscription = this.authService.currentUser$.subscribe((user) => {
      this.currentUser = user;
      this.isAdminUser = user?.role === 'admin';

      if (user?.restaurantName) {
        this.contextService.setActiveRestaurant({
          id: user.restaurantId,
          name: user.restaurantName,
        });
      }

      if (!this.isAdminUser && this.router.url.startsWith('/app/gestion')) {
        this.router.navigateByUrl('/app/mesas');
      }
    });

    this.contextSubscription = this.contextService.activeRestaurant$.subscribe((context) => {
      this.activeRestaurantName = context?.name ?? 'Sin restaurante';
    });

    this.routerSubscription = this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        this.isPedidosOrComanda = this.router.url.startsWith('/app/pedidos') || this.router.url.startsWith('/app/comanda');
        this.refreshCajaStatus();
      });

    this.authService.restoreSession().pipe(take(1)).subscribe({
      next: () => undefined,
      error: () => {
        this.currentUser = null;
        this.isAdminUser = false;
      },
    });

    this.refreshCajaStatus();
  }

  public ngOnDestroy(): void {
    this.timerSubscription?.unsubscribe();
    this.userSubscription?.unsubscribe();
    this.contextSubscription?.unsubscribe();
    this.routerSubscription?.unsubscribe();
  }

  private refreshCajaStatus(): void {
    const deviceId = this.authService.getDeviceId();
    if (!deviceId) return;
    this.tpvService.getActiveCashSession(deviceId).pipe(take(1)).subscribe({
      next: (session) => {
        this.isCajaOpen = session?.status === 'open';
        this.cajaLoaded = true;
      },
      error: () => {
        this.isCajaOpen = false;
        this.cajaLoaded = true;
      },
    });
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

}
