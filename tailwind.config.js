const defaultTheme = require("tailwindcss/defaultTheme");

/** @type {import('tailwindcss').Config} */
const plugin = require('tailwindcss/plugin');

const rotateX = plugin(function ({ addUtilities }) {
    addUtilities({
        '.rotate-y-180': {
            transform: 'rotateY(180deg)',
        },
    });
});
module.exports = {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
        "node_modules/flowbite-vue/**/*.{js,jsx,ts,tsx}",
        "node_modules/flowbite/**/*.{js,jsx,ts,tsx}",
        './Modules/**/Resources/views/**/*.blade.php',
        './Modules/**/Resources/assets/js/**/*.vue',
        './Modules/**/Resources/views/**/**.blade.php',
        './Modules/**/Resources/assets/js/**/**.vue',
    ],
    darkMode: 'class',
    safelist: [
        'w-64',
        'w-1/2',
        'rounded-l-lg',
        'rounded-r-lg',
        'bg-gray-200',
        'grid-cols-4',
        'grid-cols-7',
        'h-6',
        'leading-6',
        'h-9',
        'leading-9',
        'shadow-lg',
        {
            pattern: /(bg|text|from|via|to|border|ring)-(blue|red|green|orange|purple|indigo|purple|violet|emerald|teal|rose|pink|amber|yellow)-(50|100|400|500|600|700|800|900)/,
            variants: ['dark', 'hover', 'focus', 'dark:from', 'dark:to'],
        },
    ],
    theme: {
        container: {
            center: true,
        },
        extend: {
            colors: {
                /* Admin / Existing palette */
                primary: {
                    DEFAULT: '#4361ee',
                    light: '#eaf1ff',
                    'dark-light': 'rgba(67,97,238,.15)',
                },
                secondary: {
                    DEFAULT: '#805dca',
                    light: '#ebe4f7',
                    'dark-light': 'rgb(128 93 202 / 15%)',
                },
                success: {
                    DEFAULT: '#00ab55',
                    light: '#ddf5f0',
                    'dark-light': 'rgba(0,171,85,.15)',
                },
                danger: {
                    DEFAULT: '#e7515a',
                    light: '#fff5f5',
                    'dark-light': 'rgba(231,81,90,.15)',
                },
                warning: {
                    DEFAULT: '#e2a03f',
                    light: '#fff9ed',
                    'dark-light': 'rgba(226,160,63,.15)',
                },
                info: {
                    DEFAULT: '#2196f3',
                    light: '#e7f7ff',
                    'dark-light': 'rgba(33,150,243,.15)',
                },
                dark: {
                    DEFAULT: '#3b3f5c',
                    light: '#eaeaec',
                    'dark-light': 'rgba(59,63,92,.15)',
                },
                black: {
                    DEFAULT: '#0e1726',
                    light: '#e3e4eb',
                    'dark-light': 'rgba(14,23,38,.15)',
                },
                white: {
                    DEFAULT: '#ffffff',
                    light: '#e0e6ed',
                    dark: '#888ea8',
                },
                /* ARACODE Brand Palette */
                'ara-navy': {
                    DEFAULT: '#060E2D',
                    light: '#0c1a40',
                },
                'ara-blue': {
                    DEFAULT: '#0188EE',
                    light: '#e6f4fc',
                    dark: '#016bc4',
                },
                'ara-green': {
                    DEFAULT: '#4EAE33',
                    light: '#edf8ea',
                    dark: '#3d8b29',
                },
                'ara-red': {
                    DEFAULT: '#F11600',
                    light: '#fef0ee',
                    dark: '#c41200',
                },
                'ara-slate': {
                    50: '#F5F7FA',
                    100: '#E8ECF1',
                    200: '#CBD2DC',
                    300: '#9DA6B4',
                    400: '#64748B',
                    500: '#475569',
                    600: '#334155',
                    700: '#1E293B',
                    800: '#0F172A',
                    900: '#060E2D',
                },
            },
            fontFamily: {
                nunito: ['Nunito', 'sans-serif'],
                'ara-sans': ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
            },
            spacing: {
                4.5: '18px',
            },
            boxShadow: {
                '3xl': '0 2px 2px rgb(224 230 237 / 46%), 1px 6px 7px rgb(224 230 237 / 46%)',
            },
            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        '--tw-prose-invert-headings': theme('colors.white.dark'),
                        '--tw-prose-invert-links': theme('colors.white.dark'),
                        h1: { fontSize: '40px', marginBottom: '0.5rem', marginTop: 0 },
                        h2: { fontSize: '32px', marginBottom: '0.5rem', marginTop: 0 },
                        h3: { fontSize: '28px', marginBottom: '0.5rem', marginTop: 0 },
                        h4: { fontSize: '24px', marginBottom: '0.5rem', marginTop: 0 },
                        h5: { fontSize: '20px', marginBottom: '0.5rem', marginTop: 0 },
                        h6: { fontSize: '16px', marginBottom: '0.5rem', marginTop: 0 },
                        p: { marginBottom: '0.5rem' },
                        li: { margin: 0 },
                        img: { margin: 0 },
                    },
                },
            }),
            keyframes: {
                pointRight: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '50%': { transform: 'translateX(4px)' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(30px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideInLeft: {
                    '0%': { opacity: '0', transform: 'translateX(-30px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(30px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                countUp: {
                    '0%': { opacity: '0', transform: 'scale(0.5)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
                pulse: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.5' },
                },
            },
            animation: {
                'point-right': 'pointRight 1s ease-in-out infinite',
                'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
                'fade-in': 'fadeIn 0.6s ease-out forwards',
                'slide-in-left': 'slideInLeft 0.6s ease-out forwards',
                'slide-in-right': 'slideInRight 0.6s ease-out forwards',
                'count-up': 'countUp 0.5s ease-out forwards',
                'float': 'float 3s ease-in-out infinite',
                'pulse-slow': 'pulse 3s ease-in-out infinite',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
        require('@tailwindcss/typography'),
        rotateX,
    ],
};
