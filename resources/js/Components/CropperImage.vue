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
                <div class="relative">
                    <input type="file" ref="input" @change="onChange" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-400 transition-colors bg-gray-50 dark:bg-gray-800">
                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            <span class="font-medium">Haz clic para seleccionar</span> o arrastra y suelta
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">PNG, JPG, GIF hasta 10MB</p>
                    </div>
                </div>
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
    imgDefault:{
      type: String,
      default: '/img/image-3@2x.jpg'
    }
  },
  data() {
    return {
      imageSrc: '',
      isLoading: false
    };
  },
  methods: {
    onChange(event) {
      const files = event.target.files;
      if (files && files.length > 0) {
        this.isLoading = true;
        const reader = new FileReader();
        reader.onload = () => {
          this.imageSrc = reader.result;
          this.isLoading = false;
          this.initCropper();
        };
        reader.readAsDataURL(files[0]);
      }
    },
    initCropper() {
      const image = new Image();
      image.src = this.imageSrc;
      image.onload = () => {
        this.cropper = new Cropper(this.$refs.image, {
          aspectRatio: this.aspectRatio,
          viewMode: this.viewMode,
          crop : (event) => {
            this.cropImage()
          }
        });
      };
    },
    cropImage() {
      const croppedCanvas = this.cropper.getCroppedCanvas();
      if (croppedCanvas) {
        this.$emit('onCrop',croppedCanvas.toDataURL());
      }
    },
    resetCropper() {
      this.imageSrc = '';
      if (this.cropper) {
        this.cropper.destroy();
        this.cropper = null;
      }
    }
  }
};
</script>
