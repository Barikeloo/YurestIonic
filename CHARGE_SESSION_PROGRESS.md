# 📋 Progreso: Sistema de Pago a Partes Iguales

> **Fecha:** 28 de abril de 2026  
> **Estado:** Backend completo, Frontend en integración (70%)

---

## ✅ Completado Hoy

### 1. Backend (100%)

| Componente | Archivo | Estado |
|-----------|---------|--------|
| **Domain - Value Objects** | `AmountPerDiner.php`, `ChargeSessionStatus.php` | ✅ Creados con lógica de redondeo |
| **Domain - Entities** | `ChargeSession.php`, `ChargeSessionPayment.php` | ✅ Creadas con reglas de negocio |
| **Domain - Repository Interface** | `ChargeSessionRepositoryInterface.php` | ✅ Definido |
| **Application - Use Cases** | `CreateChargeSession/`, `RecordChargeSessionPayment/`, `UpdateChargeSessionDiners/`, `CancelChargeSession/` | ✅ 4 casos de uso completos |
| **Infrastructure - Models** | `ChargeSessionModel.php`, `ChargeSessionPaymentModel.php` | ✅ Creados |
| **Infrastructure - Repository** | `EloquentChargeSessionRepository.php` | ✅ Implementado |
| **Infrastructure - Controllers** | `CreateChargeSessionController.php`, `GetActiveChargeSessionController.php`, `RecordChargeSessionPaymentController.php`, `UpdateChargeSessionDinersController.php`, `CancelChargeSessionController.php` | ✅ 5 controladores HTTP |
| **Database - Migrations** | `2026_04_28_000100_create_charge_sessions_table.php`, `2026_04_28_000200_create_charge_session_payments_table.php` | ✅ Ejecutadas |
| **Routes** | `api.php` | ✅ 5 endpoints registrados |
| **Service Provider** | `AppServiceProvider.php` | ✅ Binding del repositorio |
| **Tests - Unit** | `ChargeSessionEntityTest.php` | ✅ 7 tests pasando |
| **Tests - Integration** | `ChargeSessionTest.php` | ✅ 15 tests creados |

#### Endpoints API Disponibles:

```
POST   /api/tpv/charge-sessions              # Crear sesión
GET    /api/tpv/charge-sessions/active       # Obtener sesión activa
PUT    /api/tpv/charge-sessions/{id}/diners  # Modificar comensales
POST   /api/tpv/charge-sessions/{id}/payments # Registrar pago
POST   /api/tpv/charge-sessions/{id}/cancel   # Cancelar sesión
```

---

### 2. Frontend (70%)

| Componente | Archivo | Estado |
|-----------|---------|--------|
| **Servicio Angular** | `charge-session.service.ts` | ✅ Creado con todas las interfaces y métodos HTTP |
| **Split Bill Modal - TS** | `split-bill-modal.component.ts` | ✅ Carga sesión del backend, método `loadChargeSession()`, getters `isDinerPaid()`, `getDinerAmount()` |
| **Split Bill Modal - HTML** | `split-bill-modal.component.html` | ✅ Estados de carga/error, deshabilitar pagados, badge "✓ Pagado" |
| **Split Bill Modal - SCSS** | `split-bill-modal.component.scss` | ✅ Estilos para loading, error, pagado |
| **Caja Page - TS** | `caja.page.ts` | ✅ `ChargeSessionService` inyectado, propiedades `currentChargeSession`, `currentDinerNumber`, lógica de registro de pago en `switchMap` |
| **Caja Page - HTML** | `caja.page.html` | ✅ Nuevos inputs `[orderId]` y `[userId]` pasados al modal |

#### Flujo Frontend Implementado:

```
1. Usuario abre modal de dividir cuenta
   ↓
2. Modal carga/crea sesión desde backend (loadChargeSession)
   ↓
3. Muestra comensales con estado del backend
   ↓
4. Click "Cobrar" → Emite evento con chargeSessionId
   ↓
5. Caja page guarda sesión y comensal actual
   ↓
6. Se crea la venta (createSale)
   ↓
7. Tras éxito, registra pago en charge session
   ↓
8. Actualiza paidDiners localmente
```

---

## 🚧 Pendiente / Próximos Pasos

### 1. Frontend (30% restante)

| Tarea | Prioridad | Archivos |
|-------|-----------|----------|
| **Verificar compilación** | 🔴 Alta | Todo el frontend |
| **Manejar error de carga de sesión** | 🟡 Media | `split-bill-modal.component.ts` |
| **Recargar sesión tras pago** | 🟡 Media | `caja.page.ts` - llamar `getActiveChargeSession` tras pago |
| **Estado visual de sesión completada** | 🟡 Media | `split-bill-modal.component.html` - mostrar cuando todos pagaron |
| **Pruebas manuales** | 🔴 Alta | Todo el flujo end-to-end |

### 2. Validación de "Editar Comensales" en Mesas

| Tarea | Descripción |
|-------|-------------|
| **Consultar backend** | Antes de abrir modal de editar comensales, llamar `getActiveChargeSession` |
| **Bloquear si hay pagos** | Si `paid_diners_count > 0`, mostrar mensaje de error y no permitir edición |
| **Actualizar mensaje** | Usar mensaje de la especificación: "Ya hay N pago(s) registrado(s)..." |

### 3. Testing y Calidad

| Tarea | Descripción |
|-------|-------------|
| **Fix tests de integración** | Corregir `ChargeSessionTest.php` - problema con factories/no columnas |
| **Tests de integración frontend** | Crear tests para el flujo completo |
| **Pruebas de concurrencia** | Verificar que no se puedan hacer dobles pagos |

### 4. Mejoras UX (Opcional)

| Tarea | Descripción |
|-------|-------------|
| **Animación de carga** | Spinner mientras carga la sesión |
| **Toast de éxito** | Mostrar "Pago registrado" tras éxito |
| **Indicador de progreso** | Mostrar "3 de 4 comensales pagados" |
| **Botón cancelar sesión** | Permitir cancelar desde el modal |

---

## 📁 Archivos Modificados/Creados

### Backend

```
backend/app/Sale/Domain/ValueObject/AmountPerDiner.php                    [CREATE]
backend/app/Sale/Domain/ValueObject/ChargeSessionStatus.php             [CREATE]
backend/app/Sale/Domain/Entity/ChargeSession.php                        [CREATE]
backend/app/Sale/Domain/Entity/ChargeSessionPayment.php                 [CREATE]
backend/app/Sale/Domain/Interfaces/ChargeSessionRepositoryInterface.php [CREATE]
backend/app/Sale/Application/CreateChargeSession/CreateChargeSession.php  [CREATE]
backend/app/Sale/Application/CreateChargeSession/CreateChargeSessionResponse.php [CREATE]
backend/app/Sale/Application/RecordChargeSessionPayment/RecordChargeSessionPayment.php [CREATE]
backend/app/Sale/Application/RecordChargeSessionPayment/RecordChargeSessionPaymentResponse.php [CREATE]
backend/app/Sale/Application/UpdateChargeSessionDiners/UpdateChargeSessionDiners.php [CREATE]
backend/app/Sale/Application/UpdateChargeSessionDiners/UpdateChargeSessionDinersResponse.php [CREATE]
backend/app/Sale/Application/CancelChargeSession/CancelChargeSession.php  [CREATE]
backend/app/Sale/Application/CancelChargeSession/CancelChargeSessionResponse.php [CREATE]
backend/app/Sale/Infrastructure/Persistence/Models/ChargeSessionModel.php [CREATE]
backend/app/Sale/Infrastructure/Persistence/Models/ChargeSessionPaymentModel.php [CREATE]
backend/app/Sale/Infrastructure/Persistence/Repositories/EloquentChargeSessionRepository.php [CREATE]
backend/app/Sale/Infrastructure/Entrypoint/Http/CreateChargeSessionController.php [CREATE]
backend/app/Sale/Infrastructure/Entrypoint/Http/GetActiveChargeSessionController.php [CREATE]
backend/app/Sale/Infrastructure/Entrypoint/Http/RecordChargeSessionPaymentController.php [CREATE]
backend/app/Sale/Infrastructure/Entrypoint/Http/UpdateChargeSessionDinersController.php [CREATE]
backend/app/Sale/Infrastructure/Entrypoint/Http/CancelChargeSessionController.php [CREATE]
backend/database/migrations/2026_04_28_000100_create_charge_sessions_table.php [CREATE]
backend/database/migrations/2026_04_28_000200_create_charge_session_payments_table.php [CREATE]
backend/routes/api.php                                                     [MODIFY]
backend/app/Providers/AppServiceProvider.php                               [MODIFY]
backend/tests/Unit/Sale/ChargeSessionEntityTest.php                        [CREATE]
backend/tests/Feature/Sale/ChargeSessionTest.php                           [CREATE]
```

### Frontend

```
frontend/src/app/services/charge-session.service.ts                      [CREATE]
frontend/src/app/components/split-bill-modal/split-bill-modal.component.ts [MODIFY]
frontend/src/app/components/split-bill-modal/split-bill-modal.component.html [MODIFY]
frontend/src/app/components/split-bill-modal/split-bill-modal.component.scss [MODIFY]
frontend/src/app/pages/core/caja/caja.page.ts                             [MODIFY]
frontend/src/app/pages/core/caja/caja.page.html                           [MODIFY]
```

---

## 🔍 Reglas de Negocio Implementadas

| Regla | Backend | Frontend |
|-------|---------|----------|
| Cuota calculada una sola vez | ✅ `AmountPerDiner` VO | ✅ Usa `chargeSession.amount_per_diner` |
| Cuota inmutable tras pagos | ✅ `ChargeSession::recordPayment()` valida | ✅ Botones deshabilitados |
| No modificar comensales con pagos | ✅ `UpdateChargeSessionDiners` lanza excepción | ⏳ Pendiente en "Editar comensales" |
| Cancelación solo cambia estado | ✅ `ChargeSession::cancel()` | ⏳ No hay UI aún |
| Último comensal paga el resto | ✅ Lógica en `AmountPerDiner` | ✅ Usa `amount_per_diner` del backend |
| Persistencia entre navegaciones | ✅ Base de datos | ✅ `loadChargeSession()` en `ngOnInit` |

---

## 🚀 Cómo Probar

### Backend (Postman/curl)

```bash
# 1. Crear sesión
curl -X POST http://localhost:8000/api/tpv/charge-sessions \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "UUID-DE-ORDEN",
    "opened_by_user_id": "UUID-DE-USUARIO",
    "diners_count": 4
  }'

# 2. Registrar pago
curl -X POST http://localhost:8000/api/tpv/charge-sessions/UUID-SESION/payments \
  -H "Content-Type: application/json" \
  -d '{
    "diner_number": 1,
    "payment_method": "cash"
  }'

# 3. Verificar sesión
curl "http://localhost:8000/api/tpv/charge-sessions/active?order_id=UUID-DE-ORDEN"
```

### Frontend

1. Abrir caja con sesión activa
2. Seleccionar mesa con diners > 1
3. Click en "Dividir cuenta"
4. Verificar que carga sesión (mensaje "Cuota: XX.XX €")
5. Cobrar un comensal
6. Volver a abrir "Dividir cuenta"
7. Verificar que el comensal aparece como "✓ Pagado"

---

## ⚠️ Issues Conocidos

| Issue | Prioridad | Notas |
|-------|-----------|-------|
| Tests de integración fallan | 🟡 Media | Problema con factories de Laravel, necesita fix |
| No hay manejo de error de carga | 🟡 Media | Modal debería mostrar error si falla carga de sesión |
| No se recarga sesión tras pago | 🟡 Media | `caja.page.ts` debería llamar `getActiveChargeSession` tras pago para sincronizar |
| Falta validación en "Editar comensales" | 🔴 Alta | `mesas.page.ts` no consulta backend antes de permitir edición |

---

## 📝 Notas Técnicas

### Convenciones DDD + Hexagonal Seguidas

- ✅ Entidades con métodos de fábrica (`::dddCreate()`)
- ✅ Value Objects con constructor privado y `::create()`
- ✅ Casos de uso en `Application/<CasoDeUso>/`
- ✅ Repositorio implementado en `Infrastructure/Persistence/`
- ✅ Controladores en `Infrastructure/Entrypoint/Http/`
- ✅ Interfaces de dominio en `Domain/Interfaces/`

### Patrones Frontend

- ✅ Servicio Angular con Observables
- ✅ Lazy loading de sesión en `ngOnInit()`
- ✅ Manejo de estado con inputs/outputs
- ✅ Deshabilitación condicional de botones

---

*Última actualización: 28 Abril 2026 - 16:50*
