import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-register-modal',
  templateUrl: './register-modal.component.html',
  styleUrls: ['./register-modal.component.scss'],
  imports: [CommonModule, ReactiveFormsModule],
})
export class RegisterModalComponent {
  @Input() public isOpen: boolean = false;

  @Output() public readonly closed = new EventEmitter<void>();
  @Output() public readonly created = new EventEmitter<string>();

  public readonly registerForm = this.formBuilder.nonNullable.group({
    restaurantName: ['', [Validators.required, Validators.maxLength(255)]],
    legalName: ['', [Validators.maxLength(255)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
    nif: [''],
    pin: ['', [Validators.pattern(/^\d{4}$/)]],
    password: ['', [Validators.required, Validators.minLength(8)]],
  });

  public isSubmitting: boolean = false;
  public errorMessage: string | null = null;
  public successMessage: string | null = null;
  public generatedPinMessage: string | null = null;

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
  ) {}

  public close(): void {
    if (this.isSubmitting) {
      return;
    }

    this.closed.emit();
  }

  public closeOutside(event: MouseEvent): void {
    if (event.target instanceof HTMLElement && event.target.classList.contains('modal-overlay')) {
      this.close();
    }
  }

  public submit(): void {
    if (this.registerForm.invalid) {
      this.registerForm.markAllAsTouched();

      return;
    }

    this.isSubmitting = true;
    this.errorMessage = null;
    this.successMessage = null;
    this.generatedPinMessage = null;

    const { restaurantName, legalName, email, nif, pin, password } = this.registerForm.getRawValue();

    this.authService
      .register(restaurantName, email, password, nif, legalName, pin || undefined)
      .pipe(
        take(1),
        finalize(() => {
          this.isSubmitting = false;
        }),
      )
      .subscribe({
        next: (response) => {
          this.successMessage = 'Cuenta creada correctamente. Ya puedes iniciar sesion.';
          if (response.admin_pin) {
            this.generatedPinMessage = `PIN de administrador: ${response.admin_pin}`;
          }
          this.registerForm.patchValue({
            restaurantName: '',
            legalName: '',
            email: '',
            nif: '',
            pin: '',
            password: '',
          });
          this.created.emit(email);

          setTimeout(() => {
            this.closed.emit();
          }, 700);
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo crear la cuenta.';
        },
      });
  }
}