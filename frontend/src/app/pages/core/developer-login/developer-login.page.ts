import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-developer-login',
  templateUrl: './developer-login.page.html',
  styleUrls: ['./developer-login.page.scss'],
  imports: [CommonModule, ReactiveFormsModule, IonContent],
})
export class DeveloperLoginPage {
  public readonly loginForm = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  public isSubmitting: boolean = false;
  public errorMessage: string | null = null;

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
    this.errorMessage = null;

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
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo iniciar sesion.';
        },
      });
  }
}
