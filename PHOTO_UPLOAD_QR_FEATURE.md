# Feature: Subida de foto de producto vía QR

## Qué hace

El operador pulsa **"📷 Añadir foto"** en la gestión de un producto dentro del TPV. El sistema genera un código QR temporal. El operador lo escanea con su móvil; este abre una página web que activa la cámara, permite recortar la foto y la sube. **El TPV la muestra en tiempo real** sin recargar, gracias a WebSocket (Laravel Reverb).

```
TPV (admin)             Mobile (cualquier browser)          Backend
──────────────          ─────────────────────────          ─────────────────────
[Añadir foto] ──POST──▶ /admin/products/{id}/photo-upload-token
               ◀── { token, upload_url, expires_at }
[Show QR]
[Echo subscribe: photo-upload.{token}]

                        Escanea QR → abre /u/foto/{token}
                        GET /public/photo-upload/{token} ──▶ { product_name }
                        [Cámara → recorta → "Usar esta foto"]
                        POST /public/photo-upload/{token} con foto
                                                           ──▶ guarda imagen
                                                           ──▶ broadcast evento
[Recibe evento Echo]
[Actualiza miniatura ✓]
```

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 12, DDD/Hexagonal |
| WebSocket | Laravel Reverb (propio) |
| Almacenamiento | Disk `public` (dev) → Cloudflare R2/AWS S3 (prod) |
| Frontend TPV | Angular 20 + Ionic 8, señales, Echo client |
| Página móvil | Angular 20 standalone (ruta pública sin auth) |
| Resize | Pendiente: Intervention Image v3 |

---

## Archivos creados / modificados

### Backend

```
backend/
├── database/migrations/
│   └── 2026_06_08_000000_create_product_photo_upload_tokens_table.php
│
├── app/Product/
│   ├── Domain/
│   │   ├── Entity/
│   │   │   ├── Product.php                          ← añadido changeImage()
│   │   │   └── ProductPhotoUploadToken.php          ← NEW
│   │   ├── Exception/
│   │   │   ├── ProductPhotoUploadTokenNotFoundException.php   ← NEW
│   │   │   ├── ProductPhotoUploadTokenExpiredException.php    ← NEW
│   │   │   ├── ProductPhotoUploadTokenAlreadyUsedException.php← NEW
│   │   │   └── InvalidProductPhotoException.php              ← NEW
│   │   ├── Interfaces/
│   │   │   ├── ProductRepositoryInterface.php        ← añadido findByIdAndRestaurant()
│   │   │   ├── ProductPhotoUploadTokenRepositoryInterface.php ← NEW
│   │   │   ├── ProductPhotoStorageInterface.php               ← NEW
│   │   │   └── ProductPhotoUploadNotifierInterface.php        ← NEW
│   │
│   ├── Application/
│   │   ├── GenerateProductPhotoUploadToken/          ← NEW (Command/Response/UseCase)
│   │   ├── GetProductPhotoUploadContext/             ← NEW
│   │   └── UploadProductPhoto/                      ← NEW
│   │
│   └── Infrastructure/
│       ├── Broadcasting/
│       │   └── ProductPhotoUploaded.php              ← NEW (ShouldBroadcast, canal público)
│       ├── Notification/
│       │   ├── LogProductPhotoUploadNotifier.php     ← NEW (no-op fase 1)
│       │   └── BroadcastProductPhotoUploadNotifier.php ← NEW (activo)
│       ├── Persistence/
│       │   ├── Models/EloquentProductPhotoUploadToken.php        ← NEW
│       │   ├── Repositories/EloquentProductPhotoUploadTokenRepository.php ← NEW
│       │   └── Repositories/EloquentProductRepository.php        ← añadido findByIdAndRestaurant()
│       ├── Entrypoint/Http/
│       │   ├── GeneratePhotoUploadTokenController.php   ← NEW (admin, auth)
│       │   ├── PublicPhotoUploadContextController.php   ← NEW (público, sin auth)
│       │   └── PublicPhotoUploadController.php          ← NEW (público, sin auth)
│       │   └── Requests/ (3 FormRequests)               ← NEW
│       └── Storage/
│           └── FilesystemProductPhotoStorage.php        ← NEW
│
├── config/
│   ├── product_photos.php   ← NEW (disk, ttl, public_base_url)
│   ├── broadcasting.php     ← NEW (instalado por laravel/reverb)
│   └── reverb.php           ← NEW
│
├── routes/
│   ├── api.php              ← añadidas 3 rutas nuevas
│   └── channels.php         ← NEW
│
├── tests/
│   ├── Feature/Product/ProductPhotoUploadTest.php    ← NEW (4 tests)
│   └── Unit/Product/ProductPhotoUploadTokenEntityTest.php ← NEW (4 tests)
│
└── app/
    ├── Audit/Domain/AuditEventCatalog.php  ← añadido slug product.photo_updated
    └── Providers/AppServiceProvider.php    ← 3 bindings nuevos
```

### Frontend

```
frontend/src/app/
├── services/
│   └── public-photo-upload.service.ts        ← NEW (getContext, uploadPhoto)
│   └── product.service.ts                    ← añadido generatePhotoUploadToken()
│
├── core/services/
│   └── echo.service.ts                       ← NEW (cliente Laravel Echo/Reverb)
│
├── components/gestion/
│   ├── photo-upload-qr-modal/                ← NEW (botón, QR, countdown, estados)
│   │   ├── photo-upload-qr-modal.component.ts
│   │   ├── photo-upload-qr-modal.component.html
│   │   └── photo-upload-qr-modal.component.scss
│   └── products-management/                  ← añadido botón + modal + onPhotoUploaded()
│
├── pages/
│   ├── core/gestion/facades/gestion-products.facade.ts  ← añadido applyPhoto()
│   └── public/photo-upload/                  ← NEW (ruta pública /u/foto/:token)
│       ├── facades/photo-upload.facade.ts    ← NEW
│       ├── photo-upload.page.ts              ← NEW
│       ├── photo-upload.page.html            ← NEW
│       └── photo-upload.page.scss            ← NEW
│
└── app.routes.ts                             ← añadida ruta u/foto/:token
```

### Infra Docker

```
docker-compose.yml     ← servicio reverb en puerto 8080
docker/php/Dockerfile  ← extensión pcntl añadida (necesaria para Reverb)
```

---

## Rutas API

| Método | Ruta | Auth | Descripción |
|---|---|---|---|
| `POST` | `/api/admin/products/{uuid}/photo-upload-token` | Admin session | Genera token QR |
| `GET` | `/api/public/photo-upload/{token}` | Ninguna (throttle 30/min) | Contexto del token (nombre producto) |
| `POST` | `/api/public/photo-upload/{token}` | Ninguna (throttle 30/min) | Sube la foto |

### Token (tabla `product_photo_upload_tokens`)

| Campo | Tipo | Descripción |
|---|---|---|
| `token` | `string(64)` | 32 bytes hex aleatorio, único, actúa de secreto |
| `product_id` | `FK` | Producto al que pertenece |
| `restaurant_id` | `FK` | Tenant — siempre validado |
| `expires_at` | `timestamp` | TTL configurable (default 10 min) |
| `used_at` | `timestamp?` | Nulo = disponible; rellenado = de un solo uso |

---

## Máquina de estados (página móvil)

```
validating → ready ──[Hacer foto]──▶ camera ──[Shoot]──▶ crop ──[Usar]──▶ uploading ──▶ success
                  └──[Galería]──▶ crop ───────────────────────────────────────────▶
                                                                  uploading ──▶ error ──[Retry]──▶ uploading
ready ◀──[Timeout]── expired
ready ◀────────────── used (409)
```

---

## Configuración

### Variables de entorno `backend/.env`

```dotenv
# Reverb WebSocket
REVERB_APP_ID=yurestionic
REVERB_APP_KEY=yurestionic-key
REVERB_APP_SECRET=yurestionic-secret
REVERB_HOST=reverb          # container name en Docker (servidor → servidor)
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_CLIENT_HOST=localhost # lo que usa el NAVEGADOR para conectarse
REVERB_CLIENT_PORT=8080

# Foto de producto
PRODUCT_PHOTO_TOKEN_TTL_MINUTES=10
PRODUCT_PHOTO_PUBLIC_BASE_URL=http://localhost:4200   # ⚠️ ver sección "Dev con móvil real"
PRODUCT_PHOTOS_DISK=public                            # prod: s3
```

### Variables de entorno Frontend `environment.ts`

```typescript
reverb: {
  key: 'yurestionic-key',
  host: 'localhost',    // a donde se conecta el NAVEGADOR del TPV
  port: 8080,
  scheme: 'http',
}
```

---

## ⚠️ Dev con móvil real (imprescindible para probar el QR)

El mayor problema al desarrollar: el QR genera una URL con `localhost`, que el móvil no puede resolver porque `localhost` apunta al propio teléfono, no al ordenador.

### Opción A — IP de red local (más sencilla)

Tu IP local es **`192.168.0.188`**.

1. En `backend/.env`:
   ```dotenv
   PRODUCT_PHOTO_PUBLIC_BASE_URL=http://192.168.0.188:4200
   ```
2. El frontend Docker ya arranca con `--host 0.0.0.0`, así que ya acepta conexiones externas.
3. El móvil y el ordenador deben estar en la **misma red WiFi**.
4. Reiniciar la API para recargar config: `docker compose restart api`

El QR generará `http://192.168.0.188:4200/u/foto/{token}` → el móvil lo abre directamente.

### Opción B — Túnel (si no comparten WiFi o quieres HTTPS)

```bash
# Instalar cloudflared (una sola vez):
brew install cloudflared

# Arrancar un túnel hacia el frontend:
cloudflared tunnel --url http://localhost:4200
# Te da una URL tipo https://xyz.trycloudflare.com

# Poner esa URL en backend/.env:
PRODUCT_PHOTO_PUBLIC_BASE_URL=https://xyz.trycloudflare.com
```

> **Nota:** Para iOS la cámara desde web solo funciona con HTTPS. Si usas la opción A con HTTP, usa la cámara del sistema (`<input capture>`), que sí funciona. Con la opción B (túnel HTTPS) funciona el visor nativo de la cámara.

---

## Tests

```bash
# Backend (810 tests, 0 fallos)
docker compose exec -T api php artisan test

# Solo la feature de fotos
docker compose exec -T api php artisan test --filter=ProductPhotoUpload

# Frontend build
cd frontend && npx ng build --configuration development
```

**Tests nuevos:**

| Archivo | Tipo | Cobertura |
|---|---|---|
| `ProductPhotoUploadTest.php` | Feature | Flujo completo, token usado, token no existe, rechaza no-imagen, evento broadcast |
| `ProductPhotoUploadTokenEntityTest.php` | Unit | Entidad: create, expired, markUsed, fromPersistence |

---

## Cómo levantar todo en local

```bash
# 1. Levantar backend + DB + Reverb
docker compose up -d api db reverb

# 2. Verificar que Reverb está corriendo
docker compose logs reverb
# Debe mostrar: INFO  Starting server on 0.0.0.0:8080

# 3. Levantar frontend (ya corre en Docker también)
docker compose up -d frontend

# 4. Abrir el TPV en el navegador:
#    http://localhost:4200
#    Login como admin → Gestión → Productos → Seleccionar un producto → "📷 Añadir foto"

# 5. Escanear el QR con el móvil
#    (móvil en la misma WiFi, IP configurada en backend/.env)
```

---

## Pendiente (Fase 2 — producción)

### Cloudflare R2 + resize de imagen

En producción las fotos deben almacenarse en la nube y redimensionarse para no servir imágenes de 5MB desde el TPV.

#### Pasos

1. **Instalar dependencias backend:**
   ```bash
   docker compose exec -T api composer require intervention/image league/flysystem-aws-s3-v3
   ```

2. **Crear un bucket en Cloudflare R2** (es gratis hasta 10 GB):
   - Panel Cloudflare → R2 → Crear bucket → nombre: `yurestionic-photos`
   - Crear API token con permisos `Object Read & Write` sobre ese bucket
   - Anotar: `Account ID`, `Access Key ID`, `Secret Access Key`

3. **Variables en `backend/.env` (producción):**
   ```dotenv
   FILESYSTEM_DISK=s3
   PRODUCT_PHOTOS_DISK=s3

   AWS_ACCESS_KEY_ID=tu_r2_access_key
   AWS_SECRET_ACCESS_KEY=tu_r2_secret_key
   AWS_DEFAULT_REGION=auto
   AWS_BUCKET=yurestionic-photos
   AWS_ENDPOINT=https://{ACCOUNT_ID}.r2.cloudflarestorage.com
   AWS_USE_PATH_STYLE_ENDPOINT=true
   ```

4. **Añadir resize en `FilesystemProductPhotoStorage`:**
   ```php
   // En el método store(), antes de $disk->put():
   $manager = new \Intervention\Image\ImageManager(
       new \Intervention\Image\Drivers\Gd\Driver()
   );
   $image = $manager->read($contents);
   $image->scaleDown(width: 1024);
   $contents = $image->toJpeg(quality: 85)->toString();
   $extension = 'jpg'; // normalizar a jpg
   ```

5. **Configurar bucket público** para que las URLs devueltas por `$disk->url()` sean accesibles desde el navegador (Cloudflare R2 → Settings → Public access).

---

## Decisiones de diseño

| Decisión | Razón |
|---|---|
| Canal de broadcast **público** (no privado) | El `token` (64 hex aleatorios) actúa de secreto. No hay usuario autenticado en el móvil, así que un canal privado requeriría auth extra sin beneficio real. |
| Token de **un solo uso** | Evita que dos móviles suban fotos para el mismo QR (condición de carrera). El primero en subir lo marca `used_at`, el segundo recibe 409. |
| `findByIdAndRestaurant()` **sin `TenantContext`** | Los endpoints públicos no tienen sesión, así que no hay tenant resuelto. El filtro de restaurante viene del propio token firmado. |
| `ProductPhotoStorageInterface` como **port** | Permite cambiar el backend de almacenamiento (local → R2 → S3) sin tocar los use cases. La implementación actual es `FilesystemProductPhotoStorage`. |
| Notifier como **port** | En Fase 1 se inyecta el notifier de log. En Fase 3 se swappea por `BroadcastProductPhotoUploadNotifier` sin cambiar el use case. |
| Pantalla móvil en **Angular standalone** | La app ya es Angular. Reutiliza el router, el build pipeline y los servicios. Sin overhead de app nativa ni iframe. |
| `position: absolute` en todas las pantallas | Preserva las transiciones CSS del diseño (opacity + translateY) sin Angular Animations. Fiel al prototipo pixel-perfect. |

---

## Flujo de datos completo (diagrama)

```
[TPV admin]
  └── click "Añadir foto"
        └── GeneratePhotoUploadTokenController  (POST /admin/products/{id}/photo-upload-token)
              └── GenerateProductPhotoUploadToken (use case)
                    ├── ProductRepository.findByIdAndRestaurant()
                    ├── ProductPhotoUploadToken.dddCreate(ttl=10min)
                    ├── TokenRepository.save()
                    └── return { token, upload_url, expires_at }
  └── PhotoUploadQrModalComponent
        ├── renderiza QR (qrcode library) con upload_url
        ├── countdown hasta expires_at
        └── EchoService.listenOnce("photo-upload.{token}", "photo.uploaded")

[Móvil — /u/foto/{token}]
  └── PhotoUploadPage + PhotoUploadFacade
        └── PublicPhotoUploadService.getContext(token)
              └── PublicPhotoUploadContextController (GET /public/photo-upload/{token})
                    └── GetProductPhotoUploadContext (use case)
        └── [cámara → recorte canvas 1080×1080 → blob]
        └── PublicPhotoUploadService.uploadPhoto(token, blob)
              └── PublicPhotoUploadController (POST /public/photo-upload/{token})
                    └── UploadProductPhoto (use case)
                          ├── valida token (existe, no expirado, no usado)
                          ├── FilesystemProductPhotoStorage.store() → guarda en disk
                          ├── Product.changeImage(ProductImageSrc)
                          ├── ProductRepository.save()
                          ├── token.markUsed() → TokenRepository.save()
                          ├── AuditRecorder → product.photo_updated
                          └── BroadcastProductPhotoUploadNotifier.uploaded()
                                └── ProductPhotoUploaded::dispatch() → Reverb
                                      └── canal "photo-upload.{token}"

[TPV admin — Echo escuchando]
  └── recibe evento "photo.uploaded" con { product_uuid, image_src }
        ├── GestionProductsFacade.applyPhoto(uuid, imageSrc)
        └── popup muestra ✓ + miniatura actualizada
```
