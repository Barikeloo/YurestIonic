  ---                                                                                                                                           
  Revisión del código — Fases 1-3 (Caja)       

  docker compose exec api php artisan db:seed --class=SaonaDemoSeeder                                                                                                 
                                                                                                                                              
  Resumen                                                                                                                                       
                                                                                                                                                
  - Scaffolding DDD correcto en la mayor parte: entidades con ctor privado + dddCreate/fromPersistence, VOs con create(), repos por interfaz,   
  casos de uso orquestando dominio.                                                                                                             
  - Pero hay 5 bugs críticos que rompen el runtime (el sistema no puede completar el ciclo abrir→cobrar→cerrar→Z sin crashear).                 
  - Multi-tenancy rota en todos los modelos Eloquent de Cash.                                                                                 
  - Varios errores de corrección financiera en el Z-Report (doble conteo, abs(), numeración global).                                          
  - Violaciones de AGENTS.md en varias VO y en acoplamiento entre dominios.  
                                                                             
  ---                                                                        
  🔴 BLOQUEANTES — rompen el runtime                                                                                                            
                                                                  
  B1. fromPersistence con argumentos posicionales desplazados en los 4 repos de Cash                                                            
                                                                                                                                                
  Patrón común en EloquentCashSessionRepository::toDomain, EloquentCashMovementRepository::toDomain, EloquentSalePaymentRepository::toDomain y  
  EloquentTipRepository::toDomain: se olvidan de pasar $model->uuid como 3º argumento (el campo $uuid del entity), y todo lo demás queda corrido
   una posición.                                                                                                                                
                                                                                                                                                
  Ejemplo en EloquentCashSessionRepository.php:102-122: la entity espera (id, restaurantId, uuid, deviceId, openedByUserId, ...) pero el repo   
  pasa (uuid, restaurantUuid, device_id, openedByUserUuid, closedByUserUuid, ...) — device_id ("test-device-013") termina en el slot de uuid,   
  lanzando Uuid::create(...) InvalidArgumentException.                                                                                        
                                                                                                                                                
  Impacto: cualquier lectura desde BD (findByUuid, findActiveByDeviceId, findByCashSessionId, etc.) crashea nada más encontrar una fila. Esto   
  rompe CloseCashSession, CancelClosingCashSession, GenerateZReport, CreateSale (porque llama findActiveByDeviceId) y RegisterCashMovement.     
                                                                                                                                                
  En CashMovement el error es aún más ruidoso: MovementReasonCode::create($model->amount_cents) recibe un int donde espera string → TypeError.
                                                                                                                                                
  Arreglo: pasar el uuid dos veces (como hacen EloquentOrderRepository y EloquentSaleRepository). Ya que semánticamente id === uuid en este
  codebase, se puede simplificar eliminando el parámetro $uuid de fromPersistence.                                                              
                                                                                                                    
  B2. ZReportRepository::toDomain usa ZReport::generate() en lugar de fromPersistence                                                           
                                              
  EloquentZReportRepository.php:82-98 llama a ZReport::generate(...), que internamente hace Uuid::generate() y recalcula el hash. Cada lectura  
  devuelve un Z-Report con id y hash distintos al que se guardó. El report_hash persistido es irrecuperable; no sirve para verificar integridad.
                                                                                                                                              
  Arreglo: ZReport necesita un fromPersistence(id, cashSessionId, reportNumber, reportHash, totalSales, ..., generatedAt) como el resto de      
  entities. Y el repo debe usarlo.                                                                                  
                                                                                                                                                
  B3. Hash del Z no determinista                                                                                                                
                                                                                                                                              
  ZReport::calculateHash() (ZReport.php:108) incluye DomainDateTime::now()->format(...) como parte del string hashed. Aunque fromPersistence se 
  arregle, recalcular el hash a partir de los datos guardados nunca dará el mismo resultado porque now() ha cambiado.
                                                                                                                                                
  Arreglo: usar el generatedAt del entity (que debería ser inmutable y venir de BD), no now(). O mejor: no incluir el timestamp en lo hasheado y
   que el generated_at sea un campo aparte inmutable.                                                                                         
                                                                                                                                                
  Además, para una cadena fiscal real, el hash debería incluir el hash del Z anterior (cadena tipo blockchain), como dice el doc de diseño. No  
  está.                                                                                                                                       
                                                                                                                                                
  B4. GenerateZReport compara PaymentMethod como si fuera string                                                    
                                                                                                                                                
  GenerateZReport.php:45:                                                                                                                       
  switch ($payment->method()) {                                                                                                               
      case 'cash': ...                                                                                                                          
      case 'card': ...                                                                                                                          
  }                                                                                                                                           
  $payment->method() devuelve un PaymentMethod VO (objeto), no string. Sin __toString() ni operador ==, ningún case hace match: todo va a       
  default. Resultado: totalCash y totalCard siempre valen 0 en el Z, todo el dinero aparece en totalOther.                                      
                                                                                                                                              
  GetCashSessionSummary.php:51 ya lo hace bien: switch ($payment->method()->value()). Replicar ese patrón en GenerateZReport.                   
                                                                                                                    
  B5. cash_session_id nunca se guarda en sales                                                                                                  
                                                                                                                    
  Cadena rota:                                                                                                                                
  1. Migración 2026_04_22_000600_extend_sales_for_caja.php añade la columna. ✓
  2. EloquentSale.fillable no incluye cash_session_id (EloquentSale.php:21-35). ✗
  3. EloquentSaleRepository::save() no escribe cash_session_id (EloquentSaleRepository.php:34-49). ✗                                            
  4. La entidad Sale no tiene campo cashSessionId. ✗                                                
  5. CreateSale lee la sesión activa pero solo la usa para vincular SalePayment. No la pone en Sale.                                            
                                                                                                                                                
  Consecuencia: SaleRepository::findByCashSessionId() siempre devuelve array vacío → GenerateZReport.salesCount = 0 siempre.                    
                                                                                                                                                
  ---                                                                                                                                         
  🟠 Correctitud financiera                                                                                                                     
                                                                  
  F1. Discrepancia con abs() pierde el signo                                                                                                    
                                                                                                                                                
  GenerateZReport.php:101: $discrepancyCents = abs(...).                                                                                        
                                                                                                                                                
  Faltante (−50€) y sobrante (+50€) son contablemente distintos. Abs los colapsa. Además Money::create() rechaza negativos, así que aunque      
  quitases el abs(), seguirías sin poder representar un faltante.                                                                               
                                                                                                                                                
  F2. Money no admite negativos                                                                                                               
                                                                                                                                              
  Money.php:11-13: throw new \InvalidArgumentException('Money cannot be negative'); y subtract() lanza si result < 0.                           
                                                                                                                                                
  Rompe: notas de abono (obligatoriamente negativas), descuadre negativo, devoluciones, tips source=card_added que se restan del efectivo       
  recibido.                                                                                                                                     
                                                                                                                                              
  Arreglo: admitir enteros con signo, o introducir SignedMoney/MoneyDelta para valores con signo y mantener Money para las cantidades positivas 
  (precios).                                                                                                        
                                                                                                                                              
  F3. Fórmula del teórico con doble conteo                                                                                                      
                                              
  GenerateZReport.php:94-98:                                                                                                                    
  $expectedFinal = $cashSession->initialAmount()                                                                    
      ->add($totalCash)                                                                                                                         
      ->add($cashIn)                                                                                                                            
      ->subtract($cashOut)                                                                                                                    
      ->add($totalTips);                                                                                                                        
  $totalTips incluye las de source=card_added (que están en el TPV bancario, no en el cajón). Sumarlas al efectivo teórico es incorrecto. Las   
  únicas que afectan al efectivo son las cash_declared, y además esas ya se suman si se materializaron como CashMovement(type=in,             
  reason=tip_declared).                                                                                                                         
                                                                                                                    
  Fórmula correcta del diseño:                                                                                                                  
  teorico_efectivo = initial                                                                                                                    
                   + Σ SalePayment(method=cash)                                                                                               
                   + Σ CashMovement(type=in)                                                                                                    
                   - Σ CashMovement(type=out)                                                                       
                                                                                                                                                
  F4. expectedAmount y discrepancy llegan desde el cliente y luego se recalculan                                                                
                                                                                                                                              
  CloseCashSession los recibe como parámetros del request y los persiste en cash_sessions. Después, GenerateZReport los recalcula con fórmula   
  distinta y los escribe en z_reports. Dos valores pueden diferir, y el del request es falsificable por el cliente. Debería ser el servidor     
  quien calcule; el cliente solo aporta el final_amount_cents (lo que ha contado).                                                            
                                                                                                                                                
  F5. nextTicketNumber sin scope de tenant                                                                                                      
                                                                                                                                                
  EloquentSaleRepository.php:113-118:                                                                                                           
  public function nextTicketNumber(string $restaurantId): int                                                                                   
  {                                                                                                                                             
      $max = $this->model->newQuery()->max('ticket_number');                                                                                    
      ...                                                                                                                                       
  }                                                                                                                                           
  No filtra por restaurant_id. Depende de HasTenantScope (global scope), que solo se aplica si TenantContext está poblado — peligroso en        
  seeders, CLI o contextos sin middleware. Además $restaurantId se ignora: ni siquiera se usa.                                          
                                                                                                                                                
  Mismo problema potencial en nextReportNumber de ZReport, aunque éste sí filtra explícitamente por restaurant_id.  
                                                                                                                                                
  F6. cash_sessions.z_report_number y z_report_hash se quedan en NULL                                               
                                                                                                                                              
  CashSession.close() los acepta opcionales; CloseCashSession los pasa como null; GenerateZReport se ejecuta después y nunca escribe de vuelta  
  en la sesión. La salida del doc lo confirma: "z_report_number": 0, "z_report_hash": "".
                                                                                                                                                
  Arreglo: invertir el flujo. GenerateZReport se invoca primero (en closing), devuelve número y hash; CloseCashSession los recibe y los persiste
   en la CashSession. O añadir un método CashSession::attachZReport(number, hash) que se llame tras generar.                                  
                                                                                                                                                
  ---                                                                                                                                           
  🟡 DDD / AGENTS.md — violaciones arquitectónicas                                                                                              
                                                                                                                                                
  D1. VOs de Cash en Shared/Domain/ValueObject                                                                                                
                                                                                                                                                
  CashSessionStatus, MovementType, MovementReasonCode, PaymentMethod, DocumentType están en App\Shared\Domain\ValueObject. AGENTS.md 3.2:       
  "Reutilizables: en Shared/Domain/ValueObject/ si aplican a varios dominios". Ninguno de estos aplica a múltiples dominios. Deberían ir en   
  App\Cash\Domain\ValueObject (o App\Sale\Domain\ValueObject para PaymentMethod/DocumentType, que son semántica de venta).                      
                                                                                                                    
  Solo Money y quizá DocumentType son dudosos de dominio — todos los demás son claramente de Cash.                                            
                                                                                                                                                
  D2. Sale.status como string primitivo, no VO                               
                                                                                                                                                
  Sale.php:27: private string $status = 'completed';. Las comparaciones en cancel() y isCancelled() son contra literals. Debería ser un         
  SaleStatus VO. Inconsistencia añadida: el default del constructor es 'completed' pero dddCreate setea 'closed'. Nunca se usa 'completed' pero 
  queda como tripwire.                                                                                                                          
                                                                                                                                              
  D3. Tip.source como string primitivo                                                                                                          
                                                              
  Tip.php:22: private readonly string $source; con in_array en el ctor. Debería ser TipSource VO (cardAdded(), cashDeclared(),                  
  isCardAdded()...).                                                                                                
                                                                                                                                                
  D4. Acoplamiento cruzado Sale → Cash                                                                              
                                                                                                                                                
  Sale/Application/CreateSale/CreateSale.php importa:                                                               
  - App\Cash\Domain\Interfaces\CashSessionRepositoryInterface                                                                                 
  - App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface                
  - App\Cash\Domain\Entity\SalePayment  ← inline fully-qualified \App\Cash\Domain\Entity\SalePayment::dddCreate(...) en l.75                    
                                                                                                                            
  AGENTS.md §2.1: "Dominio autocontenido. No depender de otros dominios para la lógica de negocio".                                             
                                                                                                                                                
  Decisión a tomar: ¿SalePayment pertenece a Sale (es parte del agregado venta) o a Cash (es movimiento de caja)? Conceptualmente es ambos, pero
   es hijo del Sale (no existe SalePayment sin un Sale). Lo movería a Sale/Domain/Entity/SalePayment.php y dejaría a Cash ignorarlo. Cash solo  
  consulta agregados vía queries de lectura (que podrían vivir en un servicio de lectura).                          
                                                                                                                                                
  D5. ZReport entity sin restaurantId                                                                               
                                                                                                                                                
  ZReport.php no guarda restaurantId. Se pierde la trazabilidad tenant a nivel dominio. La migration lo tiene pero el domain no.                
                                                                                                                                                
  D6. ZReport sin fromPersistence                                                                                                               
                                                                                                                                                
  Solo tiene generate(). No hay forma DDD-correcta de rehidratar un Z persistido. Requiere añadir fromPersistence(Uuid $id, Uuid $restaurantId, 
  Uuid $cashSessionId, int $reportNumber, string $reportHash, Money $totalSales, ..., DomainDateTime $generatedAt).                           
                                                                                                                                                
  D7. Tipos primitivos en firmas del dominio                      
                                                                                                                                                
  - ZReportRepositoryInterface::nextReportNumber(string $restaurantId): int — debe ser Uuid $restaurantId.          
  - SaleRepositoryInterface::nextTicketNumber(string $restaurantId): int — igual.                                                               
                                                                                                                    
  AGENTS.md §2.6: "Tipar métodos con entidades del dominio y VOs, nunca con arrays planos ni tipos genéricos de framework".                     
                                                                             
  D8. Referencias fully-qualified inline                                                                                                        
                                                                                                                                                
  - CreateSale.php:75: \App\Cash\Domain\Entity\SalePayment::dddCreate(...).                                                                   
  - OpenCashSession.php:31: \App\Cash\Domain\Entity\CashSession::dddCreate(...).                                                                
  - EloquentSaleRepository.php:93: \App\Cash\Infrastructure\Persistence\Models\EloquentCashSession::query().        
  - EloquentZReportRepository.php:22,26,61: ídem.                                                                                               
                                                                                                                                                
  Todos deberían ser use imports. AGENTS.md §6: "Imports (use) explícitos para todas las clases que no estén en el espacio de nombres global".  
                                                                                                                                                
  ---                                                                                                                                         
  🟡 Multi-tenancy y seguridad                                                                                                                  
                                                                                                                    
  T1. Modelos de Cash sin HasTenantScope                                                                                                        
                                                                                                                                                
  EloquentCashSession, EloquentCashMovement, EloquentSalePayment, EloquentTip, EloquentZReport: ninguno usa el trait HasTenantScope. El global  
  scope que filtra por restaurant_id no se aplica, permitiendo lecturas cross-tenant si el middleware no está activo o si se usa desde          
  seeders/CLI.                                                                                                                                  
                                                                                                                                                
  Esto + B1 hace que findByUuid busque globalmente. Si mañana dos restaurantes usan el mismo device_id (improbable pero posible con UUIDs     
  generados cliente), el caso queda sin cubrir.                                                                                                 
                                                                             
  T2. Modelos sin SoftDeletes                                                                                                                   
                                                                                                                                                
  Las migraciones tienen softDeletes() pero los modelos no usan el trait. EloquentCashSession, EloquentCashMovement, EloquentSalePayment,       
  EloquentTip hacen delete() duro. EloquentZReport sí tiene SoftDeletes — inconsistencia.                                                       
                                                                                                                                              
  T3. Migración z_reports rompe el patrón de shard key                                                                                          
                                              
  2026_04_22_103832_create_z_reports_table.php usa ->foreign('restaurant_id')->references('id')->on('restaurants') y                            
  ->foreign('cash_session_id')->references('id'). El resto del esquema usa composite (restaurant_id, id) desde                                  
  2026_03_25_000000_enforce_composite_shard_keys.php. Romper el patrón:                                                                       
  - impide cascadas coherentes cuando se borra un tenant,                                                                                       
  - deja el riesgo de que una cash_session de tenant A se enlace a un z_report de tenant B.                         
                                                                                                                                                
  También faltan: unique(['restaurant_id', 'id']) para que otras tablas puedan referenciar, y unique(['restaurant_id', 'report_number']) para   
  garantizar numeración correlativa única por tenant.                                                                                         
                                                                                                                                                
  T4. restaurant_id llega por request body                                                                          
                                                                                                                                                
  OpenCashSessionController, RegisterCashMovementController, CreateSale, etc. toman restaurant_id del body validado. Eso es forjable. Debería
  venir del TenantContext resuelto por middleware (como en los demás endpoints TPV bajo ResolveTenantContext). El middleware está, pero los use 
  cases no lo consumen.                                                      
                                                                                                                                                
  ---                                                                                                                                           
  🟡 Lógica / completitud                                                                                                                       
                                                                                                                                                
  L1. Estado abandoned declarado pero sin transición                                                                                          
                                                                                                                                                
  El enum CashSessionStatus incluye abandoned, pero ningún use case lo setea. ForceCloseCashSession pone closed, perdiendo la distinción entre
  "cerrado normalmente" y "cerrado a la fuerza por turno huérfano". El doc de diseño lo contemplaba.                                            
                                                                                                                    
  L2. ForceCloseCashSession sin check de rol                                                                                                  
                                                              
  El doc dice: solo admin. En el código, cualquiera puede llamar al endpoint. No hay middleware RequireAdminSession.
                                              
  L3. GenerateZReport sin precondición de estado                                                                                                
                                                                                                                                                
  Se puede ejecutar sobre una sesión open y generar un "Z oficial" con totales parciales. Debería exigir closing o closed.                      
                                                                                                                                                
  L4. CreateSale no cierra la Order                                                                                                           
                                                                                                                                              
  El caso de uso crea Sale + SalePayments pero no actualiza Order.status = 'invoiced'. El flujo actual de mesas esperaba esta transición tras   
  cobrar.                                                         
                                                                                                                                                
  L5. CloseCashSession sin precondición de ventas pendientes                                                        
                                                                                                                                                
  El doc: "si hay Sales con status=pending → 409". No hay check.                                                    
                                                                                                                                                
  L6. Sin transacciones                                                                                             
                                                                                                                                                
  CreateSale crea Sale + N SalePayments. Si falla a mitad, queda el Sale sin pagos completos. Igual en CloseCashSession → close() +
  GenerateZReport + save Z sin transaction. Hay TransactionManagerInterface disponible en Shared, pero no se usa.                               
                                                                                                                                                
  L7. Tip sin caso de uso                                                                                                                     
                                                                                                                                                
  La entidad, VO, repo y migración están, pero no hay ningún use case (ni en Fase 3) que cree Tip. Código muerto hasta que Fase 7 lo conecte. OK
   si es deliberado; anotarlo.                                                                                                                  
                                                                                                                    
  L8. EloquentSale.fillable incompleto                                                                                                          
                                              
  Faltan cash_session_id, cancelled_at, parent_sale_id, document_type, customer_fiscal_data. Incluso si el código quisiera setearlos, mass      
  assignment los dropearía silenciosamente.                                                                         
                                                                                                                                              
  ---                                                                                                                                           
  🟢 Lo que está bien                 
                                                                                                                                                
  - Patrón dddCreate/fromPersistence aplicado consistentemente en todas las entidades nuevas.                                                   
  - VOs con constructor privado, validación en create(), métodos fábrica semánticos (::open(), ::closing(), ::in()…).                         
  - Casos de uso puros, reciben interfaces por constructor.                                                                                     
  - Controllers como adaptadores finos.                                                                             
  - Interfaces del dominio en Domain/Interfaces/ como pide AGENTS.md.                                                                           
  - Rutas registradas bajo ResolveTenantContext.                                                                    
  - Migraciones de Fase 1 (las iniciales) siguen el patrón de composite FK correctamente. Solo la de z_reports rompe el patrón.               
  - GetCashSessionSummary está bien escrito (usa $payment->method()->value() en el switch, usa $movement->type()->isIn()).                      
                                                                             
  ---                                                                                                                                           
  Plan de arreglo sugerido — por prioridad                                                                                                      
                                                                                                                                                
  Bloque A (antes de seguir a Fase 4): arreglar bugs runtime.                                                                                   
  1. Corregir los 4 toDomain de Cash con los argumentos desplazados (B1).                                                                       
  2. Añadir ZReport::fromPersistence y usarlo en el repo (B2).                                                                                  
  3. Determinar generatedAt en el fromPersistence y quitar now() del hash (B3).                                                                 
  4. GenerateZReport → ->method()->value() en el switch (B4).                                                                                   
  5. Añadir cashSessionId al entity Sale, fillables y save() (B5).                                                                              
                                                                                                                                                
  Bloque B: correctitud financiera.                                                                                                             
  6. Permitir Money con signo (o introducir SignedMoney) (F2).                                                                                
  7. Quitar abs() del discrepancy; corregir fórmula del teórico (F1, F3).                                                                       
  8. Invertir orden Close→Generate para que el Z quede colgado en la sesión (F6).                                                               
  9. Scope de tenant en nextTicketNumber y nextReportNumber; tipar con Uuid (F5, D7).                                                           
  10. Calcular expected/discrepancy en servidor, quitarlo del request (F4).                                                                     
                                                                                                                                                
  Bloque C: DDD y multi-tenancy.                                                                                                                
  11. Mover VOs de Cash desde Shared a Cash/Domain/ValueObject (D1).                                                                            
  12. Crear SaleStatus VO y usarlo (D2).                                                                                                        
  13. Crear TipSource VO (D3).                                                                                                                  
  14. Añadir HasTenantScope + SoftDeletes a los 5 modelos de Cash (T1, T2).                                                                     
  15. Reescribir migración z_reports con composite FKs y uniques (T3).                                                                          
  16. Resolver restaurant_id desde TenantContext en use cases, no del body (T4).                                                                
  17. Decidir y reubicar SalePayment (mover a Sale o justificar quedarlo en Cash); limpiar imports fully-qualified (D4, D8).                    
                                                                                                                                                
  Bloque D: lógica pendiente.                                                                                                                   
  18. Estado abandoned + transition en ForceClose (L1).                                                                                         
  19. Middleware de rol para ForceClose (L2).                                                                                                   
  20. Pre-checks de estado en GenerateZReport y CloseCashSession (L3, L5).                                                                      
  21. Cerrar la Order al crear Sale (L4).                                                                                                       
  22. Envolver use cases críticos en transacciones (L6).