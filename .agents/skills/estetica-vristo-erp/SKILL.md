---
name: estetica-vristo-erp
description: Convenciones de UI/UX del ERP ARACODE (tema Vristo) — SweetAlert2 con clase sweet-alerts para alertas y confirmaciones, componentes compartidos (TextInput, FormSection, Keypad), modales del núcleo (Modal, ModalLarge, ModalLargeX, ModalLargeXX, ModalMedium, ModalSmall, ConfirmationModal), AppLayout + Navigation. Usar SIEMPRE al crear o editar cualquier página Vue de este proyecto (núcleo o módulos) para mantener la apariencia estética unificada.
metadata:
  category: frontend
  framework: vue3-inertia-vristo
---

# Convenciones de UI — Tema Vristo (ERP ARACODE)

Usar esta skill **siempre** al crear o editar páginas Vue (núcleo o módulos). El objetivo: que todo el ERP tenga una apariencia estética unificada reutilizando los componentes existentes en lugar de reinventar.

## 1. Alertas y confirmaciones — SweetAlert2 (obligatorio)

Importar siempre `import Swal2 from 'sweetalert2';` (la convención es nombrarlo `Swal2` o `Swal`; NUNCA usar `window.confirm`, `alert()` nativos ni `Modal.confirm` de ant-design-vue).

### Confirmación de acción destructiva (eliminar/anular) — patrón estándar

```js
const destroyX = (id) => {
    Swal2.fire({
        title: '¿Estas seguro?',
        text: '¡No podrás revertir esto!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',      // siempre este azul
        cancelButtonColor: '#d33',          // siempre este rojo
        confirmButtonText: '¡Sí, Eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        padding: '2em',
        customClass: 'sweet-alerts',        // ← clave del tema Vristo, SIEMPRE incluirlo
        preConfirm: () => {
            return axios.delete(route('mi_ruta_destroy', id)).then((res) => {
                if (!res.data.success) {
                    Swal2.showValidationMessage(res.data.message)
                }
                return res
            });
        },
        allowOutsideClick: () => !Swal2.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se Eliminó correctamente',
                icon: 'success',
            });
            router.visit(route('mi_ruta_list'), { replace: true, method: 'get' });
        }
    });
}
```

### Toast de éxito (para guardados exitosos)

```js
const toast = Swal2.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: 3000,
    padding: '2em',
    customClass: { container: 'toast' },
});
toast.fire({ icon: 'success', title: 'Se registró correctamente' });
```

### Alertas informativas

```js
Swal2.fire({ title: 'Atención', text: 'Mensaje...', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
// icon: 'error' | 'warning' | 'success' | 'info' + padding: '2em' + customClass: 'sweet-alerts'
```

### Prompt con input dentro de Swal (motivos, cantidades)

```js
const { value: motivo } = await Swal2.fire({
    title: 'Anular comprobante',
    input: 'textarea',
    inputLabel: 'Motivo',
    showCancelButton: true,
    confirmButtonText: 'Anular',
    padding: '2em',
    customClass: 'sweet-alerts',
    preConfirm: (v) => {
        if (!v?.trim()) {
            Swal2.showValidationMessage('El motivo es obligatorio');
            return false;
        }
        return v.trim();
    },
    allowOutsideClick: () => !Swal2.isLoading()
});
```

### Reglas Swal

- `padding: '2em'` + `customClass: 'sweet-alerts'` en **todas** las llamadas (en toasts, `customClass: { container: 'toast' }`).
- Títulos de éxito: 'Enhorabuena'; aviso: 'Atención'; error: 'Error'.
- Textos de UI siempre en español ('¡Sí, Eliminar!', 'Cancelar').
- Cuando hay llamada HTTP durante la confirmación → `showLoaderOnConfirm: true` + `preConfirm` con axios, y `allowOutsideClick: () => !Swal2.isLoading()`.
- Éxito → toast o modal success; el listado se refresca con `router.visit(..., { replace: true, method: 'get' })`.

## 2. Modales del núcleo (componentes compartidos)

Ubicación: `resources/js/Components/` — importar con alias `@/Components/X.vue`. **No crear modales propios por módulo.**

| Componente | Uso típico | Tamaño |
|---|---|---|
| `Modal.vue` | Modal base genérico (sin header) para casos especiales. Props: `show`, `maxWidth`, `closeable`. Slot por defecto. | configurable |
| `ModalSmall.vue` | Confirmaciones menores, campos únicos. | max-w-sm |
| `ModalMedium.vue` | Formularios de 1-2 campos. | medio |
| `ModalLarge.vue` | Formularios estándar con varios campos. | sm:max-w-2xl |
| `ModalLargeX.vue` | Formularios grandes / buscadores (SearchClients, SearchProducts). | más ancho |
| `ModalLargeXX.vue` | Formularios muy grandes / asistentes multi-paso. | el más ancho |
| `ConfirmationModal.vue`, `DialogModal.vue`, `ModalStatus.vue` | Confirmaciones y estados del núcleo (menos usado en módulos). | configurable |

### API común de ModalSmall/Medium/Large/LargeX/LargeXX (headlessui Dialog)

- **Props**: `show` (Boolean), `onClose` (Function), `icon` (String opcional, ruta de imagen). Cierra con Escape, clic en overlay y botón X.
- **Slots**: `#title`, `#message` (subtítulo bajo el título), `#content`, `#buttons` (botones de acción; el botón "Cerrar" se agrega solo).

```vue
<ModalLarge :show="show" :on-close="close" :icon="'/img/caja-registradora.png'">
    <template #title>Ver pagos</template>
    <template #message><span v-if="rental">Reserva #{{ rental.id }}</span></template>
    <template #content>
        <!-- contenido; tarjetas: panel p-3, inputs: form-input -->
    </template>
    <template #buttons>
        <PrimaryButton @click="save" :disabled="form.processing">Guardar</PrimaryButton>
    </template>
</ModalLarge>
```

- Contenido en grids: `grid grid-cols-1 md:grid-cols-3 gap-4` con tarjetas `panel p-3`.
- Mientras carga: `<icon-loader class="w-8 h-8 animate-spin" />` o `SpinnerLoading`.
- Un solo modal abierto a la vez; `close()` resetea el form.

## 3. Formularios — componentes compartidos del núcleo

- Siempre `import { useForm, Link } from '@inertiajs/vue3';` — `useForm` da estados, errores y processing.
- **Inputs**: `TextInput` del núcleo (`@/Components/TextInput.vue`, clase `form-input`) o `<input class="form-input w-full" />` directo. Fecha/hora: `<input type="date" class="form-input w-full" />`.
- **Selects**: `Select` de **ant-design-vue** (`import { Select } from 'ant-design-vue';`, a veces envuelto en `ConfigProvider`) o `<select class="form-select">` nativo.
- **Labels y errores**: `InputLabel` + `InputError :message="form.errors.campo" class="mt-2"`.
- **Botones**: `PrimaryButton` (guardar), `DangerButton` (destructivo), `SecondaryButton`, `GreenButton`, `WarningButton`, `RedButton` en `@/Components/`.
- **Secciones de formulario**: `FormSection` con slots `#title`, `#description`, `#form` (grid col-span-6) y `#actions` con **Keypad** → slot `#botones` para Guardar + spinner de `form.processing`.
- **Checkbox**: input nativo con clases Vristo o `Checkbox` del núcleo.
- **Breadcrumb**: `Navigation` (`@/Components/vristo/layout/Navigation.vue`) con `:routeModule="route('xxx_dashboard')"`, `:titleModule="'Nombre del módulo'"`, `:data="[{ title: 'Subsección' }]"`.
- **Layout**: `AppLayout` con prop `title` + `<Head title="..." />`.

## 4. Tablas y listados

- Botón "Nuevo": `Link` con clase `btn btn-primary` + `icon-plus`.
- Búsqueda: input `form-input py-2 ltr:pr-11 rtl:pl-11 peer` que hace `form.get(route(...))` al dar Enter.
- Acciones por fila: `Dropdown/Menu/MenuItem` de ant-design-vue o botones-icono (`IconView`, `IconDelete`).
- Exportaciones: `DropdownExports` del núcleo. Paginación: `Pagination` del núcleo.
- Tablas: clases Vristo `panel`, `table-responsive`, `table-hover`.

## 5. Estética general

- Todo dentro de `AppLayout` + `Navigation`.
- Card contenedora: clase `panel`. Grids responsive: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4`.
- Iconos: `@/Components/vristo/icon/*.vue` (`icon-plus`, `icon-search`, `icon-x`, `icon-loader`, ...) o FontAwesome (registrado globalmente).
- Dark mode: siempre acompañar con clases `dark:` — todo componente nuevo debe verse bien en ambos modos.
- Montos: `new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' })` o helper del módulo.

## 6. Checklist antes de terminar una página

1. ¿Usa AppLayout + Navigation + Head?
2. ¿Todas las alertas usan Swal2 con `customClass: 'sweet-alerts'` y `padding: '2em'`?
3. ¿Las confirmaciones destructivas siguen el patrón preConfirm + allowOutsideClick?
4. ¿Los modales son del núcleo (ModalSmall/Medium/Large/LargeX/LargeXX) — sin modales propios?
5. ¿Inputs/labels/errores usan TextInput/InputLabel/InputError o clases `form-input` de Vristo?
6. ¿Botones del núcleo (PrimaryButton/DangerButton/...) y `form.processing` deshabilita el botón?
7. ¿Se ve bien en dark mode?
8. ¿Textos de UI en español?
