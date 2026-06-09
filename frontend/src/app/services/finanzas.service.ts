import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';
import type {
  FinanzasPeriod,
  DashboardSummaryResponse,
  SalesReportResponse,
  SaleDetailResponse,
  ProductsReportResponse,
  EmployeesReportResponse,
  TaxReportResponse,
  HeatmapRow,
} from '../pages/core/finanzas/models/finanzas.models';

@Injectable({ providedIn: 'root' })
export class FinanzasService extends BaseApiService {
  getSummary(period: FinanzasPeriod): Observable<DashboardSummaryResponse> {
    return this.get<DashboardSummaryResponse>('/admin/reports/summary', { period });
  }

  getSales(period: FinanzasPeriod, page = 1, perPage = 50): Observable<SalesReportResponse> {
    return this.get<SalesReportResponse>('/admin/reports/sales', { period, page, per_page: perPage });
  }

  getSaleDetail(uuid: string): Observable<SaleDetailResponse> {
    return this.get<SaleDetailResponse>(`/admin/reports/sales/${uuid}`);
  }

  getProducts(period: FinanzasPeriod): Observable<ProductsReportResponse> {
    return this.get<ProductsReportResponse>('/admin/reports/products', { period });
  }

  getEmployees(period: FinanzasPeriod): Observable<EmployeesReportResponse> {
    return this.get<EmployeesReportResponse>('/admin/reports/employees', { period });
  }

  getHeatmap(): Observable<{ data: HeatmapRow[] }> {
    return this.get<{ data: HeatmapRow[] }>('/admin/reports/heatmap');
  }

  getTaxes(period: FinanzasPeriod, quarter?: string): Observable<TaxReportResponse> {
    return this.get<TaxReportResponse>(
      '/admin/reports/taxes',
      quarter ? { quarter } : { period },
    );
  }
}
