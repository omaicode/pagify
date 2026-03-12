module.exports = {
  content: [
    './layouts/**/*.twig',
    './components/**/*.twig',
    './pages/**/*.twig',
    './src/js/**/*.js',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        body: ['Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif']
      }
    }
  },
  plugins: []
};
