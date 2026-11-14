/**
 * Mirrors the configuration the admin layout used with the Tailwind Play CDN (cdn.tailwindcss.com → 3.4.17),
 * so the compiled file renders identically. Paths are relative to this file.
 */
module.exports = {
  content: [
    '../../app/Views/admin/**/*.php',
    '../../app/Controllers/**/*.php',
    '../../app/Services/**/*.php',
    '../../app/Helpers/**/*.php',
    '../../public/assets/js/**/*.js',
    '../../public/offline.html',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          dark: '#072412',
          primary: '#1D6A34',
          accent: '#37CE97',
          hover: '#155227',
          light: '#EAF6EF',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
      },
    },
  },
};
