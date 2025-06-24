<template>
    <!-- Sidebar -->
    <div class="absolute hs-overlay [--auto-close:lg]
        hs-overlay-open:translate-x-0 -translate-x-full duration-300 transform
        fixed top-0 start-0 bottom-0 z-60
        w-64 h-full
        bg-white border-e border-gray-200
        lg:block lg:translate-x-0 lg:end-auto lg:bottom-0
        dark:bg-neutral-900 dark:border-neutral-700" role="dialog" tabindex="-1" aria-label="Sidebar"
    >
        <nav class="size-full flex flex-col">
            <div class="flex items-center pt-4 pe-4 ps-7">
                <!-- Logo -->
                <div class="flex-none focus:outline-hidden focus:opacity-80" href="../templates.html" aria-label="Preline">
                    Asistentes
                </div>
                <!-- End Logo -->

                <div class="hidden lg:block lg:ms-2">

                </div>
            </div>

            <div class="h-full overflow-y-auto [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-neutral-700 dark:[&::-webkit-scrollbar-thumb]:bg-neutral-500">
                <!-- List -->
                <ul class="space-y-1.5 p-4">
                    <li>
                        <a class="flex items-center gap-x-3 py-2 px-3 text-sm text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-neutral-300 dark:focus:bg-neutral-900 dark:focus:text-neutral-300" href="#">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            New chat
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center gap-x-3 py-2 px-3 text-sm text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-neutral-300 dark:focus:bg-neutral-900 dark:focus:text-neutral-300" href="#">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M13.545 2.907a13.227 13.227 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.19 12.19 0 0 0-3.658 0 8.258 8.258 0 0 0-.412-.833.051.051 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.041.041 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032c.001.014.01.028.021.037a13.276 13.276 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019c.308-.42.582-.863.818-1.329a.05.05 0 0 0-.01-.059.051.051 0 0 0-.018-.011 8.875 8.875 0 0 1-1.248-.595.05.05 0 0 1-.02-.066.051.051 0 0 1 .015-.019c.084-.063.168-.129.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.052.052 0 0 1 .053.007c.08.066.164.132.248.195a.051.051 0 0 1-.004.085 8.254 8.254 0 0 1-1.249.594.05.05 0 0 0-.03.03.052.052 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.235 13.235 0 0 0 4.001-2.02.049.049 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.034.034 0 0 0-.02-.019Zm-8.198 7.307c-.789 0-1.438-.724-1.438-1.612 0-.889.637-1.613 1.438-1.613.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612Zm5.316 0c-.788 0-1.438-.724-1.438-1.612 0-.889.637-1.613 1.438-1.613.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612Z"/>
                            </svg>
                            Preline AI Discord
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center gap-x-3 py-2 px-3 text-sm text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-neutral-300 dark:focus:bg-neutral-900 dark:focus:text-neutral-300" href="#">
                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                            Save conversation
                        </a>
                    </li>
                </ul>
                <!-- End List -->
            </div>

            <!-- Footer -->
            <div class="mt-auto">
                <div class="py-2.5 px-7">
                    <p class="inline-flex items-center gap-x-2 text-xs text-green-600">
                    <span class="block size-1.5 rounded-full bg-green-600"></span>
                    Active 12,320 people
                    </p>
                </div>

                <div class="p-4 border-t border-gray-200 dark:border-neutral-700">
                    <a class="flex justify-between items-center gap-x-3 py-2 px-3 text-sm text-gray-700 rounded-lg hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 dark:hover:bg-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-300 dark:focus:text-neutral-300" href="#">
                    Sign in
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                    </a>
                </div>
            </div>
            <!-- End Footer -->
        </nav>
    </div>
    <!-- End Sidebar -->

    <!-- Content -->
    <div class="relative h-[calc(90vh-100px)] w-full lg:ps-64">
        <div class="py-14">
            <perfect-scrollbar
                    :options="{
                        swipeEasing: true,
                        wheelPropagation: false,
                    }"
                    class="h-[calc(70vh-160px)] relative"
                >
            <!-- Title -->
            <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto text-center">
                <h1 class="text-3xl font-bold text-gray-800 sm:text-4xl dark:text-white">
                    Welcome to Preline AI
                </h1>
                <p class="mt-3 text-gray-600 dark:text-neutral-400">
                    Your AI-powered copilot for the web
                </p>
            </div>
            <!-- End Title -->

            <ul class="mt-16 space-y-5">




                <!-- Chat Bubble -->
                <li class="max-w-4xl py-2 px-4 sm:px-6 lg:px-8 mx-auto flex gap-x-2 sm:gap-x-4">
                    <svg class="shrink-0 size-9.5 rounded-full" width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="38" height="38" rx="6" fill="#2563EB"/>
                        <path d="M10 28V18.64C10 13.8683 14.0294 10 19 10C23.9706 10 28 13.8683 28 18.64C28 23.4117 23.9706 27.28 19 27.28H18.25" stroke="white" stroke-width="1.5"/>
                        <path d="M13 28V18.7552C13 15.5104 15.6863 12.88 19 12.88C22.3137 12.88 25 15.5104 25 18.7552C25 22 22.3137 24.6304 19 24.6304H18.25" stroke="white" stroke-width="1.5"/>
                        <ellipse cx="19" cy="18.6554" rx="3.75" ry="3.6" fill="white"/>
                    </svg>

                    <div class="grow max-w-[90%] md:max-w-2xl w-full space-y-3">
                        <!-- Card -->
                        <div class="space-y-3">
                            <p class="text-sm text-gray-800 dark:text-white">
                            Preline UI is an open-source set of prebuilt UI components based on the utility-first Tailwind CSS framework.
                            </p>
                            <div class="space-y-1.5">
                                <p class="text-sm text-gray-800 dark:text-white">
                                    Here're some links to get started
                                </p>
                                <ul>
                                    <li>
                                        <a class="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="../docs/index.html">
                                            Installation Guide
                                        </a>
                                    </li>
                                    <li>
                                        <a class="text-sm text-blue-600 decoration-2 hover:underline focus:outline-hidden focus:underline font-medium dark:text-blue-500 dark:hover:text-blue-400" href="../docs/frameworks.html">
                                            Framework Guides
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- End Card -->

                        <!-- Button Group -->
                        <div>
                            <div class="sm:flex sm:justify-between">
                                <div>
                                    <div class="inline-flex border border-gray-200 rounded-full p-0.5 dark:border-neutral-700">
                                        <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2h0a3.13 3.13 0 0 1 3 3.88Z"/></svg>
                                        </button>
                                        <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-full text-gray-500 hover:bg-blue-100 hover:text-blue-800 focus:z-10 focus:outline-hidden focus:bg-blue-100 focus:text-blue-800 dark:text-neutral-500 dark:hover:bg-blue-900 dark:hover:text-blue-200 dark:focus:bg-blue-900 dark:focus:text-blue-200">
                                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                                        </button>
                                    </div>
                                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 14V2"/><path d="M9 18.12 10 14H4.17a2 2 0 0 1-1.92-2.56l2.33-8A2 2 0 0 1 6.5 2H20a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2.76a2 2 0 0 0-1.79 1.11L12 22h0a3.13 3.13 0 0 1-3-3.88Z"/></svg>
                                        Copy
                                    </button>
                                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                                        <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                                        Share
                                    </button>
                                </div>

                                <div class="mt-1 sm:mt-0">
                                    <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm rounded-full border border-transparent text-gray-500 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:text-neutral-400 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                                    New answer
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- End Button Group -->
                    </div>
                </li>
                <!-- End Chat Bubble -->

                <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                 <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    generate 3-dimensional abstract images
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->

                <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                        <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                            <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                                <span class="text-sm font-medium text-white">AZ</span>
                            </span>

                            <div class="grow mt-2 space-y-3">
                                <p class="text-gray-800 dark:text-neutral-200">
                                    ELMER RODRIGUZZ
                                </p>
                            </div>
                        </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
                <!-- Chat Bubble -->
                <li class="py-2 sm:py-4">
                    <div class="max-w-4xl px-4 sm:px-6 lg:px-8 mx-auto">
                    <div class="max-w-2xl flex gap-x-2 sm:gap-x-4">
                        <span class="shrink-0 inline-flex items-center justify-center size-9.5 rounded-full bg-gray-600">
                        <span class="text-sm font-medium text-white">AZ</span>
                        </span>

                        <div class="grow mt-2 space-y-3">
                        <p class="text-gray-800 dark:text-neutral-200">
                            2 files uploaded
                        </p>

                        <ul class="flex flex-col justify-end text-start -space-y-px">
                            <li class="flex items-center gap-x-2 p-3 text-sm bg-white border border-gray-200 text-gray-800 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                            <div class="w-full flex justify-between truncate">
                                <span class="me-3 flex-1 w-0 truncate">
                                resume_web_ui_developer.csv
                                </span>
                                <button type="button" class="flex items-center gap-x-2 text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 whitespace-nowrap dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Download
                                </button>
                            </div>
                            </li>
                            <li class="flex items-center gap-x-2 p-3 text-sm bg-white border border-gray-200 text-gray-800 first:rounded-t-lg first:mt-0 last:rounded-b-lg dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-200">
                            <div class="w-full flex justify-between truncate">
                                <span class="me-3 flex-1 w-0 truncate">
                                coverletter_web_ui_developer.pdf
                                </span>
                                <button type="button" class="flex items-center gap-x-2 text-gray-500 hover:text-blue-600 focus:outline-hidden focus:text-blue-600 whitespace-nowrap dark:text-neutral-500 dark:hover:text-blue-500 dark:focus:text-blue-500">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Download
                                </button>
                            </div>
                            </li>
                        </ul>
                        </div>
                    </div>
                    </div>
                </li>
                <!-- End Chat Bubble -->
            </ul>
           </perfect-scrollbar>
        </div>

        <!-- Textarea -->
        <div class="max-w-4xl mx-auto sticky bottom-0 z-10 p-3 sm:py-6">
            <div class="lg:hidden flex justify-end mb-2 sm:mb-3">
                <!-- Sidebar Toggle -->
                <button type="button" class="p-2 inline-flex items-center gap-x-2 text-xs font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-2xs hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-900 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-800 dark:focus:bg-neutral-800" aria-haspopup="dialog" aria-expanded="false" aria-controls="hs-application-sidebar" aria-label="Toggle navigation" data-hs-overlay="#hs-application-sidebar">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="6" y2="6"/><line x1="3" x2="21" y1="12" y2="12"/><line x1="3" x2="21" y1="18" y2="18"/></svg>
                    <span>Sidebar</span>
                </button>
                <!-- End Sidebar Toggle -->
            </div>

            <!-- Input -->
            <div class="relative">
                <textarea class="form-textarea p-3 sm:p-4 pb-12 sm:pb-12 block w-full bg-gray-100 rounded-lg sm:text-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500" placeholder="Ask me anything..."></textarea>

                <!-- Toolbar -->
                <div class="absolute bottom-px inset-x-px p-2 rounded-b-lg bg-gray-100 dark:bg-neutral-800">
                    <div class="flex flex-wrap justify-between items-center gap-2">
                            <!-- Button Group -->
                        <div class="flex items-center">
                            <!-- Mic Button -->
                            <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-white focus:z-10 focus:outline-hidden focus:bg-white dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="9" x2="15" y1="15" y2="9"/></svg>
                            </button>
                            <!-- End Mic Button -->

                            <!-- Attach Button -->
                            <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-white focus:z-10 focus:outline-hidden focus:bg-white dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            </button>
                            <!-- End Attach Button -->
                        </div>
                        <!-- End Button Group -->

                        <!-- Button Group -->
                        <div class="flex items-center gap-x-1">
                            <!-- Mic Button -->
                            <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-gray-500 hover:bg-white focus:z-10 focus:outline-hidden focus:bg-white dark:text-neutral-500 dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg>
                            </button>
                            <!-- End Mic Button -->

                            <!-- Send Button -->
                            <button type="button" class="inline-flex shrink-0 justify-center items-center size-8 rounded-lg text-white bg-blue-600 hover:bg-blue-500 focus:z-10 focus:outline-hidden focus:bg-blue-500">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
                                </svg>
                            </button>
                            <!-- End Send Button -->
                        </div>
                        <!-- End Button Group -->
                    </div>
                </div>
                <!-- End Toolbar -->
            </div>
            <!-- End Input -->
        </div>
        <!-- End Textarea -->
    </div>
    <!-- End Content -->
</template>
