import { expect, test } from '@playwright/test';
import { clearDeviceLink } from '../../support/browser-state';
import { linkDevice } from '../../support/auth';
import { employees, restaurant } from '../../support/fixtures';

test.describe('login', () => {
  test('shows email login when the device is not linked', async ({ page }) => {
    await clearDeviceLink(page);

    await page.goto('/login');

    await expect(page.getByText('Ventoo')).toBeVisible();
    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Contrasena')).toBeVisible();
    await expect(page.getByRole('button', { name: /entrar/i })).toBeVisible();
  });

  test('validates required email and password fields', async ({ page }) => {
    await clearDeviceLink(page);

    await page.goto('/login');
    await page.getByRole('button', { name: /entrar/i }).click();

    await expect(page.getByText('Introduce un email valido.')).toBeVisible();
    await expect(page.getByText('La contrasena es obligatoria.')).toBeVisible();
  });

  test('shows quick users from the backend when the device is linked', async ({ page }) => {
    await linkDevice(page);

    await expect(page.locator('.login-logo-text')).toHaveText(restaurant.name);
    await expect(page.getByPlaceholder('Buscar empleado...')).toBeVisible();

    const employeeCards = page.locator('.employee-name');
    await expect(employeeCards.filter({ hasText: employees.supervisor.name })).toBeVisible();
    await expect(employeeCards.filter({ hasText: employees.carlos.name })).toBeVisible();
    await expect(employeeCards.filter({ hasText: employees.laura.name })).toBeVisible();
    await expect(employeeCards.filter({ hasText: employees.admin.name })).toHaveCount(0);
  });

  test('can switch linked device login to email form', async ({ page }) => {
    await linkDevice(page);

    await page.getByRole('button', { name: /iniciar sesión con correo y contraseña/i }).click();

    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Contrasena')).toBeVisible();
    await expect(page.getByRole('button', { name: /entrar/i })).toBeVisible();
  });
});
