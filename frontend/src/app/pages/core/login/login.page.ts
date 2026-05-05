import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { IonContent } from '@ionic/angular/standalone';
import { finalize, take } from 'rxjs/operators';
import { AuthService } from '../../../services/auth.service';
import { DeviceStorageService, LinkedRestaurant } from '../../../services/device-storage.service';

interface QuickUser {
  name: string;
  initials: string;
  userUuid: string;
  role: string;
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

  public employees: QuickUser[] = [];
  public filteredEmployees: QuickUser[] = [];
  public selectedEmployee: QuickUser | null = null;
  public isLoading: boolean = true;
  public searchQuery: string = '';

  public linkedRestaurant: LinkedRestaurant | null = null;

  public showPinPanel: boolean = false;
  public showEmailForm: boolean = false;

  private returnUrl: string = '/app/caja';

  constructor(
    private readonly formBuilder: FormBuilder,
    private readonly authService: AuthService,
    private readonly router: Router,
    private readonly route: ActivatedRoute,
    private readonly deviceStorageService: DeviceStorageService,
  ) {}

  public ionViewWillEnter(): void {
    this.returnUrl = this.route.snapshot.queryParamMap.get('returnUrl') ?? '/app/caja';
    this.linkedRestaurant = this.deviceStorageService.getLinkedRestaurant();
    this.selectedEmployee = null;
    this.pinValue = '';
    this.showPinPanel = false;
    this.showEmailForm = false;
    this.errorMessage = null;

    if (this.linkedRestaurant) {
      this.loadEmployees();
    } else {
      this.isLoading = false;
    }
  }

  public loadEmployees(): void {
    this.isLoading = true;
    this.errorMessage = null;

    this.authService
      .getQuickUsers(this.authService.getDeviceId(), this.linkedRestaurant?.uuid)
      .pipe(take(1))
      .subscribe({
        next: (users) => {
          this.employees = users.map((user) => ({
            name: user.name,
            initials: this.buildInitials(user.name),
            userUuid: user.user_uuid,
            role: user.role,
            color: this.roleColor(user.role),
          }));
          this.filteredEmployees = [...this.employees];
          this.isLoading = false;
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudieron cargar los empleados.';
          this.isLoading = false;
        },
      });
  }

  public onSearchChange(event: Event): void {
    const query = (event.target as HTMLInputElement).value.toLowerCase();
    this.searchQuery = query;
    this.filteredEmployees = this.employees.filter((employee) => employee.name.toLowerCase().includes(query));
  }

  public selectEmployee(employee: QuickUser): void {
    this.selectedEmployee = employee;
    this.pinValue = '';
    this.errorMessage = null;
    this.showPinPanel = true;
    this.showEmailForm = false;
  }

  public isSelectedEmployee(employee: QuickUser): boolean {
    return this.selectedEmployee?.userUuid === employee.userUuid;
  }

  public backToEmployeeList(): void {
    this.selectedEmployee = null;
    this.pinValue = '';
    this.errorMessage = null;
    this.showPinPanel = false;
    this.showEmailForm = false;
  }

  public showClassicLogin(): void {
    this.selectedEmployee = null;
    this.pinValue = '';
    this.errorMessage = null;
    this.showPinPanel = false;
    this.showEmailForm = true;
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

    if (!this.selectedEmployee) {
      this.errorMessage = 'Selecciona un empleado para acceder con PIN.';
      return;
    }

    if (this.pinValue.length !== 4) {
      this.errorMessage = 'El PIN debe tener 4 dígitos.';
      return;
    }

    this.loginWithPinApi(this.selectedEmployee.userUuid, this.pinValue);
  }

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
          this.router.navigateByUrl(this.returnUrl);
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo iniciar sesión.';
        },
      });
  }

  private loginWithPinApi(userUuid: string, pin: string): void {
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
          this.pinValue = '';
          this.router.navigateByUrl(this.returnUrl);
        },
        error: (error: unknown) => {
          this.errorMessage = error instanceof Error ? error.message : 'No se pudo iniciar sesión con PIN.';
          this.pinValue = '';
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
    if (this.linkedRestaurant && (this.showPinPanel || this.showEmailForm)) {
      this.backToEmployeeList();
    } else {
      this.router.navigateByUrl('/home');
    }
  }
}