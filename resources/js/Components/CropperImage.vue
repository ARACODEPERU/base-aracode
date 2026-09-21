<template>
    <div class="space-y-4">
        <div v-if="isLoading" class="flex items-center justify-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600 dark:text-gray-400">Cargando imagen...</span>
        </div>
        <div v-else>
            <div v-if="imageSrc" class="relative">
                <img :src="imageSrc" ref="image" alt="Imagen para recortar" class="max-w-full h-auto rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="mt-4 flex justify-center">
                    <span @click="resetCropper" class="text-blue-600 hover:text-blue-800 cursor-pointer text-sm transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Cambiar imagen
                    </span>
                </div>
            </div>
            <div v-else>
                <label
                    class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 hover:border-gray-400 dark:hover:border-gray-500">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click para subir</span> o arrastra y suelta</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG o WEBP</p>
                    </div>
                    <input ref="input" type="file" class="hidden" accept="image/*" @change="onChange" />
                </label>
                <p class="mt-2 text-xs text-center text-gray-500 dark:text-gray-400">
                    Selecciona una imagen para recortar. Asegúrate de que sea clara y de buena calidad.
                </p>
            </div>
        </div>
    </div>
</template>
<script>
import 'cropperjs/dist/cropper.css';
import Cropper from 'cropperjs';

const PLACEHOLDER_IMAGE = '/img/image-3@2x.jpg';

// Resolucion maxima del recorte exportado (evita base64 gigantes que revientan el POST)
const MAX_EXPORT_DIMENSION = 1600;
// Calidad de exportacion para JPEG/WEBP
const EXPORT_QUALITY = 0.85;

export default {
  props: {
    aspectRatio: {
      type: Number,
      default: 10 / 10
    },
    viewMode: {
      type: Number,
      default: 2
    },
    imgDefault: {
      type: String,
      default: PLACEHOLDER_IMAGE
    },
    // Cuando es true, solo emite onCrop cuando el usuario suelta el recorte.
    // Los usos existentes sin esta prop mantienen el comportamiento anterior.
    emitOnCropEnd: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      imageSrc: '',
      isLoading: false,
      cropper: null,
      userImage: false,
    };
  },
  mounted() {
    if (this.shouldLoadDefault(this.imgDefault)) {
      this.loadFromDataUrl(this.imgDefault);
    }
  },
  watch: {
    imgDefault(newValue) {
      if (this.shouldLoadDefault(newValue)) {
        this.loadFromDataUrl(newValue);
      }
    },
  },
  methods: {
    shouldLoadDefault(value) {
      if (!value || typeof value !== 'string') {
        return false;
      }

      return value !== PLACEHOLDER_IMAGE && !value.endsWith('image-3@2x.jpg');
    },
    onChange(event) {
      const files = event.target.files;
      if (files && files.length > 0) {
        this.isLoading = true;
        this.userImage = true;
        const reader = new FileReader();
        reader.onload = () => {
          this.loadFromDataUrl(reader.result);
        };
        reader.readAsDataURL(files[0]);
      }
    },
    loadFromDataUrl(dataUrl) {
      if (!dataUrl) {
        return;
      }

      this.isLoading = true;
      this.destroyCropper();
      this.imageSrc = dataUrl;
      this.isLoading = false;

      this.$nextTick(() => {
        this.initCropper();
      });
    },
    destroyCropper() {
      if (this.cropper) {
        this.cropper.destroy();
        this.cropper = null;
      }
    },
    initCropper() {
      if (!this.$refs.image) {
        return;
      }

      this.destroyCropper();

      this.cropper = new Cropper(this.$refs.image, {
        aspectRatio: this.aspectRatio,
        viewMode: this.viewMode,
        crop: () => {
          // Compatibilidad: si emitOnCropEnd no esta activo, emite en cada
          // movimiento como antes (los consumidores existentes dependen de eso).
          if (!this.emitOnCropEnd) {
            this.cropImage();
          }
        },
        cropend: () => {
          // Emision unica y confiable al terminar el gesto del usuario.
          if (this.emitOnCropEnd) {
            this.cropImage();
          }
        },
        ready: () => {
          // La imagen precargada (edicion) NO debe llenar logo_path por si sola.
          if (this.emitOnCropEnd && !this.userImage) {
            return;
          }
          this.cropImage();
        },
      });
    },
    cropImage() {
      if (!this.cropper) {
        return;
      }

      const croppedCanvas = this.cropper.getCroppedCanvas();
      if (croppedCanvas) {
        this.$emit('onCrop', this.exportCanvas(croppedCanvas));
      }
    },
    exportCanvas(canvas) {
      const w = canvas.width;
      const h = canvas.height;
      const max = Math.max(w, h);

      // Recorte pequeño: PNG como siempre (conserva transparencias)
      if (max <= MAX_EXPORT_DIMENSION) {
        return canvas.toDataURL();
      }

      // Imagen gigante (foto de camara/celular): se reduce y exporta en JPEG
      // para que el base64 quepa en el POST sin tocar post_max_size
      const scale = MAX_EXPORT_DIMENSION / max;
      const scaled = document.createElement('canvas');
      scaled.width = Math.round(w * scale);
      scaled.height = Math.round(h * scale);

      const ctx = scaled.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, scaled.width, scaled.height);
      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(canvas, 0, 0, scaled.width, scaled.height);

      return scaled.toDataURL('image/jpeg', EXPORT_QUALITY);
    },
    resetCropper() {
      this.imageSrc = '';
      this.destroyCropper();
      if (this.$refs.input) {
        this.$refs.input.value = '';
      }
    },
  },
  beforeUnmount() {
    this.destroyCropper();
  },
};
</script>
