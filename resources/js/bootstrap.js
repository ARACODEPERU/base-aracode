/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
import io from 'socket.io-client';
import { applyCsrfToAxiosConfig, setCsrfToken } from './utils/csrf.js';

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

let pendingJobTokenPromise = null;

const fetchSocketJobToken = () => {
    if (!pendingJobTokenPromise) {
        pendingJobTokenPromise = axios.post('/internal/job-token')
            .then(({ data }) => data.token)
            .finally(() => {
                pendingJobTokenPromise = null;
            });
    }

    return pendingJobTokenPromise;
};

const waitForSocketIoRetry = (retryCount) => new Promise((resolve) => {
    setTimeout(resolve, getSocketIoRetryDelay(retryCount));
});

const authPaths = [
    '/login',
    '/logout',
    '/forgot-password',
    '/reset-password',
    '/verify-email',
];

const buildLoginRedirectUrl = () => {
    const currentLocation = `${window.location.pathname}${window.location.search}`;

    if (authPaths.some((path) => window.location.pathname.startsWith(path))) {
        return '/login';
    }

    return `/login?redirect_to=${encodeURIComponent(currentLocation)}`;
};

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Configurar un timeout global de 20 segundos (20000 milisegundos)
window.axios.defaults.timeout = 20000; // 20 segundos
window.axios.interceptors.request.use(async (config) => {
    applyCsrfToAxiosConfig(config);

    if (typeof config.url === 'string' && config.url.includes('/internal/job-token')) {
        return config;
    }

    const method = config.method?.toLowerCase();
    if ((method === 'post' || method === 'put' || method === 'patch') && isSocketIoRequest(config)) {
        const jobToken = await fetchSocketJobToken();
        config.headers = config.headers ?? {};
        config.headers['X-Job-Token'] = jobToken;

        if (config.data instanceof FormData) {
            config.data.append('jobToken', jobToken);
        } else if (config.data && typeof config.data === 'object' && !(config.data instanceof URLSearchParams)) {
            config.data = { ...config.data, jobToken };
        }
    }

    // timeout: 0 = sin límite (p. ej. generación de imágenes con IA)
    if (isSocketIoRequest(config) && config.timeout == null) {
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
        window.location.replace(buildLoginRedirectUrl());
        return Promise.reject(error);
      }

      if (error.response?.status === 419 && error.config && !error.config.__csrfRetried) {
        error.config.__csrfRetried = true;
        return axios.get('/csrf-token')
          .then(({ data }) => {
            if (data?.token) {
              setCsrfToken(data.token);
            }
            applyCsrfToAxiosConfig(error.config);
            return axios(error.config);
          })
          .catch(() => Promise.reject(error));
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
