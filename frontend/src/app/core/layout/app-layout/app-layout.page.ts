
import { Component, inject, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { Subscription, interval } from 'rxjs';
import { filter, take } from 'rxjs/operators';
import { AppContextService } from '../../services/app-context.service';
import { AuthService, AuthUser } from '../../services/auth.service';
import { RestaurantContextFacade } from '../../facades/restaurant-context.facade';
import { TpvService } from '../../../features/cash/services/tpv.service';
import { AppLayoutFacade } from '../facades/app-layout.facade';
import { UserRole } from '../../enums/user-role.enum';

@Component({
  selector: 'app-layout-page',
  templateUrl: './app-layout.page.html',
  styleUrls: ['./app-layout.page.scss'],
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
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
  private adminSelectedContext: boolean = false;

  protected readonly layoutFacade = inject(AppLayoutFacade);
  protected readonly restaurantContextFacade = inject(RestaurantContextFacade);

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
      this.isAdminUser = user?.role === UserRole.ADMIN;

      // Limpiar contexto persistido si el usuario no es admin
      if (!this.isAdminUser) {
        this.adminSelectedContext = false;
        this.restaurantContextFacade.clearRestaurantContext();
        localStorage.removeItem('gestion_selected_restaurant_uuid');
      }

      // Solo establecer el contexto del usuario autenticado si no ha sido seleccionado manualmente por admin
      if (user?.restaurantName && !this.adminSelectedContext) {
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
      if (context?.name) {
        this.activeRestaurantName = context.name;
        // Marcar que el contexto ha sido seleccionado manualmente
        if (this.isAdminUser) {
          this.adminSelectedContext = true;
        }
      }
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
    this.layoutFacade.refreshCajaStatus().subscribe({
      next: (session: any) => {
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

  public logout(): void {
    this.layoutFacade.logout().subscribe({
      next: () => {
        this.layoutFacade.clearActiveRestaurant();
        this.isAdminUser = false;
        this.router.navigateByUrl('/login');
      },
      error: () => {
        this.layoutFacade.clearActiveRestaurant();
        this.isAdminUser = false;
        this.router.navigateByUrl('/login');
      },
    });
  }

  public goToCaja(): void {
    this.layoutFacade.goToCaja();
  }

}
