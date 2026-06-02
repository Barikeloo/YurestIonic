import { Page } from '@playwright/test';
import { linkedRestaurant } from './fixtures';

const linkedRestaurantStorageKey = 'tpv_linked_restaurant';

export async function clearDeviceLink(page: Page): Promise<void> {
  await page.addInitScript((storageKey) => {
    window.localStorage.removeItem(storageKey);
  }, linkedRestaurantStorageKey);
}

export async function seedLinkedRestaurant(page: Page): Promise<void> {
  await page.addInitScript(
    ({ storageKey, restaurant }) => {
      window.localStorage.setItem(storageKey, JSON.stringify(restaurant));
    },
    {
      storageKey: linkedRestaurantStorageKey,
      restaurant: linkedRestaurant,
    },
  );
}
