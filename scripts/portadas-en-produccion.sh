#!/usr/bin/env bash
#
# Diagnóstico de las portadas de la raíz (SALUD, Dental) en el servidor.
#
# Las portadas se publican en / según el parámetro PW00001, pero cada una
# necesita tres cosas juntas EN LA COPIA QUE SIRVE EL DOMINIO:
#
#   1. config/pagina_principal.php con su entrada ('6' SALUD, '7' Dental)
#   2. resources/views/<portada>/pages/home.blade.php
#   3. public/themes/<portada>/... (CSS e imágenes)
#
# Cuando falta cualquiera de las tres, Laravel devuelve 404 en los assets y cae
# a la portada 1, sin error visible: parece que "la opción no aparece".
#
# Este script NO modifica nada: solo dice qué falta y en qué directorio.
#
# Uso, en el servidor:
#     bash scripts/portadas-en-produccion.sh
#
set -u

# El proyecto es el checkout donde vive este script. El argumento opcional es
# una raíz servida extra, para cuando no se pueden leer los vhosts.
PROYECTO="$(cd "$(dirname "$0")/.." && pwd)"
RAIZ_EXTRA="${1:-}"
SALUD_CSS="themes/salud/css/salud.css"
DENTAL_IMG="themes/dental/images/hero.jpg"

ok()    { printf '  [OK]    %s\n' "$1"; }
falta() { printf '  [FALTA] %s\n' "$1"; }

echo "Proyecto analizado: $PROYECTO"
echo

# ---------------------------------------------------------------- 1. El código
echo "1) Qué tiene este checkout"
if [ -f "$PROYECTO/config/pagina_principal.php" ]; then
    for clave in "'6'" "'7'"; do
        if grep -q "^\s*$clave =>" "$PROYECTO/config/pagina_principal.php"; then
            ok "config/pagina_principal.php declara la portada $clave"
        else
            falta "config/pagina_principal.php NO declara la portada $clave"
        fi
    done
else
    falta "no existe config/pagina_principal.php en $PROYECTO"
fi

for vista in salud dental; do
    if [ -f "$PROYECTO/resources/views/$vista/pages/home.blade.php" ]; then
        ok "vista $vista/pages/home.blade.php"
    else
        falta "vista $vista/pages/home.blade.php"
    fi
done

for ruta in "$SALUD_CSS" "$DENTAL_IMG"; do
    if [ -f "$PROYECTO/public/$ruta" ]; then
        ok "public/$ruta"
    else
        falta "public/$ruta"
    fi
done

if [ -d "$PROYECTO/.git" ]; then
    echo "  commit de este checkout: $(git -C "$PROYECTO" log --oneline -1 2>/dev/null)"
fi
echo

# ------------------------------------------------- 2. Los directorios servidos
echo "2) Directorios que el servidor web puede estar sirviendo"

candidatos="$(
    {
        # Apache
        grep -rhi "^[[:space:]]*DocumentRoot" /etc/apache2 /etc/httpd /etc/apache 2>/dev/null
        # Nginx
        grep -rhi "^[[:space:]]*root[[:space:]]" /etc/nginx 2>/dev/null
        # LiteSpeed
        grep -rhi "docRoot" /usr/local/lsws/conf 2>/dev/null | sed 's/.*docRoot\s*//'
        # Rutas típicas de despliegue
        ls -d /var/www/html/*/public /var/www/*/public /home/*/public_html 2>/dev/null
        # Raíz indicada a mano en la línea de comandos
        [ -n "$RAIZ_EXTRA" ] && echo "$RAIZ_EXTRA"
    } \
    | sed -E 's/^[^/]*(\/[^;[:space:]]*).*/\1/' \
    | sed -E 's/;+$//' | tr -d '"' | sed 's|/$||' \
    | sort -u
)"

if [ -z "$candidatos" ]; then
    echo "  No pude leer la configuración del servidor web."
    echo "  Búscala a mano, por ejemplo:  apachectl -S   |   nginx -T   |   ls /usr/local/lsws/conf/vhosts"
    echo
fi

encontrada=""
for raiz in $candidatos; do
    [ -d "$raiz" ] || continue

    # ¿Es un public/ de un proyecto Laravel o una copia suelta de assets?
    if [ -f "$raiz/index.php" ] && grep -q "salud_aracode\|vendor/autoload" "$raiz/index.php" 2>/dev/null; then
        tipo="proyecto Laravel"
    else
        tipo="copia de archivos públicos"
    fi

    salud="FALTA"; dental="FALTA"
    [ -f "$raiz/$SALUD_CSS" ] && salud="ok"
    [ -f "$raiz/$DENTAL_IMG" ] && dental="ok"

    printf '  %s (%s)\n' "$raiz" "$tipo"
    printf '      %s -> %s\n' "$SALUD_CSS" "$salud"
    printf '      %s -> %s\n' "$DENTAL_IMG" "$dental"

    if [ "$salud" = "ok" ] && [ "$dental" = "ok" ]; then
        encontrada="$raiz"
    fi
done
echo

# --------------------------------------------------------------- 3. El veredicto
echo "3) Qué hacer"
if [ -z "${candidatos:-}" ]; then
    echo "  No pude enumerar raíces. Ejecuta esto para verlas y vuelve a correr el"
    echo "  script pasando la raíz como argumento:"
    echo "      apachectl -S        (Apache)"
    echo "      nginx -T            (Nginx)"
    echo "      bash scripts/portadas-en-produccion.sh /ruta/servida"
elif [ -n "$encontrada" ]; then
    ok "La raíz $encontrada ya tiene las dos portadas."
    echo "     Si el dominio sigue sin mostrarlas, limpia la caché en el proyecto"
    echo "     que apunta esa raíz:"
    echo "         php artisan optimize:clear"
else
    echo "  Ninguna raíz servida tiene las portadas. Para cada raíz donde el"
    echo "  proyecto Laravel NO esté actualizado, actualízalo en su propio"
    echo "  directorio (no en $PROYECTO si es otro):"
    echo
    echo "      cd <directorio-del-proyecto-servido>"
    echo "      git fetch origin && git pull origin master"
    echo "      php artisan optimize:clear"
    echo
    echo "  Si la raíz es una copia de los archivos públicos (public_html sin"
    echo "  vendor/), copia solo lo que falta desde el proyecto:"
    echo
    echo "      rsync -a $PROYECTO/public/themes/salud/  <raiz>/themes/salud/"
    echo "      rsync -a $PROYECTO/public/themes/dental/ <raiz>/themes/dental/"
    echo
    echo "  Ojo: si el panel de Parámetros tampoco ofrece SALUD y Dental, el PHP"
    echo "  servido también es antiguo, y entonces hay que actualizar el proyecto"
    echo "  completo de esa raíz, no solo los assets."
fi
