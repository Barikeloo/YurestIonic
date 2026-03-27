import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService, AuthUser } from '../../../services/auth.service';

export interface UserModalData {
  mode: 'create' | 'edit' | 'list';
  restaurantUuid: string;
  restaurantName: string;
  user?: AuthUser;
}

@Component({
  selector: 'app-user-modal',
  templateUrl: './user-modal.component.html',
  styleUrls: ['./user-modal.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
  ],
})
export class UserModalComponent implements OnChanges {
  @Input() isOpen = false;
  @Input() modalData: UserModalData = { mode: 'list', restaurantUuid: '', restaurantName: '' };
  @Output() close = new EventEmitter<void>();
  @Output() save = new EventEmitter<void>();

  public form: FormGroup;
  public users: AuthUser[] = [];
  public isLoading = false;
  public isSubmitting = false;
  public error: string | null = null;
  public currentMode: 'list' | 'form' = 'list';

  constructor(
    private readonly fb: FormBuilder,
    private readonly authService: AuthService,
  ) {
    this.form = this.createForm();
  }

  public ngOnChanges(changes: SimpleChanges): void {
    const justOpened = !!changes['isOpen'] && this.isOpen;
    const dataChangedWhileOpen = !!changes['modalData'] && this.isOpen;

    if (justOpened || dataChangedWhileOpen) {
      this.initializeState();
    }
  }

  public get title(): string {
    if (this.currentMode === 'list') {
      return `Usuarios de ${this.modalData.restaurantName}`;
    }

    return this.modalData.mode === 'create' ? 'Nuevo Usuario' : 'Editar Usuario';
  }

  public onClose(): void {
    this.currentMode = 'list';
    this.form.reset();
    this.error = null;
    this.close.emit();
  }

  public switchToCreate(): void {
    this.modalData = {
      ...this.modalData,
      mode: 'create',
      user: undefined,
    };
    this.currentMode = 'form';
    this.setupCreateForm();
  }

  public switchToList(): void {
    this.modalData = {
      ...this.modalData,
      mode: 'list',
      user: undefined,
    };
    this.currentMode = 'list';
    this.loadUsers();
  }

  private loadUsers(): void {
    this.isLoading = true;
    this.error = null;

    this.authService.getRestaurantUsers(this.modalData.restaurantUuid).subscribe({
      next: (users) => {
        this.users = users;
        this.isLoading = false;
      },
      error: (error) => {
        this.error = error instanceof Error ? error.message : 'Error al cargar usuarios';
        this.isLoading = false;
      },
    });
  }

  public onSubmit(): void {
    if (!this.form.valid) {
      return;
    }

    this.isSubmitting = true;
    this.error = null;

    if (this.currentMode === 'form') {
      const formValue = this.form.getRawValue();

      if (this.modalData.mode === 'create') {
        const createData = {
          name: formValue.name,
          email: formValue.email,
          password: formValue.password,
          role: formValue.role || 'operator',
          pin: formValue.pin,
        };

        this.authService.createRestaurantUser(this.modalData.restaurantUuid, createData).subscribe({
          next: () => {
            this.isSubmitting = false;
            this.form.reset();
            this.switchToList();
            this.save.emit();
          },
          error: (error) => {
            this.isSubmitting = false;
            this.error = error instanceof Error ? error.message : 'Error al crear usuario';
          },
        });
      } else if (this.modalData.mode === 'edit' && this.modalData.user) {
        const updateData = {
          name: formValue.name,
          role: formValue.role,
          pin: formValue.pin,
        };

        this.authService
          .updateRestaurantUser(this.modalData.restaurantUuid, this.modalData.user.id, updateData)
          .subscribe({
            next: () => {
              this.isSubmitting = false;
              this.form.reset();
              this.switchToList();
              this.save.emit();
            },
            error: (error) => {
              this.isSubmitting = false;
              this.error = error instanceof Error ? error.message : 'Error al actualizar usuario';
            },
          });
      }
    }
  }

  public deleteUser(user: AuthUser): void {
    if (!confirm(`¿Estás seguro de que deseas eliminar a ${user.name}?`)) {
      return;
    }

    this.authService.deleteRestaurantUser(this.modalData.restaurantUuid, user.id).subscribe({
      next: () => {
        this.loadUsers();
        this.save.emit();
      },
      error: (error) => {
        this.error = error instanceof Error ? error.message : 'Error al eliminar usuario';
      },
    });
  }

  public editUser(user: AuthUser): void {
    this.modalData = {
      ...this.modalData,
      mode: 'edit',
      user,
    };
    this.currentMode = 'form';
    this.setupEditForm(user);
  }

  private initializeState(): void {
    this.error = null;
    this.isSubmitting = false;

    if (this.modalData.mode === 'list') {
      this.currentMode = 'list';
      this.loadUsers();

      return;
    }

    this.currentMode = 'form';

    if (this.modalData.mode === 'edit' && this.modalData.user) {
      this.setupEditForm(this.modalData.user);

      return;
    }

    this.setupCreateForm();
  }

  private setupCreateForm(): void {
    this.form.reset({
      name: '',
      email: '',
      password: '',
      role: 'operator',
      pin: '',
    });

    this.form.get('email')?.enable();
    this.form.get('password')?.enable();
    this.form.get('password')?.setValidators([Validators.required, Validators.minLength(8)]);
    this.form.get('password')?.updateValueAndValidity();
    this.error = null;
  }

  private setupEditForm(user: AuthUser): void {
    this.form.reset({
      name: user.name,
      email: user.email,
      password: '',
      role: user.role ?? 'operator',
      pin: user.pin ?? '',
    });

    // In edit mode, email and password are not editable.
    this.form.get('email')?.disable();
    this.form.get('password')?.disable();
    this.form.get('password')?.clearAsyncValidators();
    this.form.get('password')?.clearValidators();
    this.form.get('password')?.updateValueAndValidity();
    this.error = null;
  }

  private createForm(): FormGroup {
    return this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      role: ['operator', Validators.required],
      pin: [''],
    });
  }
}
