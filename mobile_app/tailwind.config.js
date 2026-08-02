/** @type {import('tailwindcss').Config} */
const { colors } = require('./theme/tokens');

module.exports = {
  content: ["./App.{js,jsx,ts,tsx}", "./screens/**/*.{js,jsx,ts,tsx}", "./components/**/*.{js,jsx,ts,tsx}", "./navigation/**/*.{js,jsx,ts,tsx}"],
  presets: [require("nativewind/preset")],
  theme: {
    extend: {
      colors: {
        primary: colors.forest,
        secondary: colors.greenBright,
        background: colors.surface,
        surface: colors.white,
        text: colors.text,
        subtext: colors.textSecondary,
        mint: colors.mint,
        error: colors.error,
      },
      fontFamily: {
        sans: ['Outfit_400Regular'],
        medium: ['Outfit_500Medium'],
        semibold: ['Outfit_600SemiBold'],
        bold: ['Outfit_700Bold'],
      }
    },
  },
  plugins: [],
}
