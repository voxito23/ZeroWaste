/** Configuración de Tailwind CSS para el microservicio Flask — @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./templates/**/*.html",
    "./static/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
          primary: "#00E096",
          "primary-hover": "#00C281",
          secondary: "#064E3B",
          "forest-dark": "#022C22",
          "surface-light": "#ECFDF5",
          "surface-dark": "#062C25",
          "text-light": "#064E3B",
          "text-dark": "#D1FAE5",
          accent: "#34D399",
      },
      fontFamily: {
          sans: ['Inter', 'sans-serif'],
          display: ['Montserrat', 'sans-serif'],
      },
      maxWidth: {
          'desktop': '1440px',
      },
      animation: {
          'spin-slow': 'spin 1.5s linear infinite',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/container-queries')
  ]
}
