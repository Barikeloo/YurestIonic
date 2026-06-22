import { Pipe, PipeTransform } from '@angular/core';
import { ALLERGEN_LABELS } from '../models/guest-catalog.models';

@Pipe({ name: 'allergenIcon', standalone: true, pure: true })
export class AllergenIconPipe implements PipeTransform {
  transform(code: string): string {
    return ALLERGEN_LABELS[code]?.emoji ?? '⚠️';
  }
}

@Pipe({ name: 'allergenName', standalone: true, pure: true })
export class AllergenNamePipe implements PipeTransform {
  transform(code: string): string {
    return ALLERGEN_LABELS[code]?.name ?? code;
  }
}
