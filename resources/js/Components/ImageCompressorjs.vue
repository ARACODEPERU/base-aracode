
<template>
    <div 
        class="w-full"
        :style="{ minHeight: '200px' }"
        @dragover.prevent="onDragOver"
        @dragleave.prevent="onDragLeave"
        @drop.prevent="onDrop"
    >
        <input ref="fileInput" @change="handleFileChange" accept=".svg, .png, .jpg, .jpeg, .gif" class="hidden" id="compressorjs-input" type="file">
        
        <!-- Estado: Cargando (comprimiendo) -->
        <div v-if="loading" 
            class="w-full h-full border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 
                   flex flex-col items-center justify-center p-6 cursor-not-allowed"
            :style="{ minHeight: '200px' }"
        >
            <svg aria-hidden="true" class="w-10 h-10 text-blue-500 animate-spin mb-3" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor" />
            </svg>
            <p class="text-gray-600 font-medium">Comprimiendo imagen...</p>
            <p class="text-sm text-gray-400">Por favor espera</p>
        </div>
        
        <!-- Estado: Imagen seleccionada -->
        <div v-else-if="selectedFileName"
            class="w-full h-full border-2 border-dashed border-green-300 rounded-xl bg-green-50 
                   flex flex-col items-center justify-center p-6 cursor-pointer transition-all duration-300"
            :style="{ minHeight: '200px' }"
            @click="triggerFileInput"
        >
            <div class="w-12 h-12 mb-3 rounded-full bg-green-100 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <p class="text-gray-700 font-medium mb-1">{{ selectedFileName }}</p>
            <p class="text-sm text-gray-500 mb-4">{{ selectedFileSize }}</p>
            <button @click.stop="triggerFileInput" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Cambiar imagen
            </button>
        </div>
        
        <!-- Estado: Zona de carga (vacío) -->
        <div v-else
            class="w-full h-full border-2 border-dashed rounded-xl bg-gray-50 
                   flex flex-col items-center justify-center p-6 cursor-pointer transition-all duration-300"
            :class="isDragging 
                ? 'border-green-500 bg-green-50' 
                : 'border-gray-300 hover:bg-blue-50 hover:border-blue-400'"
            :style="{ minHeight: '200px' }"
            @click="triggerFileInput"
        >
            <!-- Ícono de cámara -->
            <div class="w-16 h-16 mb-4 rounded-full flex items-center justify-center transition-colors duration-300"
                :class="isDragging ? 'bg-green-100' : 'bg-blue-50'"
            >
                <svg v-if="isDragging" class="w-8 h-8 text-green-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                <svg v-else class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            
            <!-- Textos -->
            <h3 class="text-lg font-semibold text-gray-700 mb-2 text-center">
                {{ isDragging ? '¡Suelta aquí!' : 'Arrastra tu imagen aquí' }}
            </h3>
            <p class="text-gray-500 mb-3 text-center">
                {{ isDragging ? 'Tu imagen se cargará' : 'o haz clic para buscar' }}
            </p>
            
            <!-- Badge de formatos -->
            <span class="px-3 py-1 bg-gray-100 text-xs text-gray-600 rounded-full font-medium">
                PNG, JPG, GIF • Máx 5MB
            </span>
        </div>
    </div>
</template>

<script>
import Compressor from 'compressorjs';

export default {
    props: {
        onImageCompressed: Function
    },
    data() {
        return {
            loading: false,
            isDragging: false,
            selectedFileName: '',
            selectedFileSize: '',
        };
    },
    methods: {
        triggerFileInput() {
            this.$refs.fileInput.click();
        },
        async handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            this.selectedFileName = file.name;
            this.selectedFileSize = this.formatFileSize(file.size);
            
            this.loading = true;
            try {
                const imageUrl = await this.compressAndConvertToBase64(file);
                this.onImageCompressed(imageUrl);
            } catch (error) {
                console.error('Error en la compresión:', error.message);
                this.selectedFileName = '';
                this.selectedFileSize = '';
            } finally {
                this.loading = false;
            }
        },
        async compressAndConvertToBase64(file) {
            return new Promise((resolve, reject) => {
                new Compressor(file, {
                    quality: 0.2,
                    success: (result) => {
                        const imageUrl = URL.createObjectURL(result);
                        resolve(this.convertUrlToBase64(imageUrl));
                    },
                    error: (error) => {
                        reject(error);
                    },
                });
            });
        },
        async convertUrlToBase64(url) {
            const response = await fetch(url);
            const blob = await response.blob();

            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        },
        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
        onDragOver(event) {
            this.isDragging = true;
        },
        onDragLeave(event) {
            this.isDragging = false;
        },
        onDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                this.$refs.fileInput.files = dataTransfer.files;
                this.handleFileChange({ target: this.$refs.fileInput });
            }
        }
    },
};
</script>
