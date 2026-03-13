<script setup>
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import StepTabs from './components/StepTabs.vue';
import StatusPanel from './components/StatusPanel.vue';
import SystemChecksStep from './components/steps/SystemChecksStep.vue';
import ConfigurationStep from './components/steps/ConfigurationStep.vue';
import PurposeStep from './components/steps/PurposeStep.vue';
import SelectionStep from './components/steps/SelectionStep.vue';
import FinalizeStep from './components/steps/FinalizeStep.vue';
import { useInstallerStore } from './stores/installerStore';

const props = defineProps({
  apiBase: String,
  translations: Object,
  translationBundles: Object,
  locales: Array,
  locale: String,
  csrfToken: String,
});

const store = useInstallerStore();
store.init({
  apiBase: props.apiBase,
  csrfToken: props.csrfToken,
  translations: props.translations,
  translationBundles: props.translationBundles,
  locales: props.locales,
  locale: props.locale,
});

const {
  steps,
  currentStep,
  completedSteps,
  checks,
  plugins,
  themes,
  statusType,
  statusMessage,
  loading,
  fieldErrors,
  installerState,
  translations,
  currentLocale,
  locales,
  maxUnlockedStep,
} = storeToRefs(store);

const t = computed(() => translations.value ?? {});
const initialAppUrl = typeof window !== 'undefined' ? window.location.origin : '';
const localeOptions = computed(() => {
  const fromStore = Array.isArray(locales.value) ? locales.value : [];
  const fromLabels = Object.keys(t.value?.locale_names ?? {});

  return Array.from(new Set([...fromStore, ...fromLabels, 'en', 'vi'])).filter(
    (item) => typeof item === 'string' && item !== ''
  );
});

function goStep(step) {
  store.goStep(step);
}

function onChangeLocale(locale) {
  const switched = store.setLocale(locale);

  if (switched) {
    return;
  }

  if (typeof window === 'undefined') {
    return;
  }

  const url = new URL(window.location.href);
  url.searchParams.set('lang', locale);
  window.location.assign(url.toString());
}

function readCheckedSlugs() {
  const section = document.querySelector(`section[data-step=\"${currentStep.value}\"]`);
  const checked = section ? Array.from(section.querySelectorAll('input[type=checkbox]:checked')) : [];
  return checked.map((input) => input.value);
}

async function refreshState() {
  await store.refreshState();
}

async function onRunChecks() {
  await store.runChecks();
}

async function onSaveConfiguration(payload) {
  await store.saveConfiguration(payload);
}

async function onSavePurpose(payload) {
  await store.savePurpose(payload);
}

async function onInstallPlugins() {
  const slugs = readCheckedSlugs();
  await store.installPlugins(slugs);
}

async function onSkipPlugins() {
  await store.skipPlugins();
}

async function onInstallThemes() {
  const slugs = readCheckedSlugs();
  await store.installThemes(slugs);
}

async function onSkipThemes() {
  await store.skipThemes();
}

async function onPreflight() {
  await store.preflight();
}

async function onFinalize() {
  const redirect = await store.finalize();
  if (redirect) {
    window.location.assign(redirect);
  }
}

async function safe(action) {
  await store.runSafely(action);
}

safe(refreshState);
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-8">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-card">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-3xl font-extrabold text-slate-800">{{ t.title }}</h1>
          <p class="mt-2 text-slate-600">{{ t.subtitle }}</p>
        </div>

        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
          <label class="text-sm font-semibold text-slate-700" for="installer-locale">{{ t.language ?? 'Language' }}</label>
          <select
            id="installer-locale"
            class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-700"
            :value="currentLocale"
            @change="onChangeLocale($event.target.value)"
          >
            <option v-for="locale in localeOptions" :key="locale" :value="locale">
              {{ t.locale_names?.[locale] ?? locale.toUpperCase() }}
            </option>
          </select>
        </div>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-[300px_1fr]">
        <StepTabs
          :steps="steps"
          :current-step="currentStep"
          :completed-steps="completedSteps"
          :step-label="t.step ?? 'Step'"
          :completed-label="t.completed ?? 'Completed'"
          :pending-label="t.pending ?? 'Pending'"
          :locked-label="t.locked ?? 'Locked'"
          :max-unlocked-step="maxUnlockedStep"
          @go="goStep"
        />

        <div class="rounded-2xl border border-dashed border-slate-200 bg-gradient-to-br from-white to-slate-50 p-4">
          <section v-show="currentStep === 1" data-step="1">
            <SystemChecksStep
              :title="t.tabs?.checks"
              :summary="t.summaries?.checks ?? 'Checks permissions, extensions, version and PHP limits before moving forward.'"
              :run-label="t.run_checks ?? 'Run checks'"
              :retry-label="t.retry ?? 'Retry'"
              :checks="checks"
              :check-labels="t.system_checks?.labels ?? {}"
              :reason-label="t.system_checks?.reason_label ?? 'Reason'"
              :status-ok="t.status_ok ?? 'OK'"
              :status-error="t.status_error ?? 'Error'"
              :disabled="loading"
              @run="safe(onRunChecks)"
              @retry="safe(onRunChecks)"
            />
          </section>

          <section v-show="currentStep === 2" data-step="2">
            <ConfigurationStep
              :labels="{
                ...t.fields,
                configuration: t.tabs?.configuration,
              }"
              :submit-label="t.save_and_continue ?? 'Save and continue'"
              :disabled="loading"
              :errors="fieldErrors"
              :initial-configuration="installerState?.configuration ?? {}"
              :initial-app-url="initialAppUrl"
              @submit="(payload) => safe(() => onSaveConfiguration(payload))"
            />
          </section>

          <section v-show="currentStep === 3" data-step="3">
            <PurposeStep
              :title="t.tabs?.purpose"
              :field-label="t.fields?.purpose"
              :submit-label="t.save_and_continue ?? 'Save and continue'"
              :purpose-options="t.purpose_options ?? {}"
              :disabled="loading"
              @submit="(payload) => safe(() => onSavePurpose(payload))"
            />
          </section>

          <section v-show="currentStep === 4" data-step="4">
            <SelectionStep
              :title="t.tabs?.plugins"
              :button-label="t.install_selected_plugins ?? 'Install selected plugins'"
              :retry-label="t.retry ?? 'Retry'"
              :skip-label="t.skip_optional ?? 'Skip this optional step'"
              :empty-message="t.empty_states?.plugins ?? 'No plugin items available.'"
              :items="plugins"
              :disabled="loading"
              @install="safe(onInstallPlugins)"
              @retry="safe(onInstallPlugins)"
              @skip="safe(onSkipPlugins)"
            />
          </section>

          <section v-show="currentStep === 5" data-step="5">
            <SelectionStep
              :title="t.tabs?.themes"
              :button-label="t.install_selected_themes ?? 'Install selected themes'"
              :retry-label="t.retry ?? 'Retry'"
              :skip-label="t.skip_optional ?? 'Skip this optional step'"
              :empty-message="t.empty_states?.themes ?? 'No theme items available.'"
              :items="themes"
              :disabled="loading"
              @install="safe(onInstallThemes)"
              @retry="safe(onInstallThemes)"
              @skip="safe(onSkipThemes)"
            />
          </section>

          <section v-show="currentStep === 6" data-step="6">
            <FinalizeStep
              :title="t.tabs?.finalize"
              :summary="t.summaries?.finalize ?? 'Run preflight before finalizing. Installer will migrate database and create initial admin/site.'"
              :preflight-label="t.run_preflight ?? 'Run preflight'"
              :finalize-label="t.finalize ?? 'Finalize installation'"
              :disabled="loading"
              @preflight="safe(onPreflight)"
              @finalize="safe(onFinalize)"
            />
          </section>

          <StatusPanel
            :status-type="statusType"
            :status-message="statusMessage"
            :loading="loading"
            :loading-label="t.loading ?? 'Loading...'"
          />
        </div>
      </div>
    </div>
  </div>
</template>
