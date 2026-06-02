import { expect, test } from '@playwright/test';
import { clearDeviceLink } from '../../support/browser-state';

test.describe('home', () => {
  test.beforeEach(async ({ page }) => {
    await clearDeviceLink(page);
  });

  test('shows the entry actions', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { name: /gestión de tu restaurante/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /vincular dispositivo/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /acceder como desarrollador/i })).toBeVisible();
  });

  test('navigates to device linking', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: /vincular dispositivo/i }).click();

    await expect(page).toHaveURL(/\/link-device-admin-login$/);
    await expect(page.getByRole('heading', { name: /vincular dispositivo/i })).toBeVisible();
  });

  test('navigates to developer login', async ({ page }) => {
    await page.goto('/');
    await page.getByRole('button', { name: /acceder como desarrollador/i }).click();

    await expect(page).toHaveURL(/\/developer-login$/);
    await expect(page.getByRole('heading', { name: /acceso desarrollador/i })).toBeVisible();
  });
});
