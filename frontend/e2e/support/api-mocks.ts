import { Page } from '@playwright/test';
import { quickUsersResponse } from './fixtures';

export async function mockQuickUsers(page: Page): Promise<void> {
  await page.route('**/api/auth/quick-users**', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(quickUsersResponse),
    });
  });
}
