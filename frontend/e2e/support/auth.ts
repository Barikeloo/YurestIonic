import { expect, Page } from '@playwright/test';
import { adminCredentials, Employee, restaurant } from './fixtures';

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

export async function loginByPin(page: Page, employee: Employee): Promise<void> {
  await linkDevice(page);
  await selectEmployeeAndEnterPin(page, employee);
  await expect(page).toHaveURL(/\/app\//);
}

export async function selectEmployeeAndEnterPin(page: Page, employee: Employee): Promise<void> {
  await page.locator('.employee-card', { hasText: employee.name }).click();
  await expect(page.locator('.pin-panel')).toBeVisible();
  await expect(page.locator('.pin-title')).toHaveText(employee.name);
  await pressPin(page, employee.pin);
}

export async function pressPin(page: Page, pin: string): Promise<void> {
  const keypad = page.locator('.pin-keypad');
  for (const digit of pin) {
    await keypad.getByRole('button', { name: digit, exact: true }).click();
  }
}
