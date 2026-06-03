import { Page } from '@playwright/test';

const linkedRestaurantStorageKey = 'tpv_linked_restaurant';

export async function clearDeviceLink(page: Page): Promise<void> {
  await page.addInitScript((storageKey) => {
    window.localStorage.removeItem(storageKey);
  }, linkedRestaurantStorageKey);
}
