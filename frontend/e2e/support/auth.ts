import { expect, Page } from '@playwright/test';
import { adminCredentials, restaurant } from './fixtures';

export async function linkDevice(page: Page): Promise<void> {
  await page.goto('/home');
  await page.getByRole('button', { name: /vincular dispositivo/i }).click();

  await expect(page).toHaveURL(/\/link-device-admin-login$/);

  await page.getByPlaceholder('admin@restaurante.com').fill(adminCredentials.email);
  await page.getByPlaceholder('Tu contraseña').fill(adminCredentials.password);
  await page.getByRole('button', { name: /continuar/i }).click();

  await expect(page).toHaveURL(/\/link-device-select-restaurant/);

  await page.locator('.restaurant-item', { hasText: restaurant.name }).click();

  await expect(page).toHaveURL(/\/login$/);
  await expect(page.locator('.login-logo-text')).toHaveText(restaurant.name);
}
