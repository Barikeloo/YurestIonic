start:
	docker compose up -d

stop:
	docker compose down

restart: stop start

recreate:
	docker compose down
	docker compose up -d --build

status:
	docker compose ps

install:
	docker compose run --rm api composer install
	docker compose run --rm api php artisan migrate

install-frontend:
	docker compose run --rm frontend npm install

db-migrate:
	docker compose exec api php artisan migrate

test:
	docker compose exec api php artisan test

test-frontend:
	docker compose exec frontend npx ng test --watch=false --browsers=ChromeHeadlessCI

# E2E tests run from the host because the global-setup needs `docker compose exec`
# to seed the API container. Make sure `make start` is running so frontend (4200)
# and api (8000) are up before invoking.
test-e2e:
	cd frontend && E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 npx playwright test

test-e2e-headed:
	cd frontend && E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 npx playwright test --headed

test-e2e-ui:
	cd frontend && E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 npx playwright test --ui

# Force video + trace + screenshot for every test, useful for demos / debugging.
test-e2e-videos:
	cd frontend && E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 \
		E2E_VIDEO=on E2E_TRACE=on E2E_SCREENSHOT=on npx playwright test

# Skip the seed step (assumes DB is already in a known state).
test-e2e-fast:
	cd frontend && E2E_SKIP_WEB_SERVER=1 E2E_BASE_URL=http://localhost:4200 \
		E2E_SKIP_SEED=1 npx playwright test

# Open the HTML report from the last run on http://localhost:9323.
test-e2e-report:
	cd frontend && npx playwright show-report e2e/reports/html --host 127.0.0.1 --port 9323

build-frontend:
	docker compose exec frontend npx ng build

# Servidor de desarrollo con live reload (watch) en primer plano. Abre http://localhost:4200 en el navegador.
# Si el frontend ya está en marcha (make start), lo detiene antes para liberar el puerto 4200.
# Para abrir el navegador automáticamente: cd frontend && npm start -- --open
serve-frontend:
	docker compose stop frontend 2>/dev/null || true
	docker compose run --rm -p 4200:4200 frontend sh -c "npm install && npx ng serve --host 0.0.0.0"

lint:
	docker compose exec api vendor/bin/pint

logs-backend:
	docker compose exec -it api sh -c "touch storage/logs/laravel.log && less +F storage/logs/laravel.log"
