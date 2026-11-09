---
name: aracodito-alertas
description: Política de alertas del ERP ARACODE — toda confirmación y todo mensaje posterior a una acción (éxito, error, advertencia) se muestra con window.alertaSistema, que usa Aracodito si el usuario lo tiene activo y la vista está marcada como nueva, y SweetAlert2 en caso contrario. Usar SIEMPRE al crear o editar pantallas Vue/Inertia (núcleo o módulos) y al añadir cualquier aviso, confirmación o resultado de una acción.
metadata:
  category: frontend
  framework: vue3-inertia-vristo
  depende_de: [estetica-vristo-erp]
---

# Alertas y confirmaciones — Aracodito o Swal según la configuración

Regla del proyecto: **ningún aviso se muestra con Swal2 directamente**. Toda confirmación y todo mensaje posterior a una acción pasan por `window.alertaSistema(...)`, el puente de `resources/js/alerta-sistema.js`, que decide el canal:

- **Aracodito activo** (`users.aracodito_enabled` = 1) **y vista marcada como nueva** → el mensaje sale con Aracodito (el robot con su burbuja).
- **Aracodito desactivado, o vista sin marcar** → SweetAlert2, exactamente como se mostraba antes. Las vistas antiguas no cambian.

La decisión es del puente, no de la vista: la vista solo elige el texto, el tipo y las opciones. No se replica la condición `aracodito_enabled` a mano en cada componente.

## Qué cuenta como "mensaje posterior a una acción"

Todo lo que hoy sería un `Swal2.fire` suelto:

- Confirmación antes de guardar, eliminar, anular, cambiar de estado o enviar.
- Aviso de éxito después de guardar/eliminar/enviar (incluido el "Enhorabuena" clásico).
- Aviso de error o de validación devuelto por el backend.
- Advertencias y bloqueos ("esta cuota ya tiene comprobantes", "no se puede cambiar el estado").
- Preguntas de sí/no con botones.

## Marcar una vista como nueva

Solo las vistas que se están creando o rehaciendo se marcan; las antiguas siguen con Swal.

```vue
<script setup>
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <AppLayout title="...">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>
        ...
    </AppLayout>
</template>
```

Alternativa dinámica: `document.body.dataset.aracoditoView = "new"`.
Nunca poner la marca en el layout global: encendería Aracodito en todas las pantallas antiguas.

## API del puente

```js
window.alertaSistema(title, text, icon = "info", options = {});
```

- `icon`: `success` | `error` | `warning` | `info` | `question`.
- `options.duration`: milisegundos; `0` deja el aviso abierto hasta que el usuario responda (obligatorio en confirmaciones).
- `options.showCancelButton`, `confirmButtonText`, `cancelButtonText`.
- `options.onConfirm`: función async que ejecuta el proceso al confirmar. **Aracodito se queda visible con el loader del pecho hasta que resuelve y muestra el resultado en la misma burbuja.**
- `options.loadingText`, `successMessage`, `errorMessage`, `resultDuration`: textos y duración del proceso.
- `options.preConfirm` + `showLoaderOnConfirm: true`: equivalente para el respaldo SweetAlert2 (mantiene el modal abierto esperando al backend).
- `options.audio`, `options.ia`: normalmente los resuelve el puente desde `window.USER_CONFIG`; no hace falta pasarlos.
- `window.alertaSistema.enAracodito()`: `true` si en esta vista y con este usuario las alertas saldrán con Aracodito. Sirve para saber si el resultado ya lo mostró el asistente y no duplicar el aviso.

## Patrones

### Aviso simple

```js
window.alertaSistema("Contrato guardado", "El contrato se guardó correctamente.", "success");
```

### Confirmación y luego la acción

```js
const result = await window.alertaSistema("Eliminar pago", "¿Continuar?", "warning", {
    duration: 0,
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
});

if (!result?.isConfirmed) return;

await axios.delete(route("ruta_destroy", id));
window.alertaSistema("Pago eliminado", "Se eliminó correctamente.", "success");
```

### Confirmación que espera al backend (Aracodito + Swal)

La confirmación no se cierra hasta que responde el servidor; el mensaje final (éxito o error) aparece en el mismo canal:

```js
const persist = () => new Promise((resolve, reject) => {
    form.post(route("ruta_store", props.id), {
        preserveScroll: true,
        onSuccess: () => resolve("El cronograma se guardó correctamente."),
        onError: (errors) => reject(new Error(Object.values(errors || {})[0] || "Revisa los datos.")),
    });
});

const result = await window.alertaSistema(
    "Guardar cronograma",
    "¿Deseas continuar?",
    "question",
    {
        duration: 0,
        showCancelButton: true,
        confirmButtonText: "Sí, guardar",
        cancelButtonText: "Cancelar",
        loadingText: "Guardando cronograma...",
        successMessage: "Cronograma guardado. Se guardó correctamente.",
        errorMessage: "No se pudo guardar. Revisa los datos.",
        showLoaderOnConfirm: true,
        onConfirm: async () => ({ message: await persist(), type: "success" }),
        preConfirm: () => persist(),
    }
);

if (!result?.isConfirmed) return;

// Aracodito ya mostró el resultado en su burbuja; Swal devuelve el valor.
if (window.alertaSistema.enAracodito?.()) return;

if (result.value) {
    window.alertaSistema("Cronograma guardado", result.value, "success");
}
```

Si el proceso falla, Aracodito muestra el mensaje de error en la burbuja y el puente devuelve el error de validación; no hace falta un `catch` que abra otro aviso.

## Reglas de convivencia

1. **No** importar `Swal2` en componentes nuevos solo para alertar. El único lugar donde se usa `Swal2` es `resources/js/alerta-sistema.js` (respaldo).
2. `window.confirm`, `alert()` y `Modal.confirm` de ant-design-vue siguen prohibidos.
3. Si una vista antigua ya usa `Swal2` y se está tocando, migrar sus avisos a `window.alertaSistema` y marcarla como vista nueva; si no se toca, se deja como está.
4. Los textos van en español, en tono directo y con el contexto de la acción ("Vas a cambiar la cuota 3 de «Pendiente» a «Pagado». ¿Estás seguro?").
5. La confirmación de acciones destructivas mantiene el color y la estética del tema: el puente ya aplica `sweet-alerts` y el estilo Vristo cuando cae en Swal.
6. `form.processing` (o una bandera equivalente) debe evitar el doble envío: el handler de confirmación vuelve de inmediato si ya hay un proceso en curso.

## Referencias reales en el repo

- `resources/js/alerta-sistema.js` — puente y detección de Aracodito (`isNewView`, `enAracodito`).
- `resources/js/aracodito.js` — API `window.Aracodito.showMessage`, loader con `onConfirm` y mensaje final.
- `resources/views/app.blade.php` — inyecta `window.USER_CONFIG` desde el usuario autenticado.
- `Modules/Commercial/Resources/assets/js/Pages/Contracts/Partials/ContractForm.vue` — aviso de guardado/actualizado.
- `Modules/Commercial/Resources/assets/js/Pages/Contracts/Payments.vue` — confirmación de estado, guardado con `onConfirm`/`preConfirm` y alta de cuotas.

## Checklist antes de dar por terminada una pantalla

- [ ] La vista está marcada con `aracodito-view` si es nueva.
- [ ] Ninguna confirmación ni aviso usa `Swal2.fire` directo.
- [ ] Las confirmaciones usan `duration: 0` y revisan `result.isConfirmed`.
- [ ] Si hay proceso asíncrono, la confirmación espera (`onConfirm` + `preConfirm`) y el mensaje final sale por el mismo canal.
- [ ] Probado con un usuario con `aracodito_enabled = 1` y con uno con `0`: el primero ve a Aracodito y el segundo las alertas clásicas.
