import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { map, shareReplay, Observable, of } from 'rxjs';

export interface IconEntry {
  name: string;
  tags: string[];
}

interface ManifestIcon {
  tags: string[];
  svg: string;
}

interface LucideManifest {
  _meta: { count: number };
  icons: Record<string, ManifestIcon>;
}

const SVG_WRAPPER = (inner: string) =>
  `<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${inner}</svg>`;

const NORMALIZE = (s: string) =>
  s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

const SPANISH_MAP: Record<string, string[]> = {
  huevo: ['egg', 'egg-fried'],
  pan: ['bread', 'croissant', 'toast', 'biscuit'],
  pescado: ['fish', 'shrimp', 'lobster'],
  pollo: ['chicken', 'drumstick', 'turkey'],
  carne: ['meat', 'beef', 'pork', 'steak', 'lamb', 'sausage'],
  cerdo: ['piggy-bank', 'pork'],
  ternera: ['beef'],
  queso: ['cheese'],
  leche: ['milk'],
  agua: ['glass', 'bottle', 'droplets', 'droplet'],
  vino: ['wine', 'bottle'],
  cerveza: ['beer', 'bottle'],
  cafe: ['coffee'],
  fruta: ['apple', 'orange', 'cherry', 'citrus', 'lemon', 'lime', 'pear', 'banana', 'grape', 'strawberry', 'watermelon', 'kiwi', 'pineapple', 'mango'],
  naranja: ['orange', 'citrus'],
  manzana: ['apple'],
  limon: ['lemon', 'citrus'],
  sandia: ['watermelon'],
  fresa: ['strawberry'],
  uva: ['grape'],
  pera: ['pear'],
  platano: ['banana'],
  cereza: ['cherry'],
  verdura: ['broccoli', 'carrot', 'salad', 'cucumber', 'tomato', 'onion', 'garlic', 'pepper', 'mushroom', 'corn', 'pea'],
  tomate: ['tomato'],
  zanahoria: ['carrot'],
  cebolla: ['onion'],
  ajo: ['garlic'],
  lechuga: ['lettuce', 'salad'],
  brocoli: ['broccoli'],
  seta: ['mushroom'],
  champinon: ['mushroom'],
  postre: ['cake', 'candy', 'ice-cream', 'pie', 'cookie', 'donut', 'cupcake', 'lollipop', 'pudding'],
  helado: ['ice-cream'],
  dulce: ['candy', 'cookie', 'cake', 'donut', 'lollipop'],
  pastel: ['cake', 'pie', 'cupcake'],
  galleta: ['cookie', 'biscuit'],
  arroz: ['rice-bowl', 'wheat', 'grain'],
  pasta: ['wheat', 'grain'],
  pizza: ['pizza'],
  sopa: ['soup', 'cooking-pot'],
  pure: ['cooking-pot', 'soup'],
  bebida: ['drink', 'glass', 'bottle', 'cup', 'mug', 'wine', 'beer', 'coffee', 'tea', 'juice'],
  te: ['tea', 'cup', 'mug'],
  zumo: ['juice', 'orange', 'apple', 'citrus', 'glass', 'bottle'],
  batido: ['drink', 'glass'],
  cocina: ['utensils', 'cooking-pot', 'oven', 'microwave', 'fridge'],
  plato: ['utensils', 'salad', 'soup'],
  taza: ['cup', 'mug', 'coffee', 'tea'],
  vaso: ['glass', 'cup'],
  cuchara: ['utensils'],
  tenedor: ['fork', 'utensils'],
  cuchillo: ['knife', 'utensils'],
  panaderia: ['bread', 'croissant', 'wheat', 'biscuit'],
  parrilla: ['flame', 'meat', 'steak'],
  horno: ['oven', 'flame', 'cooking-pot'],
  freidora: ['cooking-pot', 'flame'],
  parrillada: ['flame', 'meat', 'steak', 'grill'],
  marisco: ['shrimp', 'lobster', 'fish', 'shellfish', 'crab'],
  gamba: ['shrimp'],
  langosta: ['lobster'],
  cangrejo: ['crab'],
  almeja: ['shellfish'],
  ensalada: ['salad'],
  vegetariano: ['leaf', 'sprout', 'salad'],
  vegano: ['leaf', 'sprout'],
  sin_gluten: ['wheat-off', 'wheat'],
  eco: ['leaf', 'sprout', 'recycle'],
  fuego: ['flame'],
  calor: ['flame', 'sun', 'thermometer'],
  frio: ['snowflake', 'ice-cream'],
  desayuno: ['coffee', 'tea', 'egg', 'croissant', 'bread', 'toast', 'milk', 'juice'],
  comida: ['utensils', 'salad', 'soup', 'meat', 'fish', 'plate'],
  cena: ['utensils', 'plate', 'wine', 'candle'],
  menu: ['book', 'book-open', 'list', 'menu', 'clipboard-list'],
  ticket: ['receipt', 'printer', 'file-text'],
  cuenta: ['receipt', 'file-text', 'dollar-sign'],
  comanda: ['clipboard-list', 'receipt', 'file-text', 'list', 'notepad-text'],
  nota: ['sticky-note', 'file-text', 'notebook-text'],
  precio: ['dollar-sign', 'tag', 'badge', 'badge-dollar-sign'],
  descuento: ['percent', 'badge-percent'],
  iva: ['percent', 'badge-percent', 'receipt'],
  cliente: ['user', 'users', 'user-round', 'users-round'],
  empleado: ['user', 'users', 'user-round', 'badge'],
  camarero: ['user-round', 'user'],
  cocinero: ['utensils', 'cooking-pot', 'chef-hat'],
  dinero: ['dollar-sign', 'coins', 'wallet', 'credit-card', 'banknote', 'euro'],
  efectivo: ['banknote', 'dollar-sign', 'coins'],
  tarjeta: ['credit-card'],
  cobrar: ['dollar-sign', 'receipt', 'cash-register'],
  imprimir: ['printer'],
  impresora: ['printer'],
  puerta: ['door-open', 'door-closed'],
  ventana: ['window'],
  mesa: ['table', 'square'],
  silla: ['chair', 'armchair'],
  telefono: ['phone', 'smartphone', 'phone-call'],
  movil: ['smartphone', 'phone'],
  camara: ['camera'],
  foto: ['camera', 'image', 'image-plus'],
  casa: ['house', 'home'],
  hora: ['clock', 'alarm-clock', 'timer'],
  fecha: ['calendar', 'calendar-days'],
  abierto: ['door-open', 'unlock', 'check-circle'],
  cerrado: ['door-closed', 'lock', 'lock-keyhole', 'x-circle'],
  especial: ['sparkles', 'star', 'badge', 'badge-plus'],
  extra: ['plus', 'plus-circle', 'sparkles'],
  basico: ['circle', 'square'],
};

// Pre-built normalized map for fast partial-match Spanish search
const SPANISH_NORMALIZED: Array<{ key: string; names: Set<string> }> = Object.entries(SPANISH_MAP).map(
  ([key, names]) => ({ key: NORMALIZE(key), names: new Set(names) })
);

function spanishMatches(q: string): Set<string> {
  const result = new Set<string>();
  for (const entry of SPANISH_NORMALIZED) {
    if (entry.key.includes(q) || q.includes(entry.key)) {
      entry.names.forEach(n => result.add(n));
    }
  }
  return result;
}

@Injectable({ providedIn: 'root' })
export class LucideService {
  private http = inject(HttpClient);
  private sanitizer = inject(DomSanitizer);

  private manifest$ = this.http.get<LucideManifest>('assets/lucide-icons.json').pipe(
    shareReplay(1)
  );

  search(query: string): Observable<IconEntry[]> {
    const q = NORMALIZE(query.trim());
    if (!q) return of([]);

    const fromSpanish = spanishMatches(q);

    return this.manifest$.pipe(
      map(manifest => {
        const seen = new Set<string>();
        const results: IconEntry[] = [];

        for (const [name, data] of Object.entries(manifest.icons)) {
          if (seen.has(name)) continue;

          const match =
            NORMALIZE(name).includes(q) ||
            data.tags.some(t => NORMALIZE(t).includes(q)) ||
            fromSpanish.has(name);

          if (match) {
            seen.add(name);
            results.push({ name, tags: data.tags });
          }
        }

        return results.slice(0, 60);
      })
    );
  }

  getIconSvgHtml(name: string): Observable<SafeHtml> {
    return this.manifest$.pipe(
      map(manifest => {
        const inner = manifest.icons[name]?.svg ?? '';
        return this.sanitizer.bypassSecurityTrustHtml(SVG_WRAPPER(inner));
      })
    );
  }
}
