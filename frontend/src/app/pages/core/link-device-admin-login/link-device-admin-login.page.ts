import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-link-device-admin-login',
  templateUrl: './link-device-admin-login.page.html',
  styleUrls: ['./link-device-admin-login.page.scss'],
  imports: [ReactiveFormsModule, IonContent],
})
export class LinkDeviceAdminLoginPage {
  public readonly loginForm = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  public isSubmitting: boolean = false;

  private readonly toastService = inject(ToastService);

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly router: Router,
  ) {}

  public submit(): void {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();
      return;
    }

    const { email, password } = this.loginForm.getRawValue();

    this.loginWithApi(email, password);
  }

  private loginWithApi(email: string, password: string): void {
    this.isSubmitting = true;

    this.authService
      .loginForDeviceLink(email, password)
      .pipe(
        take(1),
        finalize(() => {
          this.isSubmitting = false;
        }),
      )
      .subscribe({
        next: (user) => {
          this.router.navigate(['/link-device-select-restaurant'], { queryParams: { adminName: user.name } });
        },
        error: (error: unknown) => {
          const message = error instanceof Error ? error.message : 'No se pudo iniciar sesión.';
          const friendlyMessage = message.includes('Only admin users can link devices')
            ? 'Solo los usuarios admin pueden vincular dispositivos.'
            : message;
          this.toastService.presentError(friendlyMessage);
        },
      });
  }

  public goBack(): void {
    this.router.navigateByUrl('/home');
  }
}
