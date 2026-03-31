# Dominio TPV — Formación Técnica 2026
> Lecciones del Mundo Real en Integraciones de Hostelería

---

## Resumen General

Formación técnica avanzada sobre el diseño correcto de APIs para sistemas TPV (Terminal Punto de Venta) en el sector hostelero. Cubre los errores más comunes en implementaciones reales y establece las reglas de diseño correctas para construir una API robusta, fiscal y operativamente correcta.

---

## Objetivo del Documento

Proporcionar a equipos técnicos (desarrolladores, arquitectos, integradores) un conjunto de **reglas y patrones accionables** para diseñar o auditar APIs de TPV en hostelería, evitando los fallos más frecuentes detectados en producción.

---

## Requisitos Funcionales

### 1. Importes y Cálculo de Totales

- El total de cabecera del ticket **nunca debe ser un campo manual**. Debe calcularse siempre como:
  ```
  total = Σ líneas[i].subtotal
  ```
- Si el TPV envía cabecera y líneas y **no cuadran → rechazar el ticket**.
- La fuente de verdad del importe es siempre la **suma de las líneas**.

---

### 2. Timestamps por Línea

Cada línea de comanda debe incluir obligatoriamente:

| Campo | Propósito |
|---|---|
| `created_at` | Orden de inserción real de la comanda |
| `updated_at` | Auditoría de modificaciones |
| `user_id` | Trazabilidad de quién realizó la acción |

Sin estos campos no es posible: reconstruir el orden de la comanda, medir tiempos de servicio, analizar picos por franja horaria, resolver disputas con clientes ni auditar cambios post-inserción.

---

### 3. Jerarquía de Productos

La API **no debe ser plana**. Cada línea debe incluir `tipo` y `parent_id` para representar la estructura del pedido.

**Tipos mínimos requeridos:**
- `LINEA_SUELTA` — producto independiente
- `MENU` — padre del menú compuesto
- `HIJO_MENU` — componente de un menú
- `EXTRA` — modificador de un producto

```json
{
  "tipo": "MENU",
  "parent_id": null,
  "hijos": [
    { "tipo": "PRIMER_PLATO", "parent_id": 1 },
    { "tipo": "SEGUNDO", "parent_id": 1 },
    { "tipo": "EXTRA", "parent_id": 2 }
  ]
}
```

---

### 4. Fracciones y Unidades de Venta

Dos campos obligatorios en cada línea: `cantidad` (decimal) + `unidad_venta`.

| Caso | cantidad | unidad_venta | precio |
|---|---|---|---|
| Botella entera | `1` | `BOTELLA` | `35,00€` |
| Copa (fracción) | `0.2` | `BOTELLA` | `7,00€` |
| ❌ Ambiguo | `1` | ??? | `7,00€` |

Valores válidos para `unidad_venta`: `BOTELLA`, `COPA`, `RACIÓN`, etc. Afecta directamente a inventario y facturación.

---

### 5. Menús vs. Promociones

Son **entidades distintas** en el modelo de datos. Nunca deben mezclarse.

| Concepto | Definición |
|---|---|
| **MENÚ** | Producto compuesto con precio propio. El desglose interno es solo informativo, no genera descuento. |
| **PROMOCIÓN** | Descuento aplicado sobre productos sueltos con precio individual. |

> Mezclar ambos destruye la trazabilidad de precios.

---

### 6. Backoffice vs. Front de Venta

La API debe servir a dos consumidores con necesidades opuestas sin que uno contamine al otro.

| Característica | Backoffice | Front de Venta (TPV) |
|---|---|---|
| Operaciones | CRUD: familias, productos, zonas, impuestos | Transaccional: abrir/cerrar ventas, añadir líneas |
| Velocidad | No crítica | Respuesta < 200ms |
| Interfaz | Escritorio, teclado | Tablet táctil |
| Usuarios | 1–2 personas | Multi-terminal concurrente |
| Tolerancia a errores | Validaciones estrictas | Cero tolerancia |
| Features | Paginación, filtros, búsqueda | Cálculo de totales en tiempo real |

---

### 7. Modelo de Datos: Snapshot de Precio e Impuesto

Cada línea de venta debe guardar `precio_unitario` y `porcentaje_impuesto` como **valores propios**, nunca como FK a la tabla de impuestos.

```json
// ❌ Error: referencia mutable
{ "impuesto_id": 3 }

// ✅ Correcto: snapshot inmutable
{
  "precio_unitario": 8.50,
  "porcentaje_impuesto": 10.00,
  "nombre_impuesto": "IVA 10%"
}
```

> Si el IVA cambia, las líneas históricas deben permanecer con el tipo vigente en el momento de la venta.

---

### 8. Soft Deletes

Obligatorio en todas las entidades que tengan ventas asociadas (productos, impuestos, zonas, etc.).

- Nunca usar hard delete si hay ventas referenciadas.
- Usar campo `deleted_at`.
- Las queries deben distinguir contexto:

| Endpoint | Comportamiento |
|---|---|
| `GET /productos` | Solo activos (`WHERE deleted_at IS NULL`) |
| `GET /productos?all=true` | Todos (incluye eliminados) |

---

### 9. Estados de Mesa y Concurrencia

**Flujo de estados:** `LIBRE → OCUPADA → LIBRE`

Problemas de concurrencia con múltiples terminales requieren control obligatorio:

- **Bloqueo optimista (mínimo requerido):**
  ```sql
  UPDATE mesas SET estado='OCUPADA'
  WHERE id=5 AND estado='LIBRE'
  -- Si affected_rows = 0 → alguien se adelantó → rechazar
  ```
- **Alternativa:** `SELECT ... FOR UPDATE` (bloqueo pesimista)

---

### 10. Ciclo de Vida del Ticket Fiscal

```
PROFORMA → TICKET FISCAL → RECTIFICATIVA
(mutable)   (inmutable)     (corrección)
```

| Estado | Descripción |
|---|---|
| **PROFORMA** | Venta abierta. Borrador mutable. Admite cambios de líneas y cantidades. |
| **TICKET FISCAL** | Documento real. Inmutable tras el pago. IVA registrado. Número de serie asignado. |
| **RECTIFICATIVA** | Corrección post-pago. Documento nuevo que referencia al original. |

---

### 11. Series de Facturación

- Cada tipo de documento tiene su propia serie:
  - `T-2026/0001` → Tickets
  - `F-2026/0001` → Facturas
  - `R-2026/0001` → Rectificativas
- La numeración reinicia anualmente. El año es parte del identificador de serie.
- La API debe permitir: prefijo personalizado, número inicial configurable y varias series activas simultáneas (necesario en migraciones de TPV).

---

### 12. Métodos de Pago Mixtos

Un ticket puede pagarse con múltiples métodos. Modelar siempre como **colección**, nunca como campo único.

```json
"pagos": [
  { "tipo": "EFECTIVO", "importe": 30.00 },
  { "tipo": "TARJETA",  "importe": 45.00, "ref": "OP-98234" },
  { "tipo": "VALE",     "importe": 10.00, "ref": "V-0042" }
]
```

**Validación obligatoria:** `Σ pagos == total ticket`

---

### 13. IVA Desglosado por Línea

Cada línea tiene su tipo impositivo. El ticket fiscal debe mostrar desglose completo:

| Tipo | Base imponible | Cuota |
|---|---|---|
| IVA 10% (alimentos, bebidas no alcohólicas) | `13,80€` | `1,38€` |
| IVA 21% (alcohol, tabaco) | `8,50€` | `1,79€` |
| **Total IVA** | | **3,17€** |

---

### 14. Anulaciones Parciales

Una devolución parcial no edita el ticket original. Genera una **factura rectificativa** nueva.

**Tipos de rectificativa:**
- **Por diferencias:** Solo la línea afectada (ej. devolver una copa en mal estado → `-5,00€`)
- **Por sustitución:** Reemplaza la totalidad del ticket original

Ambas deben referenciar al documento original mediante su número de serie.

---

## Requisitos No Funcionales

| Requisito | Detalle |
|---|---|
| **Rendimiento** | Endpoints del front de venta deben responder en < 200ms |
| **Inmutabilidad fiscal** | Tickets pagados son inmutables por ley |
| **Numeración correlativa** | Sin huecos en series. Auditable por Hacienda |
| **Concurrencia** | Soporte multi-terminal con control de bloqueos |
| **Trazabilidad** | Cada cambio con usuario y timestamp |
| **Compatibilidad legal** | Cumplimiento de la Ley Antifraude / Verifactu (España) |

---

## Reglas Fiscales Innegociables

1. **Un ticket pagado es INMUTABLE.** Cualquier corrección requiere emitir una rectificativa. Nunca editar un ticket cerrado.
2. **Las series son CORRELATIVAS.** Los huecos en numeración son señal de fraude para Hacienda.
3. **El IVA es TRAZABLE por línea.** El total del ticket debe cuadrar con la suma de bases + cuotas por tramo.
4. **Ley Antifraude / Verifactu.** Los TPV en España deben cumplir requisitos de inalterabilidad, trazabilidad y accesibilidad de registros fiscales.

---

## Flujo de Operación

```
[Mesa LIBRE]
    │
    ▼ abrir venta
[PROFORMA — Mesa OCUPADA]
    │ agregar/quitar líneas, modificar cantidades
    ▼ pago completado
[TICKET FISCAL — Inmutable]
    │ si hay error post-cierre
    ▼
[RECTIFICATIVA — referencia al ticket original]
```

---

## Tecnologías y Conceptos Implicados

- APIs REST para TPV e integración con hostelería
- Modelado de datos relacional (FK, soft delete, snapshots)
- Control de concurrencia: bloqueo optimista / pesimista (`SELECT FOR UPDATE`)
- Fiscalidad española: IVA reducido (10%), general (21%), series correlativas
- Ley Antifraude / Verifactu (requisito legal España)
- Gestión de estados de mesa (máquina de estados)
- Gestión de pagos mixtos y devoluciones parciales

---

## Tareas a Realizar (To-Do)

- [ ] Validar que el total de cabecera = suma de líneas en cada ingesta de ticket
- [ ] Añadir `created_at`, `updated_at` y `user_id` a todas las líneas de comanda
- [ ] Implementar jerarquía de líneas con `tipo` y `parent_id`
- [ ] Añadir campo `unidad_venta` a todas las líneas de producto
- [ ] Separar entidades `MENU` y `PROMOCION` en el modelo de datos
- [ ] Diseñar endpoints diferenciados para backoffice y front de venta
- [ ] Implementar snapshot de `precio_unitario` y `porcentaje_impuesto` en cada línea de venta
- [ ] Aplicar soft delete en todas las entidades con ventas asociadas
- [ ] Implementar control de concurrencia en apertura/traslado de mesas
- [ ] Diseñar el ciclo de vida del ticket: PROFORMA → TICKET FISCAL → RECTIFICATIVA
- [ ] Implementar series de facturación configurables con reinicio anual
- [ ] Modelar `pagos` como colección con validación `Σ pagos == total`
- [ ] Incluir desglose de IVA por tipo impositivo en el ticket fiscal
- [ ] Implementar lógica de rectificativas por diferencias y por sustitución
- [ ] Verificar cumplimiento con Ley Antifraude / Verifactu

---

## Consideraciones Importantes

- **Los errores de diseño en APIs son muy costosos de corregir en producción.** Cambiar la estructura cuando hay clientes activos es órdenes de magnitud más caro que hacerlo bien desde el inicio.
- Los campos `parent_id`, `tipo_linea` y `unidad_venta` **no se pueden añadir fácilmente después** sin romper integraciones existentes.
- El front de venta no debe cargar con la complejidad del backoffice. Si la API no distingue ambos mundos, el rendimiento del TPV se ve penalizado.
- La mezcla de menú y promoción es uno de los errores más comunes y destruye completamente la trazabilidad de precios.

---

## Restricciones y Limitaciones

- **Inmutabilidad fiscal:** No está permitido editar o eliminar tickets ya cerrados. Es un requisito legal.
- **Series sin huecos:** La numeración correlativa no puede tener saltos. Cualquier hueco es una anomalía fiscal.
- **IVA por referencia (FK) está prohibido** en líneas de venta históricas: los cambios de tipo impositivo no deben afectar retroactivamente.
- **Hard delete prohibido** en entidades con ventas asociadas.

---

## Suposiciones / Información Faltante

- No se especifica el stack tecnológico concreto (framework, base de datos, lenguaje). Los patrones son agnósticos.
- No se detalla el mecanismo exacto de cumplimiento Verifactu (firma, envío a AEAT, etc.). Requiere revisión con el área legal/fiscal.
- No se especifica si la API es REST, GraphQL u otro paradigma. Se asume REST por contexto.
- No se indica si existe un sistema de colas o eventos para la sincronización multi-terminal. Podría ser necesario para garantizar consistencia en entornos de alta concurrencia.
- Se mencionan vales (`VALE`) como método de pago pero no se detalla su modelo de gestión (saldo, caducidad, etc.).