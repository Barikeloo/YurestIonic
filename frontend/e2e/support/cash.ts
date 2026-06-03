import { expect, Locator, Page } from '@playwright/test';
import { pressPin } from './auth';
import { Employee } from './fixtures';

export async function enterAmountWithNumpad(scope: Locator, cents: number): Promise<void> {
  const numpad = scope.locator('.numpad').first();
  await numpad.getByRole('button', { name: 'C', exact: true }).click();
  for (const digit of cents.toString()) {
    await numpad.getByRole('button', { name: digit, exact: true }).click();
  }
}

export async function openCashSession(page: Page, employee: Employee, initialAmountCents: number): Promise<void> {
  await expect(page).toHaveURL(/\/app\/caja$/);
  await expect(page.locator('.status-badge')).toContainText('CERRADA');

  await page.locator('.pre-apertura').getByRole('button', { name: 'Abrir caja', exact: true }).click();

  const pinModal = page.locator('.pin-auth-overlay');
  await expect(pinModal).toBeVisible();
  await pressPin(page, employee.pin);
  await expect(pinModal).toBeHidden();

  const openModal = page.locator('.modal-overlay').filter({ hasText: 'Abrir caja — Nueva sesión' });
  await expect(openModal).toBeVisible();
  await enterAmountWithNumpad(openModal, initialAmountCents);
  await openModal.getByRole('button', { name: /^Abrir caja/i }).click();

  await expect(openModal).toBeHidden();
  await expect(page.locator('.status-badge')).toContainText('ABIERTA');
}

export async function closeCashSession(page: Page, countedAmountCents: number): Promise<void> {
  await page.getByRole('button', { name: /cerrar caja/i }).click();

  const wizard = page.locator('.wizard-overlay');
  await expect(wizard).toBeVisible();

  await enterAmountWithNumpad(wizard, countedAmountCents);
  await wizard.getByRole('button', { name: /siguiente/i }).click();

  // Si hay descuadre aparece el paso 2 (justificar). En el flujo limpio del happy path
  // contado == teórico, así que paso directamente al paso 3.
  if (await wizard.getByText(/Descuadre detectado/i).isVisible().catch(() => false)) {
    await wizard.locator('.reason-chip').first().click();
    await wizard.getByRole('button', { name: /siguiente/i }).click();
  }

  await wizard.getByRole('button', { name: /Confirmar Z y cerrar caja/i }).click();
  await expect(wizard).toBeHidden();
}

export async function registerCashMovement(page: Page, amountCents: number): Promise<void> {
  await page.getByRole('button', { name: /\+ Entrada/i }).click();

  const modal = page.locator('.modal-overlay').filter({ hasText: 'Movimiento de caja' });
  await expect(modal).toBeVisible();

  await enterAmountWithNumpad(modal, amountCents);
  await modal.getByRole('button', { name: /^Registrar entrada/i }).click();

  await expect(modal).toBeHidden();
}
