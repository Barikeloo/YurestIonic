import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  imports: [IonContent],
})
export class HomePage {
  constructor(private readonly router: Router) {}

  public goToLogin(): void {
    this.router.navigateByUrl('/login');
  }

  public goToRegister(): void {
    this.router.navigate(['/login'], {
      queryParams: { register: '1' },
    });
  }
}
