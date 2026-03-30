import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { take } from 'rxjs/operators';
import { AppContextService } from '../../../services/app-context.service';
import { FamilyItem, FamilyService } from '../../../services/family.service';
import { RestaurantService } from '../../../services/restaurant.service';

type ManagementEntityKey = 'restaurant' | 'users' | 'families' | 'products' | 'zones' | 'taxes';
type UserRole = 'operator' | 'supervisor' | 'admin';

interface ManagementRestaurant {
  id: number;
  uuid?: string;
  name: string;
  legalName: string;
  taxId: string;
  email: string;
  status: 'active';
  users: number;
  zones: number;
  products: number;
}

interface UserRow {
  uuid?: string;
  name: string;
  role: UserRole;
  email: string;
  pin?: string;
  password?: string;
}

interface FamilyRow {
  uuid?: string;
  name: string;
  active: boolean;
}

interface TaxRow {
  name: string;
  percentage: number;
}

interface TableRow {
  name: string;
}

interface ZoneRow {
  name: string;
  tables: TableRow[];
}

interface ProductRow {
  name: string;
  family: string;
  price: number;
  tax: number;
  stock: number;
  active: boolean;
}

interface ManagementDataRow {
  users: UserRow[];
  families: FamilyRow[];
  taxes: TaxRow[];
  zones: ZoneRow[];
  products: ProductRow[];
}

@Component({
  selector: 'app-gestion',
  templateUrl: './gestion.page.html',
  styleUrls: ['./gestion.page.scss'],
  imports: [CommonModule, FormsModule, RouterModule],
})
export class GestionPage {
  public apiErrorMessage: string | null = null;
  public isSavingRestaurant: boolean = false;
  public isSavingUser: boolean = false;
  public isSavingFamily: boolean = false;

  public readonly managementRestaurants: ManagementRestaurant[] = [
    {
      id: 1,
      name: 'Restaurante Voraz',
      legalName: 'Voraz Food S.L.',
      taxId: 'B12345678',
      email: 'admin@voraz.es',
      status: 'active',
      users: 6,
      zones: 4,
      products: 78,
    },
    {
      id: 2,
      name: 'Bahia 21',
      legalName: 'Bahia 21 Hosteleria S.L.',
      taxId: 'B84533122',
      email: 'direccion@bahia21.es',
      status: 'active',
      users: 4,
      zones: 3,
      products: 54,
    },
    {
      id: 3,
      name: 'La Terraza Azul',
      legalName: 'Grupo Terraza Azul S.L.',
      taxId: 'B78451235',
      email: 'admin@terrazaazul.es',
      status: 'active',
      users: 5,
      zones: 5,
      products: 92,
    },
  ];

  public readonly managementData: Record<number, ManagementDataRow> = {
    1: {
      users: [
        { name: 'Maria Gomez', role: 'admin', email: 'maria@voraz.es', pin: '1234' },
        { name: 'Carlos Ruiz', role: 'operator', email: 'carlos@voraz.es', pin: '2580' },
      ],
      families: [
        { name: 'Entrantes', active: true },
        { name: 'Principales', active: true },
        { name: 'Bebidas', active: true },
      ],
      taxes: [
        { name: 'IVA reducido', percentage: 10 },
        { name: 'IVA general', percentage: 21 },
      ],
      zones: [
        { name: 'Terraza', tables: [{ name: 'T1' }, { name: 'T2' }, { name: 'T3' }, { name: 'T4' }] },
        { name: 'Salon', tables: [{ name: 'S1' }, { name: 'S2' }, { name: 'S3' }] },
        { name: 'Barra', tables: [{ name: 'B1' }, { name: 'B2' }] },
      ],
      products: [
        { name: 'Burger de Retinto', family: 'Principales', price: 1450, tax: 10, stock: 27, active: true },
        { name: 'Paulaner', family: 'Bebidas', price: 300, tax: 10, stock: 80, active: true },
      ],
    },
    2: {
      users: [
        { name: 'Eva Luna', role: 'admin', email: 'eva@bahia21.es', pin: '4455' },
        { name: 'Joel Nunez', role: 'operator', email: 'joel@bahia21.es', pin: '2211' },
      ],
      families: [
        { name: 'Tapas', active: true },
        { name: 'Bebidas', active: true },
      ],
      taxes: [{ name: 'IVA hosteleria', percentage: 10 }],
      zones: [
        { name: 'Principal', tables: [{ name: 'P1' }, { name: 'P2' }, { name: 'P3' }] },
        { name: 'Terraza', tables: [{ name: 'T1' }, { name: 'T2' }, { name: 'T3' }, { name: 'T4' }] },
      ],
      products: [
        { name: 'Croqueta casera', family: 'Tapas', price: 250, tax: 10, stock: 120, active: true },
        { name: 'Tinto de verano', family: 'Bebidas', price: 320, tax: 10, stock: 75, active: true },
      ],
    },
    3: {
      users: [
        { name: 'Paula Sanz', role: 'admin', email: 'paula@terrazaazul.es', pin: '4451' },
        { name: 'Leo Martin', role: 'operator', email: 'leo@terrazaazul.es', pin: '7854' },
      ],
      families: [
        { name: 'Desayunos', active: true },
        { name: 'Cafeteria', active: true },
        { name: 'Postres', active: true },
      ],
      taxes: [
        { name: 'IVA reducido', percentage: 10 },
        { name: 'IVA general', percentage: 21 },
      ],
      zones: [
        { name: 'Mirador', tables: [{ name: 'M1' }, { name: 'M2' }, { name: 'M3' }] },
        { name: 'Interior', tables: [{ name: 'I1' }, { name: 'I2' }] },
        { name: 'Lounge', tables: [{ name: 'L1' }, { name: 'L2' }] },
      ],
      products: [
        { name: 'Brunch premium', family: 'Desayunos', price: 1890, tax: 10, stock: 20, active: true },
        { name: 'Cafe flat white', family: 'Cafeteria', price: 290, tax: 10, stock: 200, active: true },
      ],
    },
  };

  public readonly managementEntities: Array<{ key: ManagementEntityKey; label: string }> = [
    { key: 'restaurant', label: 'Restaurante' },
    { key: 'users', label: 'Usuarios' },
    { key: 'families', label: 'Familias' },
    { key: 'products', label: 'Productos' },
    { key: 'zones', label: 'Zonas y Mesas' },
    { key: 'taxes', label: 'Impuestos' },
  ];

  public managementState: {
    restaurantId: number;
    entity: ManagementEntityKey;
    selectedIndex: Record<'users' | 'families' | 'products' | 'zones' | 'tables' | 'taxes', number>;
  } = {
    restaurantId: 1,
    entity: 'restaurant',
    selectedIndex: {
      users: 0,
      families: 0,
      products: 0,
      zones: 0,
      tables: 0,
      taxes: 0,
    },
  };

  public restaurantForm = {
    name: '',
    legalName: '',
    taxId: '',
    email: '',
    password: '',
  };

  public userForm = {
    name: '',
    email: '',
    role: 'operator' as UserRole,
    pin: '',
    password: '',
  };

  public readonly roleOptions: Array<{ value: UserRole; label: string }> = [
    { value: 'operator', label: 'Operario' },
    { value: 'supervisor', label: 'Supervisor' },
    { value: 'admin', label: 'Administrador' },
  ];

  public familyForm = {
    name: '',
    active: true,
  };

  public productForm = {
    name: '',
    family: '',
    tax: 10,
    price: '',
    stock: 0,
    active: true,
  };

  public zoneForm = {
    name: '',
  };

  public tableForm = {
    name: '',
  };

  public taxForm = {
    name: '',
    percentage: 10,
  };

  constructor(
    private readonly contextService: AppContextService,
    private readonly familyService: FamilyService,
    private readonly restaurantService: RestaurantService,
  ) {
    this.syncForms();
    if (this.selectedRestaurant) {
      this.contextService.setActiveRestaurant({ name: this.selectedRestaurant.name });
    }
    this.loadRestaurantsFromApi();
  }

  private loadFamilies(silent: boolean = false): void {
    this.familyService
      .listFamilies()
      .pipe(take(1))
      .subscribe({
        next: (families) => {
          const restaurant = this.selectedRestaurant;
          if (!restaurant) {
            return;
          }

          this.managementData[restaurant.id].families = families.map((family: FamilyItem): FamilyRow => ({
            uuid: family.id,
            name: family.name,
            active: family.active,
          }));

          this.syncForms();

          if (!silent) {
            this.apiErrorMessage = null;
          }
        },
        error: (error: unknown) => {
          if (!silent) {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar las familias.';
          }
        },
      });
  }

  public get selectedRestaurant(): ManagementRestaurant | null {
    return this.managementRestaurants.find((restaurant) => restaurant.id === this.managementState.restaurantId) ?? null;
  }

  public get selectedData(): ManagementDataRow {
    return (
      this.managementData[this.managementState.restaurantId] ?? {
        users: [],
        families: [],
        taxes: [],
        zones: [],
        products: [],
      }
    );
  }

  public get selectedZone(): ZoneRow | null {
    return this.selectedItem('zones', this.selectedData.zones);
  }

  public get selectedTable(): TableRow | null {
    const zone = this.selectedZone;
    if (!zone || !zone.tables.length) {
      return null;
    }

    const idx = this.managementState.selectedIndex.tables;
    if (idx === -1) {
      return null;
    }

    if (idx < 0 || idx >= zone.tables.length) {
      this.managementState.selectedIndex.tables = 0;

      return zone.tables[0];
    }

    return zone.tables[idx];
  }

  public canDeleteSelectedUser(): boolean {
    const users = this.selectedData.users;
    const idx = this.managementState.selectedIndex.users;

    if (idx < 0 || idx >= users.length) {
      return true;
    }

    return users[idx].role !== 'admin';
  }

  public isRestaurantActive(restaurantId: number): boolean {
    return this.managementState.restaurantId === restaurantId;
  }

  public isEntityActive(entity: ManagementEntityKey): boolean {
    return this.managementState.entity === entity;
  }

  public isSelectedRow(entityKey: keyof ManagementDataRow, index: number): boolean {
    return this.managementState.selectedIndex[entityKey] === index;
  }

  public isSelectedTableRow(index: number): boolean {
    return this.managementState.selectedIndex.tables === index;
  }

  public selectRestaurant(restaurantId: number): void {
    this.managementState.restaurantId = restaurantId;
    if (this.selectedRestaurant) {
      this.contextService.setActiveRestaurant({ name: this.selectedRestaurant.name });
      if (this.selectedRestaurant.uuid) {
        this.restaurantService
          .selectAdminRestaurantContext(this.selectedRestaurant.uuid)
          .pipe(take(1))
          .subscribe({
            next: () => {
              this.loadRestaurantUsers(this.selectedRestaurant!.uuid!);
              this.loadFamilies();
            },
            error: (error: unknown) => {
              const message = error instanceof Error ? error.message : 'No se pudo seleccionar el restaurante.';

              if (message === 'Forbidden for this tax id.') {
                this.apiErrorMessage = null;
                this.loadRestaurantsFromApi();

                return;
              }

              this.apiErrorMessage = message;
            },
          });
      }
    }
    this.syncForms();
  }

  public selectEntity(entity: ManagementEntityKey): void {
    this.managementState.entity = entity;
    this.syncForms();
  }

  public selectManagementItem(entityKey: keyof ManagementDataRow, index: number): void {
    this.managementState.selectedIndex[entityKey] = index;
    if (entityKey === 'zones') {
      this.managementState.selectedIndex.tables = 0;
    }
    this.syncForms();
  }

  public startCreateManagementItem(entityKey: keyof ManagementDataRow): void {
    this.managementState.selectedIndex[entityKey] = -1;
    if (entityKey === 'zones') {
      this.managementState.selectedIndex.tables = -1;
      this.tableForm = { name: '' };
    }
    this.syncForms();
  }

  public deleteSelectedManagementItem(entityKey: keyof ManagementDataRow): void {
    const rows = this.selectedData[entityKey];
    const idx = this.managementState.selectedIndex[entityKey];

    if (!rows.length || idx < 0 || idx >= rows.length) {
      window.alert('No hay un registro seleccionado para eliminar.');

      return;
    }

    if (entityKey === 'families') {
      const family = rows[idx] as FamilyRow;
      if (!family.uuid) {
        window.alert('No se puede eliminar: familia sin identificador.');

        return;
      }

      this.familyService
        .deleteFamily(family.uuid)
        .pipe(take(1))
        .subscribe({
          next: () => {
            rows.splice(idx, 1);
            this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.apiErrorMessage = null;
            window.alert('Familia eliminada.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar la familia.';
          },
        });

      return;
    }

    if (entityKey === 'users') {
      const user = rows[idx] as UserRow;
      if (!user.uuid || !this.selectedRestaurant?.uuid) {
        window.alert('No se puede eliminar: usuario sin identificador.');

        return;
      }

      this.restaurantService
        .deleteRestaurantUser(this.selectedRestaurant.uuid, user.uuid)
        .pipe(take(1))
        .subscribe({
          next: () => {
            rows.splice(idx, 1);
            this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.apiErrorMessage = null;
            window.alert('Usuario eliminado.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar el usuario.';
          },
        });

      return;
    }

    if (entityKey === 'zones') {
      const selectedZone = rows[idx] as ZoneRow;
      if (selectedZone.tables.length > 0) {
        window.alert('No puedes eliminar una zona con mesas. Elimina o reasigna primero sus mesas.');

        return;
      }
      this.managementState.selectedIndex.tables = 0;
    }

    rows.splice(idx, 1);
    this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
    this.updateRestaurantKpis(this.managementState.restaurantId);
    this.syncForms();
    window.alert('Registro eliminado.');
  }

  public saveRestaurantChanges(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    const name = this.restaurantForm.name.trim();
    const email = this.restaurantForm.email.trim();
    const password = this.restaurantForm.password.trim();

    if (!name || !email) {
      window.alert('Completa todos los campos obligatorios.');

      return;
    }

    if (!restaurant.uuid) {
      window.alert('No se puede actualizar: restaurante sin identificador.');

      return;
    }

    this.isSavingRestaurant = true;

    this.restaurantService
      .updateAdminRestaurant(restaurant.uuid, {
        name,
        email,
        ...(password ? { password } : {}),
      })
      .pipe(take(1))
      .subscribe({
        next: () => {
          restaurant.name = name;
          restaurant.email = email;
          this.restaurantForm.password = '';
          this.apiErrorMessage = null;

          this.syncForms();
          this.isSavingRestaurant = false;
          window.alert('Restaurante actualizado.');
        },
        error: (error: unknown) => {
          this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el restaurante.';
          this.isSavingRestaurant = false;
        },
      });
  }

  public saveManagementEntity(entityKey: keyof ManagementDataRow): void {
    const rows = this.selectedData[entityKey];
    const idx = this.managementState.selectedIndex[entityKey];

    if (entityKey === 'users') {
      const name = this.userForm.name.trim();
      const email = this.userForm.email.trim();
      const role = this.normalizeRole(this.userForm.role);
      const password = this.userForm.password.trim();
      const pin = this.userForm.pin.trim();

      if (!name || !email || !role) {
        window.alert('Completa los campos requeridos (nombre, email, rol).');

        return;
      }

      if (pin !== '' && !/^\d{4}$/.test(pin)) {
        window.alert('El PIN debe tener 4 digitos.');

        return;
      }

      const selectedUser = idx >= 0 && idx < rows.length ? (rows[idx] as UserRow) : null;

      // New user requires password
      if (!selectedUser && !password) {
        window.alert('Contraseña requerida para nuevos usuarios.');

        return;
      }

      if (!this.selectedRestaurant?.uuid) {
        window.alert('No se puede guardar: restaurante sin identificador.');

        return;
      }

      if (selectedUser?.uuid) {
        // Update existing user
        this.isSavingUser = true;
        this.restaurantService
          .updateRestaurantUser(this.selectedRestaurant.uuid, selectedUser.uuid, {
            name,
            email,
            role,
            ...(password ? { password } : {}),
            ...(pin ? { pin } : {}),
          })
          .pipe(take(1))
          .subscribe({
            next: () => {
              selectedUser.name = name;
              selectedUser.email = email;
              selectedUser.role = role;
              this.userForm.password = '';
              this.userForm.pin = '';
              this.apiErrorMessage = null;
              this.isSavingUser = false;
              this.syncForms();
              window.alert('Usuario actualizado.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el usuario.';
              this.isSavingUser = false;
            },
          });
      } else {
        // Create new user
        this.isSavingUser = true;
        this.restaurantService
          .createRestaurantUser(this.selectedRestaurant.uuid, {
            name,
            email,
            password,
            role,
            ...(pin ? { pin } : {}),
          })
          .pipe(take(1))
          .subscribe({
            next: (response) => {
              const newUser: UserRow = {
                uuid: response.uuid,
                name: response.name,
                email: response.email,
                role: this.normalizeRole(response.role ?? role),
              };

              (rows as UserRow[]).push(newUser);
              this.managementState.selectedIndex[entityKey] = rows.length - 1;
              this.userForm.password = '';
              this.userForm.pin = '';
              this.apiErrorMessage = null;
              this.updateRestaurantKpis(this.managementState.restaurantId);
              this.syncForms();
              window.alert('Usuario creado.');
              this.isSavingUser = false;
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear el usuario.';
              this.isSavingUser = false;
            },
          });
      }

      return;
    }

    if (entityKey === 'families') {
      const name = this.familyForm.name.trim();
      if (!name) {
        window.alert('Indica el nombre de la familia.');

        return;
      }

      const desiredActive = this.familyForm.active;
      const selectedFamily = idx >= 0 && idx < rows.length ? (rows[idx] as FamilyRow) : null;

      this.isSavingFamily = true;

      if (selectedFamily?.uuid) {
        this.familyService
          .updateFamily(selectedFamily.uuid, { name })
          .pipe(take(1))
          .subscribe({
            next: (updated) => {
              const applyActivation$ = desiredActive
                ? this.familyService.activateFamily(updated.id)
                : this.familyService.deactivateFamily(updated.id);

              applyActivation$.pipe(take(1)).subscribe({
                next: (finalFamily) => {
                  selectedFamily.uuid = finalFamily.id;
                  selectedFamily.name = finalFamily.name;
                  selectedFamily.active = finalFamily.active;
                  this.apiErrorMessage = null;
                  this.isSavingFamily = false;
                  this.syncForms();
                  window.alert('Familia actualizada.');
                },
                error: (error: unknown) => {
                  this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el estado de la familia.';
                  this.isSavingFamily = false;
                },
              });
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar la familia.';
              this.isSavingFamily = false;
            },
          });
      } else {
        this.familyService
          .createFamily({ name })
          .pipe(take(1))
          .subscribe({
            next: (created) => {
              const applyActivation$ = desiredActive
                ? this.familyService.activateFamily(created.id)
                : this.familyService.deactivateFamily(created.id);

              applyActivation$.pipe(take(1)).subscribe({
                next: (finalFamily) => {
                  const newFamily: FamilyRow = {
                    uuid: finalFamily.id,
                    name: finalFamily.name,
                    active: finalFamily.active,
                  };

                  (rows as FamilyRow[]).push(newFamily);
                  this.managementState.selectedIndex[entityKey] = rows.length - 1;
                  this.apiErrorMessage = null;
                  this.isSavingFamily = false;
                  this.updateRestaurantKpis(this.managementState.restaurantId);
                  this.syncForms();
                  window.alert('Familia creada.');
                },
                error: (error: unknown) => {
                  this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el estado de la familia.';
                  this.isSavingFamily = false;
                },
              });
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear la familia.';
              this.isSavingFamily = false;
            },
          });
      }

      return;
    }

    if (entityKey === 'products') {
      const name = this.productForm.name.trim();
      const family = this.productForm.family.trim();
      const tax = Number(this.productForm.tax);
      const price = this.euroToCents(this.productForm.price);
      const stock = Number(this.productForm.stock);

      if (!name || !family || !tax || price <= 0 || !Number.isFinite(stock) || stock < 0) {
        window.alert('Revisa los datos del producto.');

        return;
      }

      const payload: ProductRow = {
        name,
        family,
        tax,
        price,
        stock,
        active: this.productForm.active,
      };
      this.upsertRow(rows, idx, payload, entityKey);
      return;
    }

    if (entityKey === 'zones') {
      const name = this.zoneForm.name.trim();
      if (!name) {
        window.alert('Revisa los datos de la zona.');

        return;
      }

      const currentTables = idx >= 0 && idx < rows.length ? (rows[idx] as ZoneRow).tables : [];
      const payload: ZoneRow = { name, tables: currentTables };
      this.upsertRow(rows, idx, payload, entityKey);
      this.managementState.selectedIndex.tables = 0;
      return;
    }

    if (entityKey === 'taxes') {
      const name = this.taxForm.name.trim();
      const percentage = Number(this.taxForm.percentage);
      if (!name || !Number.isFinite(percentage) || percentage < 0 || percentage > 100) {
        window.alert('Revisa los datos del impuesto.');

        return;
      }

      const payload: TaxRow = { name, percentage };
      this.upsertRow(rows, idx, payload, entityKey);
    }
  }

  public startCreateManagementTable(): void {
    this.managementState.selectedIndex.tables = -1;
    this.tableForm = { name: '' };
  }

  public selectManagementTable(index: number): void {
    this.managementState.selectedIndex.tables = index;
    const selectedTable = this.selectedTable;
    this.tableForm = { name: selectedTable?.name ?? '' };
  }

  public saveManagementTable(): void {
    const zone = this.selectedZone;
    if (!zone) {
      window.alert('Selecciona una zona antes de gestionar mesas.');

      return;
    }

    const name = this.tableForm.name.trim();
    if (!name) {
      window.alert('Indica el nombre de la mesa.');

      return;
    }

    const idx = this.managementState.selectedIndex.tables;
    const payload: TableRow = { name };

    if (idx >= 0 && idx < zone.tables.length) {
      zone.tables[idx] = payload;
    } else {
      zone.tables.push(payload);
      this.managementState.selectedIndex.tables = zone.tables.length - 1;
    }

    this.tableForm = { name };
    window.alert('Mesa guardada.');
  }

  public deleteSelectedManagementTable(): void {
    const zone = this.selectedZone;
    if (!zone) {
      window.alert('No hay zona seleccionada.');

      return;
    }

    const idx = this.managementState.selectedIndex.tables;
    if (idx < 0 || idx >= zone.tables.length) {
      window.alert('No hay mesa seleccionada para eliminar.');

      return;
    }

    zone.tables.splice(idx, 1);
    this.managementState.selectedIndex.tables = zone.tables.length ? Math.min(idx, zone.tables.length - 1) : -1;
    this.tableForm = { name: this.selectedTable?.name ?? '' };
    window.alert('Mesa eliminada.');
  }

  public toEuroFromCents(cents: number): string {
    return `${((cents || 0) / 100).toFixed(2).replace('.', ',')}€`;
  }

  private selectedItem<T>(entityKey: keyof ManagementDataRow, items: T[]): T | null {
    if (!items.length) {
      return null;
    }

    const idx = this.managementState.selectedIndex[entityKey];
    if (idx === -1) {
      return null;
    }

    if (idx < 0 || idx >= items.length) {
      this.managementState.selectedIndex[entityKey] = 0;

      return items[0];
    }

    return items[idx];
  }

  private euroToCents(value: string): number {
    const normalized = value.replace(',', '.');
    const amount = Number.parseFloat(normalized);

    return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
  }

  private updateRestaurantKpis(restaurantId: number): void {
    const restaurant = this.managementRestaurants.find((row) => row.id === restaurantId);
    const data = this.managementData[restaurantId];

    if (!restaurant || !data) {
      return;
    }

    restaurant.users = data.users.length;
    restaurant.zones = data.zones.length;
    restaurant.products = data.products.length;
  }

  private upsertRow(rows: unknown[], idx: number, payload: unknown, entityKey: keyof ManagementDataRow): void {
    if (idx >= 0 && idx < rows.length) {
      rows[idx] = payload;
    } else {
      rows.push(payload);
      this.managementState.selectedIndex[entityKey] = rows.length - 1;
    }

    this.updateRestaurantKpis(this.managementState.restaurantId);
    this.syncForms();
    window.alert('Cambios guardados.');
  }

  private syncForms(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    this.contextService.setActiveRestaurant({ name: restaurant.name });

    this.restaurantForm = {
      name: restaurant.name,
      legalName: restaurant.legalName,
      taxId: restaurant.taxId,
      email: restaurant.email,
      password: '',
    };

    const selectedUser = this.selectedItem('users', this.selectedData.users);
    this.userForm = {
      name: selectedUser?.name ?? '',
      email: selectedUser?.email ?? '',
      role: selectedUser?.role ?? 'operator',
      pin: '',
      password: '',
    };

    const selectedFamily = this.selectedItem('families', this.selectedData.families);
    this.familyForm = {
      name: selectedFamily?.name ?? '',
      active: selectedFamily?.active ?? true,
    };

    const selectedProduct = this.selectedItem('products', this.selectedData.products);
    this.productForm = {
      name: selectedProduct?.name ?? '',
      family: selectedProduct?.family ?? this.selectedData.families[0]?.name ?? '',
      tax: selectedProduct?.tax ?? this.selectedData.taxes[0]?.percentage ?? 10,
      price: selectedProduct ? (selectedProduct.price / 100).toFixed(2) : '',
      stock: selectedProduct?.stock ?? 0,
      active: selectedProduct?.active ?? true,
    };

    const selectedZone = this.selectedItem('zones', this.selectedData.zones);
    this.zoneForm = {
      name: selectedZone?.name ?? '',
    };

    this.tableForm = {
      name: this.selectedTable?.name ?? '',
    };

    const selectedTax = this.selectedItem('taxes', this.selectedData.taxes);
    this.taxForm = {
      name: selectedTax?.name ?? '',
      percentage: selectedTax?.percentage ?? 10,
    };
  }

  private loadRestaurantsFromApi(): void {
    this.restaurantService
      .getAdminRestaurants()
      .pipe(take(1))
      .subscribe({
        next: (response) => {
          this.apiErrorMessage = null;

          if (!response.data.length) {
            this.managementRestaurants.splice(0, this.managementRestaurants.length);
            this.managementState.restaurantId = 0;
            this.contextService.clearActiveRestaurant();

            return;
          }

          this.managementRestaurants.splice(
            0,
            this.managementRestaurants.length,
            ...response.data.map((row, index) => ({
              id: index + 1,
              uuid: row.uuid,
              name: row.name,
              legalName: row.legal_name,
              taxId: row.tax_id,
              email: row.email,
              status: 'active' as const,
              users: 0,
              zones: 0,
              products: 0,
            })),
          );

          for (const restaurant of this.managementRestaurants) {
            if (!this.managementData[restaurant.id]) {
              this.managementData[restaurant.id] = {
                users: [],
                families: [],
                taxes: [],
                zones: [],
                products: [],
              };
            }

            // Keep KPI cards in sync even before selecting a restaurant.
            this.updateRestaurantKpis(restaurant.id);

            if (restaurant.uuid) {
              this.loadRestaurantUsers(restaurant.uuid, true);
            }
          }

          this.managementState.restaurantId = this.managementRestaurants[0].id;
          this.syncForms();

          const firstRestaurant = this.managementRestaurants[0];
          if (firstRestaurant?.uuid) {
            this.restaurantService
              .selectAdminRestaurantContext(firstRestaurant.uuid)
              .pipe(take(1))
              .subscribe({
                next: () => {
                  this.loadFamilies(true);
                },
                error: (error: unknown) => {
                  this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo seleccionar el restaurante.';
                },
              });
          }
        },
        error: (error: unknown) => {
          this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar restaurantes.';
        },
      });
  }

  private loadRestaurantUsers(restaurantUuid: string, silent: boolean = false): void {
    this.restaurantService
      .getRestaurantUsers(restaurantUuid)
      .pipe(take(1))
      .subscribe({
        next: (response) => {
          const restaurant = this.managementRestaurants.find((r) => r.uuid === restaurantUuid);
          if (!restaurant) {
            return;
          }

          const users: UserRow[] = response.users.map((user) => ({
            uuid: user.uuid,
            name: user.name,
            email: user.email,
            role: this.normalizeRole(user.role),
          }));

          this.managementData[restaurant.id].users = users;
          this.updateRestaurantKpis(restaurant.id);
          this.syncForms();

          if (!silent) {
            this.apiErrorMessage = null;
          }
        },
        error: (error: unknown) => {
          if (!silent) {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar los usuarios.';
          }
        },
      });
  }

  public getRoleLabel(role: string): string {
    const normalizedRole = this.normalizeRole(role);

    if (normalizedRole === 'admin') {
      return 'Administrador';
    }

    if (normalizedRole === 'supervisor') {
      return 'Supervisor';
    }

    return 'Operario';
  }

  public getRoleBadgeClass(role: string): string {
    const normalizedRole = this.normalizeRole(role);

    if (normalizedRole === 'admin') {
      return 'role-badge-admin';
    }

    if (normalizedRole === 'supervisor') {
      return 'role-badge-supervisor';
    }

    return 'role-badge-operator';
  }

  private normalizeRole(role: string | null | undefined): UserRole {
    if (role === 'admin' || role === 'manager') {
      return 'admin';
    }

    if (role === 'supervisor' || role === 'kitchen') {
      return 'supervisor';
    }

    return 'operator';
  }
}
