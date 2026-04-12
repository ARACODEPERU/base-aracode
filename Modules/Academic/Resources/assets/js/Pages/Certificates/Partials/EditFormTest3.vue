<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal2 from 'sweetalert2';
import { ref, watch, onMounted, onUnmounted, computed, nextTick } from 'vue';
import iconMenu from '@/Components/vristo/icon/icon-menu.vue';
import iconCaretsDown from '@/Components/vristo/icon/icon-carets-down.vue';
import VueCollapsible from 'vue-height-collapsible/vue3';
import iconCalendarDays from '@/Components/vristo/icon/icon-calendar-days.vue';
import iconUserStudent from '@/Components/vristo/icon/icon-user-student.vue';
import iconFont from '@/Components/vristo/icon/icon-font.vue';
import iconQrcode from '@/Components/vristo/icon/icon-qrcode.vue';
import iconInfoCircleTwo from '@/Components/vristo/icon/icon-info-circle-two.vue';
import switchMobinkakei from '@/Components/switch/switch-mobinkakei.vue';
import { Stage, Layer, Image as KonvaImage, Text, Rect, Group, Transformer } from 'vue-konva';
import QRCode from 'qrcode';

const props = defineProps({
    certificate: {
        type: Object,
        default: () => ({}),
    },
    gradeConfig: {
        type: Object,
        default: () => null,
    },
});

const xasset = assetUrl;

const getImage = (path) => {
    return xasset + 'storage/' + path;
};

// Free Google Fonts available (loaded via Google Fonts CDN)
const availableFonts = [
    { label: 'Pacifico (caligrafía)', value: 'Pacifico' },
    { label: 'Dancing Script (cursiva)', value: 'Dancing Script' },
    { label: 'Great Vibes (elegante)', value: 'Great Vibes' },
    { label: 'Playfair Display (serif clásica)', value: 'Playfair Display' },
    { label: 'Lora (serif moderna)', value: 'Lora' },
    { label: 'Merriweather (legible)', value: 'Merriweather' },
    { label: 'Montserrat (sans-serif)', value: 'Montserrat' },
    { label: 'Poppins (geométrica)', value: 'Poppins' },
    { label: 'Roboto (neutral)', value: 'Roboto' },
    { label: 'Open Sans (limpia)', value: 'Open Sans' },
    { label: 'Raleway (elegante sans)', value: 'Raleway' },
    { label: 'Cinzel (romana)', value: 'Cinzel' },
    { label: 'IM Fell English (clásica)', value: 'IM Fell English' },
    { label: 'Libre Baskerville (baskerville)', value: 'Libre Baskerville' },
    { label: 'Oswald (condensada)', value: 'Oswald' },
    { label: 'PT Serif (periodística)', value: 'PT Serif' },
    { label: 'Cormorant Garamond (garamond)', value: 'Cormorant Garamond' },
    { label: 'Ubuntu (tecnológica)', value: 'Ubuntu' },
    { label: 'Nunito (redondeada)', value: 'Nunito' },
    { label: 'Source Serif 4 (lectura)', value: 'Source Serif 4' },
];

const normalizeFontName = (font) => {
    if (!font) return 'Poppins';
    const map = {
        'Pacifico-Regular.ttf': 'Pacifico',
        'PlaywriteIN-Regular.ttf': 'Dancing Script',
        'OLDENGL.TTF': 'Cinzel',
        'Poppins-Light.ttf': 'Poppins',
        'Poppins-Regular.ttf': 'Poppins',
        'Poppins-Bold.ttf': 'Poppins',
        'Poppins-Medium.ttf': 'Poppins',
        'Intro-Headr.ttf': 'Montserrat',
        'arial.ttf': 'Roboto',
    };
    return map[font] || font;
};

const stageRef = ref(null);
const transformerRef = ref(null);
const selectedElement = ref(null);
const activeTab = ref('front');

const backgroundImage = ref(null);
const backBackgroundImage = ref(null);
const qrImage = ref(null);
const qrImageElement = ref(null);
const backQrImageElement = ref(null);

const generateQrCode = async (url, side = 'front') => {
    if (!url || !url.trim()) {
        if (side === 'front') qrImageElement.value = null;
        else backQrImageElement.value = null;
        return;
    }
    try {
        const dataUrl = await QRCode.toDataURL(url.trim(), {
            width: 512,
            margin: 1,
            color: { dark: '#000000ff', light: '#ffffffff' },
            errorCorrectionLevel: 'M'
        });
        const img = new Image();
        img.onload = () => {
            if (side === 'front') qrImageElement.value = img;
            else backQrImageElement.value = img;
        };
        img.src = dataUrl;
    } catch (err) {
        console.error('Error generando QR:', err);
    }
};
const isLoadingImages = ref(false);
const isSavingAll = ref(false);

const stageWidth = ref(800);
const stageHeight = ref(600);

const previewData = {
    studentName: 'Juan Pérez García',
    currentDate: new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' }),
    courseName: 'Curso de Ejemplo',
    moduleName: 'Módulo de Ejemplo',
    moduleDescription: 'Esta es la descripción personalizada del módulo que aparece en el certificado...',
    finalGrade: '95',
    certificateNumber: 'CERT-001-2024'
};

const frontElements = ref({
    date: {
        text: previewData.currentDate,
        visible: true,
        x: 0,
        y: 0,
        fontSize: 22,
        fontFamily: 'Pacifico',
        fontAlign: 'center',
        verticalAlign: 'center',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    names: {
        text: previewData.studentName,
        visible: true,
        x: 0,
        y: 0,
        fontSize: 28,
        fontFamily: 'Dancing Script',
        fontAlign: 'center',
        verticalAlign: 'center',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    title: {
        text: 'CERTIFICADO DE FINALIZACIÓN',
        visible: true,
        x: 0,
        y: 0,
        fontSize: 32,
        fontFamily: 'Cinzel',
        fontAlign: 'center',
        verticalAlign: 'center',
        maxWidth: 700,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    description: {
        text: 'Este certificado acredita que el estudiante ha completado con éxito todas las unidades modulares y evaluaciones del curso, demostrando un dominio avanzado de las competencias técnicas y prácticas exigidas. Se otorga como reconocimiento oficial a su compromiso académico y a su capacidad para aplicar estos conocimientos en entornos profesionales de alta exigencia.',
        visible: true,
        x: 0,
        y: 0,
        fontSize: 18,
        fontFamily: 'Poppins',
        fontAlign: 'center',
        verticalAlign: 'center',
        maxWidth: 600,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    qr: {
        visible: true,
        x: 0,
        y: 0,
        size: 100,
        align: 'bottom-right',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    moduleDescription: {
        text: previewData.moduleDescription,
        visible: false,
        x: 0,
        y: 0,
        fontSize: 14,
        fontFamily: 'Arial',
        fontAlign: 'left',
        verticalAlign: 'top',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    }
});

const backElements = ref({
    date: {
        text: previewData.currentDate,
        visible: false,
        x: 0,
        y: 0,
        fontSize: 22,
        fontFamily: 'Pacifico',
        fontAlign: 'center',
        verticalAlign: 'center',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    names: {
        text: previewData.studentName,
        visible: false,
        x: 0,
        y: 0,
        fontSize: 28,
        fontFamily: 'Dancing Script',
        fontAlign: 'center',
        verticalAlign: 'center',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    title: {
        text: 'CERTIFICADO DE FINALIZACIÓN',
        visible: false,
        x: 0,
        y: 0,
        fontSize: 32,
        fontFamily: 'Cinzel',
        fontAlign: 'center',
        verticalAlign: 'center',
        maxWidth: 700,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    description: {
        text: '',
        visible: false,
        x: 0,
        y: 0,
        fontSize: 18,
        fontFamily: 'Poppins',
        fontAlign: 'left',
        verticalAlign: 'top',
        maxWidth: 600,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    course: {
        text: '',
        visible: false,
        x: 0,
        y: 0,
        fontSize: 16,
        fontFamily: 'Poppins',
        fontAlign: 'left',
        verticalAlign: 'top',
        maxWidth: 600,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0,
        contentType: 'list',
        showExamGrade: false,
        showThemes: true
    },
    module: {
        text: '',
        visible: false,
        x: 0,
        y: 0,
        fontSize: 16,
        fontFamily: 'Poppins',
        fontAlign: 'left',
        verticalAlign: 'top',
        maxWidth: 600,
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0,
        contentType: 'list'
    },
    qr: {
        visible: true,
        x: 0,
        y: 0,
        size: 100,
        align: 'bottom-right',
        draggable: true,
        originalX: 0,
        originalY: 0
    },
    grade: {
        visible: false,
        x: 0,
        y: 0,
        fontSize: 20,
        fontFamily: 'Montserrat',
        color: '#000000',
        draggable: true,
        originalX: 0,
        originalY: 0,
        rectangleWidth: 100,
        rectangleHeight: 100,
        rectangleColor: '#000000'
    }
});

const form = useForm({
    id: props.certificate.id,
    grade_id: props.gradeConfig?.id ?? null,
    action_type: null,
    course_id: null,
    for_module: props.certificate.for_module == 1 ? true : false,
    certificate_img: props.certificate.certificate_img_finished ?? props.certificate.certificate_img,
    fontfamily_date: normalizeFontName(props.certificate.fontfamily_date) || 'Pacifico',
    font_align_date: props.certificate.font_align_date,
    font_vertical_align_date: props.certificate.font_vertical_align_date,
    position_date_x: props.certificate.position_date_x,
    position_date_y: props.certificate.position_date_y,
    font_size_date: props.certificate.font_size_date,
    fontfamily_names: normalizeFontName(props.certificate.fontfamily_names) || 'Dancing Script',
    font_align_names: props.certificate.font_align_names,
    font_vertical_align_names: props.certificate.font_vertical_align_names,
    position_names_x: props.certificate.position_names_x,
    position_names_y: props.certificate.position_names_y,
    font_size_names: props.certificate.font_size_names,
    fontfamily_title: normalizeFontName(props.certificate.fontfamily_title) || 'Cinzel',
    font_align_title: props.certificate.font_align_title,
    font_vertical_align_title: props.certificate.font_vertical_align_title,
    position_title_x: props.certificate.position_title_x,
    position_title_y: props.certificate.position_title_y,
    font_size_title: props.certificate.font_size_title,
    max_width_title: props.certificate.max_width_title,
    position_qr_x: props.certificate.position_qr_x,
    position_qr_y: props.certificate.position_qr_y,
    size_qr: props.certificate.size_qr,
    font_align_qr: props.certificate.font_align_qr,
    qr_url: 'https://academy.globalcpaperu.com/academy',
    fontfamily_description: normalizeFontName(props.certificate.fontfamily_description) || 'Poppins',
    font_align_description: props.certificate.font_align_description,
    font_vertical_align_description: props.certificate.font_vertical_align_description,
    position_description_x: props.certificate.position_description_x,
    position_description_y: props.certificate.position_description_y,
    font_size_description: props.certificate.font_size_description,
    max_width_description: props.certificate.max_width_description,
    text_align_description: props.certificate.text_align_description || 'left',
    interspace_description: props.certificate.interspace_description,
    name_certificate: props.certificate.name_certificate,
    state: props.certificate.state == 1 ? true : false,
    visible_image_qr: props.certificate.visible_image_qr == 1 ? true : false,
    visible_description: props.certificate.visible_description == 1 ? true : false,
    color_description: props.certificate.color_description,
    // Configuración de descripción del módulo (solo para for_module)
    fontfamily_module_description: normalizeFontName(props.certificate.fontfamily_module_description) || 'Arial',
    font_align_module_description: props.certificate.font_align_module_description || 'left',
    font_vertical_module_description: props.certificate.font_vertical_module_description || 'top',
    position_module_description_x: props.certificate.position_module_description_x || 425,
    position_module_description_y: props.certificate.position_module_description_y || 350,
    font_size_module_description: props.certificate.font_size_module_description || 14,
    max_width_module_description: props.certificate.max_width_module_description || 600,
    text_align_module_description: props.certificate.text_align_module_description || 'left',
    color_module_description: props.certificate.color_module_description || '#1a1c2d',
    visible_module_description: props.certificate.visible_module_description == 1 ? true : false,
    visible_title: props.certificate.visible_title == 1 ? true : false,
    color_title: props.certificate.color_title,
    visible_names: props.certificate.visible_names == 1 ? true : false,
    color_names: props.certificate.color_names,
    visible_date: props.certificate.visible_date == 1 ? true : false,
    color_date: props.certificate.color_date,
    has_back: props.certificate.back_certificate_img ? true : false,
    has_reverse: props.certificate.has_reverse == 1 ? true : false,
    back_certificate_img: props.certificate.back_certificate_img,
    back_description: props.certificate.back_description || '',
    back_content_show_manual: props.certificate.back_content_show_manual != 0 ? true : false,
    back_content_show_course: props.certificate.back_content_show_course != 0 ? true : false,
    back_content_show_module: props.certificate.back_content_show_module != 0 ? true : false,
    back_content_type: props.certificate.back_content_type || 'list',
    back_content_type_module: props.certificate.back_content_type_module || 'list',
    back_fontfamily_date: normalizeFontName(props.certificate.back_fontfamily_date) || 'Pacifico',
    back_font_align_date: props.certificate.back_font_align_date,
    back_font_vertical_align_date: props.certificate.back_font_vertical_align_date,
    back_position_date_x: props.certificate.back_position_date_x,
    back_position_date_y: props.certificate.back_position_date_y,
    back_font_size_date: props.certificate.back_font_size_date,
    back_color_date: props.certificate.back_color_date ?? '#000000',
    back_visible_date: props.certificate.back_visible_date == 1 ? true : false,
    back_fontfamily_names: normalizeFontName(props.certificate.back_fontfamily_names) || 'Dancing Script',
    back_font_align_names: props.certificate.back_font_align_names,
    back_font_vertical_align_names: props.certificate.back_font_vertical_align_names,
    back_position_names_x: props.certificate.back_position_names_x,
    back_position_names_y: props.certificate.back_position_names_y,
    back_font_size_names: props.certificate.back_font_size_names,
    back_color_names: props.certificate.back_color_names ?? '#000000',
    back_visible_names: props.certificate.back_visible_names == 1 ? true : false,
    back_fontfamily_title: normalizeFontName(props.certificate.back_fontfamily_title) || 'Cinzel',
    back_font_align_title: props.certificate.back_font_align_title,
    back_font_vertical_align_title: props.certificate.back_font_vertical_align_title,
    back_position_title_x: props.certificate.back_position_title_x,
    back_position_title_y: props.certificate.back_position_title_y,
    back_font_size_title: props.certificate.back_font_size_title,
    back_max_width_title: props.certificate.back_max_width_title,
    back_color_title: props.certificate.back_color_title ?? '#000000',
    back_visible_title: props.certificate.back_visible_title == 1 ? true : false,
    back_fontfamily_description: normalizeFontName(props.certificate.back_fontfamily_description) || 'Poppins',
    back_font_align_description: props.certificate.back_font_align_description,
    back_font_vertical_align_description: props.certificate.back_font_vertical_align_description,
    back_position_description_x: props.certificate.back_position_description_x,
    back_position_description_y: props.certificate.back_position_description_y,
    back_font_size_description: props.certificate.back_font_size_description,
    back_max_width_description: props.certificate.back_max_width_description,
    back_text_align_description: props.certificate.back_text_align_description || 'left',
    back_color_description: props.certificate.back_color_description ?? '#000000',
    back_visible_description: props.certificate.back_visible_description == 1 ? true : false,
    back_fontfamily_course: normalizeFontName(props.certificate.back_fontfamily_course) || 'Poppins',
    back_font_align_course: props.certificate.back_font_align_course,
    back_font_vertical_align_course: props.certificate.back_font_vertical_align_course,
    back_position_course_x: props.certificate.back_position_course_x,
    back_position_course_y: props.certificate.back_position_course_y,
    back_font_size_course: props.certificate.back_font_size_course,
    back_max_width_course: props.certificate.back_max_width_course,
    back_color_course: props.certificate.back_color_course ?? '#000000',
    back_visible_course: props.certificate.back_visible_course == 1 ? true : false,
    back_fontfamily_module: normalizeFontName(props.certificate.back_fontfamily_module) || 'Poppins',
    back_font_align_module: props.certificate.back_font_align_module,
    back_font_vertical_align_module: props.certificate.back_font_vertical_align_module,
    back_position_module_x: props.certificate.back_position_module_x,
    back_position_module_y: props.certificate.back_position_module_y,
    back_font_size_module: props.certificate.back_font_size_module,
    back_max_width_module: props.certificate.back_max_width_module,
    back_color_module: props.certificate.back_color_module ?? '#000000',
    back_visible_module: props.certificate.back_visible_module == 1 ? true : false,
    back_size_qr: props.certificate.back_size_qr ?? 100,
    back_font_align_qr: props.certificate.back_font_align_qr ?? 'right',
    back_position_qr_x: props.certificate.back_position_qr_x ?? 600,
    back_position_qr_y: props.certificate.back_position_qr_y ?? 100,
    back_visible_qr: props.certificate.back_visible_qr == 1 ? true : false,
    back_qr_url: 'https://academy.globalcpaperu.com',
    back_fontfamily_grade: normalizeFontName(props.gradeConfig?.back_fontfamily_grade) || 'Montserrat',
    back_font_size_grade: props.gradeConfig?.back_font_size_grade,
    back_color_grade: props.gradeConfig?.back_color_grade ?? '#000000',
    back_position_grade_x: props.gradeConfig?.back_position_grade_x,
    back_position_grade_y: props.gradeConfig?.back_position_grade_y,
    back_visible_grade: props.gradeConfig?.back_visible_grade == 1 ? true : false,
    back_rectangle_width: props.gradeConfig?.back_rectangle_width ?? 100,
    back_rectangle_height: props.gradeConfig?.back_rectangle_height ?? 100,
    back_rectangle_color: props.gradeConfig?.back_rectangle_color ?? '#000000',
    back_show_exam_grade: props.gradeConfig?.back_show_exam_grade == 1,
    back_show_themes: props.gradeConfig?.back_show_themes != 0,
    back_exam_fontfamily: normalizeFontName(props.gradeConfig?.back_exam_fontfamily) || 'Poppins',
    back_exam_font_size: props.gradeConfig?.back_exam_font_size ?? 14,
    back_exam_color: props.gradeConfig?.back_exam_color ?? '#000000',
});

const isShowChatMenu = ref(false);
const accordians3 = ref(0);

const loadGoogleFonts = () => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${availableFonts.map(f => f.value.replace(/ /g, '+') + ':wght@400;700').join('&family=')}&display=swap`;
    document.head.appendChild(link);
};

const fontsLoaded = ref(false);
const waitForFonts = async () => {
    try {
        await document.fonts.ready;
        fontsLoaded.value = true;
    } catch (e) {
        fontsLoaded.value = true;
    }
};

const stageKey = ref(0);
const refreshStage = () => {
    stageKey.value++;
};

const syncFrontElementsWithForm = () => {
    frontElements.value.date.visible = form.visible_date;
    frontElements.value.date.x = parseFloat(form.position_date_x) || 0;
    frontElements.value.date.y = parseFloat(form.position_date_y) || 0;
    frontElements.value.date.fontSize = parseFloat(form.font_size_date) || 22;
    frontElements.value.date.fontFamily = form.fontfamily_date || 'Pacifico';
    frontElements.value.date.fontAlign = form.font_align_date;
    frontElements.value.date.verticalAlign = form.font_vertical_align_date;
    frontElements.value.date.color = form.color_date;
    frontElements.value.date.originalX = frontElements.value.date.x;
    frontElements.value.date.originalY = frontElements.value.date.y;

    frontElements.value.names.visible = form.visible_names;
    frontElements.value.names.x = parseFloat(form.position_names_x) || 0;
    frontElements.value.names.y = parseFloat(form.position_names_y) || 0;
    frontElements.value.names.fontSize = parseFloat(form.font_size_names) || 28;
    frontElements.value.names.fontFamily = form.fontfamily_names || 'Dancing Script';
    frontElements.value.names.fontAlign = form.font_align_names;
    frontElements.value.names.verticalAlign = form.font_vertical_align_names;
    frontElements.value.names.color = form.color_names;
    frontElements.value.names.originalX = frontElements.value.names.x;
    frontElements.value.names.originalY = frontElements.value.names.y;

    frontElements.value.title.visible = form.visible_title;
    frontElements.value.title.x = parseFloat(form.position_title_x) || 0;
    frontElements.value.title.y = parseFloat(form.position_title_y) || 0;
    frontElements.value.title.fontSize = parseFloat(form.font_size_title) || 32;
    frontElements.value.title.fontFamily = form.fontfamily_title || 'Cinzel';
    frontElements.value.title.fontAlign = form.font_align_title;
    frontElements.value.title.verticalAlign = form.font_vertical_align_title;
    frontElements.value.title.maxWidth = parseFloat(form.max_width_title) || stageWidth.value;
    frontElements.value.title.color = form.color_title;
    frontElements.value.title.originalX = frontElements.value.title.x;
    frontElements.value.title.originalY = frontElements.value.title.y;

    frontElements.value.description.visible = form.visible_description;
    frontElements.value.description.x = parseFloat(form.position_description_x) || 0;
    frontElements.value.description.y = parseFloat(form.position_description_y) || 0;
    frontElements.value.description.fontSize = parseFloat(form.font_size_description) || 18;
    frontElements.value.description.fontFamily = form.fontfamily_description || 'Poppins';
    frontElements.value.description.fontAlign = form.font_align_description;
    frontElements.value.description.verticalAlign = form.font_vertical_align_description;
    frontElements.value.description.maxWidth = parseFloat(form.max_width_description) || 600;
    frontElements.value.description.color = form.color_description;
    frontElements.value.description.originalX = frontElements.value.description.x;
    frontElements.value.description.originalY = frontElements.value.description.y;

    frontElements.value.qr.visible = form.visible_image_qr;
    frontElements.value.qr.x = parseFloat(form.position_qr_x) || 0;
    frontElements.value.qr.y = parseFloat(form.position_qr_y) || 0;
    frontElements.value.qr.size = parseFloat(form.size_qr) || 100;
    frontElements.value.qr.align = form.font_align_qr;
    frontElements.value.qr.originalX = frontElements.value.qr.x;
    frontElements.value.qr.originalY = frontElements.value.qr.y;

    nextTick(() => refreshStage());
};

const syncBackElementsWithForm = () => {
    backElements.value.date.visible = form.back_visible_date;
    backElements.value.date.x = parseFloat(form.back_position_date_x) || 0;
    backElements.value.date.y = parseFloat(form.back_position_date_y) || 0;
    backElements.value.date.fontSize = parseFloat(form.back_font_size_date) || 22;
    backElements.value.date.fontFamily = form.back_fontfamily_date || 'Pacifico';
    backElements.value.date.fontAlign = form.back_font_align_date;
    backElements.value.date.verticalAlign = form.back_font_vertical_align_date;
    backElements.value.date.color = form.back_color_date;

    backElements.value.names.visible = form.back_visible_names;
    backElements.value.names.x = parseFloat(form.back_position_names_x) || 0;
    backElements.value.names.y = parseFloat(form.back_position_names_y) || 0;
    backElements.value.names.fontSize = parseFloat(form.back_font_size_names) || 28;
    backElements.value.names.fontFamily = form.back_fontfamily_names || 'Dancing Script';
    backElements.value.names.fontAlign = form.back_font_align_names;
    backElements.value.names.verticalAlign = form.back_font_vertical_align_names;
    backElements.value.names.color = form.back_color_names;

    backElements.value.title.visible = form.back_visible_title;
    backElements.value.title.x = parseFloat(form.back_position_title_x) || 0;
    backElements.value.title.y = parseFloat(form.back_position_title_y) || 0;
    backElements.value.title.fontSize = parseFloat(form.back_font_size_title) || 32;
    backElements.value.title.fontFamily = form.back_fontfamily_title || 'Cinzel';
    backElements.value.title.fontAlign = form.back_font_align_title;
    backElements.value.title.verticalAlign = form.back_font_vertical_align_title;
    backElements.value.title.maxWidth = parseFloat(form.back_max_width_title) || stageWidth.value;
    backElements.value.title.color = form.back_color_title;

    backElements.value.description.visible = form.back_visible_description;
    backElements.value.description.x = parseFloat(form.back_position_description_x) || 0;
    backElements.value.description.y = parseFloat(form.back_position_description_y) || 0;
    backElements.value.description.fontSize = parseFloat(form.back_font_size_description) || 18;
    backElements.value.description.fontFamily = form.back_fontfamily_description || 'Poppins';
    backElements.value.description.fontAlign = form.back_text_align_description;
    backElements.value.description.verticalAlign = form.back_font_vertical_align_description;
    backElements.value.description.maxWidth = parseFloat(form.back_max_width_description) || 600;
    backElements.value.description.color = form.back_color_description;
    backElements.value.description.text = form.back_description;

    backElements.value.course.visible = form.back_visible_course;
    backElements.value.course.x = parseFloat(form.back_position_course_x) || 0;
    backElements.value.course.y = parseFloat(form.back_position_course_y) || 0;
    backElements.value.course.fontSize = parseFloat(form.back_font_size_course) || 16;
    backElements.value.course.fontFamily = form.back_fontfamily_course || 'Poppins';
    backElements.value.course.fontAlign = form.back_font_align_course;
    backElements.value.course.verticalAlign = form.back_font_vertical_align_course;
    backElements.value.course.maxWidth = parseFloat(form.back_max_width_course) || 600;
    backElements.value.course.color = form.back_color_course;
    backElements.value.course.contentType = form.back_content_type;
    backElements.value.course.showExamGrade = form.back_show_exam_grade;
    backElements.value.course.showThemes = form.back_show_themes;
    backElements.value.course.text = generateCourseContent();

    backElements.value.module.visible = form.back_visible_module;
    backElements.value.module.x = parseFloat(form.back_position_module_x) || 0;
    backElements.value.module.y = parseFloat(form.back_position_module_y) || 0;
    backElements.value.module.fontSize = parseFloat(form.back_font_size_module) || 16;
    backElements.value.module.fontFamily = form.back_fontfamily_module || 'Poppins';
    backElements.value.module.fontAlign = form.back_font_align_module;
    backElements.value.module.verticalAlign = form.back_font_vertical_align_module;
    backElements.value.module.maxWidth = parseFloat(form.back_max_width_module) || 600;
    backElements.value.module.color = form.back_color_module;
    backElements.value.module.contentType = form.back_content_type_module;
    backElements.value.module.text = generateModuleContent();

    backElements.value.qr.visible = form.back_visible_qr;
    backElements.value.qr.x = parseFloat(form.back_position_qr_x) || 0;
    backElements.value.qr.y = parseFloat(form.back_position_qr_y) || 0;
    backElements.value.qr.size = parseFloat(form.back_size_qr) || 100;
    backElements.value.qr.align = form.back_font_align_qr;

    backElements.value.grade.visible = form.back_visible_grade;
    backElements.value.grade.x = parseFloat(form.back_position_grade_x) || 0;
    backElements.value.grade.y = parseFloat(form.back_position_grade_y) || 0;
    backElements.value.grade.fontSize = parseFloat(form.back_font_size_grade) || 20;
    backElements.value.grade.fontFamily = form.back_fontfamily_grade || 'Montserrat';
    backElements.value.grade.color = form.back_color_grade;
    backElements.value.grade.rectangleWidth = parseFloat(form.back_rectangle_width) || 100;
    backElements.value.grade.rectangleHeight = parseFloat(form.back_rectangle_height) || 100;
    backElements.value.grade.rectangleColor = form.back_rectangle_color;

    nextTick(() => refreshStage());
};

const generateCourseContent = () => {
    if (form.for_module) return '';

    let content = '';
    if (backElements.value.course.contentType === 'list') {
        content = 'Contenido del curso:\n• Tema 1: Introducción\n• Tema 2: Desarrollo\n• Tema 3: Conclusión';
        if (backElements.value.course.showThemes) {
            content += '\n\nTemas cubiertos:\n- Fundamentos básicos\n- Aplicaciones prácticas\n- Evaluación final';
        }
        if (backElements.value.course.showExamGrade) {
            content += `\n\nNota del examen: ${previewData.finalGrade}/100`;
        }
    } else {
        content = 'Curso\t\tHoras\nIntroducción\t4\nDesarrollo\t8\nPráctica\t6\nEvaluación\t2\nTotal\t\t20 horas';
    }
    return content;
};

const generateModuleContent = () => {
    if (!form.for_module) return '';

    if (backElements.value.module.contentType === 'list') {
        return 'Módulo: Desarrollo Avanzado\n\nContenido:\n• Lección 1: Configuración\n• Lección 2: Implementación\n• Lección 3: Pruebas\n• Lección 4: Despliegue';
    } else {
        return 'Módulo\t\tDuración\nConfiguración\t2h\nImplementación\t4h\nPruebas\t\t2h\nDespliegue\t1h\nTotal\t\t9 horas';
    }
};

const updateFormFromFrontElements = () => {
    form.position_date_x = Math.round(frontElements.value.date.x);
    form.position_date_y = Math.round(frontElements.value.date.y);
    form.visible_date = frontElements.value.date.visible;

    form.position_names_x = Math.round(frontElements.value.names.x);
    form.position_names_y = Math.round(frontElements.value.names.y);
    form.visible_names = frontElements.value.names.visible;

    form.position_title_x = Math.round(frontElements.value.title.x);
    form.position_title_y = Math.round(frontElements.value.title.y);
    form.visible_title = frontElements.value.title.visible;

    form.position_description_x = Math.round(frontElements.value.description.x);
    form.position_description_y = Math.round(frontElements.value.description.y);
    form.visible_description = frontElements.value.description.visible;

    form.position_qr_x = Math.round(frontElements.value.qr.x);
    form.position_qr_y = Math.round(frontElements.value.qr.y);
    form.visible_image_qr = frontElements.value.qr.visible;
};

const updateFormFromBackElements = () => {
    form.back_position_date_x = Math.round(backElements.value.date.x);
    form.back_position_date_y = Math.round(backElements.value.date.y);
    form.back_visible_date = backElements.value.date.visible;

    form.back_position_names_x = Math.round(backElements.value.names.x);
    form.back_position_names_y = Math.round(backElements.value.names.y);
    form.back_visible_names = backElements.value.names.visible;

    form.back_position_title_x = Math.round(backElements.value.title.x);
    form.back_position_title_y = Math.round(backElements.value.title.y);
    form.back_visible_title = backElements.value.title.visible;

    form.back_position_description_x = Math.round(backElements.value.description.x);
    form.back_position_description_y = Math.round(backElements.value.description.y);
    form.back_visible_description = backElements.value.description.visible;

    form.back_position_course_x = Math.round(backElements.value.course.x);
    form.back_position_course_y = Math.round(backElements.value.course.y);
    form.back_visible_course = backElements.value.course.visible;

    form.back_position_module_x = Math.round(backElements.value.module.x);
    form.back_position_module_y = Math.round(backElements.value.module.y);
    form.back_visible_module = backElements.value.module.visible;

    form.back_position_qr_x = Math.round(backElements.value.qr.x);
    form.back_position_qr_y = Math.round(backElements.value.qr.y);
    form.back_visible_qr = backElements.value.qr.visible;

    form.back_position_grade_x = Math.round(backElements.value.grade.x);
    form.back_position_grade_y = Math.round(backElements.value.grade.y);
    form.back_visible_grade = backElements.value.grade.visible;
};

const loadBackgroundImage = async (imageUrl, isBack = false) => {
    const currentImg = isBack ? backBackgroundImage.value : backgroundImage.value;
    if (currentImg?.src === imageUrl) {
        return currentImg;
    }

    isLoadingImages.value = true;

    return new Promise((resolve) => {
        const img = new window.Image();
        img.crossOrigin = 'Anonymous';

        img.onload = () => {
            if (!isBack) {
                if (!backgroundImage.value) {
                    stageWidth.value = img.width;
                    stageHeight.value = img.height;
                }
                backgroundImage.value = img;
            } else {
                backBackgroundImage.value = img;
            }
            isLoadingImages.value = false;
            resolve(img);
        };

        img.onerror = () => {
            console.error('Error loading background image', imageUrl);
            isLoadingImages.value = false;
            resolve(null);
        };

        img.src = imageUrl;
    });
};

const handleDragEnd = (elementType, side, event) => {
    const newX = event.target.x();
    const newY = event.target.y();

    if (side === 'front') {
        if (frontElements.value[elementType]) {
            frontElements.value[elementType].x = newX;
            frontElements.value[elementType].y = newY;
        }
        updateFormFromFrontElements();
    } else {
        if (backElements.value[elementType]) {
            backElements.value[elementType].x = newX;
            backElements.value[elementType].y = newY;
        }
        updateFormFromBackElements();
    }

    Swal2.fire({
        title: 'Posición actualizada',
        text: `X: ${Math.round(newX)}, Y: ${Math.round(newY)}`,
        icon: 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000
    });
};

const getPlainFormData = (actionType) => {
    const plain = {};
    for (const key in form) {
        const val = form[key];
        if (typeof val !== 'function') {
            plain[key] = val;
        }
    }
    plain.action_type = actionType;
    return plain;
};

const updateCertificateData = async (actionType) => {
    if (activeTab.value === 'front') {
        updateFormFromFrontElements();
    } else {
        updateFormFromBackElements();
    }

    try {
        const response = await axios({
            method: 'post',
            url: route('aca_certificate_update_info'),
            data: getPlainFormData(actionType)
        });

        const data = response.data || {};

        if (data.image && activeTab.value === 'front') {
            const currentImageUrl = backgroundImage.value?.src;
            if (currentImageUrl !== data.image) {
                await loadBackgroundImage(data.image, false);
            }
        }
        if (data.back_image && form.has_back && activeTab.value === 'back') {
            const currentBackUrl = backBackgroundImage.value?.src;
            if (currentBackUrl !== data.back_image) {
                await loadBackgroundImage(data.back_image, true);
            }
        }

        Swal2.fire({
            title: 'Guardado',
            text: 'Cambios guardados correctamente',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000
        });
    } catch (error) {
        const serverData = error?.response?.data;
        const httpStatus = error?.response?.status;

        if (serverData && httpStatus && httpStatus < 500) {
            if (serverData.image && activeTab.value === 'front') {
                const currentImageUrl = backgroundImage.value?.src;
                if (currentImageUrl !== serverData.image) {
                    await loadBackgroundImage(serverData.image, false).catch(() => {});
                }
            }
            if (serverData.back_image && form.has_back && activeTab.value === 'back') {
                const currentBackUrl = backBackgroundImage.value?.src;
                if (currentBackUrl !== serverData.back_image) {
                    await loadBackgroundImage(serverData.back_image, true).catch(() => {});
                }
            }
            Swal2.fire({
                title: 'Guardado',
                text: 'Datos guardados correctamente',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
        } else {
            console.error('Error al guardar sección:', error);
            Swal2.fire({
                title: 'Error',
                text: 'No se pudieron guardar los cambios. Verifique la conexión.',
                icon: 'error',
                confirmButtonText: 'Ok'
            });
        }
    }
};

const saveAllChanges = async () => {
    updateFormFromFrontElements();
    updateFormFromBackElements();

    isSavingAll.value = true;

    const frontActionTypes = [1, 2, 3, 4, 5];
    const backActionTypes = form.has_back ? [6, 7, 8, 9, 14, 15] : [];
    if (form.has_back && !form.for_module && form.back_content_show_course) backActionTypes.push(12);
    if (form.has_back && form.for_module && form.back_content_show_module) backActionTypes.push(13);

    const allActionTypes = [...frontActionTypes, ...backActionTypes];

    let savedCount = 0;
    let lastFrontImage = null;
    let lastBackImage = null;
    let lastFrontUrl = backgroundImage.value?.src;
    let lastBackUrl = backBackgroundImage.value?.src;

    try {
        const results = await Promise.allSettled(
            allActionTypes.map(actionType =>
                axios({
                    method: 'post',
                    url: route('aca_certificate_update_info'),
                    data: getPlainFormData(actionType)
                })
            )
        );

        results.forEach((result, index) => {
            if (result.status === 'fulfilled') {
                savedCount++;
                const data = result.value?.data || {};
                if (data.image) lastFrontImage = data.image;
                if (data.back_image) lastBackImage = data.back_image;
            } else {
                const serverData = result.reason?.response?.data;
                if (serverData?.image) lastFrontImage = serverData.image;
                if (serverData?.back_image) lastBackImage = serverData.back_image;
                const status = result.reason?.response?.status;
                if (status && status < 500) savedCount++;
            }
        });

        if (lastFrontImage && lastFrontUrl !== lastFrontImage) {
            await loadBackgroundImage(lastFrontImage, false).catch(() => {});
        }
        if (lastBackImage && form.has_back && lastBackUrl !== lastBackImage) {
            await loadBackgroundImage(lastBackImage, true).catch(() => {});
        }

        const total = allActionTypes.length;
        if (savedCount === total) {
            Swal2.fire({
                title: '¡Todo guardado!',
                text: 'Todos los cambios se guardaron correctamente.',
                icon: 'success',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else if (savedCount > 0) {
            Swal2.fire({
                title: 'Guardado parcial',
                text: `Se guardaron ${savedCount} de ${total} secciones.`,
                icon: 'warning',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000
            });
        } else {
            Swal2.fire({
                title: 'Error',
                text: 'No se pudo guardar ningún cambio. Verifique la conexión.',
                icon: 'error',
                confirmButtonText: 'Ok'
            });
        }
    } catch (error) {
        console.error('Error inesperado al guardar todo:', error);
        Swal2.fire({
            title: 'Error',
            text: 'Ocurrió un error inesperado.',
            icon: 'error',
            confirmButtonText: 'Ok'
        });
    } finally {
        isSavingAll.value = false;
    }
};

const updateCertificateStatus = async () => {
    form.action_type = 99;
    form.processing = true;

    try {
        await axios({
            method: 'post',
            url: route('aca_certificate_update_info'),
            data: form
        });

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Se registró correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        console.error('Error updating status:', error);
        Swal2.fire({
            title: 'Error',
            text: 'Error al guardar el estado',
            icon: 'error',
            confirmButtonText: 'Ok'
        });
    } finally {
        form.processing = false;
    }
};

const resetElementPosition = (elementType, side) => {
    if (side === 'front' && frontElements.value[elementType]) {
        frontElements.value[elementType].x = frontElements.value[elementType].originalX;
        frontElements.value[elementType].y = frontElements.value[elementType].originalY;
        updateFormFromFrontElements();
        Swal2.fire('Posición restablecida', '', 'success');
    } else if (side === 'back' && backElements.value[elementType]) {
        backElements.value[elementType].x = backElements.value[elementType].originalX;
        backElements.value[elementType].y = backElements.value[elementType].originalY;
        updateFormFromBackElements();
        Swal2.fire('Posición restablecida', '', 'success');
    }
};

watch(() => [
    form.visible_date, form.position_date_x, form.position_date_y, form.font_size_date,
    form.fontfamily_date, form.font_align_date, form.font_vertical_align_date, form.color_date
], () => syncFrontElementsWithForm());

watch(() => [
    form.visible_names, form.position_names_x, form.position_names_y, form.font_size_names,
    form.fontfamily_names, form.font_align_names, form.font_vertical_align_names, form.color_names
], () => syncFrontElementsWithForm());

watch(() => [
    form.visible_title, form.position_title_x, form.position_title_y, form.font_size_title,
    form.fontfamily_title, form.font_align_title, form.font_vertical_align_title,
    form.max_width_title, form.color_title
], () => syncFrontElementsWithForm());

watch(() => [
    form.visible_description, form.position_description_x, form.position_description_y,
    form.font_size_description, form.fontfamily_description, form.font_align_description,
    form.font_vertical_align_description, form.max_width_description, form.color_description
], () => syncFrontElementsWithForm());

watch(() => [
    form.visible_image_qr, form.position_qr_x, form.position_qr_y, form.size_qr, form.font_align_qr
], () => syncFrontElementsWithForm());

watch(() => form.qr_url, (url) => generateQrCode(url, 'front'));
watch(() => form.back_qr_url, (url) => generateQrCode(url, 'back'));

watch(() => [
    form.back_visible_date, form.back_position_date_x, form.back_position_date_y,
    form.back_font_size_date, form.back_fontfamily_date, form.back_font_align_date,
    form.back_font_vertical_align_date, form.back_color_date
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_names, form.back_position_names_x, form.back_position_names_y,
    form.back_font_size_names, form.back_fontfamily_names, form.back_font_align_names,
    form.back_font_vertical_align_names, form.back_color_names
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_title, form.back_position_title_x, form.back_position_title_y,
    form.back_font_size_title, form.back_fontfamily_title, form.back_font_align_title,
    form.back_font_vertical_align_title, form.back_max_width_title, form.back_color_title
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_description, form.back_position_description_x, form.back_position_description_y,
    form.back_font_size_description, form.back_fontfamily_description, form.back_text_align_description,
    form.back_font_vertical_align_description, form.back_max_width_description, form.back_color_description,
    form.back_description
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_course, form.back_position_course_x, form.back_position_course_y,
    form.back_font_size_course, form.back_fontfamily_course, form.back_font_align_course,
    form.back_font_vertical_align_course, form.back_max_width_course, form.back_color_course,
    form.back_content_type, form.back_show_exam_grade, form.back_show_themes
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_module, form.back_position_module_x, form.back_position_module_y,
    form.back_font_size_module, form.back_fontfamily_module, form.back_font_align_module,
    form.back_font_vertical_align_module, form.back_max_width_module, form.back_color_module,
    form.back_content_type_module
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_qr, form.back_position_qr_x, form.back_position_qr_y,
    form.back_size_qr, form.back_font_align_qr
], () => syncBackElementsWithForm());

watch(() => [
    form.back_visible_grade, form.back_position_grade_x, form.back_position_grade_y,
    form.back_font_size_grade, form.back_fontfamily_grade, form.back_color_grade,
    form.back_rectangle_width, form.back_rectangle_height, form.back_rectangle_color
], () => syncBackElementsWithForm());

onMounted(async () => {
    loadGoogleFonts();
    syncFrontElementsWithForm();
    syncBackElementsWithForm();

    const backgroundUrl = getImage(form.certificate_img);
    await loadBackgroundImage(backgroundUrl, false);

    if (form.has_back && form.back_certificate_img) {
        const backUrl = getImage(form.back_certificate_img);
        await loadBackgroundImage(backUrl, true);
    }

    if (form.qr_url) generateQrCode(form.qr_url, 'front');
    if (form.back_qr_url) generateQrCode(form.back_qr_url, 'back');

    await waitForFonts();
    refreshStage();
});
</script>

<template>
    <div>
        <div class="flex gap-5 relative">
            <!-- Panel lateral de configuración -->
            <div
                class="panel p-4 flex-none max-w-xs w-full absolute xl:relative z-10 space-y-4 h-full hidden xl:block overflow-y-auto dark:bg-gray-800"
                :class="isShowChatMenu && '!block !overflow-y-auto'"
                style="max-height: calc(100vh - 2rem);"
            >
                <div class="flex justify-between items-center w-full">
                    <div class="flex items-center">
                        <h4 class="uppercase font-bold">Editar Certificado</h4>
                    </div>
                    <div>
                        <button type="button" class="xl:hidden hover:text-primary" @click="isShowChatMenu = !isShowChatMenu">
                            <icon-carets-down class="m-auto rotate-90" />
                        </button>
                    </div>
                </div>

                <!-- Botón Guardar Todo -->
                <div class="w-full">
                    <button
                        @click="saveAllChanges"
                        :disabled="isSavingAll"
                        class="btn btn-primary w-full flex items-center justify-center gap-2"
                        type="button"
                    >
                        <svg v-if="isSavingAll" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        {{ isSavingAll ? 'Guardando...' : 'Guardar todo' }}
                    </button>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200 dark:border-gray-700 mb-4">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'front' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'front'"
                    >
                        Anverso
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'back' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'back'"
                    >
                        Reverso
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'general' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray300'"
                        @click="activeTab = 'general'"
                    >
                        General
                    </button>
                </div>

                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <InputLabel for="name_certificate">Nombre</InputLabel>
                        <TextInput
                            id="name_certificate"
                            v-model="form.name_certificate"
                            placeholder="Ejemplo: Modelo 1"
                            class="bg-gray-100"
                            :disabled="activeTab !== 'general'"
                        />
                        <InputError :message="form.errors.name_certificate" class="mt-1" />
                    </div>

                    <!-- Tab ANVERSO -->
                    <template v-if="activeTab === 'front'">
                        <div class="col-span-2 mb-2">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg">
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    💡 Arrastra los elementos en la vista previa para ajustar su posición
                                </p>
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 1 }"
                                @click="accordians3 === 1 ? (accordians3 = null) : (accordians3 = 1)"
                            >
                                <icon-calendar-days class="mr-2 w-4 h-4" />
                                Fecha
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 1 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 1">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_date">Fuente</InputLabel>
                                            <select v-model="form.fontfamily_date" class="form-select text-white-dark">
                                                <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_date">Tamaño</InputLabel>
                                            <TextInput type="number" v-model.number="form.font_size_date" placeholder="22" min="8" max="200" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_date">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_date" class="form-select">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centro</option>
                                                <option value="right">Derecha</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_date">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_date" class="form-select">
                                                <option value="top">Arriba</option>
                                                <option value="center">Centro</option>
                                                <option value="bottom">Abajo</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_date_x">Posición X</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_date_x" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_date_y">Posición Y</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_date_y" />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_date">Color</InputLabel>
                                            <TextInput type="color" v-model="form.color_date" />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel value="Visible" />
                                            <input type="checkbox" v-model="form.visible_date" class="form-checkbox" />
                                        </div>
                                        <div class="col-span-4">
                                            <button @click="resetElementPosition('date', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(1)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <!-- Nombre estudiante -->
                        <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 2 }"
                                @click="accordians3 === 2 ? (accordians3 = null) : (accordians3 = 2)"
                            >
                                <icon-user-student class="w-4 h-4 mr-2" />
                                Nombre del estudiante
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 2 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 2">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_names">Fuente</InputLabel>
                                            <select v-model="form.fontfamily_names" class="form-select">
                                                <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_names">Tamaño</InputLabel>
                                            <TextInput type="number" v-model.number="form.font_size_names" min="8" max="200" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_names">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_names" class="form-select">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centro</option>
                                                <option value="right">Derecha</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_names">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_names" class="form-select">
                                                <option value="top">Arriba</option>
                                                <option value="center">Centro</option>
                                                <option value="bottom">Abajo</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_names_x">Posición X</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_names_x" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_names_y">Posición Y</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_names_y" />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_names">Color</InputLabel>
                                            <TextInput type="color" v-model="form.color_names" />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel value="Visible" />
                                            <input type="checkbox" v-model="form.visible_names" class="form-checkbox" />
                                        </div>
                                        <div class="col-span-4">
                                            <button @click="resetElementPosition('names', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(2)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <!-- Título -->
                        <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 3 }"
                                @click="accordians3 === 3 ? (accordians3 = null) : (accordians3 = 3)"
                            >
                                <icon-font class="w-4 h-4 mr-2" />
                                Título
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 3 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 3">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_title">Fuente</InputLabel>
                                            <select v-model="form.fontfamily_title" class="form-select">
                                                <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_title">Tamaño</InputLabel>
                                            <TextInput type="number" v-model.number="form.font_size_title" min="8" max="200" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_title">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_title" class="form-select">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centro</option>
                                                <option value="right">Derecha</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_title">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_title" class="form-select">
                                                <option value="top">Arriba</option>
                                                <option value="center">Centro</option>
                                                <option value="bottom">Abajo</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_title_x">Posición X</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_title_x" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_title_y">Posición Y</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_title_y" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="max_width_title">Ancho máximo</InputLabel>
                                            <TextInput type="number" v-model.number="form.max_width_title" />
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_title">Color</InputLabel>
                                            <TextInput type="color" v-model="form.color_title" />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel value="Visible" />
                                            <input type="checkbox" v-model="form.visible_title" class="form-checkbox" />
                                        </div>
                                        <div class="col-span-4">
                                            <button @click="resetElementPosition('title', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(3)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <!-- QR -->
                        <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 4 }"
                                @click="accordians3 === 4 ? (accordians3 = null) : (accordians3 = 4)"
                            >
                                <icon-qrcode class="w-4 h-4 mr-2" />
                                QR
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 4 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 4">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-4">
                                            <InputLabel for="qr_url">Enlace del QR</InputLabel>
                                            <TextInput type="url" v-model="form.qr_url" placeholder="https://ejemplo.com" class="w-full" />
                                            <p v-if="form.qr_url && !qrImageElement" class="text-xs text-yellow-500 mt-1">Generando QR…</p>
                                            <p v-if="qrImageElement" class="text-xs text-success mt-1">✓ QR generado correctamente</p>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="size_qr">Tamaño</InputLabel>
                                            <TextInput type="number" v-model.number="form.size_qr" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_qr">Alineación</InputLabel>
                                            <select v-model="form.font_align_qr" class="form-select">
                                                <option value="top-left">Esquina superior izquierda</option>
                                                <option value="top-center">Centro superior</option>
                                                <option value="top-right">Esquina superior derecha</option>
                                                <option value="bottom-left">Esquina inferior izquierda</option>
                                                <option value="bottom-center">Centro inferior</option>
                                                <option value="bottom-right">Esquina inferior derecha</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_qr_x">Posición X</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_qr_x" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_qr_y">Posición Y</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_qr_y" />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel value="Visible" />
                                            <input type="checkbox" v-model="form.visible_image_qr" class="form-checkbox" />
                                        </div>
                                        <div class="col-span-4">
                                            <button @click="resetElementPosition('qr', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(4)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <!-- Descripción -->
                        <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                            <button
                                type="button"
                                class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                :class="{ '!text-primary': accordians3 === 5 }"
                                @click="accordians3 === 5 ? (accordians3 = null) : (accordians3 = 5)"
                            >
                                <icon-info-circle-two class="w-4 h-4 mr-2" />
                                Descripción
                                <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 5 }">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                </div>
                            </button>
                            <vue-collapsible :isOpen="accordians3 === 5">
                                <div class="p-4 text-white-dark text-[13px] border-t border-[#d3d3d3] dark:border-[#1b2e4b]">
                                    <div class="grid grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <InputLabel for="fontfamily_description">Fuente</InputLabel>
                                            <select v-model="form.fontfamily_description" class="form-select">
                                                <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_size_description">Tamaño</InputLabel>
                                            <TextInput type="number" v-model.number="form.font_size_description" min="8" max="200" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_align_description">A. horizontal</InputLabel>
                                            <select v-model="form.font_align_description" class="form-select">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centro</option>
                                                <option value="right">Derecha</option>
                                                <option value="justify">Justificado</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="font_vertical_align_description">A. vertical</InputLabel>
                                            <select v-model="form.font_vertical_align_description" class="form-select">
                                                <option value="top">Arriba</option>
                                                <option value="center">Centro</option>
                                                <option value="bottom">Abajo</option>
                                            </select>
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_description_x">Posición X</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_description_x" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="position_description_y">Posición Y</InputLabel>
                                            <TextInput type="number" v-model.number="form.position_description_y" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="max_width_description">Ancho máximo</InputLabel>
                                            <TextInput type="number" v-model.number="form.max_width_description" />
                                        </div>
                                        <div class="col-span-2">
                                            <InputLabel for="text_align_description">Alineación texto</InputLabel>
                                            <select v-model="form.text_align_description" class="form-select">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centrado</option>
                                                <option value="right">Derecha</option>
                                                <option value="justify">Justificado</option>
                                            </select>
                                        </div>
                                        <div class="col-span-4">
                                            <InputLabel for="color_description">Color</InputLabel>
                                            <TextInput type="color" v-model="form.color_description" />
                                        </div>
                                        <div class="col-span-4 flex items-center justify-between">
                                            <InputLabel value="Visible" />
                                            <input type="checkbox" v-model="form.visible_description" class="form-checkbox" />
                                        </div>
                                        <div class="col-span-4">
                                            <button @click="resetElementPosition('description', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(5)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>

                        <!-- Descripción del Módulo (solo para for_module) -->
                        <div v-if="form.for_module" class="col-span-2">
                            <vue-collapsible isOpen="false">
                                <div
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b] border border-[#d3d3d3] dark:border-[#1b2e4b] rounded-t-lg"
                                    slot="trigger"
                                >
                                    <icon-edit class="w-4 h-4 mr-2" />
                                    Descripción del Módulo
                                </div>
                                <div class="border border-[#d3d3d3] dark:border-[#1b2e4b] border-t-0 rounded-b-lg p-4 space-y-3">
                                    <div class="col-span-4">
                                        <InputLabel value="Visible" />
                                        <input type="checkbox" v-model="form.visible_module_description" class="form-checkbox" />
                                        <p class="text-xs text-gray-400 mt-1">Solo se mostrará si el módulo tiene descripción (certificate_description en aca_modules)</p>
                                    </div>
                                    <div>
                                        <InputLabel value="Fuente" />
                                        <select v-model="form.fontfamily_module_description" class="form-select">
                                            <option v-for="font in fonts" :key="font" :value="font">{{ font }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Alineación" />
                                        <select v-model="form.text_align_module_description" class="form-select">
                                            <option value="left">Izquierda</option>
                                            <option value="center">Centrado</option>
                                            <option value="right">Derecha</option>
                                            <option value="justify">Justificado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Tamaño" />
                                        <input type="range" v-model="form.font_size_module_description" min="8" max="36" class="w-full" />
                                        <span class="text-xs">{{ form.font_size_module_description }}px</span>
                                    </div>
                                    <div>
                                        <InputLabel value="Ancho máximo" />
                                        <input type="range" v-model="form.max_width_module_description" min="200" max="1000" step="50" class="w-full" />
                                        <span class="text-xs">{{ form.max_width_module_description }}px</span>
                                    </div>
                                    <div>
                                        <InputLabel value="Color" />
                                        <TextInput type="color" v-model="form.color_module_description" />
                                    </div>
                                    <div class="col-span-4 flex items-center justify-between">
                                        <button @click="resetElementPosition('moduleDescription', 'front')" class="btn btn-warning btn-sm w-full">Resetear posición</button>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button @click="updateCertificateData(11)" class="btn btn-success">Guardar</button>
                                    </div>
                                </div>
                            </vue-collapsible>
                        </div>
                    </template>

                    <!-- Tab REVERSO -->
                    <template v-if="activeTab === 'back'">
                        <div class="col-span-2 mb-2">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-2 rounded-lg">
                                <p class="text-xs text-blue-600 dark:text-blue-400">
                                    💡 Arrastra los elementos en la vista previa para ajustar su posición
                                </p>
                            </div>
                        </div>

                        <div class="col-span-2 mb-4">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.has_back" type="checkbox" class="form-checkbox text-primary" />
                                <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Habilitar reverso</span>
                            </label>
                        </div>

                        <div class="col-span-2 mb-4" v-if="form.has_back">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.has_reverse" type="checkbox" class="form-checkbox text-primary" />
                                <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Incluir reverso en ZIP</span>
                            </label>
                        </div>

                        <template v-if="form.has_back">
                            <div class="col-span-2 mb-4">
                                <label class="flex items-center cursor-pointer">
                                    <input v-model="form.back_content_show_manual" type="checkbox" class="form-checkbox" />
                                    <span class="ltr:ml-2 text-white-dark">Mostrar descripción manual</span>
                                </label>
                            </div>

                            <div class="col-span-2 mb-4" v-if="form.back_content_show_manual">
                                <InputLabel for="back_description">Descripción</InputLabel>
                                <textarea v-model="form.back_description" rows="3" class="form-textarea"></textarea>
                            </div>

                            <!-- Fecha Reverso -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 21 }"
                                    @click="accordians3 === 21 ? (accordians3 = null) : (accordians3 = 21)"
                                >
                                    <icon-calendar-days class="mr-2 w-4 h-4" />
                                    Fecha
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 21 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 21">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_date" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_date" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. horizontal</InputLabel>
                                                <select v-model="form.back_font_align_date" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_date" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_date_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_date_y" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_date" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_date" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(6)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Nombre Reverso -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 22 }"
                                    @click="accordians3 === 22 ? (accordians3 = null) : (accordians3 = 22)"
                                >
                                    <icon-user-student class="w-4 h-4 mr-2" />
                                    Nombre estudiante
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 22 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 22">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_names" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_names" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. horizontal</InputLabel>
                                                <select v-model="form.back_font_align_names" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_names" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_names_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_names_y" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_names" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_names" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(7)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Título Reverso -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 23 }"
                                    @click="accordians3 === 23 ? (accordians3 = null) : (accordians3 = 23)"
                                >
                                    <icon-font class="w-4 h-4 mr-2" />
                                    Título
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 23 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 23">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_title" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_title" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. horizontal</InputLabel>
                                                <select v-model="form.back_font_align_title" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_title" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_title_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_title_y" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Ancho máximo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_max_width_title" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_title" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_title" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(8)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Descripción Manual Reverso -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 24 }"
                                    @click="accordians3 === 24 ? (accordians3 = null) : (accordians3 = 24)"
                                >
                                    <icon-info-circle-two class="w-4 h-4 mr-2" />
                                    Descripción Manual
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 24 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 24">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_description" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_description" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Alineación</InputLabel>
                                                <select v-model="form.back_text_align_description" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                    <option value="justify">Justificado</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_description" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_description_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_description_y" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Ancho máximo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_max_width_description" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_description" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_description" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(9)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Contenido Curso -->
                            <div v-if="!form.for_module && form.back_content_show_course" class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 25 }"
                                    @click="accordians3 === 25 ? (accordians3 = null) : (accordians3 = 25)"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    Contenido del Curso
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 25 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 25">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_course" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_course" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Alineación</InputLabel>
                                                <select v-model="form.back_font_align_course" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_course" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_course_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_course_y" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Ancho máximo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_max_width_course" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_course" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tipo contenido</InputLabel>
                                                <select v-model="form.back_content_type" class="form-select">
                                                    <option value="list">Listado</option>
                                                    <option value="table">Tabla</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2 flex items-center">
                                                <input type="checkbox" v-model="form.back_show_exam_grade" class="form-checkbox mr-2" />
                                                <span>Mostrar nota examen</span>
                                            </div>
                                            <div class="col-span-2 flex items-center">
                                                <input type="checkbox" v-model="form.back_show_themes" class="form-checkbox mr-2" />
                                                <span>Mostrar temas</span>
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_course" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(12)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Contenido Módulo -->
                            <div v-if="form.for_module && form.back_content_show_module" class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 26 }"
                                    @click="accordians3 === 26 ? (accordians3 = null) : (accordians3 = 26)"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Contenido del Módulo
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 26 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 26">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_module" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_module" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Alineación</InputLabel>
                                                <select v-model="form.back_font_align_module" class="form-select">
                                                    <option value="left">Izquierda</option>
                                                    <option value="center">Centro</option>
                                                    <option value="right">Derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>A. vertical</InputLabel>
                                                <select v-model="form.back_font_vertical_align_module" class="form-select">
                                                    <option value="top">Arriba</option>
                                                    <option value="center">Centro</option>
                                                    <option value="bottom">Abajo</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_module_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_module_y" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Ancho máximo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_max_width_module" />
                                            </div>
                                            <div class="col-span-4">
                                                <InputLabel>Color</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_module" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tipo contenido</InputLabel>
                                                <select v-model="form.back_content_type_module" class="form-select">
                                                    <option value="list">Listado</option>
                                                    <option value="table">Tabla</option>
                                                </select>
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_module" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(13)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- QR Reverso -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 27 }"
                                    @click="accordians3 === 27 ? (accordians3 = null) : (accordians3 = 27)"
                                >
                                    <icon-qrcode class="w-4 h-4 mr-2" />
                                    QR Reverso
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 27 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 27">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-4">
                                                <InputLabel>Enlace del QR</InputLabel>
                                                <TextInput type="url" v-model="form.back_qr_url" placeholder="https://ejemplo.com" class="w-full" />
                                                <p v-if="form.back_qr_url && !backQrImageElement" class="text-xs text-yellow-500 mt-1">Generando QR…</p>
                                                <p v-if="backQrImageElement" class="text-xs text-success mt-1">✓ QR generado correctamente</p>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_size_qr" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Alineación</InputLabel>
                                                <select v-model="form.back_font_align_qr" class="form-select">
                                                    <option value="top-left">Sup. izquierda</option>
                                                    <option value="top-center">Sup. centro</option>
                                                    <option value="top-right">Sup. derecha</option>
                                                    <option value="bottom-left">Inf. izquierda</option>
                                                    <option value="bottom-center">Inf. centro</option>
                                                    <option value="bottom-right">Inf. derecha</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_qr_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_qr_y" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_qr" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(14)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>

                            <!-- Nota Final -->
                            <div class="col-span-2 border border-[#d3d3d3] dark:border-[#1b2e4b] rounded">
                                <button
                                    type="button"
                                    class="p-2.5 w-full flex items-center text-white-dark dark:bg-[#1b2e4b]"
                                    :class="{ '!text-primary': accordians3 === 28 }"
                                    @click="accordians3 === 28 ? (accordians3 = null) : (accordians3 = 28)"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Nota Final
                                    <div class="ltr:ml-auto rtl:mr-auto" :class="{ 'rotate-180': accordians3 === 28 }">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 9L12 15L5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                                    </div>
                                </button>
                                <vue-collapsible :isOpen="accordians3 === 28">
                                    <div class="p-4 border-t">
                                        <div class="grid grid-cols-4 gap-4">
                                            <div class="col-span-2">
                                                <InputLabel>Fuente</InputLabel>
                                                <select v-model="form.back_fontfamily_grade" class="form-select">
                                                    <option v-for="font in availableFonts" :key="font.value" :value="font.value" :style="{ fontFamily: font.value }">{{ font.label }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Tamaño</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_font_size_grade" min="8" max="200" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición X</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_grade_x" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Posición Y</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_position_grade_y" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Color texto</InputLabel>
                                                <TextInput type="color" v-model="form.back_color_grade" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Ancho rectángulo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_rectangle_width" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Alto rectángulo</InputLabel>
                                                <TextInput type="number" v-model.number="form.back_rectangle_height" />
                                            </div>
                                            <div class="col-span-2">
                                                <InputLabel>Color rectángulo</InputLabel>
                                                <TextInput type="color" v-model="form.back_rectangle_color" />
                                            </div>
                                            <div class="col-span-4 flex items-center justify-between">
                                                <InputLabel value="Visible" />
                                                <input type="checkbox" v-model="form.back_visible_grade" class="form-checkbox" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end mt-4">
                                            <button @click="updateCertificateData(15)" class="btn btn-success">Guardar</button>
                                        </div>
                                    </div>
                                </vue-collapsible>
                            </div>
                        </template>
                    </template>

                    <!-- Tab GENERAL -->
                    <template v-if="activeTab === 'general'">
                        <div class="col-span-2 mb-4">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.for_module" type="checkbox" class="form-checkbox text-primary" />
                                <span class="ltr:ml-2 rtl:mr-2 text-white-dark font-medium">Certificado para módulos</span>
                            </label>
                            <p class="text-xs text-gray-400 mt-1">Al descargar desde un módulo, mostrará el nombre del módulo</p>
                        </div>
                        <div class="col-span-2">
                            <label class="flex items-center cursor-pointer">
                                <input v-model="form.state" type="checkbox" class="form-checkbox" />
                                <span class="text-white-dark">Activo</span>
                            </label>
                        </div>
                        <div class="col-span-2">
                            <button @click="updateCertificateStatus" class="btn btn-primary w-full" type="button">
                                <svg v-show="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                Guardar datos principales
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Overlay para móvil -->
            <div
                class="bg-black/60 z-[5] w-full h-full absolute rounded-md hidden"
                :class="isShowChatMenu && '!block xl:!hidden'"
                @click="isShowChatMenu = !isShowChatMenu"
            ></div>

            <!-- Área de vista previa con Konva -->
            <div class="panel flex-1 space-y-4 p-4">
                <div class="flex justify-between items-center w-full">
                    <div>
                        <button type="button" class="xl:hidden hover:text-primary" @click="isShowChatMenu = !isShowChatMenu">
                            <icon-menu />
                        </button>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            💡 Arrastra los elementos para ajustar su posición
                        </div>
                        <h4 class="uppercase font-bold">
                            <span v-if="activeTab === 'front'">Vista previa del anverso</span>
                            <span v-else-if="activeTab === 'back' && form.has_back">Vista previa del reverso</span>
                            <span v-else-if="activeTab === 'back' && !form.has_back">Reverso no habilitado</span>
                            <span v-else>Vista previa</span>
                        </h4>
                    </div>
                    <button
                        @click="saveAllChanges"
                        :disabled="isSavingAll"
                        class="btn btn-primary btn-sm flex items-center gap-2"
                        type="button"
                    >
                        <svg v-if="isSavingAll" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        {{ isSavingAll ? 'Guardando...' : 'Guardar todo' }}
                    </button>
                </div>

                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>

                <!-- Mensaje cuando el reverso no está habilitado -->
                <div v-if="activeTab === 'back' && !form.has_back" class="flex flex-col items-center justify-center h-64 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-center px-4">
                        Habilite el reverso del certificado en la pestaña "Reverso"<br>
                        <span class="text-sm">para comenzar a configurar su contenido</span>
                    </p>
                </div>

                <!-- Indicador de carga inicial -->
                <div v-else-if="isLoadingImages && !backgroundImage && !backBackgroundImage" class="flex flex-col items-center justify-center h-64">
                    <svg class="animate-spin h-8 w-8 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Cargando vista previa...</p>
                </div>

                <!-- Canvas Konva -->
                <div v-else class="canvas-container" style="overflow-x: auto; text-align: center;">
                    <v-stage
                        :key="stageKey"
                        ref="stageRef"
                        :config="{
                            width: stageWidth,
                            height: stageHeight,
                            draggable: false
                        }"
                    >
                        <v-layer>
                            <!-- Imagen de fondo del anverso -->
                            <v-image
                                v-if="activeTab === 'front' && backgroundImage"
                                :config="{
                                    image: backgroundImage,
                                    width: stageWidth,
                                    height: stageHeight
                                }"
                            />

                            <!-- Imagen de fondo del reverso -->
                            <v-image
                                v-if="activeTab === 'back' && backBackgroundImage"
                                :config="{
                                    image: backBackgroundImage,
                                    width: stageWidth,
                                    height: stageHeight
                                }"
                            />

                            <!-- Elementos del ANVERSO -->
                            <template v-if="activeTab === 'front'">
                                <v-text
                                    v-if="frontElements.date.visible"
                                    :config="{
                                        text: frontElements.date.text,
                                        x: frontElements.date.x,
                                        y: frontElements.date.y,
                                        fontSize: Number(frontElements.date.fontSize),
                                        fontFamily: frontElements.date.fontFamily,
                                        fill: frontElements.date.color,
                                        align: frontElements.date.fontAlign,
                                        draggable: frontElements.date.draggable,
                                        width: stageWidth,
                                        onDragEnd: (e) => handleDragEnd('date', 'front', e)
                                    }"
                                />

                                <v-text
                                    v-if="frontElements.names.visible"
                                    :config="{
                                        text: frontElements.names.text,
                                        x: frontElements.names.x,
                                        y: frontElements.names.y,
                                        fontSize: Number(frontElements.names.fontSize),
                                        fontFamily: frontElements.names.fontFamily,
                                        fill: frontElements.names.color,
                                        align: frontElements.names.fontAlign,
                                        draggable: frontElements.names.draggable,
                                        width: stageWidth,
                                        onDragEnd: (e) => handleDragEnd('names', 'front', e)
                                    }"
                                />

                                <v-text
                                    v-if="frontElements.title.visible"
                                    :config="{
                                        text: frontElements.title.text,
                                        x: frontElements.title.x,
                                        y: frontElements.title.y,
                                        fontSize: Number(frontElements.title.fontSize),
                                        fontFamily: frontElements.title.fontFamily,
                                        fill: frontElements.title.color,
                                        align: frontElements.title.fontAlign,
                                        draggable: frontElements.title.draggable,
                                        width: Number(frontElements.title.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('title', 'front', e)
                                    }"
                                />

                                <v-text
                                    v-if="frontElements.description.visible"
                                    :config="{
                                        text: frontElements.description.text,
                                        x: frontElements.description.x,
                                        y: frontElements.description.y,
                                        fontSize: Number(frontElements.description.fontSize),
                                        fontFamily: frontElements.description.fontFamily,
                                        fill: frontElements.description.color,
                                        align: frontElements.description.fontAlign,
                                        draggable: frontElements.description.draggable,
                                        width: Number(frontElements.description.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('description', 'front', e)
                                    }"
                                />

                                <!-- QR Code anverso -->
                                <v-image
                                    v-if="frontElements.qr.visible && qrImageElement"
                                    :config="{
                                        image: qrImageElement,
                                        x: frontElements.qr.x,
                                        y: frontElements.qr.y,
                                        width: Number(frontElements.qr.size),
                                        height: Number(frontElements.qr.size),
                                        draggable: true,
                                        onDragEnd: (e) => handleDragEnd('qr', 'front', e)
                                    }"
                                />
                            </template>

                            <!-- Elementos del REVERSO -->
                            <template v-if="activeTab === 'back' && form.has_back">
                                <v-text
                                    v-if="backElements.date.visible"
                                    :config="{
                                        text: backElements.date.text,
                                        x: backElements.date.x,
                                        y: backElements.date.y,
                                        fontSize: Number(backElements.date.fontSize),
                                        fontFamily: backElements.date.fontFamily,
                                        fill: backElements.date.color,
                                        align: backElements.date.fontAlign,
                                        draggable: backElements.date.draggable,
                                        width: stageWidth,
                                        onDragEnd: (e) => handleDragEnd('date', 'back', e)
                                    }"
                                />

                                <v-text
                                    v-if="backElements.names.visible"
                                    :config="{
                                        text: backElements.names.text,
                                        x: backElements.names.x,
                                        y: backElements.names.y,
                                        fontSize: Number(backElements.names.fontSize),
                                        fontFamily: backElements.names.fontFamily,
                                        fill: backElements.names.color,
                                        align: backElements.names.fontAlign,
                                        draggable: backElements.names.draggable,
                                        width: stageWidth,
                                        onDragEnd: (e) => handleDragEnd('names', 'back', e)
                                    }"
                                />

                                <v-text
                                    v-if="backElements.title.visible"
                                    :config="{
                                        text: backElements.title.text,
                                        x: backElements.title.x,
                                        y: backElements.title.y,
                                        fontSize: Number(backElements.title.fontSize),
                                        fontFamily: backElements.title.fontFamily,
                                        fill: backElements.title.color,
                                        align: backElements.title.fontAlign,
                                        draggable: backElements.title.draggable,
                                        width: Number(backElements.title.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('title', 'back', e)
                                    }"
                                />

                                <v-text
                                    v-if="backElements.description.visible && form.back_content_show_manual"
                                    :config="{
                                        text: backElements.description.text,
                                        x: backElements.description.x,
                                        y: backElements.description.y,
                                        fontSize: Number(backElements.description.fontSize),
                                        fontFamily: backElements.description.fontFamily,
                                        fill: backElements.description.color,
                                        align: backElements.description.fontAlign,
                                        draggable: backElements.description.draggable,
                                        width: Number(backElements.description.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('description', 'back', e)
                                    }"
                                />

                                <v-text
                                    v-if="!form.for_module && backElements.course.visible && form.back_content_show_course"
                                    :config="{
                                        text: backElements.course.text,
                                        x: backElements.course.x,
                                        y: backElements.course.y,
                                        fontSize: Number(backElements.course.fontSize),
                                        fontFamily: backElements.course.fontFamily,
                                        fill: backElements.course.color,
                                        align: backElements.course.fontAlign,
                                        draggable: backElements.course.draggable,
                                        width: Number(backElements.course.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('course', 'back', e)
                                    }"
                                />

                                <v-text
                                    v-if="form.for_module && backElements.module.visible && form.back_content_show_module"
                                    :config="{
                                        text: backElements.module.text,
                                        x: backElements.module.x,
                                        y: backElements.module.y,
                                        fontSize: Number(backElements.module.fontSize),
                                        fontFamily: backElements.module.fontFamily,
                                        fill: backElements.module.color,
                                        align: backElements.module.fontAlign,
                                        draggable: backElements.module.draggable,
                                        width: Number(backElements.module.maxWidth),
                                        onDragEnd: (e) => handleDragEnd('module', 'back', e)
                                    }"
                                />

                                <template v-if="backElements.grade.visible">
                                    <v-rect
                                        :config="{
                                            x: backElements.grade.x,
                                            y: backElements.grade.y,
                                            width: Number(backElements.grade.rectangleWidth),
                                            height: Number(backElements.grade.rectangleHeight),
                                            fill: backElements.grade.rectangleColor,
                                            draggable: true,
                                            onDragEnd: (e) => {
                                                backElements.grade.x = e.target.x();
                                                backElements.grade.y = e.target.y();
                                                updateFormFromBackElements();
                                            }
                                        }"
                                    />
                                    <v-text
                                        :config="{
                                            text: previewData.finalGrade,
                                            x: backElements.grade.x + (Number(backElements.grade.rectangleWidth) / 2),
                                            y: backElements.grade.y + (Number(backElements.grade.rectangleHeight) / 2),
                                            fontSize: Number(backElements.grade.fontSize),
                                            fontFamily: backElements.grade.fontFamily,
                                            fill: backElements.grade.color,
                                            align: 'center',
                                            verticalAlign: 'middle',
                                            width: Number(backElements.grade.rectangleWidth),
                                            height: Number(backElements.grade.rectangleHeight),
                                            offsetX: Number(backElements.grade.rectangleWidth) / 2,
                                            offsetY: Number(backElements.grade.rectangleHeight) / 2,
                                            draggable: false
                                        }"
                                    />
                                </template>

                                <!-- QR Code reverso -->
                                <v-image
                                    v-if="backElements.qr.visible && backQrImageElement"
                                    :config="{
                                        image: backQrImageElement,
                                        x: backElements.qr.x,
                                        y: backElements.qr.y,
                                        width: Number(backElements.qr.size),
                                        height: Number(backElements.qr.size),
                                        draggable: true,
                                        onDragEnd: (e) => handleDragEnd('qr', 'back', e)
                                    }"
                                />
                            </template>
                        </v-layer>
                    </v-stage>
                </div>

                <!-- Información de ayuda -->
                <div class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        🖱️ Haz clic y arrastra cualquier elemento para reposicionarlo en el certificado.<br>
                        💾 Usa "Guardar todo" para guardar todos los cambios a la vez, o "Guardar" en cada sección individual.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.canvas-container {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 20px;
    min-height: 500px;
    display: flex;
    justify-content: center;
    align-items: center;
}

:deep(.konvajs-content) {
    margin: 0 auto;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    cursor: default;
}

:deep(.konvajs-content) canvas {
    cursor: grab;
}

:deep(.konvajs-content) canvas:active {
    cursor: grabbing;
}
</style>
