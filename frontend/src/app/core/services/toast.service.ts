import { Injectable, inject } from '@angular/core';
import { ToastController, ToastOptions } from '@ionic/angular/standalone';

@Injectable({
  providedIn: 'root'
})
export class ToastService {
  private readonly toastController = inject(ToastController);

  async presentSuccess(message: string, duration: number = 3000): Promise<void> {
    const toast = await this.toastController.create({
      message,
      duration,
      color: 'success',
      position: 'bottom',
      buttons: [
        {
          text: 'OK',
          role: 'cancel',
        },
      ],
    });
    await toast.present();
  }

  async presentError(message: string, duration: number = 5000): Promise<void> {
    const toast = await this.toastController.create({
      message,
      duration,
      color: 'danger',
      position: 'bottom',
      buttons: [
        {
          text: 'OK',
          role: 'cancel',
        },
      ],
    });
    await toast.present();
  }

  async presentInfo(message: string, duration: number = 3000): Promise<void> {
    const toast = await this.toastController.create({
      message,
      duration,
      color: 'primary',
      position: 'bottom',
      buttons: [
        {
          text: 'OK',
          role: 'cancel',
        },
      ],
    });
    await toast.present();
  }

  async presentWarning(message: string, duration: number = 4000): Promise<void> {
    const toast = await this.toastController.create({
      message,
      duration,
      color: 'warning',
      position: 'bottom',
      buttons: [
        {
          text: 'OK',
          role: 'cancel',
        },
      ],
    });
    await toast.present();
  }

  async presentCustom(options: ToastOptions): Promise<void> {
    const toast = await this.toastController.create(options);
    await toast.present();
  }
}
