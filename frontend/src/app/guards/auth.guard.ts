import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot } from '@angular/router';
import { Observable, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';
import { DeviceStorageService } from '../services/device-storage.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  constructor(
    private readonly authService: AuthService,
    private readonly deviceStorage: DeviceStorageService,
    private readonly router: Router,
  ) {}

  public canActivate(_route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Observable<boolean> {
    return this.authService.getMe().pipe(
      map(() => true),
      catchError(() => {
        if (this.deviceStorage.isDeviceLinked()) {
          this.router.navigate(['/login'], { queryParams: { returnUrl: state.url } });
        } else {
          this.router.navigateByUrl('/home');
        }
        return of(false);
      }),
    );
  }
}
