import { expect, test } from '@playwright/test';
import { clearDeviceLink, seedLinkedRestaurant } from '../../support/browser-state';
import { mockQuickUsers } from '../../support/api-mocks';

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

  test('shows quick users when the device is linked', async ({ page }) => {
    await seedLinkedRestaurant(page);
    await mockQuickUsers(page);

    await page.goto('/login');

    await expect(page.getByText('Bar Manolo')).toBeVisible();
    await expect(page.getByPlaceholder('Buscar empleado...')).toBeVisible();
    await expect(page.getByText('Carlos')).toBeVisible();
    await expect(page.getByText('Manolo')).toBeVisible();
  });

  test('can switch linked device login to email form', async ({ page }) => {
    await seedLinkedRestaurant(page);
    await mockQuickUsers(page);

    await page.goto('/login');
    await page.getByRole('button', { name: /iniciar sesión con correo y contraseña/i }).click();

    await expect(page.getByLabel('Email')).toBeVisible();
    await expect(page.getByLabel('Contrasena')).toBeVisible();
    await expect(page.getByRole('button', { name: /entrar/i })).toBeVisible();
  });
});
