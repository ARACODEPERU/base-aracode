/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import io from 'socket.io-client';

const socketIoHost = import.meta.env.VITE_SOCKET_IO_SERVER ?? 'https://localhost:3000';
const socketIoRetryDelays = [30000, 60000, 100000];

const isSocketIoRequest = (config) => {
    if (!config?.url) {
        return false;
    }

    try {
        const requestUrl = new URL(config.url, window.location.origin);
        const socketUrl = new URL(socketIoHost, window.location.origin);

        return requestUrl.origin === socketUrl.origin;
    } catch (error) {
        return false;
    }
};

const getSocketIoRetryDelay = (retryCount) => socketIoRetryDelays[Math.min(retryCount, socketIoRetryDelays.length - 1)];

const waitForSocketIoRetry = (retryCount) => new Promise((resolve) => {
    setTimeout(resolve, getSocketIoRetryDelay(retryCount));
});

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar un timeout global de 20 segundos (20000 milisegundos)
window.axios.defaults.timeout = 20000; // 20 segundos
window.axios.interceptors.request.use((config) => {
    if (isSocketIoRequest(config) && (!config.timeout || config.timeout === 0)) {
        config.timeout = 20000;
    }

    return config;
});
// Interceptor para capturar respuestas con código de estado 401
window.axios.interceptors.response.use(
    (response) => {
      // Si la respuesta es exitosa, simplemente devuélvela para que sea manejada normalmente.
      return response;
    },
    (error) => {
      if (error.response && error.response.status === 401) {
        // Si la respuesta tiene un código de estado 401, significa que el usuario no está autenticado.
        // Aquí redirigiremos al usuario a la página de inicio de sesión.

        // Redirigir al usuario a la página de inicio de sesión (reemplaza "/login" con la ruta real)
        window.location.href = '/login';
      }
      if (error.response && error.response.status === 419) {
        // Si la respuesta tiene un código de estado 401, significa que el usuario no está autenticado.
        // Aquí redirigiremos al usuario a la página de inicio de sesión.

        // Redirigir al usuario a la página de inicio de sesión (reemplaza "/login" con la ruta real)
        window.location.href = '/login';
      }
      if (isSocketIoRequest(error.config)) {
        const retryCount = error.config.__socketIoRetryCount ?? 0;
        error.config.__socketIoRetryCount = retryCount + 1;

        return waitForSocketIoRetry(retryCount).then(() => window.axios(error.config));
      }
      return Promise.reject(error);
    }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

window.socketIo = io(socketIoHost, {
    reconnection: true,
    reconnectionDelay: 30000,
    reconnectionDelayMax: 100000,
    randomizationFactor: 0,
    timeout: 20000,
});
