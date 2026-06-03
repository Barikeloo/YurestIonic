import { runSaonaDemoSeed } from './seed';

export default async function globalSetup(): Promise<void> {
  if (process.env.E2E_SKIP_SEED) {
    console.log('[e2e] E2E_SKIP_SEED set, skipping Saona demo seed');
    return;
  }

  const seederClass = process.env.E2E_SEEDER_CLASS ?? 'SaonaDemoSeeder';
  const start = Date.now();
  console.log(`[e2e] seeding ${seederClass} via docker compose exec...`);
  runSaonaDemoSeed();
  console.log(`[e2e] ${seederClass} seeded in ${Date.now() - start}ms`);
}
