import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';

interface QuickUser {
  name: string;
  initials: string;
  userUuid: string;
  role: string;
  restaurantName: string;
  color: string;
}

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  imports: [CommonModule, ReactiveFormsModule, IonContent],
})
export class LoginPage {
  public readonly loginForm = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  public isSubmitting: boolean = false;
  public errorMessage: string | null = null;

  public pinValue: string = '';

  public quickUsers: QuickUser[] = [];
  public selectedQuickUser: QuickUser | null = null;

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly router: Router,
  ) {
    this.loadQuickUsers();
  }

  public ionViewWillEnter(): void {
    this.loadQuickUsers();
  }

  public selectUser(user: QuickUser): void {
    this.selectedQuickUser = user;
    this.pinValue = '';
    this.errorMessage = null;
  }

  public isSelectedQuickUser(user: QuickUser): boolean {
    return this.selectedQuickUser?.userUuid === user.userUuid;
  }

  public isPinDotFilled(index: number): boolean {
    return index < this.pinValue.length;
  }

  public pinKey(value: string): void {
    if (this.isSubmitting || this.pinValue.length >= 4 || !this.selectedQuickUser) {
      return;
    }

    this.pinValue += value;

    if (this.pinValue.length === 4) {
      this.pinEnter();
    }
  }

  public pinDel(): void {
    if (this.isSubmitting || this.pinValue.length === 0) {
      return;
    }

    this.pinValue = this.pinValue.slice(0, -1);
  }

  public pinEnter(): void {
    if (this.isSubmitting) {
      return;
    }

    if (!this.selectedQuickUser) {
      this.errorMessage = 'Selecciona un usuario para acceder con PIN.';

      return;
    }

    if (this.pinValue.length !== 4) {
      this.errorMessage = 'El PIN debe tener 4 digitos.';

      return;
    }

    this.loginWithPinApi(this.selectedQuickUser.userUuid, this.pinValue, true);
  }

  public submit(): void {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();

      return;
    }

    const { email, password } = this.loginForm.getRawValue();

    this.loginWithApi(email, password, false);
  }

  private loginWithApi(email: string, password: string, fromPin: boolean): void {
    this.isSubmitting = true;
    this.errorMessage = null;

    this.authService
      .login(email, password)
      .pipe(
        take(1),
        finalize(() => {
          this.isSubmitting = false;
        }),
      )
      .subscribe({
        next: () => {
          if (fromPin) {
            this.pinValue = '';
          }

          this.router.navigateByUrl('/app/gestion');
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo iniciar sesion.';

          if (fromPin) {
            this.pinValue = '';
          }
        },
      });
  }

  private loginWithPinApi(userUuid: string, pin: string, fromPin: boolean): void {
    this.isSubmitting = true;
    this.errorMessage = null;

    this.authService
      .loginWithPin(userUuid, pin, this.getDeviceId())
      .pipe(
        take(1),
        finalize(() => {
          this.isSubmitting = false;
        }),
      )
      .subscribe({
        next: () => {
          if (fromPin) {
            this.pinValue = '';
          }

          this.router.navigateByUrl('/app/gestion');
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo iniciar sesion con PIN.';

          if (fromPin) {
            this.pinValue = '';
          }
        },
      });
  }

  private loadQuickUsers(): void {
    this.authService
      .getQuickUsers(this.getDeviceId())
      .pipe(take(1))
      .subscribe({
        next: (users) => {
          this.quickUsers = users.map((user) => ({
            name: user.name,
            initials: this.buildInitials(user.name),
            userUuid: user.user_uuid,
            role: user.role,
            restaurantName: user.restaurant_name,
            color: this.roleColor(user.role),
          }));

          this.selectedQuickUser = this.quickUsers[0] ?? null;
        },
        error: () => {
          this.quickUsers = [];
          this.selectedQuickUser = null;
        },
      });
  }

  private buildInitials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    const first = parts[0]?.charAt(0) ?? 'U';
    const second = parts[1]?.charAt(0) ?? parts[0]?.charAt(1) ?? 'S';

    return `${first}${second}`.toUpperCase();
  }

  private roleColor(role: string): string {
    if (role === 'admin') {
      return '#E8440A';
    }

    if (role === 'supervisor') {
      return '#1A6FE8';
    }

    return '#1A9E5A';
  }

  private getDeviceId(): string {
    return this.authService.getDeviceId();
  }

  public goBack(): void {
    this.router.navigateByUrl('/home');
  }
}