/** @type {import('tailwindcss').Config} */
module.exports = {
  presets: [require("nativewind/preset")],
  darkMode: "class",
  content: [
    "./app/**/*.{js,jsx,ts,tsx}",
    "./components/**/*.{js,jsx,ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: "#00E096",
        "primary-hover": "#00C281",
        secondary: "#064E3B",
        "surface-dark": "#062C25",
        "forest-dark": "#022C22",
        "surface-light": "#F0FDF4",
      }
    },
  },
  plugins: [],
}
