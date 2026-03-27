import { Component, Input, Output, EventEmitter, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { IonText } from '@ionic/angular/standalone';
import { AuthService } from '../../../services/auth.service';

export interface RestaurantModalData {
    mode: 'create' | 'edit';
    presetTaxId?: string;
    restaurant?: {
        uuid: string;
        name: string;
        legal_name: string;
        tax_id: string;
        email: string;
    };
}

@Component({
    selector: 'app-restaurant-modal',
    templateUrl: './restaurant-modal.component.html',
    styleUrls: ['./restaurant-modal.component.scss'],
    standalone: true,
    imports: [
        CommonModule,
        ReactiveFormsModule,
        IonText,
    ],
})
export class RestaurantModalComponent implements OnChanges {
    @Input() isOpen = false;
    @Input() modalData: RestaurantModalData = { mode: 'create' };
    @Output() close = new EventEmitter<void>();
    @Output() save = new EventEmitter<void>();

    public form: FormGroup;
    public isSubmitting = false;
    public error: string | null = null;

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
            this.initializeForm();
        }
    }

    private initializeForm(): void {
        this.error = null;
        this.isSubmitting = false;

        if (this.modalData.mode === 'edit' && this.modalData.restaurant) {
            this.setupEditMode();

            return;
        }

        this.setupCreateMode();
    }

    private setupEditMode(): void {
        const restaurant = this.modalData.restaurant;
        if (!restaurant) return;

        this.form.reset();
        this.form.get('tax_id')?.enable();
        this.form.get('email')?.enable();

        // Poblar el form
        this.form.patchValue({
            name: restaurant.name,
            legal_name: restaurant.legal_name,
            tax_id: restaurant.tax_id,
            email: restaurant.email,
            password: '',
            pin: '',
        });

        // Desactivar campos
        this.form.get('tax_id')?.disable();
        this.form.get('email')?.disable();
        this.form.get('password')?.clearValidators();
        this.form.get('password')?.updateValueAndValidity();
        this.form.get('pin')?.clearValidators();
        this.form.get('pin')?.updateValueAndValidity();
    }

    private setupCreateMode(): void {
        this.form.reset();
        this.form.get('tax_id')?.enable();
        this.form.get('email')?.enable();
        this.form.get('password')?.setValidators([Validators.required, Validators.minLength(8)]);
        this.form.get('password')?.updateValueAndValidity();
        this.form.get('pin')?.setValidators([Validators.required, Validators.pattern(/^\d{4}$/)]);
        this.form.get('pin')?.updateValueAndValidity();

        if (this.modalData.presetTaxId) {
            this.form.patchValue({ tax_id: this.modalData.presetTaxId });
            this.form.get('tax_id')?.disable();
        }
    }

    public onClose(): void {
        this.close.emit();
    }

    public onSubmit(): void {
        console.log('Form valid?', this.form.valid);
        if (!this.form.valid) {
            console.log('Form errors:', this.form.errors);
            return;
        }

        this.isSubmitting = true;
        this.error = null;

        const formValue = this.form.getRawValue();
        console.log('Enviando:', this.modalData.mode, formValue);

        if (this.modalData.mode === 'create') {
            this.createRestaurant(formValue);
        } else if (this.modalData.mode === 'edit' && this.modalData.restaurant) {
            this.updateRestaurant(formValue);
        }
    }

    private createRestaurant(formValue: any): void {
        const companyMode: 'existing' | 'new' = this.modalData.presetTaxId ? 'existing' : 'new';

        const createData = {
            name: formValue.name,
            legal_name: formValue.legal_name,
            tax_id: formValue.tax_id,
            email: formValue.email,
            password: formValue.password,
            pin: formValue.pin,
            company_mode: companyMode,
        };

        console.log('Creando restaurante con:', createData);

        this.authService.createRestaurant(createData).subscribe({
            next: () => {
                this.isSubmitting = false;
                console.log('✅ Restaurante creado');
                this.form.reset();
                this.save.emit();
                this.onClose();
            },
            error: (error) => {
                this.isSubmitting = false;
                this.error = error instanceof Error ? error.message : 'Error al crear restaurante';
                console.error('❌ Error crear:', this.error);
            },
        });
    }

    private updateRestaurant(formValue: any): void {
        if (!this.modalData.restaurant) return;

        const updateData = {
            name: formValue.name,
            legal_name: formValue.legal_name,
        };

        console.log('Actualizando restaurante:', this.modalData.restaurant.uuid, updateData);

        this.authService.updateRestaurant(this.modalData.restaurant.uuid, updateData).subscribe({
            next: () => {
                this.isSubmitting = false;
                console.log('✅ Restaurante actualizado');
                this.form.reset();
                this.save.emit();
                this.onClose();
            },
            error: (error) => {
                this.isSubmitting = false;
                this.error = error instanceof Error ? error.message : 'Error al actualizar restaurante';
                console.error('❌ Error actualizar:', this.error);
            },
        });
    }

    private createForm(): FormGroup {
        return this.fb.group({
            name: ['', [Validators.required, Validators.minLength(3)]],
            legal_name: ['', [Validators.required, Validators.minLength(3)]],
            tax_id: ['', [Validators.required, Validators.pattern(/^[A-Z0-9]+$/i)]],
            email: ['', [Validators.required, Validators.email]],
            password: ['', [Validators.required, Validators.minLength(8)]],
            pin: ['', [Validators.required, Validators.pattern(/^\d{4}$/)]],
        });
    }
}
