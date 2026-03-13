/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        ink: '#102a43',
        ocean: '#0b7285',
        amber: '#f59f00',
      },
      boxShadow: {
        card: '0 24px 48px rgba(11, 114, 133, 0.09)',
      },
    },
  },
  plugins: [],
};
