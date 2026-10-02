# Skill: Usar siempre los componentes de modal generales

## Regla crítica

**Todo modal de cualquier página Vue debe construirse con los componentes generales de `resources/js/Components/`, nunca con divs fijos hechos a mano** (`fixed inset-0 bg-black/40 ... panel p-6`). Está prohibido crear modales custom con overlay propio.

Componentes disponibles (elegir por tamaño del contenido):

| Componente | Uso |
|---|---|
| `ModalSmall.vue` | Formularios de 1-3 campos: editar nombre, confirmar acción, cambiar estado |
| `ModalMedium.vue` | Formularios medianos de 4-6 campos |
| `ModalLarge.vue` | Formularios completos: secciones con varios campos, convenios, exámenes |
| `ModalLargeX.vue` | Formularios extensos con grillas o muchas columnas |
| `ModalLargeXX.vue` | Pantallas casi completas: importadores, vistas previas grandes |

## Patrón de uso (copiar del existente)

```vue
<script setup>
import ModalLarge from '@/Components/ModalLarge.vue';

const sectionModal = ref(false);
const closeSectionModal = () => { sectionModal.value = false; };
</script>

<template>
    <ModalLarge :onClose="closeSectionModal" :show="sectionModal" :icon="'/img/aula.png'">
        <template #title>
            {{ sectionForm.id ? 'Editar' : 'Nueva' }} Sección
        </template>
        <template #content>
            <!-- campos del formulario -->
        </template>
        <template #buttons>
            <button type="button" class="btn btn-primary" @click="saveSection">Guardar</button>
        </template>
    </ModalLarge>
</template>
```

Notas:
- El componente ya trae overlay, animación, botón ✕ y botón "Cerrar"; solo pasar `:show`, `:onClose` y opcionalmente `:icon`.
- Slots: `#title`, `#message` (subtítulo), `#content` y `#buttons` (botones de acción extra; Cerrar es automático).

## Auditoría

Si encuentras en el repo un modal hecho a mano (div con `fixed inset-0`), conviértelo al componente general correspondiente como mejora, empezando por las páginas que se estén editando en la sesión.
