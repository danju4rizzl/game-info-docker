/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './wp-content/plugins/pandascore-tracker/**/*.php',
    './wp-content/plugins/pandascore-tracker/**/*.js',
    './wp-content/plugins/pandascore-tracker/**/*.html'
  ],
  theme: {
    extend: {
      colors: {
        'ps-red': '#ff4444',
        'ps-green': '#4ade80',
        'ps-dark': '#1a1a1a',
        'ps-gray': '#2a2a2a'
      },
      fontFamily: {
        inter: ['Inter', 'sans-serif']
      },
      maxWidth: {
        'ps-card': '350px'
      }
    }
  },
  plugins: [],
  // Prefix all Tailwind classes to avoid conflicts with WordPress/theme styles
  prefix: 'tw-'
}
