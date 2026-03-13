import { defineStore } from 'pinia';

export const useInstallerStore = defineStore('installer', {
  state: () => ({
    apiBase: '/api/v1/install',
    csrfToken: '',
    currentLocale: 'en',
    locales: ['en'],
    translationBundles: {},
    translations: {},
    steps: [],
    currentStep: 1,
    completedSteps: [],
    checks: {},
    plugins: [],
    themes: [],
    installerState: {},
    statusType: 'info',
    statusMessage: 'Installer state loaded.',
    debugText: '',
    fieldErrors: {},
    loading: false,
  }),

  getters: {
    maxUnlockedStep(state) {
      if (state.completedSteps.length === 0) {
        return 1;
      }

      return Math.min(6, Math.max(...state.completedSteps, 1) + 1);
    },
  },

  actions: {
    init(config) {
      this.apiBase = config.apiBase ?? '/api/v1/install';
      this.csrfToken = config.csrfToken ?? '';
      this.currentLocale = config.locale ?? 'en';
      this.translationBundles = config.translationBundles ?? {};

      if (Object.keys(this.translationBundles).length === 0) {
        this.translationBundles = {
          [this.currentLocale]: config.translations ?? {},
        };
      }

      const localesFromPayload = Array.isArray(config.locales) ? config.locales : [];
      const localesFromBundles = Object.keys(this.translationBundles);
      const mergedLocales = Array.from(new Set([...localesFromPayload, ...localesFromBundles])).filter(
        (item) => typeof item === 'string' && item !== ''
      );

      this.locales = mergedLocales.length > 0 ? mergedLocales : ['en'];

      if (!this.locales.includes(this.currentLocale)) {
        this.currentLocale = this.locales[0];
      }

      this.applyLocale(this.currentLocale);
    },

    applyLocale(locale) {
      this.currentLocale = locale;
      this.translations = this.translationBundles[locale] ?? this.translationBundles.en ?? {};

      const t = this.translations;
      this.steps = [
        { key: 'checks', number: 1, label: t.tabs?.checks ?? 'System checks' },
        { key: 'configuration', number: 2, label: t.tabs?.configuration ?? 'Configuration' },
        { key: 'purpose', number: 3, label: t.tabs?.purpose ?? 'Purpose' },
        { key: 'plugins', number: 4, label: t.tabs?.plugins ?? 'Plugins' },
        { key: 'themes', number: 5, label: t.tabs?.themes ?? 'Themes' },
        { key: 'finalize', number: 6, label: t.tabs?.finalize ?? 'Finalize' },
      ];

      this.statusMessage = t.messages?.state_loaded ?? 'Installer state loaded.';
    },

    setLocale(locale) {
      if (typeof locale !== 'string' || locale === '') {
        return false;
      }

      const hasBundle = Object.prototype.hasOwnProperty.call(this.translationBundles, locale);
      if (!hasBundle) {
        return false;
      }

      if (!this.locales.includes(locale)) {
        this.locales = [...this.locales, locale];
      }

      if (this.currentLocale === locale) {
        return true;
      }

      this.applyLocale(locale);
      this.setStatus('info', this.translations.messages?.locale_changed ?? 'Language updated.');
      return true;
    },

    goStep(step) {
      if (step > this.maxUnlockedStep) {
        const template = this.translations.messages?.step_locked ?? 'Complete previous steps before moving forward.';
        const message = template.replace('{step}', String(this.maxUnlockedStep));
        this.setStatus('error', message, {
          requested_step: step,
          max_unlocked_step: this.maxUnlockedStep,
        });
        return;
      }

      if (step !== 2) {
        this.fieldErrors = {};
      }

      this.currentStep = step;
    },

    setStatus(type, message, payload = null) {
      this.statusType = type;
      this.statusMessage = message;
      if (payload !== null) {
        this.debugText = JSON.stringify(payload, null, 2);
      }
    },

    readCookie(name) {
      if (typeof document === 'undefined') {
        return '';
      }

      const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const match = document.cookie.match(new RegExp(`(?:^|; )${escapedName}=([^;]*)`));
      return match ? decodeURIComponent(match[1]) : '';
    },

    resolveCsrfToken() {
      if (typeof document !== 'undefined') {
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaToken) {
          return metaToken;
        }

        const xsrf = this.readCookie('XSRF-TOKEN');
        if (xsrf) {
          return xsrf;
        }
      }

      return this.csrfToken;
    },

    async request(path, method = 'GET', body = null) {
      this.loading = true;
      const csrfToken = this.resolveCsrfToken();

      try {
        const response = await fetch(`${this.apiBase}${path}`, {
          method,
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'Accept-Language': this.currentLocale,
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'X-XSRF-TOKEN': csrfToken,
          },
          body: body ? JSON.stringify(body) : null,
        });

        const json = await response.json();

        if (!response.ok || json.success === false) {
          throw json;
        }

        return json;
      } finally {
        this.loading = false;
      }
    },

    async runSafely(action) {
      try {
        await action();
      } catch (error) {
        this.fieldErrors = this.extractFieldErrors(error);
        const { message, payload } = this.formatApiError(error);
        this.setStatus('error', message, payload);
      }
    },

    extractFieldErrors(error) {
      if (!error || typeof error !== 'object' || !error.errors || typeof error.errors !== 'object') {
        return {};
      }

      const mapped = {};

      for (const [key, value] of Object.entries(error.errors)) {
        if (Array.isArray(value) && value.length > 0) {
          mapped[key] = String(value[0]);
          continue;
        }

        if (typeof value === 'string' && value !== '') {
          mapped[key] = value;
        }
      }

      return mapped;
    },

    formatApiError(error) {
      const fallback = this.translations.messages?.request_failed ?? 'Request failed.';

      if (!error || typeof error !== 'object') {
        return {
          message: fallback,
          payload: error,
        };
      }

      const code = typeof error.code === 'string' ? error.code : '';
      const message = typeof error.message === 'string' && error.message !== '' ? error.message : fallback;
      const details = [];

      if (error.errors && typeof error.errors === 'object') {
        for (const value of Object.values(error.errors)) {
          if (Array.isArray(value)) {
            for (const item of value) {
              if (typeof item === 'string') {
                details.push(item);
              }
            }
          }
        }
      }

      if (code === 'INSTALLER_STEP_BLOCKED') {
        return {
          message: this.translations.messages?.step_blocked ?? message,
          payload: error,
        };
      }

      if (details.length > 0) {
        return {
          message: `${message} ${details[0]}`,
          payload: error,
        };
      }

      return {
        message,
        payload: error,
      };
    },

    async refreshState() {
      const res = await this.request('/state');
      this.installerState = res.data ?? {};
      this.completedSteps = Array.isArray(res.data?.completed_steps) ? res.data.completed_steps : [];
      this.currentStep = Math.min(this.maxUnlockedStep, 6);
      this.setStatus('info', this.translations.messages?.state_loaded ?? 'Installer state loaded.', res.data);
    },

    async runChecks() {
      const res = await this.request('/checks');
      this.checks = res.data?.checks ?? {};
      this.setStatus('success', this.translations.messages?.checks_success ?? 'Checks passed.', res.data);
      await this.refreshState();
      this.goStep(2);
    },

    async saveConfiguration(payload) {
      this.fieldErrors = {};
      const res = await this.request('/configuration', 'POST', payload);
      this.setStatus('success', this.translations.messages?.configuration_success ?? 'Configuration saved.', res.data);
      await this.refreshState();
      this.goStep(3);
    },

    async savePurpose(payload) {
      this.fieldErrors = {};
      const res = await this.request('/purpose', 'POST', payload);
      this.setStatus('success', this.translations.messages?.purpose_success ?? 'Purpose saved.', res.data);
      await this.refreshState();
      await this.loadPlugins();
      this.goStep(4);
    },

    async loadPlugins() {
      const res = await this.request('/plugins');
      this.plugins = Array.isArray(res.data?.items) ? res.data.items : [];
    },

    async loadThemes() {
      const res = await this.request('/themes');
      this.themes = Array.isArray(res.data?.items) ? res.data.items : [];
    },

    async installPlugins(slugs) {
      this.fieldErrors = {};
      if (this.plugins.length === 0) {
        await this.loadPlugins();
      }

      if (slugs.length === 0) {
        this.setStatus('error', this.translations.messages?.select_at_least_one ?? 'Please select at least one item.');
        return;
      }

      const res = await this.request('/plugins/install', 'POST', { slugs });
      this.setStatus('success', this.translations.messages?.plugins_success ?? 'Plugins installed.', res.data);
      await this.refreshState();
      await this.loadThemes();
      this.goStep(5);
    },

    async skipPlugins() {
      this.fieldErrors = {};
      const res = await this.request('/plugins/skip', 'POST', {});
      this.setStatus('success', this.translations.messages?.plugins_skipped ?? 'Plugin step skipped.', res.data);
      await this.refreshState();
      await this.loadThemes();
      this.goStep(5);
    },

    async installThemes(slugs) {
      this.fieldErrors = {};
      if (this.themes.length === 0) {
        await this.loadThemes();
      }

      if (slugs.length === 0) {
        this.setStatus('error', this.translations.messages?.select_at_least_one ?? 'Please select at least one item.');
        return;
      }

      const res = await this.request('/themes/install', 'POST', { slugs });
      this.setStatus('success', this.translations.messages?.themes_success ?? 'Themes installed.', res.data);
      await this.refreshState();
      this.goStep(6);
    },

    async skipThemes() {
      this.fieldErrors = {};
      const res = await this.request('/themes/skip', 'POST', {});
      this.setStatus('success', this.translations.messages?.themes_skipped ?? 'Theme step skipped.', res.data);
      await this.refreshState();
      this.goStep(6);
    },

    async preflight() {
      this.fieldErrors = {};
      const res = await this.request('/preflight', 'POST', {});
      this.setStatus('success', this.translations.messages?.preflight_success ?? 'Preflight passed.', res.data);
    },

    async finalize() {
      this.fieldErrors = {};
      const res = await this.request('/finalize', 'POST', {});
      this.setStatus('success', this.translations.messages?.finalize_success ?? 'Installation completed.', res.data);
      return res.data?.redirect_to ?? null;
    },
  },
});
