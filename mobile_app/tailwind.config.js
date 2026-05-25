/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/app/**/*.{js,jsx,ts,tsx}",
    "./src/components/**/*.{js,jsx,ts,tsx}",
  ],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        primary: "#00E096",
        secondary: "#064E3B",
        "surface-dark": "#062C25",
        "forest-dark": "#022C22",
      }
    },
  },
  plugins: [],
}
