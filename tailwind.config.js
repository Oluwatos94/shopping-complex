import defaultTheme from 'tailwindcss/defaultTheme';
import plugin from 'tailwindcss/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './resources/**/*.tsx',
        './resources/**/*.ts',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Lato', ...defaultTheme.fontFamily.sans],
                serif: ['Lora', ...defaultTheme.fontFamily.serif],
                display: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    olive: '#86885e',
                    dark: '#272518',
                    light: '#cacfca',
                    brown: '#523026',
                    peach: '#d49f89',
                },
                // New brand palette (rebrand — rolled out section by section)
                brand: {
                    ink: '#0B1F3A',
                    green: '#25D366',
                    'green-dark': '#1EB85A',
                    surface: '#F8FAFC',
                    muted: '#667085',
                    line: '#E4E7EC',
                    danger: '#F04438',
                    star: '#F5C518',
                },
            },
            fontSize: {
                'xs': ['0.75rem', { lineHeight: '1rem' }],
                'sm': ['0.875rem', { lineHeight: '1.25rem' }],
                'base': ['1rem', { lineHeight: '1.5rem' }],
                'lg': ['1.125rem', { lineHeight: '1.75rem' }],
                'xl': ['1.25rem', { lineHeight: '1.75rem' }],
                '2xl': ['1.5rem', { lineHeight: '2rem' }],
                '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
                '5xl': ['3rem', { lineHeight: '1.2' }],
                '6xl': ['3.75rem', { lineHeight: '1.1' }],
            },
            fontWeight: {
                light: '300',
                normal: '400',
                medium: '500',
                semibold: '600',
                bold: '700',
                extrabold: '800',
                black: '900',
            },
            keyframes: {
                bellShake: {
                    '0%, 100%': { transform: 'rotate(0deg)' },
                    '15%':      { transform: 'rotate(-12deg)' },
                    '30%':      { transform: 'rotate(12deg)' },
                    '45%':      { transform: 'rotate(-8deg)' },
                    '60%':      { transform: 'rotate(8deg)' },
                    '75%':      { transform: 'rotate(-4deg)' },
                    '90%':      { transform: 'rotate(4deg)' },
                },
                badgePop: {
                    '0%':   { transform: 'scale(0.6)', opacity: '0' },
                    '60%':  { transform: 'scale(1.15)', opacity: '1' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                ripple: {
                    '0%':   { transform: 'scale(1)', opacity: '0.6' },
                    '100%': { transform: 'scale(2.4)', opacity: '0' },
                },
                dropdownIn: {
                    from: { opacity: '0', transform: 'translateY(-8px) scale(0.97)' },
                    to:   { opacity: '1', transform: 'translateY(0) scale(1)' },
                },
                vendorScroll: {
                    '0%':   { transform: 'translateY(0)' },
                    '100%': { transform: 'translateY(-50%)' },
                },
                pinFloat: {
                    '0%, 100%': { transform: 'translate(0, 0)' },
                    '33%':      { transform: 'translate(5px, -7px)' },
                    '66%':      { transform: 'translate(-6px, -3px)' },
                },
                marqueeX: {
                    '0%':   { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                authRipple: {
                    '0%':   { transform: 'translate(-50%, -50%) scale(0.16)', opacity: '0' },
                    '12%':  { opacity: '0.55' },
                    '100%': { transform: 'translate(-50%, -50%) scale(1)', opacity: '0' },
                },
                authDrop: {
                    '0%':   { transform: 'translate(-50%, -50%) scale(0)', opacity: '0.7' },
                    '100%': { transform: 'translate(-50%, -50%) scale(1)', opacity: '0' },
                },
                authBob: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%':      { transform: 'translateY(-7px)' },
                },
            },
            animation: {
                'bell-shake':    'bellShake 0.6s ease',
                'badge-pop':     'badgePop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1)',
                'ripple':        'ripple 0.6s ease-out forwards',
                'dropdown-in':   'dropdownIn 0.2s cubic-bezier(0.16, 1, 0.3, 1)',
                'vendor-scroll': 'vendorScroll 14s linear infinite',
                'pin-float-1':   'pinFloat 7s ease-in-out infinite',
                'pin-float-2':   'pinFloat 9s ease-in-out 0.6s infinite',
                'pin-float-3':   'pinFloat 8s ease-in-out 1.2s infinite',
                'marquee':       'marqueeX 50s linear infinite',
                'auth-ripple':   'authRipple 4.5s ease-out infinite',
                'auth-drop':     'authDrop 2s ease-out infinite',
                'auth-bob':      'authBob 5s ease-in-out infinite',
            },
        },
    },
    plugins: [
        plugin(({ addUtilities }) => {
            addUtilities({
                '.scrollbar-thin-dark': {
                    '&::-webkit-scrollbar':       { width: '4px' },
                    '&::-webkit-scrollbar-track': { background: 'transparent' },
                    '&::-webkit-scrollbar-thumb': {
                        background: 'rgba(255,255,255,0.1)',
                        borderRadius: '4px',
                    },
                },
            });
        }),
    ],
};
