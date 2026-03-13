import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import './styles.css';

const payload = window.Pagify?.installerPayload ?? {};

const app = createApp(App, {
  apiBase: payload.apiBase ?? '/api/v1/install',
  translations: payload.translations ?? {},
  translationBundles: payload.translationBundles ?? {},
  locales: payload.locales ?? ['en'],
  locale: payload.locale ?? 'en',
  csrfToken: payload.csrfToken ?? '',
});

app.use(createPinia());
app.mount('#installer-app');
