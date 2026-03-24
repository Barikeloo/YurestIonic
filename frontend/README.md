# Frontend — Angular + Ionic

Proyecto frontend basado en **Angular 20** con **Ionic 8**.

---

## Requisitos previos

El frontend se ejecuta dentro de un contenedor Docker, por lo que no necesitas instalar Node ni Angular en tu máquina. Solo necesitas:

- **Docker** y **Docker Compose**
- **Make**

Si prefieres trabajar fuera de Docker, necesitarás **Node.js >= 20** y **npm**.

---

## Primeros pasos

```bash
# Desde la raíz del proyecto

# 1. Levantar todos los contenedores (API + frontend + DB + DbGate)
make start

# 2. Instalar dependencias del frontend
make install-frontend

# 3. Servidor de desarrollo con live reload (http://localhost:4200)
make serve-frontend
```

---

## Comandos disponibles

### Mediante Makefile (recomendado)

Todos los comandos se ejecutan desde la **raíz del proyecto**:

| Comando                | Descripción                                                  |
|------------------------|--------------------------------------------------------------|
| `make install-frontend`| Instala las dependencias (`npm install`)                     |
| `make serve-frontend`  | Arranca el servidor de desarrollo con live reload            |
| `make build-frontend`  | Genera el build de producción                                |
| `make test-frontend`   | Ejecuta los tests unitarios (Karma + Jasmine, headless)      |

### Mediante Ionic CLI (dentro del contenedor)

Si necesitas ejecutar comandos de Ionic directamente:

```bash
# Entrar al contenedor del frontend
docker compose exec frontend sh
```

Una vez dentro del contenedor:

```bash
# Servir la app en modo desarrollo
npx ionic serve

# Generar un nuevo componente
npx ionic generate component components/mi-componente

# Generar una nueva página
npx ionic generate page pages/mi-pagina

# Generar un servicio
npx ionic generate service services/mi-servicio

# Generar un pipe
npx ionic generate pipe pipes/mi-pipe

# Generar un guard
npx ionic generate guard guards/mi-guard

# Build de producción
npx ionic build --prod

# Ejecutar linter
npx ng lint
```

### Mediante Angular CLI (dentro del contenedor)

Los comandos `ng` también están disponibles:

```bash
# Generar un componente standalone
npx ng generate component components/mi-componente --standalone

# Generar un servicio
npx ng generate service services/mi-servicio

# Ejecutar tests en modo watch
npx ng test

# Ejecutar tests una sola vez (CI)
npx ng test --watch=false --browsers=ChromeHeadlessCI
```

---

## Estructura del proyecto

```
src/app/
├── components/        # Componentes reutilizables (botones, cards, modals...)
├── pages/             # Páginas de la aplicación
│   └── core/          # Páginas principales
├── pipes/             # Pipes personalizados
├── providers/         # Interceptores HTTP y providers
│   └── interceptor.ts # Añade headers por defecto (Accept, Accept-Language)
└── services/          # Servicios (llamadas a la API, lógica compartida)
```

---

## Configuración del entorno

Los archivos de entorno se encuentran en `src/environments/`:

- `environment.ts` — Desarrollo (usado por defecto con `ng serve`)
- `environment.prod.ts` — Producción (usado con `ng build --configuration production`)

Para cambiar la URL de la API, edita la propiedad `apiUrl` en el archivo correspondiente.

---

## Capacitor (apps nativas)

El proyecto incluye **Capacitor 8** para compilar la app como aplicación nativa (iOS/Android). La configuración se encuentra en `capacitor.config.ts`.

```bash
# Añadir plataforma (dentro del contenedor o con Node local)
npx cap add android
npx cap add ios

# Sincronizar el build web con las plataformas nativas
npx ionic build --prod
npx cap sync

# Abrir el proyecto nativo en el IDE correspondiente
npx cap open android   # Android Studio
npx cap open ios       # Xcode
```

---

## Recursos útiles

- [Documentación de Ionic](https://ionicframework.com/docs)
- [Documentación de Angular](https://angular.dev)
- [Documentación de Capacitor](https://capacitorjs.com/docs)
