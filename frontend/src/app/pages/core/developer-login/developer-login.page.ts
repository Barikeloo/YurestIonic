import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-developer-login',
  templateUrl: './developer-login.page.html',
  styleUrls: ['./developer-login.page.scss'],
  imports: [ReactiveFormsModule, IonContent],
})
export class DeveloperLoginPage {
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

    this.submitLogin(email, password);
  }

  public goBack(): void {
    this.router.navigateByUrl('/');
  }

  private submitLogin(email: string, password: string): void {
    this.isSubmitting = true;

    this.authService
      .superAdminLogin(email, password)
      .pipe(
        take(1),
        finalize(() => {
          this.isSubmitting = false;
        }),
      )
      .subscribe({
        next: () => {
          this.router.navigateByUrl('/app/developer-dashboard');
        },
        error: (error: unknown) => {
          const message = error instanceof Error ? error.message : 'No se pudo iniciar sesión.';
          this.toastService.presentError(message);
        },
      });
  }
}
