export type CartLineSendStatus = 'local' | 'pending' | 'sent';

export interface CartLineModifier {
  id: string;
  name: string;
  price: number;
}

export interface MenuSelection {
  section_id: string | null;
  product_id: string | null;
  product_name: string | null;
  variant_id: string | null;
  variant_name: string | null;
  extra_price: number;
}

export interface CartLine {
  localId: string;
  backendLineId?: string;
  type: 'product' | 'menu';
  name: string;
  quantity: number;
  productId?: string;
  menuId?: string;
  variantId?: string;
  variantName?: string;
  modifiers: CartLineModifier[];
  menuSelections?: MenuSelection[];
  notes?: string;
  unitPrice: number;
  sendStatus: CartLineSendStatus;
}

export interface RetryQueueEntry {
  idempotencyKey: string;
  lineIds: string[];
  roundLabel?: string;
  attemptedAt: string;
}

export interface AddToCartSpec {
  productId?: string;
  menuId?: string;
  name: string;
  variantId?: string;
  variantName?: string;
  modifiers: CartLineModifier[];
  menuSelections?: MenuSelection[];
  notes?: string;
  unitPrice: number;
  quantity: number;
}

export interface CartApiLine {
  product_id?: string;
  menu_id?: string;
  quantity: number;
  variant_id?: string;
  modifier_ids?: string[];
  notes?: string;
  menu_selections?: { section_id: string | null; product_id: string | null; variant_id: string | null }[];
}

export interface SubmitRoundBody {
  line_ids: string[];
  idempotency_key: string;
  round_label?: string;
}

export interface RoundResult {
  round_id: string;
  round_number: number;
  label: string | null;
  line_count: number;
  submitted_at: string;
  already_submitted: boolean;
}

export interface OrderHistoryLine {
  id: string;
  product_name: string | null;
  menu_name: string | null;
  quantity: number;
  unit_price: number;
  send_status: string;
}

export interface OrderHistoryRound {
  round_id: string;
  round_number: number;
  label: string | null;
  submitted_at: string;
  lines: OrderHistoryLine[];
}

export interface OrderHistoryResponse {
  rounds: OrderHistoryRound[];
  pending_lines: OrderHistoryLine[];
  total_sent_cents: number;
  total_pending_cents: number;
}
