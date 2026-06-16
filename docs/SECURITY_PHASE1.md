# Fase 1 — Configuración de seguridad

Genera una clave compartida (mismo valor en Laravel y socketioserver):

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## base-aracode `.env`

```env
INTERNAL_API_KEY=tu_clave_generada
SOCKET_IO_INTERNAL_URL=http://127.0.0.1:3000
VITE_SOCKET_IO_SERVER=http://localhost:3000
ALLOWED_ORIGINS="${APP_URL}"
```

## socketioserver `.env`

```env
INTERNAL_API_KEY=tu_clave_generada
HOST=127.0.0.1
PORT=3000
ALLOWED_ORIGINS=http://localhost,https://tu-dominio.com
NODE_ENV=production
```

## Linux (producción)

1. socketioserver escucha solo en `127.0.0.1:3000` (`pm2 start ecosystem.config.js --env production`).
2. nginx hace proxy de `/socket.io/` hacia Node.
3. Firewall: puertos públicos 80/443 únicamente.

Tras configurar, reinicia socketioserver y ejecuta `npm run build` en base-aracode.
