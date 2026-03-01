/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './public/**/*.php',
    './pages/**/*.php',
    './accounts/**/*.php',
    './admin/**/*.php',
  ],
  theme: {
    extend: {
      colors: {
        indigo: '#1e0e62',
        lavender: '#f5f3ff',
        'dark-bg': '#0f0a2e',
      },
    },
  },
  plugins: [],
};
