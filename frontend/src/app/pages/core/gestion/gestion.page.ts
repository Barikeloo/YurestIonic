import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { take } from 'rxjs/operators';
import { AppContextService } from '../../../core/services/app-context.service';
import { DeviceStorageService } from '../../../core/services/device-storage.service';
import { RestaurantContextFacade } from '../../../core/facades/restaurant-context.facade';
import { ToastService } from '../../../core/services/toast.service';
import { AppLayoutFacade } from '../../../core/layout/facades/app-layout.facade';
import { UserRole } from '../../../core/enums/user-role.enum';
import { AuthService } from '../../../core/services/auth.service';
import { FamilyItem, FamilyService } from '../../../services/family.service';
import { GestionFamiliesFacade } from './facades/gestion-families.facade';
import { GestionTaxesFacade } from './facades/gestion-taxes.facade';
import { GestionZonesFacade } from './facades/gestion-zones.facade';
import { GestionProductsFacade } from './facades/gestion-products.facade';
import { GestionMenusFacade } from './facades/gestion-menus.facade';
import { GestionUsersFacade } from './facades/gestion-users.facade';
import { GestionZReportsFacade } from './facades/gestion-zreports.facade';
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
import { MenusManagementComponent } from '../../../components/gestion/menus-management/menus-management.component';
import { ZonesManagementComponent } from '../../../components/gestion/zones-management/zones-management.component';
import { TaxesManagementComponent } from '../../../components/gestion/taxes-management/taxes-management.component';
import { ZReportsManagementComponent, ZReportRow } from '../../../components/gestion/zreports-management/zreports-management.component';
import { ManagementEntityKey } from '../../../core/enums/management-entity-key.enum';
import { TpvService } from '../../../features/cash/services/tpv.service';

interface ManagementRestaurant {
  id: number;
  uuid: string;
  name: string;
  legalName: string;
  taxId: string;
  email: string;
  status: 'active';
  users: number;
  zones: number;
  products: number;
  cashOpen: boolean;
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
  zreports: ZReportRow[];
}

@Component({
  selector: 'app-gestion',
  templateUrl: './gestion.page.html',
  styleUrls: ['./gestion.page.scss'],
  imports: [
    FormsModule,
    RouterModule,
    RestaurantListComponent,
    EntityTabsComponent,
    RestaurantDetailComponent,
    UsersManagementComponent,
    FamiliesManagementComponent,
    ProductsManagementComponent,
    MenusManagementComponent,
    ZonesManagementComponent,
    TaxesManagementComponent,
    ZReportsManagementComponent
],
  providers: [GestionFamiliesFacade, GestionTaxesFacade, GestionZonesFacade, GestionProductsFacade, GestionMenusFacade, GestionUsersFacade, GestionZReportsFacade],
})
export class GestionPage {
  protected readonly familiesFacade = inject(GestionFamiliesFacade);
  protected readonly taxesFacade = inject(GestionTaxesFacade);
  protected readonly zonesFacade = inject(GestionZonesFacade);
  protected readonly productsFacade = inject(GestionProductsFacade);
  protected readonly menusFacade = inject(GestionMenusFacade);
  protected readonly usersFacade = inject(GestionUsersFacade);
  protected readonly zreportsFacade = inject(GestionZReportsFacade);
  protected readonly toastService = inject(ToastService);
  protected readonly restaurantContextFacade = inject(RestaurantContextFacade);
  protected readonly layoutFacade = inject(AppLayoutFacade);

  public isSavingRestaurant: boolean = false;
  public isSavingUser: boolean = false;
  public isSavingFamily: boolean = false;
  public isSavingTax: boolean = false;
  public isSavingProduct: boolean = false;
  public isLoadingZReports: boolean = false;
  public isAdminUser: boolean = false;
  private preloadRunId: number = 0;

  private readonly _managementRestaurants = signal<ManagementRestaurant[]>([]);

  public readonly managementRestaurants = this._managementRestaurants.asReadonly();

  public readonly managementData: Record<number, ManagementDataRow> = {};

  public readonly managementEntities: Array<{ key: ManagementEntityKey; label: string }> = [
    { key: ManagementEntityKey.RESTAURANT, label: 'Restaurante' },
    { key: ManagementEntityKey.USERS, label: 'Usuarios' },
    { key: ManagementEntityKey.FAMILIES, label: 'Familias' },
    { key: ManagementEntityKey.PRODUCTS, label: 'Productos' },
    { key: ManagementEntityKey.MENUS, label: 'Menús' },
    { key: ManagementEntityKey.ZONES, label: 'Zonas y Mesas' },
    { key: ManagementEntityKey.TAXES, label: 'Impuestos' },
    { key: ManagementEntityKey.ZREPORTS, label: 'Z Reports' },
  ];

  public managementState: {
    restaurantId: number;
    entity: ManagementEntityKey;
    selectedIndex: Record<'users' | 'families' | 'products' | 'zones' | 'tables' | 'taxes' | 'zreports', number>;
  } = {
    restaurantId: 0,
    entity: ManagementEntityKey.RESTAURANT,
    selectedIndex: {
      users: 0,
      families: 0,
      products: 0,
      zones: 0,
      tables: 0,
      taxes: 0,
      zreports: 0,
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
    role: UserRole.OPERATOR,
    pin: '',
    password: '',
  };

  public readonly roleOptions: Array<{ value: UserRole; label: string }> = [
    { value: UserRole.OPERATOR, label: 'Operario' },
    { value: UserRole.SUPERVISOR, label: 'Supervisor' },
    { value: UserRole.ADMIN, label: 'Administrador' },
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
    private readonly authService: AuthService,
    private readonly contextService: AppContextService,
    private readonly deviceStorageService: DeviceStorageService,
    private readonly restaurantService: RestaurantService,
    private readonly familyService: FamilyService,
    private readonly taxService: TaxService,
    private readonly productService: ProductService,
    private readonly zoneService: ZoneService,
    private readonly tableService: TableService,
    private readonly router: Router,
  ) {
    // Suscribirse al usuario actual para determinar si es admin
    this.authService.currentUser$.pipe(take(1)).subscribe((user) => {
      this.isAdminUser = user?.role === UserRole.ADMIN;

      // Limpiar contexto persistido si el usuario no es admin
      if (!this.isAdminUser) {
        this.clearPersistedSelectedRestaurant();
      }
    });

    this.syncForms();
    if (this.selectedRestaurant) {
      this.contextService.setActiveRestaurant({ name: this.selectedRestaurant.name });
    }
    this.loadRestaurantsFromApi();
  }

  public goToFinanzas(): void {
    this.router.navigateByUrl('/finanzas');
  }

  public goToRegistroAuditoria(): void {
    this.router.navigateByUrl('/registro-auditoria');
  }

  public unlinkDevice(): void {
    if (confirm('¿Estás seguro de que deseas desvincular este dispositivo?')) {
      console.log('Before clear - isDeviceLinked:', this.deviceStorageService.isDeviceLinked());
      this.deviceStorageService.clearLinkedRestaurant();
      console.log('After clear - isDeviceLinked:', this.deviceStorageService.isDeviceLinked());
      this.router.navigateByUrl('/home');
    }
  }

  private async loadFamilies(silent: boolean = false): Promise<void> {
    try {
      await this.familiesFacade.load();
      this.syncFamiliesMirror();
      this.syncForms();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar las familias.';
        this.toastService.presentError(message);
      }
    }
  }

  private syncFamiliesMirror(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    const families = this.familiesFacade.families();
    this.managementData[restaurant.id].families = families.map((family): FamilyRow => ({
      uuid: family.uuid,
      name: family.name,
      active: family.active,
    }));
  }

  private async loadTaxes(silent: boolean = false): Promise<void> {
    try {
      await this.taxesFacade.load();
      this.syncTaxesMirror();
      this.syncForms();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar los impuestos.';
        this.toastService.presentError(message);
      }
    }
  }

  private syncTaxesMirror(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    const taxes = this.taxesFacade.taxes();
    this.managementData[restaurant.id].taxes = taxes.map((tax): TaxRow => ({
      uuid: tax.uuid,
      name: tax.name,
      percentage: tax.percentage,
    }));
  }

  private async loadProducts(silent: boolean = false): Promise<void> {
    try {
      await this.productsFacade.load();
      this.syncProductsMirror();
      this.syncForms();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar los productos.';
        this.toastService.presentError(message);
      }
    }
  }

  private async loadMenus(silent: boolean = false): Promise<void> {
    try {
      await this.menusFacade.load();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar los menús.';
        this.toastService.presentError(message);
      }
    }
  }

  private syncProductsMirror(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    const products = this.productsFacade.products();
    this.managementData[restaurant.id].products = products.map((product): ProductRow => ({
      uuid: product.uuid,
      name: product.name,
      family_id: product.family_id,
      tax_id: product.tax_id,
      price: product.price,
      stock: product.stock,
      active: product.active,
    }));
  }

  private async loadZonesAndTables(silent: boolean = false): Promise<void> {
    try {
      await this.zonesFacade.load();
      this.syncZonesMirror();
      this.updateRestaurantKpis(this.managementState.restaurantId);
      this.syncForms();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar las zonas.';
        this.toastService.presentError(message);
      }
    }
  }

  private syncZonesMirror(): void {
    const restaurant = this.selectedRestaurant;
    if (!restaurant) {
      return;
    }

    const zones = this.zonesFacade.zones();
    this.managementData[restaurant.id].zones = zones.map((zone): ZoneRow => ({
      uuid: zone.uuid,
      name: zone.name,
      tables: zone.tables.map((table): TableRow => ({
        uuid: table.uuid,
        name: table.name,
      })),
    }));
  }

  public get selectedRestaurant(): ManagementRestaurant | null {
    return this.managementRestaurants().find((restaurant) => restaurant.id === this.managementState.restaurantId) ?? null;
  }

  /**
   * Productos del restaurante actual mapeados al shape mínimo (`MenuProductOption`)
   * que consume el editor de menús. Evita acoplar el módulo de menús al DTO completo.
   */
  public get menuProductOptions(): Array<{ id: string; name: string; price: number; active: boolean }> {
    return this.selectedData.products
      .filter((p) => !!p.uuid)
      .map((p) => ({ id: p.uuid as string, name: p.name, price: p.price, active: p.active }));
  }

  public get selectedData(): ManagementDataRow {
    return (
      this.managementData[this.managementState.restaurantId] ?? {
        users: [],
        families: [],
        taxes: [],
        zones: [],
        products: [],
        zreports: [],
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

    return users[idx].role !== UserRole.ADMIN;
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
    this.preloadRunId++;
    this.managementState.restaurantId = restaurantId;
    if (this.selectedRestaurant) {
      this.layoutFacade.setAdminSelectedContext(this.selectedRestaurant.name);
      this.contextService.setActiveRestaurant({
        id: this.selectedRestaurant.uuid || '',
        name: this.selectedRestaurant.name,
      });
      if (this.selectedRestaurant.uuid) {
        this.restaurantService
          .selectAdminRestaurantContext(this.selectedRestaurant.uuid)
          .pipe(take(1))
          .subscribe({
            next: () => {
              this.restaurantContextFacade.setRestaurantContext(this.selectedRestaurant!.uuid!);
              this.persistSelectedRestaurant(restaurantId);
              this.loadRestaurantUsers(this.selectedRestaurant!.uuid!);
              this.loadFamilies();
              this.loadTaxes();
              this.loadProducts();
              this.loadMenus();
              this.loadZonesAndTables();
              this.startBackgroundPreload();
            },
            error: (error: unknown) => {
              const message = error instanceof Error ? error.message : 'No se pudo seleccionar el restaurante.';

              if (message === 'Forbidden for this tax id.') {
                this.loadRestaurantsFromApi();
                return;
              }

              this.toastService.presentError(message);
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
    switch (entityKey) {
      case ManagementEntityKey.ZONES:
        this.managementState.selectedIndex.tables = 0;
        this.zonesFacade.selectZone(index);
        break;
      case ManagementEntityKey.FAMILIES:
        this.familiesFacade.select(index);
        break;
      case ManagementEntityKey.TAXES:
        this.taxesFacade.select(index);
        break;
      case ManagementEntityKey.PRODUCTS:
        this.productsFacade.select(index);
        break;
      case ManagementEntityKey.USERS:
        this.usersFacade.select(index);
        break;
    }
    this.syncForms();
  }

  public startCreateManagementItem(entityKey: keyof ManagementDataRow): void {
    this.managementState.selectedIndex[entityKey] = -1;
    switch (entityKey) {
      case ManagementEntityKey.ZONES:
        this.managementState.selectedIndex.tables = -1;
        this.tableForm = { name: '' };
        this.zonesFacade.startCreateZone();
        break;
      case ManagementEntityKey.FAMILIES:
        this.familiesFacade.startCreate();
        break;
      case ManagementEntityKey.TAXES:
        this.taxesFacade.startCreate();
        break;
      case ManagementEntityKey.PRODUCTS:
        this.productsFacade.startCreate();
        break;
      case ManagementEntityKey.USERS:
        this.usersFacade.startCreate();
        break;
    }
    this.syncForms();
  }

  public deleteSelectedManagementItem(entityKey: keyof ManagementDataRow): void {
    const rows = this.selectedData[entityKey];
    const idx = this.managementState.selectedIndex[entityKey];

    if (!rows.length || idx < 0 || idx >= rows.length) {
      this.toastService.presentError('No hay un registro seleccionado para eliminar.');
      return;
    }

    switch (entityKey) {
      case ManagementEntityKey.FAMILIES: {
        const family = rows[idx] as FamilyRow;
        if (!family.uuid) {
          this.toastService.presentError('No se puede eliminar: familia sin identificador.');
          return;
        }

        const familyName = family.name || 'Sin nombre';
        if (!window.confirm(`¿Eliminar familia "${familyName}"? Esta acción no se puede deshacer.`)) {
          return;
        }

        this.familiesFacade.delete(family.uuid).then((result) => {
          if (result.ok) {
            this.syncFamiliesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess('Familia eliminada.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo eliminar la familia.');
          }
        });
        break;
      }

      case ManagementEntityKey.USERS: {
        const user = rows[idx] as UserRow;
        if (!user.uuid || !this.selectedRestaurant?.uuid) {
          this.toastService.presentError('No se puede eliminar: usuario sin identificador.');
          return;
        }

        const userName = user.name || user.email || 'Sin nombre';
        if (!window.confirm(`¿Eliminar usuario "${userName}"? Esta acción no se puede deshacer.`)) {
          return;
        }

        const restaurant = this.selectedRestaurant;
        if (!restaurant?.uuid) {
          this.toastService.presentError('No hay restaurante seleccionado.');
          return;
        }

        this.usersFacade.delete(restaurant.uuid, user.uuid).then((result) => {
          if (result.ok) {
            this.syncUsersMirror(restaurant.uuid!);
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess('Usuario eliminado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo eliminar el usuario.');
          }
        });
        break;
      }

      case ManagementEntityKey.ZONES: {
        const selectedZone = rows[idx] as ZoneRow;
        if (selectedZone.tables.length > 0) {
          this.toastService.presentError('No puedes eliminar una zona con mesas. Elimina o reasigna primero sus mesas.');
          return;
        }

        if (!selectedZone.uuid) {
          this.toastService.presentError('No se puede eliminar: zona sin identificador.');
          return;
        }

        const zoneName = selectedZone.name || 'Sin nombre';
        if (!window.confirm(`¿Eliminar zona "${zoneName}"? Esta acción no se puede deshacer.`)) {
          return;
        }

        this.zonesFacade.deleteZone(selectedZone.uuid).then((result) => {
          if (result.ok) {
            this.syncZonesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess('Zona eliminada.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo eliminar la zona.');
          }
        });
        break;
      }

      case ManagementEntityKey.TAXES: {
        const tax = rows[idx] as TaxRow;
        if (!tax.uuid) {
          this.toastService.presentError('No se puede eliminar: impuesto sin identificador.');
          return;
        }

        const taxName = tax.name || 'Sin nombre';
        if (!window.confirm(`¿Eliminar impuesto "${taxName}"? Esta acción no se puede deshacer.`)) {
          return;
        }

        this.taxesFacade.delete(tax.uuid).then((result) => {
          if (result.ok) {
            this.syncTaxesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess('Impuesto eliminado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo eliminar el impuesto.');
          }
        });
        break;
      }

      case ManagementEntityKey.PRODUCTS: {
        const product = rows[idx] as ProductRow;
        if (!product.uuid) {
          this.toastService.presentError('No se puede eliminar: producto sin identificador.');
          return;
        }

        const productName = product.name || 'Sin nombre';
        if (!window.confirm(`¿Eliminar producto "${productName}"? Esta acción no se puede deshacer.`)) {
          return;
        }

        this.productsFacade.delete(product.uuid).then((result) => {
          if (result.ok) {
            this.syncProductsMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess('Producto eliminado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo eliminar el producto.');
          }
        });
        break;
      }
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
      this.toastService.presentError('Completa todos los campos obligatorios.');
      return;
    }

    if (!restaurant.uuid) {
      this.toastService.presentError('No se puede actualizar: restaurante sin identificador.');
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

          this.syncForms();
          this.isSavingRestaurant = false;
          this.toastService.presentSuccess('Restaurante actualizado.');
        },
        error: (error: unknown) => {
          const message = error instanceof Error ? error.message : 'No se pudo actualizar el restaurante.';
          this.toastService.presentError(message);
          this.isSavingRestaurant = false;
        },
      });
  }

  public saveManagementEntity(entityKey: keyof ManagementDataRow): void {
    const rows = this.selectedData[entityKey];
    const idx = this.managementState.selectedIndex[entityKey];

    switch (entityKey) {
      case ManagementEntityKey.USERS: {
        const name = this.userForm.name.trim();
        const email = this.userForm.email.trim();
        const role = this.normalizeRole(this.userForm.role);
        const password = this.userForm.password.trim();
        const pin = this.userForm.pin.trim();

        if (!name || !email || !role) {
          this.toastService.presentError('Completa los campos requeridos (nombre, email, rol).');
          return;
        }

        if (pin !== '' && !/^\d{4}$/.test(pin)) {
          this.toastService.presentError('El PIN debe tener 4 digitos.');
          return;
        }

        const selectedUser = idx >= 0 && idx < rows.length ? (rows[idx] as UserRow) : null;

        if (!selectedUser && !password) {
          this.toastService.presentError('Contraseña requerida para nuevos usuarios.');
          return;
        }

        if (!this.selectedRestaurant?.uuid) {
          this.toastService.presentError('No se puede guardar: restaurante sin identificador.');
          return;
        }

        const restaurantUuid = this.selectedRestaurant.uuid;

        if (selectedUser?.uuid) {
          this.usersFacade.select(idx);
        } else {
          this.usersFacade.startCreate();
        }

        this.usersFacade.setForm({ name, email, role, pin, password });
        this.usersFacade.save(restaurantUuid).then((result) => {
          if (result.ok) {
            this.syncUsersMirror(restaurantUuid);
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess(result.message || 'Usuario guardado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo guardar el usuario.');
          }
        });
        break;
      }

      case ManagementEntityKey.FAMILIES: {
        const name = this.familyForm.name.trim();
        if (!name) {
          this.toastService.presentError('Indica el nombre de la familia.');
          return;
        }

        const selectedFamily = idx >= 0 && idx < rows.length ? (rows[idx] as FamilyRow) : null;

        if (selectedFamily?.uuid) {
          this.familiesFacade.select(idx);
        } else {
          this.familiesFacade.startCreate();
        }

        this.familiesFacade.setForm({ name, active: this.familyForm.active });
        this.familiesFacade.save().then((result) => {
          if (result.ok) {
            this.syncFamiliesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess(result.message || 'Familia guardada.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo guardar la familia.');
          }
        });
        break;
      }

      case ManagementEntityKey.PRODUCTS: {
        const name = this.productForm.name.trim();
        const familyId = this.productForm.family_id;
        const taxId = this.productForm.tax_id;
        const price = this.euroToCents(this.productForm.price);
        const stock = Number(this.productForm.stock);
        const active = this.productForm.active;

        if (!name || !familyId || !taxId || price <= 0 || !Number.isFinite(stock) || stock < 0) {
          this.toastService.presentError('Revisa los datos del producto.');
          return;
        }

        const selectedProduct = idx >= 0 && idx < rows.length ? (rows[idx] as ProductRow) : null;

        if (selectedProduct?.uuid) {
          this.productsFacade.select(idx);
        } else {
          this.productsFacade.startCreate();
        }

        const currentAllergens = this.productsFacade.selectedProduct()?.allergens ?? [];
        this.productsFacade.setForm({ name, family_id: familyId, tax_id: taxId, price, stock, active, allergens: currentAllergens });
        this.productsFacade.save().then((result) => {
          if (result.ok) {
            this.syncProductsMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess(result.message || 'Producto guardado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo guardar el producto.');
          }
        });
        break;
      }

      case ManagementEntityKey.ZONES: {
        const name = this.zoneForm.name.trim();
        if (!name) {
          this.toastService.presentError('Revisa los datos de la zona.');
          return;
        }

        const selectedZone = idx >= 0 && idx < rows.length ? (rows[idx] as ZoneRow) : null;

        if (selectedZone?.uuid) {
          this.zonesFacade.selectZone(idx);
        } else {
          this.zonesFacade.startCreateZone();
        }

        this.zonesFacade.setZoneForm({ name });
        this.zonesFacade.saveZone().then((result) => {
          if (result.ok) {
            this.syncZonesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess(result.message || 'Zona guardada.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo guardar la zona.');
          }
        });
        break;
      }

      case ManagementEntityKey.TAXES: {
        const name = this.taxForm.name.trim();
        const percentage = Number(this.taxForm.percentage);
        if (!name || !Number.isFinite(percentage) || percentage < 0 || percentage > 100) {
          this.toastService.presentError('Revisa los datos del impuesto.');
          return;
        }

        const selectedTax = idx >= 0 && idx < rows.length ? (rows[idx] as TaxRow) : null;

        if (selectedTax?.uuid) {
          this.taxesFacade.select(idx);
        } else {
          this.taxesFacade.startCreate();
        }

        this.taxesFacade.setForm({ name, percentage });
        this.taxesFacade.save().then((result) => {
          if (result.ok) {
            this.syncTaxesMirror();
            this.updateRestaurantKpis(this.managementState.restaurantId);
            this.syncForms();
            this.toastService.presentSuccess(result.message || 'Impuesto guardado.');
          } else {
            this.toastService.presentError(result.error || 'No se pudo guardar el impuesto.');
          }
        });
        break;
      }
    }
  }

  public startCreateManagementTable(): void {
    this.managementState.selectedIndex.tables = -1;
    this.tableForm = { name: '' };
    this.zonesFacade.startCreateTable();
  }

  public selectManagementTable(index: number): void {
    this.managementState.selectedIndex.tables = index;
    this.zonesFacade.selectTable(index);
    const selectedTable = this.selectedTable;
    this.tableForm = { name: selectedTable?.name ?? '' };
  }

  public async saveManagementTable(): Promise<void> {
    const zone = this.selectedZone;
    if (!zone) {
      this.toastService.presentError('Selecciona una zona antes de gestionar mesas.');
      return;
    }

    const name = this.tableForm.name.trim();
    if (!name) {
      this.toastService.presentError('Indica el nombre de la mesa.');
      return;
    }

    if (!zone.uuid) {
      this.toastService.presentError('Guarda primero la zona antes de crear mesas.');
      return;
    }

    const idx = this.managementState.selectedIndex.tables;
    const selectedTable = idx >= 0 && idx < zone.tables.length ? zone.tables[idx] : null;

    const existingTableWithSameName = zone.tables.find(
      (table, tableIdx) => table.name.toLowerCase() === name.toLowerCase() && tableIdx !== idx
    );
    if (existingTableWithSameName) {
      this.toastService.presentError('Ya existe una mesa con ese nombre en esta zona.');
      return;
    }

    this.zonesFacade.setTableForm({ name });
    const result = await this.zonesFacade.saveTable();

    if (result.ok) {
      this.syncZonesMirror();
      this.syncForms();
      this.toastService.presentSuccess(result.message || 'Mesa guardada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo guardar la mesa.');
    }
  }

  public async deleteSelectedManagementTable(): Promise<void> {
    const zone = this.selectedZone;
    if (!zone) {
      this.toastService.presentError('No hay zona seleccionada.');
      return;
    }

    const idx = this.managementState.selectedIndex.tables;
    if (idx < 0 || idx >= zone.tables.length) {
      this.toastService.presentError('No hay mesa seleccionada para eliminar.');
      return;
    }

    const table = zone.tables[idx];
    if (!table.uuid) {
      this.toastService.presentError('No se puede eliminar: mesa sin identificador.');
      return;
    }

    if (!window.confirm(`¿Eliminar mesa "${table.name}"? Esta acción no se puede deshacer.`)) {
      return;
    }

    const result = await this.zonesFacade.deleteSelectedTable();

    if (result.ok) {
      this.syncZonesMirror();
      this.syncForms();
      this.toastService.presentSuccess('Mesa eliminada.');
    } else {
      this.toastService.presentError(result.error || 'No se pudo eliminar la mesa.');
    }
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
    const restaurants = this.managementRestaurants();
    const restaurantIndex = restaurants.findIndex((row) => row.id === restaurantId);
    const data = this.managementData[restaurantId];

    if (restaurantIndex === -1 || !data) {
      return;
    }

    const updatedRestaurants = [...restaurants];
    updatedRestaurants[restaurantIndex] = {
      ...updatedRestaurants[restaurantIndex],
      users: data.users.length,
      zones: data.zones.length,
      products: data.products.length,
    };

    this._managementRestaurants.set(updatedRestaurants);
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
    this.toastService.presentSuccess('Cambios guardados.');
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
    if (this.managementState.entity === ManagementEntityKey.USERS) {
      const facadeForm = this.usersFacade.formData();
      this.userForm = {
        name: facadeForm.name,
        email: facadeForm.email,
        role: facadeForm.role as UserRole,
        pin: facadeForm.pin,
        password: facadeForm.password,
      };
    } else {
      this.userForm = {
        name: selectedUser?.name ?? '',
        email: selectedUser?.email ?? '',
        role: selectedUser?.role ?? UserRole.OPERATOR,
        pin: '',
        password: '',
      };
    }

    const selectedFamily = this.selectedItem('families', this.selectedData.families);
    if (this.managementState.entity === ManagementEntityKey.FAMILIES) {
      const facadeForm = this.familiesFacade.formData();
      this.familyForm = {
        name: facadeForm.name,
        active: facadeForm.active,
      };
    } else {
      this.familyForm = {
        name: selectedFamily?.name ?? '',
        active: selectedFamily?.active ?? true,
      };
    }

    const selectedProduct = this.selectedItem('products', this.selectedData.products);
    if (this.managementState.entity === ManagementEntityKey.PRODUCTS) {
      const facadeForm = this.productsFacade.formData();
      this.productForm = {
        name: facadeForm.name,
        family_id: facadeForm.family_id,
        tax_id: facadeForm.tax_id,
        price: facadeForm.price > 0 ? (facadeForm.price / 100).toFixed(2) : '',
        stock: facadeForm.stock,
        active: facadeForm.active,
      };
    } else {
      this.productForm = {
        name: selectedProduct?.name ?? '',
        family_id: selectedProduct?.family_id ?? this.selectedData.families[0]?.uuid ?? '',
        tax_id: selectedProduct?.tax_id ?? this.selectedData.taxes[0]?.uuid ?? '',
        price: selectedProduct ? (selectedProduct.price / 100).toFixed(2) : '',
        stock: selectedProduct?.stock ?? 0,
        active: selectedProduct?.active ?? true,
      };
    }

    const selectedZone = this.selectedItem('zones', this.selectedData.zones);
    if (this.managementState.entity === ManagementEntityKey.ZONES) {
      const facadeForm = this.zonesFacade.zoneFormData();
      this.zoneForm = {
        name: facadeForm.name,
      };
    } else {
      this.zoneForm = {
        name: selectedZone?.name ?? '',
      };
    }

    if (this.managementState.entity === ManagementEntityKey.ZONES) {
      const facadeForm = this.zonesFacade.tableFormData();
      this.tableForm = {
        name: facadeForm.name,
      };
    } else {
      this.tableForm = {
        name: this.selectedTable?.name ?? '',
      };
    }

    const selectedTax = this.selectedItem('taxes', this.selectedData.taxes);
    if (this.managementState.entity === ManagementEntityKey.TAXES) {
      const facadeForm = this.taxesFacade.formData();
      this.taxForm = {
        name: facadeForm.name,
        percentage: facadeForm.percentage,
      };
    } else {
      this.taxForm = {
        name: selectedTax?.name ?? '',
        percentage: selectedTax?.percentage ?? 10,
      };
    }
  }

  private persistSelectedRestaurant(restaurantId: number): void {
    // Solo persistir si el usuario es admin
    if (this.isAdminUser) {
      const restaurant = this.managementRestaurants().find((r) => r.id === restaurantId);
      if (restaurant?.uuid) {
        localStorage.setItem('gestion_selected_restaurant_uuid', restaurant.uuid);
      }
    }
  }

  private getPersistedSelectedRestaurantUuid(): string | null {
    // Solo restaurar si el usuario es admin
    if (this.isAdminUser) {
      return localStorage.getItem('gestion_selected_restaurant_uuid');
    }
    return null;
  }

  private clearPersistedSelectedRestaurant(): void {
    localStorage.removeItem('gestion_selected_restaurant_uuid');
  }

  private loadRestaurantsFromApi(): void {
    this.restaurantService
      .getAdminRestaurants()
      .pipe(take(1))
      .subscribe({
        next: (response) => {
          if (!response.data.length) {
            this._managementRestaurants.set([]);
            for (const key of Object.keys(this.managementData)) {
              delete this.managementData[Number(key)];
            }
            return;
          }

          const restaurants = response.data.map((item, index) => ({
            id: index + 1,
            uuid: item.uuid,
            name: item.name,
            legalName: item.legal_name,
            taxId: item.tax_id,
            email: item.email,
            status: 'active' as const,
            users: item.users,
            zones: item.zones,
            products: item.products,
            cashOpen: item.has_open_cash_session,
          }));

          this._managementRestaurants.set(restaurants);

          for (const restaurant of restaurants) {
            if (!this.managementData[restaurant.id]) {
              this.managementData[restaurant.id] = {
                users: [],
                families: [],
                taxes: [],
                zones: [],
                products: [],
                zreports: [],
              };

              if (restaurant.uuid) {
                this.loadRestaurantUsers(restaurant.uuid, true);
              }
            }
          }

          const persistedUuid = this.getPersistedSelectedRestaurantUuid();
          const selectedId = persistedUuid && restaurants.find((r) => r.uuid === persistedUuid)
            ? restaurants.find((r) => r.uuid === persistedUuid)!.id
            : restaurants[0].id;

          this.managementState.restaurantId = selectedId;
          this.syncForms();

          // Solo establecer el contexto si es el primer restaurante y no hay selección persistida
          const firstRestaurant = restaurants[0];
          if (firstRestaurant?.uuid && !persistedUuid) {
            this.restaurantService
              .selectAdminRestaurantContext(firstRestaurant.uuid)
              .pipe(take(1))
              .subscribe({
                next: () => {
                  this.restaurantContextFacade.setRestaurantContext(firstRestaurant.uuid);
                  this.loadFamilies(true);
                  this.loadTaxes();
                  this.loadProducts();
                  this.loadMenus(true);
                  this.loadZonesAndTables();
                  this.startBackgroundPreload();
                },
                error: (error: unknown) => {
                  const message = error instanceof Error ? error.message : 'No se pudo seleccionar el restaurante.';
                  this.toastService.presentError(message);
                },
              });
          } else if (persistedUuid) {
            const persistedRestaurant = restaurants.find((r) => r.uuid === persistedUuid);
            if (persistedRestaurant?.uuid) {
              this.restaurantContextFacade.setRestaurantContext(persistedRestaurant.uuid);
              this.loadFamilies(true);
              this.loadTaxes();
              this.loadProducts();
              this.loadMenus(true);
              this.loadZonesAndTables();
              this.startBackgroundPreload();
            }
          }
        },
        error: (error: unknown) => {
          const message = error instanceof Error ? error.message : 'No se pudieron cargar los restaurantes.';
          this.toastService.presentError(message);
        },
      });
  }

  private startBackgroundPreload(): void {
    const selectedRestaurant = this.selectedRestaurant;

    if (!selectedRestaurant?.uuid) {
      return;
    }

    const runId = ++this.preloadRunId;
    void this.preloadRestaurantsInBackground(runId, selectedRestaurant.uuid);
  }

  private async preloadRestaurantsInBackground(runId: number, selectedRestaurantUuid: string): Promise<void> {
    for (const restaurant of this.managementRestaurants()) {
      if (runId !== this.preloadRunId) {
        return;
      }

      if (!restaurant.uuid || restaurant.uuid === selectedRestaurantUuid) {
        continue;
      }

      const currentData = this.managementData[restaurant.id];
      if (!currentData) {
        continue;
      }

      const alreadyLoaded =
        currentData.users.length > 0 ||
        currentData.families.length > 0 ||
        currentData.taxes.length > 0 ||
        currentData.products.length > 0 ||
        currentData.zones.length > 0;

      if (alreadyLoaded) {
        continue;
      }

      try {
        await firstValueFrom(this.restaurantService.selectAdminRestaurantContext(restaurant.uuid));

        const [usersResponse, families, taxes, products, zones, tables] = await Promise.all([
          firstValueFrom(this.restaurantService.getRestaurantUsers(restaurant.uuid)),
          firstValueFrom(this.familyService.listFamilies()),
          firstValueFrom(this.taxService.listTaxes()),
          firstValueFrom(this.productService.listProducts()),
          firstValueFrom(this.zoneService.listZones()),
          firstValueFrom(this.tableService.listTables()),
        ]);

        if (runId !== this.preloadRunId) {
          return;
        }

        currentData.users = usersResponse.users.map((user) => ({
          uuid: user.uuid,
          name: user.name,
          email: user.email,
          role: this.normalizeRole(user.role) as UserRole,
        }));

        currentData.families = families.map((family) => ({
          uuid: family.id,
          name: family.name,
          active: family.active,
        }));

        currentData.taxes = taxes.map((tax) => ({
          uuid: tax.id,
          name: tax.name,
          percentage: tax.percentage,
        }));

        currentData.products = products.map((product) => ({
          uuid: product.id,
          name: product.name,
          family_id: product.family_id,
          tax_id: product.tax_id,
          price: product.price,
          stock: product.stock,
          active: product.active,
        }));

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

        currentData.zones = zoneRows;

        this.updateRestaurantKpis(restaurant.id);
      } catch {
      }
    }

    if (runId !== this.preloadRunId) {
      return;
    }

    const currentlySelected = this.selectedRestaurant;

    if (!currentlySelected?.uuid) {
      return;
    }

    try {
      await firstValueFrom(this.restaurantService.selectAdminRestaurantContext(currentlySelected.uuid));
    } catch {
    }
  }

  private async loadRestaurantUsers(restaurantUuid: string, silent: boolean = false): Promise<void> {
    try {
      await this.usersFacade.load(restaurantUuid);
      this.syncUsersMirror(restaurantUuid);
      this.syncForms();
    } catch (error: unknown) {
      if (!silent) {
        const message = error instanceof Error ? error.message : 'No se pudieron cargar los usuarios.';
        this.toastService.presentError(message);
      }
    }
  }

  private syncUsersMirror(restaurantUuid: string): void {
    const restaurants = this.managementRestaurants();
    const restaurantIndex = restaurants.findIndex((r) => r.uuid === restaurantUuid);
    if (restaurantIndex === -1) {
      return;
    }

    const users = this.usersFacade.users();
    this.managementData[restaurants[restaurantIndex].id].users = users.map((user): UserRow => ({
      uuid: user.uuid,
      name: user.name,
      email: user.email,
      role: user.role as UserRole,
    }));
  }

  public getRoleLabel(role: string): string {
    const normalizedRole = this.normalizeRole(role);

    if (normalizedRole === UserRole.ADMIN) {
      return 'Administrador';
    }

    if (normalizedRole === UserRole.SUPERVISOR) {
      return 'Supervisor';
    }

    return 'Operario';
  }

  public getRoleBadgeClass(role: string): string {
    const normalizedRole = this.normalizeRole(role);

    if (normalizedRole === UserRole.ADMIN) {
      return 'badge-admin';
    }

    if (normalizedRole === UserRole.SUPERVISOR) {
      return 'badge-supervisor';
    }

    return 'badge-operator';
  }

  public normalizeRole(role: string): UserRole {
    if (!role) return UserRole.OPERATOR;

    const lower = role.toLowerCase();

    if (lower === 'admin' || lower === 'administrator') {
      return UserRole.ADMIN;
    }

    if (lower === 'supervisor') {
      return UserRole.SUPERVISOR;
    }

    return UserRole.OPERATOR;
  }

  public async loadZReports(): Promise<void> {
    const restaurant = this.selectedRestaurant;
    if (!restaurant || !restaurant.uuid) return;

    try {
      await this.zreportsFacade.load();
      this.managementData[restaurant.id].zreports = this.zreportsFacade.reports();
      this.isLoadingZReports = false;
    } catch (error) {
      this.isLoadingZReports = false;
      const message = error instanceof Error ? error.message : 'No se pudieron cargar los Z-Reports.';
      this.toastService.presentError(message);
    }
  }
}
