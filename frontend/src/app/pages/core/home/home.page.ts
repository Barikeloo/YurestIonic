import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { DeviceStorageService } from '../../../services/device-storage.service';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [IonContent],
})
export class HomePage {
  constructor(
    private readonly router: Router,
    private readonly deviceStorageService: DeviceStorageService,
  ) {}

  public ionViewWillEnter(): void {
    console.log('HomePage ionViewWillEnter - isDeviceLinked:', this.deviceStorageService.isDeviceLinked());
    if (this.deviceStorageService.isDeviceLinked()) {
      this.router.navigateByUrl('/login');
    }
  }

  public goToLinkDevice(): void {
    console.log('goToLinkDevice called');
    this.router.navigateByUrl('/link-device-admin-login').then(() => {
      console.log('Navigation successful');
    }).catch((error) => {
      console.error('Navigation failed:', error);
    });
  }

  public goToDeveloperLogin(): void {
    this.router.navigateByUrl('/developer-login');
  }
}
