module.exports = {
  darkMode: 'media', // Cambia a 'class' para controlar manualmente el modo oscuro
  content: [
    './app/**/*.{js,ts,jsx,tsx}',
    './components/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {},
  },
  plugins: [
    tailwindcss,
    autoprefixer,
  ],
};