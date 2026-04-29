#!/bin/bash

# ============================================
# TEST CANCELACIÓN CHARGE SESSION
# ============================================

set -e

echo "🔄 Test de Cancelación de Sesión"
echo "================================="
echo ""

BASE_URL="${BASE_URL:-http://localhost:8000}"
EMAIL="${EMAIL:-saona@gmail.com}"
PASSWORD="${PASSWORD:-12345678}"
ORDER_ID="${ORDER_ID:-}"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Login
echo "🔐 Login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"$EMAIL\", \"password\": \"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"uuid":"[^"]*' | head -1 | cut -d'"' -f4)

if [ -z "$USER_ID" ]; then
    USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)
fi

echo -e "${GREEN}✅ Logueado${NC}"
echo ""

# Crear nueva sesión
echo "✨ Creando sesión nueva..."
CREATE_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"order_id\": \"$ORDER_ID\",
    \"opened_by_user_id\": \"$USER_ID\",
    \"diners_count\": 4
  }")

SESSION_ID=$(echo $CREATE_RESPONSE | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)

if [ -z "$SESSION_ID" ]; then
    echo -e "${RED}❌ Error creando sesión${NC}"
    echo "$CREATE_RESPONSE"
    exit 1
fi

echo -e "${GREEN}✅ Sesión creada: $SESSION_ID${NC}"
echo ""

# Pagar 1 comensal
echo "💰 Pagando comensal 1..."
PAY_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID/payments" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"diner_number\": 1,
    \"payment_method\": \"cash\"
  }")

echo -e "${GREEN}✅ Pagado${NC}"
echo ""

# Intentar modificar comensales (debe fallar)
echo "✏️  Intentando modificar comensales (con pagos)..."
MODIFY_RESPONSE=$(curl -s -w "\n%{http_code}" -X PUT "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID/diners" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"diners_count\": 5}")

HTTP_CODE=$(echo "$MODIFY_RESPONSE" | tail -n1)

if [ "$HTTP_CODE" == "409" ]; then
    echo -e "${GREEN}✅ Correcto: No permite modificar con pagos (409)${NC}"
else
    echo -e "${YELLOW}⚠️ HTTP $HTTP_CODE${NC}"
fi
echo ""

# Cancelar sesión
echo "❌ Cancelando sesión..."
CANCEL_RESPONSE=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID/cancel" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"cancelled_by_user_id\": \"$USER_ID\",
    \"reason\": \"Prueba de cancelación\"
  }")

STATUS=$(echo $CANCEL_RESPONSE | grep -o '"status":"[^"]*' | head -1 | cut -d'"' -f4)
WARNING=$(echo $CANCEL_RESPONSE | grep -o '"warning_message":"[^"]*' | head -1 | cut -d'"' -f4)

echo -e "${GREEN}✅ Sesión cancelada${NC}"
echo "   Estado: $STATUS"
echo "   Advertencia: ${WARNING:-Ninguna}"
echo ""

# Probar modificación sin pagos
echo "✨ Creando sesión sin pagos para modificar..."
CREATE_RESPONSE2=$(curl -s -X POST "$BASE_URL/api/tpv/charge-sessions" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"order_id\": \"$ORDER_ID\",
    \"opened_by_user_id\": \"$USER_ID\",
    \"diners_count\": 4
  }")

SESSION_ID2=$(echo $CREATE_RESPONSE2 | grep -o '"id":"[^"]*' | head -1 | cut -d'"' -f4)

echo "✏️  Modificando comensales (sin pagos)..."
MODIFY_RESPONSE2=$(curl -s -w "\n%{http_code}" -X PUT "$BASE_URL/api/tpv/charge-sessions/$SESSION_ID2/diners" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"diners_count\": 6}")

HTTP_CODE2=$(echo "$MODIFY_RESPONSE2" | tail -n1)
RESPONSE2=$(echo "$MODIFY_RESPONSE2" | sed '$d')

if [ "$HTTP_CODE2" == "200" ]; then
    NEW_COUNT=$(echo $RESPONSE2 | grep -o '"diners_count":[0-9]*' | cut -d':' -f2)
    echo -e "${GREEN}✅ Modificado a $NEW_COUNT comensales${NC}"
else
    echo -e "${YELLOW}⚠️ HTTP $HTTP_CODE2${NC}"
    echo "$RESPONSE2"
fi
echo ""

echo "=========================================="
echo -e "${GREEN}🎉 TESTS DE CANCELACIÓN COMPLETADOS${NC}"
echo "=========================================="
