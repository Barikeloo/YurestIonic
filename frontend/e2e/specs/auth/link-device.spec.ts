import { expect, test } from '@playwright/test';
import { adminCredentials, restaurant } from '../../support/fixtures';

test.describe('link device', () => {
  test('links a device through the admin credentials flow', async ({ page }) => {
    await page.goto('/home');
    await page.getByRole('button', { name: /vincular dispositivo/i }).click();

    await expect(page).toHaveURL(/\/link-device-admin-login$/);
    await expect(page.getByRole('heading', { name: /vincular dispositivo/i })).toBeVisible();

    await page.getByPlaceholder('admin@restaurante.com').fill(adminCredentials.email);
    await page.getByPlaceholder('Tu contraseña').fill(adminCredentials.password);
    await page.getByRole('button', { name: /continuar/i }).click();

    await expect(page).toHaveURL(/\/link-device-select-restaurant/);
    await expect(page.getByRole('heading', { name: /seleccionar restaurante/i })).toBeVisible();
    await expect(page.getByText(restaurant.legalName)).toBeVisible();

    await page.locator('.restaurant-item', { hasText: restaurant.name }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('.login-logo-text')).toHaveText(restaurant.name);

    const stored = await page.evaluate(() => window.localStorage.getItem('tpv_linked_restaurant'));
    expect(stored).not.toBeNull();
    const parsed = JSON.parse(stored as string) as { name: string };
    expect(parsed.name).toBe(restaurant.name);
  });

  test('rejects invalid admin credentials', async ({ page }) => {
    await page.goto('/link-device-admin-login');

    await page.getByPlaceholder('admin@restaurante.com').fill(adminCredentials.email);
    await page.getByPlaceholder('Tu contraseña').fill('wrong-password');
    await page.getByRole('button', { name: /continuar/i }).click();

    await expect(page).toHaveURL(/\/link-device-admin-login$/);
  });

  test('validates required admin credentials', async ({ page }) => {
    await page.goto('/link-device-admin-login');

    const submit = page.getByRole('button', { name: /continuar/i });
    await expect(submit).toBeDisabled();

    await page.getByPlaceholder('admin@restaurante.com').fill('not-an-email');
    await page.getByPlaceholder('admin@restaurante.com').blur();

    await expect(page.getByText('Correo no válido')).toBeVisible();
  });
});
