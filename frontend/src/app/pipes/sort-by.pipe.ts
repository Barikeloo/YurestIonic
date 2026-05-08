import { Pipe, PipeTransform } from '@angular/core';
import { SortDirection } from '../core/enums/sort-direction.enum';

export { SortDirection };

@Pipe({
  name: 'sortBy',
  standalone: true,
  pure: true,
})
export class SortByPipe implements PipeTransform {
  public transform<T>(items: T[] | null | undefined, property: keyof T, direction: SortDirection = SortDirection.ASC): T[] {
    if (!items) {
      return [];
    }

    const sorted = [...items].sort((a, b) => {
      const valueA = a[property];
      const valueB = b[property];

      if (valueA === valueB) {
        return 0;
      }

      if (valueA === null || valueA === undefined) {
        return 1;
      }

      if (valueB === null || valueB === undefined) {
        return -1;
      }

      if (typeof valueA === 'number' && typeof valueB === 'number') {
        return direction === SortDirection.ASC ? valueA - valueB : valueB - valueA;
      }

      const stringA = String(valueA).toLowerCase();
      const stringB = String(valueB).toLowerCase();

      const comparison = stringA.localeCompare(stringB);

      return direction === SortDirection.ASC ? comparison : -comparison;
    });

    return sorted;
  }
}
