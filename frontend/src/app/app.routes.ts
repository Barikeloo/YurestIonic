import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: 'app',
    loadComponent: () => import('./pages/core/app-layout/app-layout.page').then((m) => m.AppLayoutPage),
    children: [
      {
        path: 'gestion',
        loadComponent: () => import('./pages/core/gestion/gestion.page').then((m) => m.GestionPage),
      },
      {
        path: 'mesas',
        loadComponent: () => import('./pages/core/mesas/mesas.page').then((m) => m.MesasPage),
      },
      {
        path: 'pedidos',
        loadComponent: () => import('./pages/core/pedidos/pedidos.page').then((m) => m.PedidosPage),
      },
      {
        path: 'comanda',
        loadComponent: () => import('./pages/core/comanda/comanda.page').then((m) => m.ComandaPage),
      },
      {
        path: 'autoservicio',
        loadComponent: () => import('./pages/core/autoservicio/autoservicio.page').then((m) => m.AutoservicioPage),
      },
      {
        path: 'caja',
        loadComponent: () => import('./pages/core/caja/caja.page').then((m) => m.CajaPage),
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
    path: '',
    redirectTo: 'home',
    pathMatch: 'full',
  },
  {
    path: '**',
    redirectTo: 'home',
  },
];
