<script setup>
defineProps({
    name: {
        type: String,
        default: 'clipboard',
    },
});
</script>

<template>
    <svg
        v-if="name === 'clipboard'"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.9"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!-- CC0 medical clipboard style reference: https://www.svgrepo.com/svg/432233/medical-clipboard -->
        <path d="M9 4.5h6" />
        <path d="M9.5 3h5a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 14.5 7h-5A1.5 1.5 0 0 1 8 5.5v-1A1.5 1.5 0 0 1 9.5 3Z" />
        <path d="M8 5H6.8A2.8 2.8 0 0 0 4 7.8v10.4A2.8 2.8 0 0 0 6.8 21h10.4a2.8 2.8 0 0 0 2.8-2.8V7.8A2.8 2.8 0 0 0 17.2 5H16" />
        <path d="m8.5 14 2 2 5-5" />
    </svg>
    <svg v-else-if="name === 'patient'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4.5 21a7.5 7.5 0 0 1 15 0" />
        <path d="M18.5 8.5h3" />
        <path d="M20 7v3" />
    </svg>
    <svg v-else-if="name === 'doctor'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 doctor/stethoscope style reference: https://www.svgrepo.com/svg/384579/doctor-health-healthcare-hospital-medical-stethoscope -->
        <path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M5 21v-2.2A6.8 6.8 0 0 1 11.8 12h.4A6.8 6.8 0 0 1 19 18.8V21" />
        <path d="M9 13.2V16a3 3 0 0 0 6 0v-2.8" />
        <path d="M7.5 16.5h3" />
        <path d="M13.5 16.5h3" />
    </svg>
    <svg v-else-if="name === 'chat'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 11.5a7.5 7.5 0 0 1-7.5 7.5H8l-5 3 1.7-4.6A7.5 7.5 0 1 1 21 11.5Z" />
        <path d="M8 11h8" />
        <path d="M8 14h5" />
    </svg>
    <svg v-else-if="name === 'search'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="10.5" cy="10.5" r="6.5" />
        <path d="m16 16 5 5" />
    </svg>
    <svg v-else-if="name === 'stethoscope'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 stethoscope style reference: https://www.svgrepo.com/svg/44400/stethoscope-medical-tool -->
        <path d="M6 4v5a5 5 0 0 0 10 0V4" />
        <path d="M4 4h4" />
        <path d="M14 4h4" />
        <path d="M11 14v1.5a4.5 4.5 0 0 0 9 0V14" />
        <circle cx="20" cy="12" r="2" />
    </svg>
    <svg v-else-if="name === 'tag'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20 13 13 20a2.8 2.8 0 0 1-4 0l-5-5a2.8 2.8 0 0 1 0-4l7-7h7v7Z" />
        <circle cx="15.5" cy="8.5" r="1.5" />
    </svg>
    <svg v-else-if="name === 'document'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M6 3h8l4 4v14H6Z" />
        <path d="M14 3v5h5" />
        <path d="M9 13h6" />
        <path d="M9 17h4" />
    </svg>
    <svg v-else-if="name === 'treatment'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m10 14-3.5 3.5a3.2 3.2 0 0 0 4.5 4.5L14.5 18" />
        <path d="m14 10 3.5-3.5A3.2 3.2 0 0 1 22 11l-3.5 3.5" />
        <path d="m8.5 15.5 8-8" />
        <path d="M7 7h4" />
        <path d="M9 5v4" />
    </svg>
    <svg v-else-if="name === 'flask'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9 3h6" />
        <path d="M10 3v6l-5.2 9.2A1.9 1.9 0 0 0 6.5 21h11a1.9 1.9 0 0 0 1.7-2.8L14 9V3" />
        <path d="M7.3 16h9.4" />
    </svg>
    <svg v-else-if="name === 'heart-pulse'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 heart pulse style reference: https://www.svgrepo.com/svg/472666/heart-pulse -->
        <path d="M20.8 5.8a5.2 5.2 0 0 0-7.4 0L12 7.2l-1.4-1.4a5.2 5.2 0 0 0-7.4 7.4L12 22l8.8-8.8a5.2 5.2 0 0 0 0-7.4Z" />
        <path d="M5.6 12h3l1.5-3 2.4 6 1.5-3h4.4" />
    </svg>
    <svg v-else-if="name === 'lungs'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 lungs style reference: https://www.svgrepo.com/svg/201596/lungs-healthcare-and-medical -->
        <path d="M12 3v8" />
        <path d="M12 11c-1.8-1.9-3.2-3.1-5.1-3.7C4.9 6.7 3 8.2 3 10.3v6.3A3.4 3.4 0 0 0 6.4 20c2.1 0 3.7-1.7 3.7-3.8V12" />
        <path d="M12 11c1.8-1.9 3.2-3.1 5.1-3.7 2-.6 3.9.9 3.9 3v6.3a3.4 3.4 0 0 1-3.4 3.4c-2.1 0-3.7-1.7-3.7-3.8V12" />
        <path d="M9 6.2c1.1.5 2 1.4 3 2.7 1-1.3 1.9-2.2 3-2.7" />
    </svg>
    <svg v-else-if="name === 'ruler'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 ruler style reference: https://www.svgrepo.com/svg/190030/ruler -->
        <path d="m4 17 13-13 3 3L7 20Z" />
        <path d="m7 14 2 2" />
        <path d="m10 11 2 2" />
        <path d="m13 8 2 2" />
    </svg>
    <svg v-else-if="name === 'weight-scale'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <!-- CC0 weight scale style reference: https://www.svgrepo.com/svg/189870/weight-scale-weight -->
        <rect x="5" y="4" width="14" height="16" rx="3" />
        <path d="M9 8h6" />
        <path d="M10 11a2 2 0 0 1 4 0" />
        <path d="M12 11V9.5" />
        <path d="M9 16h6" />
    </svg>
    <svg v-else-if="name === 'body-mass'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="7" r="3" />
        <path d="M6 21a6 6 0 0 1 12 0Z" />
        <path d="M8 21h8" />
    </svg>
    <svg v-else-if="name === 'eye-off'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m3 3 18 18" />
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
        <path d="M8.5 5.7A10.8 10.8 0 0 1 12 5c5.5 0 9 5 9 7a8.7 8.7 0 0 1-2 3" />
        <path d="M6.2 6.9C4.2 8.3 3 10.5 3 12c0 2 3.5 7 9 7 1.4 0 2.7-.3 3.8-.9" />
    </svg>
    <svg v-else-if="name === 'chevron-up'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m6 15 6-6 6 6" />
    </svg>
    <svg v-else-if="name === 'lock'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="5" y="10" width="14" height="11" rx="2" />
        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
        <path d="M12 15v2" />
    </svg>
    <svg v-else-if="name === 'home'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m3 11 9-8 9 8" />
        <path d="M5 10v11h14V10" />
        <path d="M9 21v-6h6v6" />
    </svg>
    <svg v-else-if="name === 'save'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M5 3h12l2 2v16H5Z" />
        <path d="M8 3v6h8V3" />
        <path d="M8 21v-7h8v7" />
    </svg>
    <svg v-else-if="name === 'back'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 19 3 12l7-7" />
        <path d="M3 12h18" />
    </svg>
    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="9" />
        <path d="M12 8v8" />
        <path d="M8 12h8" />
    </svg>
</template>
