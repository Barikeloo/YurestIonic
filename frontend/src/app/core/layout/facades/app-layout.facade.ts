import { Injectable, inject } from '@angular/core';
import { Observable, of, take } from 'rxjs';
import { Router } from '@angular/router';
import { AppContextService } from '../../services/app-context.service';
import { AuthService } from '../../services/auth.service';
import { TpvService } from '../../../features/cash/services/tpv.service';

@Injectable({
  providedIn: 'root'
})
export class AppLayoutFacade {
  private readonly authService = inject(AuthService);
  private readonly contextService = inject(AppContextService);
  private readonly tpvService = inject(TpvService);
  private readonly router = inject(Router);

  // Métodos de negocio
  public refreshCajaStatus(): Observable<any> {
    const deviceId = this.authService.getDeviceId();
    if (!deviceId) {
      return of(null);
    }
    return this.tpvService.getActiveCashSession(deviceId).pipe(take(1));
  }

  public logout(): Observable<void> {
    return this.authService.logout().pipe(take(1));
  }

  public goToCaja(): void {
    this.router.navigateByUrl('/app/caja');
  }

  public clearActiveRestaurant(): void {
    this.contextService.clearActiveRestaurant();
  }

  public getDeviceId(): string {
    return this.authService.getDeviceId();
  }
}
