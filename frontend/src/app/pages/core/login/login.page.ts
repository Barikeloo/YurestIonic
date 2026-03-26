import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';
import { RegisterModalComponent } from '../../../components/register-modal/register-modal.component';

interface QuickUser {
  name: string;
  initials: string;
  email: string;
  pin: string;
  color: string;
}

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  imports: [CommonModule, ReactiveFormsModule, IonContent, RegisterModalComponent],
})
export class LoginPage {
  public readonly loginForm = this.formBuilder.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  public isSubmitting: boolean = false;
  public errorMessage: string | null = null;
  public isRegisterModalOpen: boolean = false;

  public pinValue: string = '';

  public readonly quickUsers: QuickUser[] = [
    {
      name: 'Admin',
      initials: 'AD',
      email: 'admin@tpv.local',
      pin: '1234',
      color: '#E8440A',
    },
    {
      name: 'Supervisor',
      initials: 'SU',
      email: 'supervisor@tpv.local',
      pin: '1235',
      color: '#1A6FE8',
    },
    {
      name: 'Ana',
      initials: 'AN',
      email: 'ana@tpv.local',
      pin: '1236',
      color: '#1A9E5A',
    },
    {
      name: 'Luis',
      initials: 'LU',
      email: 'luis@tpv.local',
      pin: '1237',
      color: '#5A36E2',
    },
  ];

  public selectedQuickUser: QuickUser = this.quickUsers[0];

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly route: ActivatedRoute,
    private readonly router: Router,
  ) {
    this.route.queryParamMap.pipe(take(1)).subscribe((params) => {
      if (params.get('register') === '1') {
        this.isRegisterModalOpen = true;
      }
    });
  }

  public selectUser(user: QuickUser): void {
    this.selectedQuickUser = user;
    this.pinValue = '';
    this.errorMessage = null;
  }

  public isSelectedQuickUser(user: QuickUser): boolean {
    return this.selectedQuickUser.email === user.email;
  }

  public isPinDotFilled(index: number): boolean {
    return index < this.pinValue.length;
  }

  public pinKey(value: string): void {
    if (this.isSubmitting || this.pinValue.length >= 4) {
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

    if (this.pinValue.length !== 4) {
      this.errorMessage = 'El PIN debe tener 4 digitos.';

      return;
    }

    if (this.pinValue !== this.selectedQuickUser.pin) {
      this.errorMessage = 'PIN incorrecto para el usuario seleccionado.';
      this.pinValue = '';

      return;
    }

    this.loginWithApi(this.selectedQuickUser.email, 'password', true);
  }

  public submit(): void {
    if (this.loginForm.invalid) {
      this.loginForm.markAllAsTouched();

      return;
    }

    const { email, password } = this.loginForm.getRawValue();

    this.loginWithApi(email, password, false);
  }

  public onCreateAccount(): void {
    this.isRegisterModalOpen = true;
  }

  public closeRegisterModal(): void {
    this.isRegisterModalOpen = false;
  }

  public onUserCreated(email: string): void {
    this.loginForm.patchValue({ email, password: '' });
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

  public goBack(): void {
    this.router.navigateByUrl('/home');
  }
}