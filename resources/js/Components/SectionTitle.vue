<template>
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex-1 ltr:mr-4 rtl:ml-4">
            <h4 class="font-semibold text-lg ">
                <slot name="title" />
            </h4>
            <p class="media-text">
                <slot name="description" />
            </p>
        </div>
        <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
            <slot name="aside" />
        </div>
    </div>
</template>
