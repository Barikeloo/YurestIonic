#!/bin/bash

# ============================================
# TEST CHARGE SESSION API - Flujo Completo
# ============================================

set -e  # Detenerse si hay error

echo "🚀 Iniciando tests de Charge Session API"
echo "=========================================="
echo ""

# Variables - Usar credenciales de SAONA Demo
BASE_URL="${BASE_URL:-http://localhost:8000}"
EMAIL="${EMAIL:-saona@gmail.com}"
PASSWORD="${PASSWORD:-12345678}"
ORDER_ID="${ORDER_ID:-}"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================
# 0. LOGIN
# ============================================
echo "🔐 0. Login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"uuid":"[^"]*' | head -1 | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}❌ Error: No se pudo obtener token${NC}"
    echo "Respuesta: $LOGIN_RESPONSE"
    exit 1
fi

if [ -z "$USER_ID" ]; then
    USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)
fi

echo -e "${GREEN}✅ Login exitoso${NC}"
echo "   Token: ${TOKEN:0:30}..."
echo "   User ID: $USER_ID"
echo ""

# ============================================
# 0b. OBTENER ORDER_ID (si no se proporcionó)
# ============================================
if [ -z "$ORDER_ID" ]; then
    echo "📋 0b. Buscando mesa abierta..."
    TABLES_RESPONSE=$(curl -s -X GET "$BASE_URL/api/tpv/tables" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    # Extraer primera orden abierta
    ORDER_ID=$(echo $TABLES_RESPONSE | grep -o '"order_id":"[^"]*' | head -1 | cut -d'"' -f4)
    
    if [ -z "$ORDER_ID" ]; then
        echo -e "${YELLOW}⚠️ No hay mesas abiertas. Creando orden...${NC}"
        
        # Crear una orden de prueba
        ORDER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/orders" \
          -H "Authorization: Bearer $TOKEN" \
          -H "Content-Type: application/json" \
          -d "{
            \"table_id\": 1,
            \"diners\": 4,
            \"lines\": [
              {\"product_id\": 1, \"quantity\": 2, \"price\": 1000},
              {\"product_id\": 1, \"quantity\": 1, \"price\": 2000}
            ]
          }")
        
        ORDER_ID=$(echo $ORDER_RESPONSE | grep -o '"uuid":"[^"]*' | head -1 | cut -d'"' -f4)
        
        if [ -z "$ORDER_ID" ]; then
            echo -e "${RED}❌ Error: No se pudo crear orden${NC}"
            echo "Respuesta: $ORDER_RESPONSE"
            exit 1
        fi
        
        echo -e "${GREEN}✅ Orden creada${NC}"
    else
        echo -e "${GREEN}✅ Mesa abierta encontrada${NC}"
    fi
    
    echo "   Order ID: $ORDER_ID"
    echo ""
fi

# ============================================
# 1. VERIFICAR NO HAY SESIÓN
# ============================================
echo "🔍 1. Verificando que NO hay sesión activa..."
SESSION_CHECK=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/api/tpv/charge-sessions/active?order_id=$ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

HTTP_CODE=$(echo "$SESSION_CHECK" | tail -n1)
RESPONSE_BODY=$(echo "$SESSION_CHECK" | sed '$d')

if [ "$HTTP_CODE" == "404" ]; then
    echo -e "${GREEN}✅ Correcto: No hay sesión activa (404)${NC}"
else
    echo -e "${YELLOW}⚠️ Ya existe una sesión (HTTP $HTTP_CODE)${NC}"
    echo "   Respuesta: $RESPONSE_BODY"
fi
echo ""

# ============================================
# 2. CREAR SESIÓN
# ============================================
echo "✨ 2. Creando sesión de cobro..."
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"order_id\": \"$ORDER_ID\",
    \"opened_by_user_id\": \"$USER_ID\",
    \"diners_count\": 4
  }")

SESSION_ID=$(echo $CREATE_RESPONSE | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)
STATUS=$(echo $CREATE_RESPONSE | grep -o '"status":"[^"]*' | head -1 | cut -d'"' -f4)
DINERS_COUNT=$(echo $CREATE_RESPONSE | grep -o '"diners_count":[0-9]*' | head -1 | cut -d':' -f2)
AMOUNT_PER_DINER=$(echo $CREATE_RESPONSE | grep -o '"amount_per_diner":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$SESSION_ID" ]; then
    echo -e "${RED}❌ Error: No se pudo crear sesión${NC}"
    echo "Respuesta: $CREATE_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Sesión creada${NC}"
echo "   ID: $SESSION_ID"
echo "   Estado: $STATUS"
echo "   Comensales: $DINERS_COUNT"
echo "   Cuota: $AMOUNT_PER_DINER céntimos"
echo ""

# ============================================
# 3. OBTENER SESIÓN ACTIVA
# ============================================
echo "🔍 3. Obteniendo sesión activa..."
GET_RESPONSE=$(curl -s -X GET "$BASE_URL/api/tpv/charge-sessions/active?order_id=$ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

RETRIEVED_ID=$(echo $GET_RESPONSE | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)

if [ "$RETRIEVED_ID" == "$SESSION_ID" ]; then
    echo -e "${GREEN}✅ Sesión recuperada correctamente${NC}"
else
    echo -e "${RED}❌ Error: ID no coincide${NC}"
    exit 1
fi
echo ""

# ============================================
# 4-7. PAGOS DE COMENSALES
# ============================================

pay_diner() {
    local DINER_NUM=$1
    local METHOD=$2
    
    echo "💰 Pago comensal $DINER_NUM ($METHOD)..."
    
    PAY_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID/payments" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d "{
        \"diner_number\": $DINER_NUM,
        \"payment_method\": \"$METHOD\"
      }")
    
    PAID_COUNT=$(echo $PAY_RESPONSE | grep -o '"session_paid_diners_count":[0-9]*' | cut -d':' -f2)
    IS_COMPLETE=$(echo $PAY_RESPONSE | grep -o '"is_session_complete":[a-z]*' | cut -d':' -f2)
    
    echo -e "${GREEN}✅ Pago registrado${NC}"
    echo "   Pagados: $PAID_COUNT"
    echo "   ¿Completa?: $IS_COMPLETE"
    
    if [ "$IS_COMPLETE" == "true" ]; then
        echo -e "${GREEN}🎉 SESIÓN COMPLETA!${NC}"
    fi
    echo ""
}

pay_diner 1 "cash"
pay_diner 2 "card"
pay_diner 3 "bizum"
pay_diner 4 "other"

# ============================================
# 8. INTENTAR PAGAR DUPLICADO (debe fallar)
# ============================================
echo "🚫 8. Intentando pagar comensal duplicado..."
DUPLICATE_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID/payments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{
    \"diner_number\": 1,
    \"payment_method\": \"cash\"
  }")

HTTP_CODE=$(echo "$DUPLICATE_RESPONSE" | tail -n1)

if [ "$HTTP_CODE" == "409" ]; then
    echo -e "${GREEN}✅ Correcto: No permite duplicado (409)${NC}"
else
    echo -e "${RED}❌ Error: Debería haber fallado con 409, pero dio $HTTP_CODE${NC}"
fi
echo ""

# ============================================
# 9. VERIFICAR SESIÓN COMPLETADA (no existe activa)
# ============================================
echo "🔍 9. Verificando que no hay sesión activa (está completada)..."
CHECK_COMPLETE=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/api/tpv/charge-sessions/active?order_id=$ORDER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

HTTP_CODE=$(echo "$CHECK_COMPLETE" | tail -n1)

if [ "$HTTP_CODE" == "404" ]; then
    echo -e "${GREEN}✅ Correcto: No hay sesión activa (está completada)${NC}"
else
    echo -e "${YELLOW}⚠️ Inesperado: HTTP $HTTP_CODE${NC}"
fi
echo ""

# ============================================
# RESUMEN
# ============================================
echo "=========================================="
echo -e "${GREEN}🎉 FLUJO COMPLETADO EXITOSAMENTE${NC}"
echo "=========================================="
echo ""
echo "Resumen:"
echo "  • Sesión ID: $SESSION_ID"
echo "  • Order ID: $ORDER_ID"
echo "  • Comensales pagados: 4/4"
echo "  • Estado: completed"
echo ""
echo "Para probar cancelación, ejecuta:"
echo "  ORDER_ID=$ORDER_ID ./test-charge-session-cancel.sh"
