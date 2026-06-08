import { AllergenCode } from '../../../services/product.service';

export interface AllergenCatalogItem {
  code: AllergenCode;
  label: string;
  icon: string;
}

export const ALLERGEN_CATALOG: readonly AllergenCatalogItem[] = [
  { code: 'gluten', label: 'Gluten', icon: '🌾' },
  { code: 'crustaceans', label: 'Crustáceos', icon: '🦐' },
  { code: 'eggs', label: 'Huevos', icon: '🥚' },
  { code: 'fish', label: 'Pescado', icon: '🐟' },
  { code: 'peanuts', label: 'Cacahuetes', icon: '🥜' },
  { code: 'soy', label: 'Soja', icon: '🫘' },
  { code: 'dairy', label: 'Lácteos', icon: '🥛' },
  { code: 'nuts', label: 'Frutos secos', icon: '🌰' },
  { code: 'celery', label: 'Apio', icon: '🥬' },
  { code: 'mustard', label: 'Mostaza', icon: '🟡' },
  { code: 'sesame', label: 'Sésamo', icon: '⚪️' },
  { code: 'sulphites', label: 'Sulfitos', icon: '🍷' },
  { code: 'lupin', label: 'Altramuces', icon: '🌼' },
  { code: 'molluscs', label: 'Moluscos', icon: '🦪' },
];
