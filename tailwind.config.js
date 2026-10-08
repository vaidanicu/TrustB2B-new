import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Newsreader', 'Georgia', 'Times New Roman', 'serif'],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // TrustBridge identity — same palette as the roadmap/plan
                // document, now shared by the product itself instead of
                // living only in the planning artifact.
                paper: { DEFAULT: '#eff1ea', raised: '#f8f9f4', sunken: '#e6e8e0' },
                ink: { DEFAULT: '#17212b', soft: '#3c4750', muted: '#5b6560' },
                line: '#d7d9cd',
                accent: { DEFAULT: '#a97a35', soft: '#e9dcc5', ink: '#5c3f18' },
                teal: { DEFAULT: '#2c6a5d', soft: '#dce9e4', ink: '#1c4238' },
                danger: { DEFAULT: '#9c4234', soft: '#f1dcd6', ink: '#5e281f' },
            },
        },
    },

    plugins: [forms],
};
