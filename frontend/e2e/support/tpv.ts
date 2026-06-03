import { expect, Page } from '@playwright/test';
import { pressPin } from './auth';
import { Employee } from './fixtures';

export async function navigateToMesas(page: Page): Promise<void> {
  await page.goto('/app/mesas');
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.mesas-page .zone-tabs button').first()).toBeVisible({ timeout: 10_000 });
}

export async function selectZone(page: Page, zoneName: string): Promise<void> {
  await page.getByRole('button', { name: zoneName, exact: true }).click();
}

export async function openTable(
  page: Page,
  zoneName: string,
  tableName: string,
  diners: number,
  employee?: Employee,
): Promise<void> {
  await selectZone(page, zoneName);

  const mesa = page.locator('.mesa').filter({
    has: page.locator('.mesa-name', { hasText: new RegExp(`^${tableName}$`) }),
  });
  await mesa.first().click();

  await page.locator('.order-panel').getByRole('button', { name: /^Abrir mesa$/ }).click();

  // Mesas page may prompt for PIN re-auth (AuthActionType.NORMAL) when the
  // pin-auth context has been cleared (e.g. after a hard navigation).
  if (employee) {
    const pinModal = page.locator('.pin-auth-overlay');
    try {
      await pinModal.waitFor({ state: 'visible', timeout: 3_000 });
      await pressPin(page, employee.pin);
      await expect(pinModal).toBeHidden();
    } catch {
      // PIN modal didn't appear: pin-auth context still valid, continue.
    }
  }

  const modal = page.locator('.modal-box:visible').filter({ hasText: 'Abrir mesa' }).filter({ hasText: 'Comensales' }).first();
  await expect(modal).toBeVisible();
  await modal.getByRole('button', { name: String(diners), exact: true }).click();
  await modal.getByRole('button', { name: 'Abrir mesa', exact: true }).click();

  await expect(modal).toBeHidden();
}

export async function enterComandaForSelectedTable(page: Page): Promise<void> {
  await page.locator('.order-panel').getByRole('button', { name: /Nueva comanda/i }).click();
  await expect(page).toHaveURL(/\/app\/comanda/);
}

export async function addProductToComanda(page: Page, family: string, productName: string): Promise<void> {
  await page.getByRole('button', { name: family, exact: true }).click();
  const card = page.locator('.product-card').filter({
    has: page.locator('.product-name', { hasText: new RegExp(`^${productName}$`) }),
  });
  await card.first().click();

  // Products with modifiers/variants open the config modal; confirm if shown.
  const configModal = page.locator('app-product-config-modal .modal-overlay');
  try {
    await configModal.waitFor({ state: 'visible', timeout: 1_500 });
    await configModal.getByRole('button', { name: /Añadir a la comanda/i }).click();
    await expect(configModal).toBeHidden();
  } catch {
    // No config modal: product was added directly.
  }
}

export async function sendComanda(page: Page): Promise<void> {
  await page.getByRole('button', { name: /Enviar comanda/i }).click();
  await expect(page.locator('.order-section-label', { hasText: 'Sin enviar' })).toBeHidden();
}

export async function goBackFromComanda(page: Page): Promise<void> {
  await page.locator('.btn-back').click();
  await expect(page).toHaveURL(/\/app\/(mesas|pedidos)/);
}

export async function closeAccountFromMesa(page: Page, employee?: Employee): Promise<void> {
  await page.locator('.order-panel').getByRole('button', { name: /Cerrar cuenta/i }).click();

  if (employee) {
    const pinModal = page.locator('.pin-auth-overlay');
    try {
      await pinModal.waitFor({ state: 'visible', timeout: 3_000 });
      await pressPin(page, employee.pin);
      await expect(pinModal).toBeHidden();
    } catch {
      // PIN modal not required.
    }
  }

  const modal = page.locator('.modal-box:visible').filter({ hasText: 'Cerrar cuenta' }).first();
  await expect(modal).toBeVisible();
  await modal.getByRole('button', { name: /Confirmar cierre/i }).click();
  // Mesa transitions to TO_CHARGE on success; assert the "Cobrar" button appears.
  await expect(page.locator('.order-panel').getByRole('button', { name: /^Cobrar$/ })).toBeVisible({ timeout: 10_000 });
}

export async function chargeFromMesa(page: Page, employee: Employee): Promise<void> {
  await page.locator('.order-panel').getByRole('button', { name: /^Cobrar$/ }).click();

  const pinModal = page.locator('.pin-auth-overlay');
  if (await pinModal.isVisible().catch(() => false)) {
    await pressPin(page, employee.pin);
    await expect(pinModal).toBeHidden();
  }

  await expect(page).toHaveURL(/\/app\/caja/);
}

export async function payCash(page: Page): Promise<void> {
  const cobrarModal = page.locator('app-cobrar-modal .modal-content');
  await expect(cobrarModal).toBeVisible();

  // Wait for any entry animation to finish before clicking the sticky footer button.
  const confirmBtn = cobrarModal.locator('.modal-footer').getByRole('button', { name: /Cobrar.*€/i });
  await expect(confirmBtn).toBeVisible();
  await page.waitForTimeout(300);
  await confirmBtn.click({ force: true });

  // The cobrar modal closes when the payment is registered; assert that as the success signal.
  await expect(cobrarModal).toBeHidden({ timeout: 15_000 });

  // Optionally dismiss the success overlay if it remains (e.g. when a ticket preview shows).
  const successOverlay = page.locator('app-payment-success .success-overlay');
  if (await successOverlay.isVisible({ timeout: 500 }).catch(() => false)) {
    const closeBtn = successOverlay.getByRole('button', { name: /^Cerrar$/i });
    if (await closeBtn.isVisible({ timeout: 500 }).catch(() => false)) {
      await closeBtn.click();
    }
    await expect(successOverlay).toBeHidden({ timeout: 10_000 });
  }
}

export async function readExpectedAmountCents(page: Page): Promise<number> {
  const text = await page.locator('.hero-value').first().innerText();
  const cleaned = text.replace(/[^\d,]/g, '').replace(',', '.');
  const euros = Number.parseFloat(cleaned);
  if (Number.isNaN(euros)) {
    throw new Error(`Could not parse expected cash amount from "${text}"`);
  }
  return Math.round(euros * 100);
}
