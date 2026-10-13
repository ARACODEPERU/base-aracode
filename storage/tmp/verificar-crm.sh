#!/usr/bin/env bash
# Verifica por HTTP cada pantalla del módulo CRM con la sesión del administrador.
# Uso: bash storage/tmp/verificar-crm.sh
set -uo pipefail

BASE="${BASE:-http://base-aracode.test}"
EMAIL="${ADMIN_EMAIL:-admin@gmail.com}"
PASS="${ADMIN_PASSWORD:-12345678}"
TMP="storage/tmp/crm-verif"
JAR="$TMP/cookies.txt"
rm -rf "$TMP" && mkdir -p "$TMP"

url_decode() { php -r 'echo urldecode($argv[1]);' "$1"; }

# 1) login
curl -s -c "$JAR" -o "$TMP/login.html" "$BASE/login" --max-time 30
TOKEN=$(grep -i 'XSRF-TOKEN' "$JAR" | awk '{print $7}' | tail -1)
if [ -z "$TOKEN" ]; then echo "ERROR: no se obtuvo cookie XSRF-TOKEN"; exit 1; fi
TOKEN=$(url_decode "$TOKEN")

# La cookie XSRF-TOKEN va cifrada: el header X-XSRF-TOKEN es el que el servidor descifra.
CODE=$(curl -s -b "$JAR" -c "$JAR" -o "$TMP/login-post.html" -w '%{http_code}' \
  -X POST "$BASE/login" --max-time 30 \
  -H "X-XSRF-TOKEN: $TOKEN" \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  --data-urlencode "email=$EMAIL" \
  --data-urlencode "password=$PASS")
echo "login: HTTP $CODE (destino: $(grep -o 'http-equiv="refresh"[^>]*' "$TMP/login-post.html" | head -1))"

# token csrf fresco para los POST
curl -s -b "$JAR" -c "$JAR" -o "$TMP/panel.html" "$BASE/dashboard" --max-time 30
TOKEN=$(grep -i 'XSRF-TOKEN' "$JAR" | awk '{print $7}' | tail -1 | xargs -I{} php -r 'echo urldecode($argv[1]);' {})
echo "sesión activa: $(grep -c 'data-page' "$TMP/panel.html") componente(s) inertia"

probar_get() {
  local ruta="$1"
  local salida="$TMP/$(echo "$ruta" | tr '/' '_').html"
  local code
  code=$(curl -s -b "$JAR" -c "$JAR" -o "$salida" -w '%{http_code}' "$BASE$ruta" --max-time 30)
  local comp
  comp=$(grep -o 'data-page="[^"]\{0,120\}' "$salida" | head -1 | sed 's/data-page="//' | php -r '$s=html_entity_decode(stream_get_contents(STDIN)); echo substr($s,0,110);')
  printf '%-38s %-4s %s\n' "$ruta" "$code" "$comp"
}

probar_post() {
  local ruta="$1"
  local code
  code=$(curl -s -b "$JAR" -c "$JAR" -o "$TMP/post.json" -w '%{http_code}' \
    -X POST "$BASE$ruta" --max-time 30 \
    -H "X-XSRF-TOKEN: $TOKEN" -H 'X-Requested-With: XMLHttpRequest' \
    -H 'Accept: application/json')
  printf 'POST %-33s %-4s %s\n' "$ruta" "$code" "$(head -c 200 "$TMP/post.json" | tr -d '\n')"
}

echo
echo "== PANTALLAS DEL MENÚ DEL CRM =="
probar_get /crm/dashboard
probar_get /crm/companies/list
probar_get /crm/contacts/list
probar_get /crm/chat/dashboard
probar_get /crm/chat/asistentes
probar_get /crm/mailbox/dashboard
probar_get /crm/common-questions
probar_get /crm/complaints-book
probar_get /crm/new_catchments

echo
echo "== OTRAS PANTALLAS DEL CRM (no están en el menú) =="
probar_get /crm/chatbot/index
probar_get /crm/application-ai-prompt
probar_get /crm/contacts/mass/mailing
probar_get /crm/companies/createecho
 echo "== LLAMADAS DE FONDO =="
probar_get /crm/contacts/docents/chat
probar_post /person/birthdays
probar_get /crm/chat/notifications
probar_get /crm/chat/contacts
