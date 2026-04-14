import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { take } from 'rxjs/operators';
import { AppContextService } from '../../../services/app-context.service';
import { DeviceStorageService } from '../../../services/device-storage.service';
import { FamilyItem, FamilyService } from '../../../services/family.service';
import { ProductItem, ProductService } from '../../../services/product.service';
import { RestaurantService } from '../../../services/restaurant.service';
import { TableItem, TableService } from '../../../services/table.service';
import { TaxItem, TaxService } from '../../../services/tax.service';
import { ZoneItem, ZoneService } from '../../../services/zone.service';
import { RestaurantListComponent } from '../../../components/gestion/restaurant-list/restaurant-list.component';
import { EntityTabsComponent } from '../../../components/gestion/entity-tabs/entity-tabs.component';
import { RestaurantDetailComponent } from '../../../components/gestion/restaurant-detail/restaurant-detail.component';
import { UsersManagementComponent } from '../../../components/gestion/users-management/users-management.component';
import { FamiliesManagementComponent } from '../../../components/gestion/families-management/families-management.component';
import { ProductsManagementComponent } from '../../../components/gestion/products-management/products-management.component';
import { ZonesManagementComponent } from '../../../components/gestion/zones-management/zones-management.component';
import { TaxesManagementComponent } from '../../../components/gestion/taxes-management/taxes-management.component';

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
  uuid?: string;
  name: string;
  percentage: number;
}

interface TableRow {
  uuid?: string;
  name: string;
}

interface ZoneRow {
  uuid?: string;
  name: string;
  tables: TableRow[];
}

interface ProductRow {
  uuid?: string;
  family_id: string;
  tax_id: string;
  name: string;
  price: number;
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
  imports: [
    CommonModule,
    FormsModule,
    RouterModule,
    RestaurantListComponent,
    EntityTabsComponent,
    RestaurantDetailComponent,
    UsersManagementComponent,
    FamiliesManagementComponent,
    ProductsManagementComponent,
    ZonesManagementComponent,
    TaxesManagementComponent,
  ],
})
export class GestionPage {
  public apiErrorMessage: string | null = null;
  public isSavingRestaurant: boolean = false;
  public isSavingUser: boolean = false;
  public isSavingFamily: boolean = false;
  public isSavingTax: boolean = false;
  public isSavingProduct: boolean = false;

  public readonly managementRestaurants: ManagementRestaurant[] = [];

  public readonly managementData: Record<number, ManagementDataRow> = {};

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
    restaurantId: 0,
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
    family_id: '',
    tax_id: '',
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
    private readonly router: Router,
    private readonly deviceStorageService: DeviceStorageService,
    private readonly familyService: FamilyService,
    private readonly productService: ProductService,
    private readonly restaurantService: RestaurantService,
    private readonly tableService: TableService,
    private readonly taxService: TaxService,
    private readonly zoneService: ZoneService,
  ) {
    this.syncForms();
    if (this.selectedRestaurant) {
      this.contextService.setActiveRestaurant({ name: this.selectedRestaurant.name });
    }
    this.loadRestaurantsFromApi();
  }

  public clearApiError(): void {
    this.apiErrorMessage = null;
  }

  public unlinkDevice(): void {
    if (confirm('¿Estás seguro de que deseas desvincular este dispositivo?')) {
      console.log('Before clear - isDeviceLinked:', this.deviceStorageService.isDeviceLinked());
      this.deviceStorageService.clearLinkedRestaurant();
      console.log('After clear - isDeviceLinked:', this.deviceStorageService.isDeviceLinked());
      this.router.navigateByUrl('/home');
    }
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

  private loadTaxes(silent: boolean = false): void {
    this.taxService
      .listTaxes()
      .pipe(take(1))
      .subscribe({
        next: (taxes) => {
          const restaurant = this.selectedRestaurant;
          if (!restaurant) {
            return;
          }

          this.managementData[restaurant.id].taxes = taxes.map((tax: TaxItem): TaxRow => ({
            uuid: tax.id,
            name: tax.name,
            percentage: tax.percentage,
          }));

          this.syncForms();

          if (!silent) {
            this.apiErrorMessage = null;
          }
        },
        error: (error: unknown) => {
          if (!silent) {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar los impuestos.';
          }
        },
      });
  }

  private loadProducts(silent: boolean = false): void {
    this.productService
      .listProducts()
      .pipe(take(1))
      .subscribe({
        next: (products) => {
          const restaurant = this.selectedRestaurant;
          if (!restaurant) {
            return;
          }

          this.managementData[restaurant.id].products = products.map((product: ProductItem): ProductRow => ({
            uuid: product.id,
            name: product.name,
            family_id: product.family_id,
            tax_id: product.tax_id,
            price: product.price,
            stock: product.stock,
            active: product.active,
          }));

          this.syncForms();

          if (!silent) {
            this.apiErrorMessage = null;
          }
        },
        error: (error: unknown) => {
          if (!silent) {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar los productos.';
          }
        },
      });
  }

  private loadZonesAndTables(silent: boolean = false): void {
    this.zoneService
      .listZones()
      .pipe(take(1))
      .subscribe({
        next: (zones: ZoneItem[]) => {
          this.tableService
            .listTables()
            .pipe(take(1))
            .subscribe({
              next: (tables: TableItem[]) => {
                const restaurant = this.selectedRestaurant;
                if (!restaurant) {
                  return;
                }

                const zoneRows: ZoneRow[] = zones.map((zone) => ({
                  uuid: zone.id,
                  name: zone.name,
                  tables: [],
                }));

                const zoneById = new Map(zoneRows.map((zone) => [zone.uuid, zone]));

                tables.forEach((table) => {
                  const zone = zoneById.get(table.zone_id);
                  if (!zone) {
                    return;
                  }

                  zone.tables.push({
                    uuid: table.id,
                    name: table.name,
                  });
                });

                this.managementData[restaurant.id].zones = zoneRows;
                this.updateRestaurantKpis(restaurant.id);
                this.syncForms();

                if (!silent) {
                  this.apiErrorMessage = null;
                }
              },
              error: (error: unknown) => {
                if (!silent) {
                  this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar las mesas.';
                }
              },
            });
        },
        error: (error: unknown) => {
          if (!silent) {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudieron cargar las zonas.';
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
              this.loadTaxes();
              this.loadProducts();
              this.loadZonesAndTables();
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

      if (!selectedZone.uuid) {
        window.alert('No se puede eliminar: zona sin identificador.');

        return;
      }

      this.zoneService
        .deleteZone(selectedZone.uuid)
        .pipe(take(1))
        .subscribe({
          next: () => {
            this.managementState.selectedIndex.tables = 0;
            rows.splice(idx, 1);
            this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.apiErrorMessage = null;
            window.alert('Zona eliminada.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar la zona.';
          },
        });

      return;
    }

    if (entityKey === 'taxes') {
      const tax = rows[idx] as TaxRow;
      if (!tax.uuid) {
        window.alert('No se puede eliminar: impuesto sin identificador.');

        return;
      }

      this.taxService
        .deleteTax(tax.uuid)
        .pipe(take(1))
        .subscribe({
          next: () => {
            rows.splice(idx, 1);
            this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.apiErrorMessage = null;
            window.alert('Impuesto eliminado.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar el impuesto.';
          },
        });

      return;
    }

    // Productos
    if (entityKey === 'products') {
      const product = rows[idx] as ProductRow;
      if (!product.uuid) {
        window.alert('No se puede eliminar: producto sin identificador.');

        return;
      }

      this.productService
        .deleteProduct(product.uuid)
        .pipe(take(1))
        .subscribe({
          next: () => {
            rows.splice(idx, 1);
            this.managementState.selectedIndex[entityKey] = rows.length ? Math.min(idx, rows.length - 1) : -1;
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.apiErrorMessage = null;
            window.alert('Producto eliminado.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar el producto.';
          },
        });

      return;
    }
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
      const familyId = this.productForm.family_id;
      const taxId = this.productForm.tax_id;
      const price = this.euroToCents(this.productForm.price);
      const stock = Number(this.productForm.stock);
      const active = this.productForm.active;

      if (!name || !familyId || !taxId || price <= 0 || !Number.isFinite(stock) || stock < 0) {
        window.alert('Revisa los datos del producto.');

        return;
      }

      const selectedProduct = idx >= 0 && idx < rows.length ? (rows[idx] as ProductRow) : null;

      this.isSavingProduct = true;

      if (selectedProduct?.uuid) {
        // Update existing product
        this.productService
          .updateProduct(selectedProduct.uuid, { name, family_id: familyId, tax_id: taxId, price, stock, active })
          .pipe(take(1))
          .subscribe({
            next: (updated) => {
              selectedProduct.uuid = updated.id;
              selectedProduct.name = updated.name;
              selectedProduct.family_id = updated.family_id;
              selectedProduct.tax_id = updated.tax_id;
              selectedProduct.price = updated.price;
              selectedProduct.stock = updated.stock;
              selectedProduct.active = updated.active;
              this.apiErrorMessage = null;
              this.isSavingProduct = false;
              this.syncForms();
              window.alert('Producto actualizado.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el producto.';
              this.isSavingProduct = false;
            },
          });
      } else {
        // Create new product
        this.productService
          .createProduct({ name, family_id: familyId, tax_id: taxId, price, stock, active })
          .pipe(take(1))
          .subscribe({
            next: (created) => {
              const newProduct: ProductRow = {
                uuid: created.id,
                name: created.name,
                family_id: created.family_id,
                tax_id: created.tax_id,
                price: created.price,
                stock: created.stock,
                active: created.active,
              };
              (rows as ProductRow[]).push(newProduct);
              this.managementState.selectedIndex[entityKey] = rows.length - 1;
              this.apiErrorMessage = null;
              this.isSavingProduct = false;
              this.updateRestaurantKpis(this.managementState.restaurantId);
              this.syncForms();
              window.alert('Producto creado.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear el producto.';
              this.isSavingProduct = false;
            },
          });
      }

      return;
    }

    if (entityKey === 'zones') {
      const name = this.zoneForm.name.trim();
      if (!name) {
        window.alert('Revisa los datos de la zona.');

        return;
      }

      const selectedZone = idx >= 0 && idx < rows.length ? (rows[idx] as ZoneRow) : null;

      if (selectedZone?.uuid) {
        this.zoneService
          .updateZone(selectedZone.uuid, { name })
          .pipe(take(1))
          .subscribe({
            next: (updated) => {
              selectedZone.uuid = updated.id;
              selectedZone.name = updated.name;
              this.managementState.selectedIndex.tables = 0;
              this.apiErrorMessage = null;
              this.syncForms();
              window.alert('Zona actualizada.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar la zona.';
            },
          });
      } else {
        this.zoneService
          .createZone({ name })
          .pipe(take(1))
          .subscribe({
            next: (created) => {
              const payload: ZoneRow = { uuid: created.id, name: created.name, tables: [] };
              this.upsertRow(rows, idx, payload, entityKey);
              this.managementState.selectedIndex.tables = 0;
              this.updateRestaurantKpis(this.managementState.restaurantId);
              this.apiErrorMessage = null;
              this.syncForms();
              window.alert('Zona creada.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear la zona.';
            },
          });
      }

      return;
    }

    if (entityKey === 'taxes') {
      const name = this.taxForm.name.trim();
      const percentage = Number(this.taxForm.percentage);
      if (!name || !Number.isFinite(percentage) || percentage < 0 || percentage > 100) {
        window.alert('Revisa los datos del impuesto.');

        return;
      }

      const selectedTax = idx >= 0 && idx < rows.length ? (rows[idx] as TaxRow) : null;

      this.isSavingTax = true;

      if (selectedTax?.uuid) {
        // Update existing tax
        this.taxService
          .updateTax(selectedTax.uuid, { name, percentage })
          .pipe(take(1))
          .subscribe({
            next: (updated) => {
              selectedTax.uuid = updated.id;
              selectedTax.name = updated.name;
              selectedTax.percentage = updated.percentage;
              this.apiErrorMessage = null;
              this.isSavingTax = false;
              this.syncForms();
              window.alert('Impuesto actualizado.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar el impuesto.';
              this.isSavingTax = false;
            },
          });
      } else {
        // Create new tax
        this.taxService
          .createTax({ name, percentage })
          .pipe(take(1))
          .subscribe({
            next: (created) => {
              const newTax: TaxRow = {
                uuid: created.id,
                name: created.name,
                percentage: created.percentage,
              };
              (rows as TaxRow[]).push(newTax);
              this.managementState.selectedIndex[entityKey] = rows.length - 1;
              this.apiErrorMessage = null;
              this.isSavingTax = false;
              this.updateRestaurantKpis(this.managementState.restaurantId);
              this.syncForms();
              window.alert('Impuesto creado.');
            },
            error: (error: unknown) => {
              this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear el impuesto.';
              this.isSavingTax = false;
            },
          });
      }

      return;
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

    if (!zone.uuid) {
      window.alert('Guarda primero la zona antes de crear mesas.');

      return;
    }

    const idx = this.managementState.selectedIndex.tables;
    const selectedTable = idx >= 0 && idx < zone.tables.length ? zone.tables[idx] : null;

    // Validar que no exista otra mesa con el mismo nombre en esta zona
    const existingTableWithSameName = zone.tables.find(
      (table, tableIdx) => table.name.toLowerCase() === name.toLowerCase() && tableIdx !== idx
    );
    if (existingTableWithSameName) {
      window.alert('Ya existe una mesa con ese nombre en esta zona.');

      return;
    }

    if (selectedTable?.uuid) {
      this.tableService
        .updateTable(selectedTable.uuid, { zone_id: zone.uuid, name })
        .pipe(take(1))
        .subscribe({
          next: (updated) => {
            zone.tables[idx] = { uuid: updated.id, name: updated.name };
            this.tableForm = { name: updated.name };
            this.apiErrorMessage = null;
            this.syncForms();
            window.alert('Mesa actualizada.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo actualizar la mesa.';
          },
        });
    } else {
      this.tableService
        .createTable({ zone_id: zone.uuid, name })
        .pipe(take(1))
        .subscribe({
          next: (created) => {
            zone.tables.push({ uuid: created.id, name: created.name });
            this.managementState.selectedIndex.tables = zone.tables.length - 1;
            this.tableForm = { name: created.name };
            this.apiErrorMessage = null;
            this.syncForms();
            window.alert('Mesa creada.');
          },
          error: (error: unknown) => {
            this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo crear la mesa.';
          },
        });
    }
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

    const selectedTable = zone.tables[idx];
    if (!selectedTable.uuid) {
      window.alert('No se puede eliminar: mesa sin identificador.');

      return;
    }

    this.tableService
      .deleteTable(selectedTable.uuid)
      .pipe(take(1))
      .subscribe({
        next: () => {
          zone.tables.splice(idx, 1);
          this.managementState.selectedIndex.tables = zone.tables.length ? Math.min(idx, zone.tables.length - 1) : -1;
          this.tableForm = { name: this.selectedTable?.name ?? '' };
          this.apiErrorMessage = null;
          this.syncForms();
          window.alert('Mesa eliminada.');
        },
        error: (error: unknown) => {
          this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo eliminar la mesa.';
        },
      });
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

  private euroToCents(value: string | number): number {
    const strValue = typeof value === 'number' ? value.toString() : value;
    const normalized = strValue.replace(',', '.');
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
      family_id: selectedProduct?.family_id ?? this.selectedData.families[0]?.uuid ?? '',
      tax_id: selectedProduct?.tax_id ?? this.selectedData.taxes[0]?.uuid ?? '',
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
            for (const key of Object.keys(this.managementData)) {
              delete this.managementData[Number(key)];
            }
            this.managementState.restaurantId = 0;
            this.contextService.clearActiveRestaurant();
            this.syncForms();

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

          for (const key of Object.keys(this.managementData)) {
            delete this.managementData[Number(key)];
          }

          for (const restaurant of this.managementRestaurants) {
            this.managementData[restaurant.id] = {
              users: [],
              families: [],
              taxes: [],
              zones: [],
              products: [],
            };

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
                  this.loadTaxes();
                  this.loadProducts();
                  this.loadZonesAndTables();
                },
                error: (error: unknown) => {
                  this.apiErrorMessage = error instanceof Error ? error.message : 'No se pudo seleccionar el restaurante.';
                },
              });
          }
        },
        error: (error: unknown) => {
          this.managementRestaurants.splice(0, this.managementRestaurants.length);
          for (const key of Object.keys(this.managementData)) {
            delete this.managementData[Number(key)];
          }
          this.managementState.restaurantId = 0;
          this.contextService.clearActiveRestaurant();
          this.syncForms();
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
