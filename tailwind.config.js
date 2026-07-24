import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Newsreader', 'Georgia', 'serif'],
            },
            colors: {
                gold: {
                    DEFAULT: '#E0A44E',
                    dark: '#C88E3C',
                    soft: 'rgba(224, 164, 78, 0.15)',
                },
                ink: {
                    950: '#101014',
                    900: '#16161C',
                    850: '#1C1C24',
                    800: '#22222C',
                    700: '#2E2E3A',
                    600: '#3E3E4E',
                },
                status: {
                    nys: '#8A8A8A',
                    todo: '#3B82F6',
                    progress: '#E0A44E',
                    review: '#A855F7',
                    done: '#22C55E',
                },
                priority: {
                    high: '#EF7C4A',
                },
            },
        },
    },

    plugins: [forms],
};
