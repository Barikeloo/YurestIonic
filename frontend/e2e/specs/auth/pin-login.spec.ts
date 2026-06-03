import { expect, test } from '@playwright/test';
import { linkDevice, pressPin, selectEmployeeAndEnterPin } from '../../support/auth';
import { employees, restaurant } from '../../support/fixtures';

test.describe('PIN login', () => {
  test('logs an operator in with a valid PIN and lands in the app shell', async ({ page }) => {
    await linkDevice(page);
    await selectEmployeeAndEnterPin(page, employees.carlos);

    await expect(page).toHaveURL(/\/app\/caja$/);
    await expect(page.locator('.topbar-name')).toHaveText(restaurant.name);
    await expect(page.locator('.user-avatar-sm')).toBeVisible();
  });

  test('logs a supervisor in with a valid PIN', async ({ page }) => {
    await linkDevice(page);
    await selectEmployeeAndEnterPin(page, employees.supervisor);

    await expect(page).toHaveURL(/\/app\/caja$/);
    await expect(page.locator('.topbar-name')).toHaveText(restaurant.name);
  });

  test('keeps the user on the login screen when the PIN is wrong', async ({ page }) => {
    await linkDevice(page);
    await page.locator('.employee-card', { hasText: employees.carlos.name }).click();
    await expect(page.locator('.pin-panel')).toBeVisible();

    await pressPin(page, '0000');

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('.pin-panel')).toBeVisible();
    await expect(page.locator('.pin-dot.filled')).toHaveCount(0);
  });

  test('navigates back from the PIN keypad to the employee list', async ({ page }) => {
    await linkDevice(page);
    await page.locator('.employee-card', { hasText: employees.carlos.name }).click();
    await expect(page.locator('.pin-panel')).toBeVisible();

    await page.locator('.pin-panel').getByRole('button', { name: /volver/i }).click();

    await expect(page.locator('.pin-panel')).toBeHidden();
    await expect(page.getByPlaceholder('Buscar empleado...')).toBeVisible();
  });

  test('logs out from the app shell and returns to the linked-device login', async ({ page }) => {
    await linkDevice(page);
    await selectEmployeeAndEnterPin(page, employees.carlos);
    await expect(page).toHaveURL(/\/app\/caja$/);

    await page.locator('.user-avatar-sm').click();

    await expect(page).toHaveURL(/\/login(\?|$)/);
    await expect(page.locator('.login-logo-text')).toHaveText(restaurant.name);
    await expect(page.getByPlaceholder('Buscar empleado...')).toBeVisible();
  });
});
