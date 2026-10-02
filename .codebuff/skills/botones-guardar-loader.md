# Skill: Botón Guardar siempre con icono loader

## Regla

**Todo botón "Guardar" (y cualquier botón que dispare una petición async: crear/editar/eliminar) debe mostrar un icono loader mientras se procesa la petición y deshabilitarse para evitar dobles envíos.** Usa el componente existente `@/Components/vristo/icon/icon-loader.vue` (spinner `animate-spin`).

## Patrón obligatorio

```vue
<script setup>
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { ref } from 'vue';

const saving = ref(false);

const save = () => {
    saving.value = true;
    axios[ method ](url, data).then(() => {
        // cerrar modal / mensaje
    }).catch((err) => {
        // mostrar error
    }).finally(() => {
        saving.value = false;   // SIEMPRE en finally
    });
};
</script>

<template>
    <button type="button" class="btn btn-primary" @click="save" :disabled="saving">
        <IconLoader v-if="saving" class="w-4 h-4 ltr:mr-2 rtl:ml-2" /> Guardar
    </button>
</template>
```

Puntos clave:
- El loader se muestra con `v-if="saving"` (no siempre visible).
- El botón se deshabilita con `:disabled="saving"`.
- `saving` se resetea en `.finally()` para que también se libere cuando falla la petición.
- Con Inertia `useForm` puedes usar `form.processing` en lugar de un ref propio.
