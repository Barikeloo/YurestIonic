import { Routes } from '@angular/router';
import { AuthGuard } from './core/guards/auth.guard';
import { RoleGuard } from './core/guards/role.guard';
import { canDeactivateGuard } from './core/guards/can-deactivate.guard';

export const routes: Routes = [
  {
    path: 'app',
    loadComponent: () => import('./core/layout/app-layout/app-layout.page').then((m) => m.AppLayoutPage),
    children: [
      {
        path: 'gestion',
        canActivate: [AuthGuard, RoleGuard],
        data: { role: 'admin' },
        loadComponent: () => import('./pages/core/gestion/gestion.page').then((m) => m.GestionPage),
      },
      {
        path: 'gestion/zones/:zoneId/floor',
        canActivate: [AuthGuard, RoleGuard],
        canDeactivate: [canDeactivateGuard],
        data: { role: 'admin' },
        loadComponent: () => import('./pages/core/gestion-zones-floor/gestion-zones-floor.page').then((m) => m.GestionZonesFloorPage),
      },
      {
        path: 'gestion/menus/nuevo',
        canActivate: [AuthGuard, RoleGuard],
        data: { role: 'admin' },
        loadComponent: () => import('./pages/core/gestion-menus-editor/gestion-menus-editor.page').then((m) => m.GestionMenusEditorPage),
      },
      {
        path: 'gestion/menus/:id/editar',
        canActivate: [AuthGuard, RoleGuard],
        data: { role: 'admin' },
        loadComponent: () => import('./pages/core/gestion-menus-editor/gestion-menus-editor.page').then((m) => m.GestionMenusEditorPage),
      },
      {
        path: 'mesas',
        canActivate: [AuthGuard],
        loadComponent: () => import('./features/tables/pages/mesas/mesas.page').then((m) => m.MesasPage),
      },
      {
        path: 'pedidos',
        canActivate: [AuthGuard],
        loadComponent: () => import('./features/orders/pages/pedidos/pedidos.page').then((m) => m.PedidosPage),
      },
      {
        path: 'comanda',
        canActivate: [AuthGuard],
        loadComponent: () => import('./features/orders/pages/comanda/comanda.page').then((m) => m.ComandaPage),
      },
      {
        path: 'autoservicio',
        canActivate: [AuthGuard],
        loadComponent: () => import('./pages/core/autoservicio/autoservicio.page').then((m) => m.AutoservicioPage),
      },
      {
        path: 'caja',
        canActivate: [AuthGuard],
        loadComponent: () => import('./features/cash/pages/caja/caja.page').then((m) => m.CajaPage),
      },
      {
        path: 'developer-dashboard',
        loadComponent: () => import('./pages/core/developer-dashboard/developer-dashboard.page').then((m) => m.DeveloperDashboardPage),
      },
      {
        path: '',
        redirectTo: 'mesas',
        pathMatch: 'full',
      },
    ],
  },
  {
    path: 'finanzas',
    loadComponent: () => import('./pages/core/finanzas/finanzas.page').then((m) => m.FinanzasPage),
  },
  {
    path: 'registro-auditoria',
    loadComponent: () => import('./pages/core/registro-auditoria/registro-auditoria.page').then((m) => m.RegistroAuditoriaPage),
  },
  {
    path: 'registro-auditoria/historico',
    loadComponent: () => import('./pages/core/registro-auditoria/historico/historico.page').then((m) => m.HistoricoPage),
  },
  {
    path: 'login',
    loadComponent: () => import('./pages/core/login/login.page').then((m) => m.LoginPage),
  },
  {
    path: 'developer-login',
    loadComponent: () => import('./pages/core/developer-login/developer-login.page').then((m) => m.DeveloperLoginPage),
  },
  {
    path: 'link-device-admin-login',
    loadComponent: () => import('./pages/core/link-device-admin-login/link-device-admin-login.page').then((m) => m.LinkDeviceAdminLoginPage),
  },
  {
    path: 'link-device-select-restaurant',
    loadComponent: () => import('./pages/core/link-device-select-restaurant/link-device-select-restaurant.page').then((m) => m.LinkDeviceSelectRestaurantPage),
  },
  {
    path: 'home',
    loadComponent: () => import('./pages/core/home/home.page').then((m) => m.HomePage),
  },
  {
    path: 'u/foto/:token',
    loadComponent: () =>
      import('./pages/public/photo-upload/photo-upload.page').then((m) => m.PhotoUploadPage),
  },
  {
    path: 's/:token',
    loadComponent: () =>
      import('./public/guest-order/guest-order.page').then((m) => m.GuestOrderPage),
  },
  {
    path: '',
    redirectTo: 'home',
    pathMatch: 'full',
  },
  {
    path: '**',
    redirectTo: 'home',
  },
];
