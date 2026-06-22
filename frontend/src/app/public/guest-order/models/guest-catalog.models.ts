export interface VariantCatalogItem {
  id: string;
  name: string;
  price_cents: number;
}

export interface ModifierCatalogItem {
  id: string;
  name: string;
  price_cents: number;
  is_required: boolean;
  selection_type: 'single' | 'multi';
}

export interface ProductCatalogItem {
  id: string;
  name: string;
  price_cents: number;
  photo_url: string | null;
  allergens: string[];
  available: boolean;
  variants: VariantCatalogItem[];
  modifiers: ModifierCatalogItem[];
}

export interface FamilyCatalogItem {
  id: string;
  name: string;
  icon: string | null;
  color: string | null;
  products: ProductCatalogItem[];
}

export interface MenuItemCatalogItem {
  id: string;
  product_id: string;
  product_name: string;
  variant_id: string | null;
  variant_name: string | null;
  extra_price_cents: number;
  position: number;
}

export interface MenuSectionCatalogItem {
  id: string;
  name: string;
  min_choices: number;
  max_choices: number;
  position: number;
  items: MenuItemCatalogItem[];
}

export interface MenuCatalogItem {
  id: string;
  name: string;
  description: string | null;
  price_cents: number;
  sections: MenuSectionCatalogItem[];
}

export interface CatalogResponse {
  version: number;
  families: FamilyCatalogItem[];
  menus: MenuCatalogItem[];
}

export const ALLERGEN_LABELS: Record<string, { emoji: string; name: string }> = {
  gluten:       { emoji: '🌾', name: 'Gluten' },
  crustaceans:  { emoji: '🦀', name: 'Crustáceos' },
  eggs:         { emoji: '🥚', name: 'Huevo' },
  fish:         { emoji: '🐟', name: 'Pescado' },
  peanuts:      { emoji: '🥜', name: 'Cacahuetes' },
  soy:          { emoji: '🫘', name: 'Soja' },
  dairy:        { emoji: '🥛', name: 'Lácteos' },
  nuts:         { emoji: '🌰', name: 'Frutos de cáscara' },
  celery:       { emoji: '🌿', name: 'Apio' },
  mustard:      { emoji: '🌱', name: 'Mostaza' },
  sesame:       { emoji: '🌻', name: 'Sésamo' },
  sulphites:    { emoji: '🍷', name: 'Sulfitos' },
  lupin:        { emoji: '🫛', name: 'Altramuces' },
  molluscs:     { emoji: '🦑', name: 'Moluscos' },
};
