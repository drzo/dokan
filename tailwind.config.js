/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme')
module.exports = {
  content: [
    "./src/**/*.{html,js,jsx,ts,tsx,vue}"
  ],
  theme: {
    extend: {
      screens: {
        'xs': '360px',
        ...defaultTheme.screens,
      }
    },
  },
  plugins: [],
}
