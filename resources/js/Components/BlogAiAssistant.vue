<script setup>
import { ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    contentText: { type: String, default: '' },
    title: { type: String, default: '' },
    description: { type: String, default: '' },
});

const emit = defineEmits([
    'update:contentText',
    'update:title',
    'update:description',
]);

const menuOpen = ref(false);
const loading = ref(false);
const menuPosition = ref({ top: 0, left: 0 });
const buttonRef = ref(null);

const toggleMenu = async () => {
    if (menuOpen.value) {
        menuOpen.value = false;
        return;
    }
    await nextTick();
    if (buttonRef.value) {
        const rect = buttonRef.value.getBoundingClientRect();
        menuPosition.value = { top: rect.bottom + 4, left: rect.left };
    }
    menuOpen.value = true;
};

// Cerrar menú al hacer click fuera
const closeMenu = () => { menuOpen.value = false; };

// ─── 1. CORREGIR ORTOGRAFÍA ───────────────────────────────────────────
const correctSpelling = async () => {
    menuOpen.value = false;

    if (!props.contentText || props.contentText.trim() === '') {
        Swal.fire('Sin contenido', 'No hay contenido en el editor para corregir.', 'info');
        return;
    }

    loading.value = true;

    try {
        const { data } = await axios.post(route('blog_ai_correct_spelling'), {
            text: props.contentText,
        });

        loading.value = false;

        if (!data.success) {
            Swal.fire('Error', data.message || 'No se pudo corregir.', 'error');
            return;
        }

        const result = await Swal.fire({
            title: '✏️ Corrección ortográfica y gramatical',
            html: `
                <div style="text-align:left; font-size:0.85rem;">
                    <div style="margin-bottom:12px;">
                        <strong style="color:#6b7280;">Texto original:</strong>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; max-height:200px; overflow-y:auto; margin-top:4px; white-space:pre-wrap; color:#374151;">${escapeHtml(data.original)}</div>
                    </div>
                    <div>
                        <strong style="color:#16a34a;">Texto corregido:</strong>
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:12px; max-height:200px; overflow-y:auto; margin-top:4px; white-space:pre-wrap; color:#374151;">${escapeHtml(data.corrected)}</div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '🔄 Reemplazar contenido',
            cancelButtonText: 'Copiar y cancelar',
            confirmButtonColor: '#002060',
            cancelButtonColor: '#6b7280',
            width: '700px',
            customClass: 'sweet-alerts',
        });

        if (result.isConfirmed) {
            emit('update:contentText', data.corrected);
            Swal.fire('¡Reemplazado!', 'El contenido ha sido actualizado con las correcciones.', 'success');
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            await navigator.clipboard.writeText(data.corrected);
            Swal.fire('Copiado', 'El texto corregido ha sido copiado al portapapeles.', 'info');
        }
    } catch (e) {
        loading.value = false;
        Swal.fire('Error', e.response?.data?.message || 'Error al conectar con la IA.', 'error');
    }
};

// ─── 2. ESCRIBIR ARTÍCULO ─────────────────────────────────────────────
const generateArticle = async () => {
    menuOpen.value = false;

    const { value: formValues } = await Swal.fire({
        title: '📝 Generar artículo con IA',
        html: `
            <div style="text-align:left;">
                <label style="font-weight:600; font-size:0.85rem; color:#374151;">Tema del artículo *</label>
                <input id="swal-topic" class="swal2-input" placeholder="Ej: NIIF 18 para pymes" style="width:100%; font-size:0.9rem; margin-bottom:12px;">
                <label style="font-weight:600; font-size:0.85rem; color:#374151;">¿De qué quieres que trate? *</label>
                <textarea id="swal-description" class="swal2-textarea" placeholder="Describe los puntos principales que debe cubrir el artículo..." style="width:100%; font-size:0.9rem; min-height:100px;"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '🚀 Generar artículo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#002060',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            const topic = document.getElementById('swal-topic').value.trim();
            const description = document.getElementById('swal-description').value.trim();
            if (!topic) {
                Swal.showValidationMessage('El tema es obligatorio');
                return false;
            }
            if (!description) {
                Swal.showValidationMessage('La descripción es obligatoria');
                return false;
            }
            return { topic, description };
        },
    });

    if (!formValues) return;

    loading.value = true;

    try {
        const { data } = await axios.post(route('blog_ai_generate_article'), {
            topic: formValues.topic,
            description: formValues.description,
        });

        loading.value = false;

        if (!data.success) {
            Swal.fire('Error', data.message || 'No se pudo generar el artículo.', 'error');
            return;
        }

        const result = await Swal.fire({
            title: 'Artículo generado',
            html: `
                <div style="text-align:left; font-size:0.85rem;">
                    <div style="margin-bottom:10px;">
                        <strong style="color:#6b7280;">Título:</strong>
                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:8px; margin-top:4px;">${escapeHtml(data.title)}</div>
                    </div>
                    <div style="margin-bottom:10px;">
                        <strong style="color:#6b7280;">Descripción:</strong>
                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:8px; margin-top:4px;">${escapeHtml(data.description)}</div>
                    </div>
                    <div>
                        <strong style="color:#6b7280;">Vista previa del contenido:</strong>
                        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:12px; max-height:300px; overflow-y:auto; margin-top:4px; line-height:1.6;">${data.content}</div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '✅ Insertar en editor',
            denyButtonText: '📋 Solo copiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#002060',
            denyButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            width: '800px',
            customClass: 'sweet-alerts',
        });

        if (result.isConfirmed) {
            emit('update:contentText', data.content);
            if (data.title) emit('update:title', data.title);
            if (data.description) emit('update:description', data.description);
            Swal.fire('¡Insertado!', 'El artículo ha sido insertado en el editor. Recuerda revisarlo y ajustarlo antes de publicar.', 'success');
        } else if (result.isDenied) {
            const textToCopy = `Título: ${data.title}\n\nDescripción: ${data.description}\n\n${data.content.replace(/<[^>]+>/g, '\n')}`;
            await navigator.clipboard.writeText(textToCopy);
            Swal.fire('Copiado', 'El contenido ha sido copiado al portapapeles.', 'info');
        }
    } catch (e) {
        loading.value = false;
        Swal.fire('Error', e.response?.data?.message || 'Error al conectar con la IA.', 'error');
    }
};

// ─── 3. VERIFICAR INFORMACIÓN ──────────────────────────────────────────
const verifyContent = async () => {
    menuOpen.value = false;

    if (!props.contentText || props.contentText.trim() === '') {
        Swal.fire('Sin contenido', 'No hay contenido en el editor para verificar.', 'info');
        return;
    }

    loading.value = true;

    try {
        const { data } = await axios.post(route('blog_ai_verify_content'), {
            text: props.contentText,
        });

        loading.value = false;

        if (!data.success) {
            Swal.fire('Error', data.message || 'No se pudo verificar.', 'error');
            return;
        }

        const ratingColor = {
            'Alta': '#16a34a',
            'Media': '#d97706',
            'Baja': '#dc2626',
        };
        const ratingIcon = {
            'Alta': '✅',
            'Media': '⚠️',
            'Baja': '❌',
        };

        const rating = data.rating || 'No determinada';
        const color = ratingColor[rating] || '#6b7280';
        const icon = ratingIcon[rating] || '❓';

        let claimsHtml = '';
        if (data.claims && data.claims.length > 0) {
            claimsHtml = data.claims.map(c => {
                const statusColor = {
                    'Verificable': '#16a34a',
                    'Posiblemente verdadero': '#d97706',
                    'No verificable': '#6b7280',
                    'Incorrecto': '#dc2626',
                };
                const sc = statusColor[c.status] || '#6b7280';
                return `<div style="margin-bottom:8px; padding:8px; background:#fff; border-radius:6px; border-left:3px solid ${sc};">
                    <div style="font-weight:500;">${escapeHtml(c.claim)}</div>
                    <div style="font-size:0.8rem; margin-top:2px;">
                        <span style="color:${sc}; font-weight:600;">${escapeHtml(c.status)}</span>
                        <span style="color:#6b7280;"> — ${escapeHtml(c.justification)}</span>
                    </div>
                </div>`;
            }).join('');
        }

        let obsHtml = '';
        if (data.observations && data.observations.length > 0) {
            obsHtml = data.observations.map(o => `<li style="margin-bottom:4px;">${escapeHtml(o)}</li>`).join('');
            obsHtml = `<ul style="padding-left:20px; margin:8px 0;">${obsHtml}</ul>`;
        }

        let recHtml = '';
        if (data.recommendations && data.recommendations.length > 0) {
            recHtml = data.recommendations.map(r => `<li style="margin-bottom:4px;">${escapeHtml(r)}</li>`).join('');
            recHtml = `<ul style="padding-left:20px; margin:8px 0;">${recHtml}</ul>`;
        }

        await Swal.fire({
            title: '🔍 Verificación de información',
            html: `
                <div style="text-align:left; font-size:0.85rem;">
                    <div style="text-align:center; margin-bottom:16px;">
                        <span style="font-size:2rem;">${icon}</span><br>
                        <span style="font-weight:700; color:${color}; font-size:1.1rem;">Fiabilidad: ${escapeHtml(rating)}</span>
                    </div>
                    ${data.claims && data.claims.length > 0 ? `
                    <div style="margin-bottom:16px;">
                        <strong style="color:#1f2937;">📋 Afirmaciones analizadas:</strong>
                        <div style="margin-top:8px;">${claimsHtml}</div>
                    </div>
                    ` : ''}
                    ${data.observations && data.observations.length > 0 ? `
                    <div style="margin-bottom:16px;">
                        <strong style="color:#1f2937;">💬 Observaciones:</strong>
                        ${obsHtml}
                    </div>
                    ` : ''}
                    ${data.recommendations && data.recommendations.length > 0 ? `
                    <div style="margin-bottom:16px;">
                        <strong style="color:#1f2937;">💡 Recomendaciones:</strong>
                        ${recHtml}
                    </div>
                    ` : ''}
                    <details style="margin-top:12px;">
                        <summary style="cursor:pointer; color:#6b7280; font-size:0.8rem;">Ver respuesta completa de la IA</summary>
                        <pre style="background:#f9fafb; padding:10px; border-radius:6px; font-size:0.75rem; max-height:200px; overflow-y:auto; white-space:pre-wrap; margin-top:8px;">${escapeHtml(data.raw || '')}</pre>
                    </details>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#002060',
            width: '700px',
            customClass: 'sweet-alerts',
        });
    } catch (e) {
        loading.value = false;
        Swal.fire('Error', e.response?.data?.message || 'Error al conectar con la IA.', 'error');
    }
};

// Utilidad para escapar HTML en el contenido del modal
const escapeHtml = (str) => {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};
</script>

<template>
    <div style="position: relative; display: inline-block;">
        <!-- Botón principal -->
        <button
            ref="buttonRef"
            type="button"
            @click="toggleMenu"
            :disabled="loading"
            class="btn btn-primary"
            style="background-color: #7c3aed; border-color: #7c3aed; border-radius: 8px; padding: 8px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px;"
        >
            <span v-if="loading" class="fa fa-spinner fa-spin"></span>
            <span v-else>🤖</span>
            {{ loading ? 'Procesando...' : 'Asistente IA' }}
            <span style="font-size: 0.6rem;" v-if="!loading">▼</span>
        </button>

        <!-- Menú desplegable -->
        <div
            v-if="menuOpen && !loading"
            :style="{ position: 'fixed', top: menuPosition.top + 'px', left: menuPosition.left + 'px', background: 'white', border: '1px solid #e5e7eb', borderRadius: '10px', boxShadow: '0 8px 24px rgba(0,0,0,0.12)', zIndex: 9999, minWidth: '260px', overflow: 'hidden' }"
        >
            <button
                type="button"
                @click="correctSpelling"
                style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: background 0.15s;"
                @mouseenter="$event.target.style.backgroundColor='#f3f4f6'"
                @mouseleave="$event.target.style.backgroundColor='transparent'"
            >
                <span style="font-size: 1.1rem;">✏️</span>
                <div>
                    <div style="font-weight: 600; color: #1f2937;">Corregir ortografía</div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Revisa y corrige el contenido actual</div>
                </div>
            </button>

            <div style="border-top: 1px solid #f3f4f6;"></div>

            <button
                type="button"
                @click="generateArticle"
                style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: background 0.15s;"
                @mouseenter="$event.target.style.backgroundColor='#f3f4f6'"
                @mouseleave="$event.target.style.backgroundColor='transparent'"
            >
                <span style="font-size: 1.1rem;">📝</span>
                <div>
                    <div style="font-weight: 600; color: #1f2937;">Escribir artículo</div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Genera un artículo completo con IA</div>
                </div>
            </button>

            <div style="border-top: 1px solid #f3f4f6;"></div>

            <button
                type="button"
                @click="verifyContent"
                style="width: 100%; padding: 12px 16px; border: none; background: none; text-align: left; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: background 0.15s;"
                @mouseenter="$event.target.style.backgroundColor='#f3f4f6'"
                @mouseleave="$event.target.style.backgroundColor='transparent'"
            >
                <span style="font-size: 1.1rem;">🔍</span>
                <div>
                    <div style="font-weight: 600; color: #1f2937;">Verificar información</div>
                    <div style="font-size: 0.75rem; color: #9ca3af;">Analiza la veracidad del contenido</div>
                </div>
            </button>
        </div>

        <!-- Overlay para cerrar menú -->
        <div
            v-if="menuOpen"
            @click="closeMenu"
            style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9998;"
        ></div>
    </div>
</template>
