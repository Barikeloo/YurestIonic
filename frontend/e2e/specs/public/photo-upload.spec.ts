import { expect, test } from '@playwright/test';
import { loginAsAdmin } from '../../support/auth';

test.describe.serial('photo upload', () => {
  test.beforeEach(({}, testInfo) => {
    test.skip(
      testInfo.project.name !== 'stateful',
      'photo upload tests mutate product data and run only in the stateful project',
    );
  });

  test('generates token, uploads photo via gallery, and verifies backend processing', async ({ page }) => {
    await loginAsAdmin(page);

    const families = await (await page.request.get('/api/admin/families')).json();
    const taxes = await (await page.request.get('/api/admin/taxes')).json();

    const product = await (await page.request.post('/api/admin/products', {
      data: {
        family_id: families[0].id,
        tax_id: taxes[0].id,
        name: 'E2E Photo Test',
        price: 500,
        stock: 10,
      },
    })).json();
    const productId = product.id as string;

    const tokenData = await (await page.request.post(`/api/admin/products/${productId}/photo-upload-token`)).json();
    const token = tokenData.token as string;

    // UI: open public page and verify context loads
    await page.goto(`/u/foto/${token}`);
    await page.waitForLoadState('networkidle');

    await expect(page.getByText('E2E Photo Test').first()).toBeVisible({ timeout: 15000 });
    await expect(page.getByText('Haz la foto del plato')).toBeVisible();

    // UI: open gallery sheet and upload a photo
    await page.getByRole('button', { name: /elegir de la galería/i }).click();
    await expect(page.locator('.sheet')).toBeVisible();

    const testPng = Buffer.from(
      'iVBORw0KGgoAAAANSUhEUgAAAMgAAADICAIAAAAiOjnJAAACFUlEQVR4nO3SQQkAIADAQDOZybDGsoRDkIMLsMfGXhOuG88L+JKxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi8QBwOer/IgOr/IAAAAASUVORK5CYII=',
      'base64',
    );

    await page.locator('input[type="file"]').last().setInputFiles({
      name: 'test.png',
      mimeType: 'image/png',
      buffer: testPng,
    });

    await expect(page.getByText('¡Foto enviada!')).toBeVisible({ timeout: 15000 });

    // Verify the uploaded image appears in the success screen
    const uploadedImg = page.locator('.done-thumb img');
    await expect(uploadedImg).toBeVisible();
    const uploadedUrl = await uploadedImg.getAttribute('src');
    expect(uploadedUrl).toBeTruthy();

    // Fetch the stored image through the Angular proxy and verify it's a valid image (WebP)
    const imgUrl = new URL(uploadedUrl!);
    const imgRes = await page.request.get(imgUrl.pathname);
    expect(imgRes.ok()).toBeTruthy();
    expect(imgRes.headers()['content-type'] ?? '').toContain('image');

    // Backend verification: product's image_src via admin API
    await expect(async () => {
      const res = await page.request.get(`/api/admin/products/${productId}`);
      const data = await res.json();
      expect(data.image_src).toBeTruthy();
    }).toPass({ timeout: 8000 });
  });
});
