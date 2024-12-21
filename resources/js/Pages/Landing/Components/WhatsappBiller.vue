<script setup>
    import iconWhatsapp from '@/Components/vristo/icon/icon-whatsapp.vue';
</script>

<template>
    <div class="fixed bottom-20 right-4">
        <a href="https://wa.link/4bfvd1" target="_blank" 
            class="wtsapp relative flex items-center justify-center w-12 h-12 p-2
            bg-green-600 text-white font-bold text-lg rounded-full shadow-md z-10" 
            data-bs-toggle="modal" data-bs-target="#exampleModal">
            <iconWhatsapp class="w-6 text-white hover:text-white z-10" />
        </a>
    </div>
</template>

<style>
    @keyframes pulse-border {
        0% {
            transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1);
            opacity: 1;
        }
        100% {
            transform: translateX(-50%) translateY(-50%) translateZ(0) scale(1.5);
            opacity: 0;
        }
    }

    .wtsapp::before {
        content: "";
        position: absolute;
        z-index: -1;
        left: 50%;
        top: 50%;
        transform: translateX(-50%) translateY(-50%);
        display: block;
        width: 4rem;
        height: 4rem;
        background-color: #25D366;
        border-radius: 50%;
        animation: pulse-border 1500ms ease-out infinite;
    }
</style>