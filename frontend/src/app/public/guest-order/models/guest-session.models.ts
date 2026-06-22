export type IdentityMode = 'anonymous' | 'named' | 'registered';

export type OrderStatus = 'none' | 'open' | 'to_charge';

export interface TableStatusResponse {
  restaurant: {
    name: string;
    logo_url: string | null;
    primary_color: string | null;
    locale: string;
  };
  table: {
    name: string;
    zone: string;
  };
  order_status: OrderStatus;
  active_sessions_count: number;
}

export interface OpenTableBody {
  session_token: string;
  diners_count: number;
  identity_mode: IdentityMode;
  guest_name?: string;
  customer_auth_token?: string;
}

export interface JoinSessionBody {
  session_token: string;
  identity_mode: IdentityMode;
  guest_name?: string;
  customer_auth_token?: string;
}

export interface SessionResult {
  session_id: string;
  session_token: string;
  order_id: string;
  identity_mode: IdentityMode;
  guest_name: string | null;
  diners_count?: number;
  expires_at: string;
}

export interface ValidateSessionResult {
  valid: boolean;
  guest_name: string | null;
  identity_mode: IdentityMode | null;
  order_status: OrderStatus | null;
  expires_at: string | null;
}
