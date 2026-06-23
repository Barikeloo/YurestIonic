import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { BaseApiService } from '../core/services/api/base-api.service';

export interface LoyaltyCustomer {
  id: string;
  name: string;
  email: string;
  points: number;
  total_spent_cents: number;
  visits_count: number;
  last_visit_at: string | null;
  created_at: string;
}

export interface LoyaltyCustomerDetail extends LoyaltyCustomer {
  visits: LoyaltyVisit[];
}

export interface LoyaltyVisit {
  id: string;
  order_id: string;
  points_earned: number;
  amount_cents: number;
  visited_at: string;
}

export interface LoyaltyCustomerList {
  data: LoyaltyCustomer[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

export interface LoyaltyStats {
  total_customers: number;
  total_points_outstanding: number;
  total_visits: number;
  total_spent_cents: number;
  avg_ticket_cents: number;
  new_customers: number;
  returning_customers: number;
  visits_last_30_days: number;
  top_customers: LoyaltyCustomer[];
}

@Injectable({ providedIn: 'root' })
export class LoyaltyService extends BaseApiService {
  getStats(): Observable<LoyaltyStats> {
    return this.get<LoyaltyStats>('/admin/loyalty/stats');
  }

  getCustomers(page = 1, search?: string): Observable<LoyaltyCustomerList> {
    const params: Record<string, string | number> = { page, per_page: 20 };
    if (search) params['search'] = search;
    return this.get<LoyaltyCustomerList>('/admin/loyalty/customers', params);
  }

  getCustomer(uuid: string): Observable<LoyaltyCustomerDetail> {
    return this.get<LoyaltyCustomerDetail>(`/admin/loyalty/customers/${uuid}`);
  }
}
