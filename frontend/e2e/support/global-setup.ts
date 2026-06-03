import { execSync } from 'node:child_process';
import { resolve } from 'node:path';

export default async function globalSetup(): Promise<void> {
  if (process.env.E2E_SKIP_SEED) {
    console.log('[e2e] E2E_SKIP_SEED set, skipping Saona demo seed');
    return;
  }

  const projectRoot = resolve(__dirname, '../../..');
  const composeService = process.env.E2E_API_SERVICE ?? 'api';
  const seederClass = process.env.E2E_SEEDER_CLASS ?? 'SaonaDemoSeeder';

  const start = Date.now();
  console.log(`[e2e] seeding ${seederClass} via docker compose exec ${composeService}...`);

  try {
    execSync(
      `docker compose exec -T ${composeService} php artisan db:seed --class=${seederClass} --no-interaction`,
      {
        cwd: projectRoot,
        stdio: process.env.E2E_VERBOSE ? 'inherit' : 'pipe',
        timeout: 180_000,
      },
    );
  } catch (err) {
    const stderr = err instanceof Error && 'stderr' in err ? String((err as { stderr: Buffer }).stderr) : '';
    const stdout = err instanceof Error && 'stdout' in err ? String((err as { stdout: Buffer }).stdout) : '';
    throw new Error(`[e2e] failed to seed ${seederClass}.\nstdout: ${stdout}\nstderr: ${stderr}`);
  }

  console.log(`[e2e] ${seederClass} seeded in ${Date.now() - start}ms`);
}
