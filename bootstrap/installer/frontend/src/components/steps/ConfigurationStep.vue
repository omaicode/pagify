<script setup>
import { nextTick, reactive, ref, watch } from 'vue';

const props = defineProps({
  labels: {
    type: Object,
    required: true,
  },
  submitLabel: String,
  disabled: Boolean,
  initialAppUrl: String,
  initialConfiguration: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['submit']);
const mailEnabled = ref(false);
const hydratedFromServer = ref(false);

const form = reactive({
  project_name: 'Pagify',
  app_url: props.initialAppUrl,
  db: {
    connection: 'mysql',
    host: '127.0.0.1',
    port: 3306,
    database: '',
    username: '',
    password: '',
  },
  mail: {
    mailer: 'smtp',
    host: '127.0.0.1',
    port: 1025,
    username: '',
    password: '',
    encryption: 'tls',
    from_address: 'hello@example.com',
    from_name: 'Pagify',
  },
  admin: {
    name: 'Administrator',
    username: 'admin',
    email: 'admin@example.com',
    password: 'password',
  },
});

function submit() {
  const payload = {
    project_name: form.project_name,
    app_url: form.app_url,
    db: {
      connection: form.db.connection,
      host: form.db.host,
      port: form.db.port,
      database: form.db.database,
      username: form.db.username,
      password: form.db.password,
    },
    mail: {
      mailer: form.mail.mailer,
      host: form.mail.host,
      port: form.mail.port,
      username: form.mail.username,
      password: form.mail.password,
      encryption: form.mail.encryption,
      from_address: form.mail.from_address,
      from_name: form.mail.from_name,
    },
    admin: {
      name: form.admin.name,
      username: form.admin.username,
      email: form.admin.email,
      password: form.admin.password,
    },
  };

  if (!mailEnabled.value) {
    payload.mail = {
      mailer: 'log',
    };
  }

  emit('submit', payload);
}

function hydrateFromServer(config) {
  if (!config || typeof config !== 'object') {
    return;
  }

  form.project_name = config.project_name ?? form.project_name;
  form.app_url = config.app_url ?? form.app_url;

  form.db.connection = config.db?.connection ?? form.db.connection;
  form.db.host = config.db?.host ?? form.db.host;
  form.db.port = config.db?.port ?? form.db.port;
  form.db.database = config.db?.database ?? form.db.database;
  form.db.username = config.db?.username ?? form.db.username;
  form.db.password = config.db?.password ?? form.db.password;

  form.mail.mailer = config.mail?.mailer ?? form.mail.mailer;
  form.mail.host = config.mail?.host ?? form.mail.host;
  form.mail.port = config.mail?.port ?? form.mail.port;
  form.mail.username = config.mail?.username ?? form.mail.username;
  form.mail.password = config.mail?.password ?? form.mail.password;
  form.mail.encryption = config.mail?.encryption ?? form.mail.encryption;
  form.mail.from_address = config.mail?.from_address ?? form.mail.from_address;
  form.mail.from_name = config.mail?.from_name ?? form.mail.from_name;

  form.admin.name = config.admin?.name ?? form.admin.name;
  form.admin.username = config.admin?.username ?? form.admin.username;
  form.admin.email = config.admin?.email ?? form.admin.email;
  form.admin.password = config.admin?.password ?? form.admin.password;

  const mailer = config.mail?.mailer ?? '';
  mailEnabled.value = !['', 'log', 'array'].includes(mailer);
}

function fieldError(field) {
  return props.errors?.[field] ?? '';
}

function fieldClass(field) {
  return fieldError(field)
    ? 'mt-1 w-full rounded-xl border border-rose-400 px-3 py-2 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-200'
    : 'mt-1 w-full rounded-xl border border-slate-300 px-3 py-2';
}

watch(
  () => props.errors,
  async (errors) => {
    const firstField = Object.keys(errors ?? {})[0];

    if (!firstField) {
      return;
    }

    await nextTick();

    const firstInput = document.querySelector(`[data-field="${firstField}"]`);
    if (firstInput instanceof HTMLElement) {
      firstInput.focus();
      firstInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  },
  { deep: true }
);

watch(
  () => props.initialConfiguration,
  (config) => {
    if (hydratedFromServer.value) {
      return;
    }

    if (!config || Object.keys(config).length === 0) {
      return;
    }

    hydrateFromServer(config);
    hydratedFromServer.value = true;
  },
  { immediate: true, deep: true }
);
</script>

<template>
  <section>
    <h3 class="text-xl font-bold text-slate-800">{{ labels.configuration }}</h3>

    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
      <div class="md:col-span-2 border-b border-slate-200 pb-1">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ labels.section_project ?? 'Project' }}</h4>
      </div>

      <label class="text-sm font-semibold text-slate-700">{{ labels.project_name }}
        <input v-model="form.project_name" data-field="project_name" :class="fieldClass('project_name')" />
        <p v-if="fieldError('project_name')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('project_name') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.app_url }}
        <input v-model="form.app_url" data-field="app_url" :class="fieldClass('app_url')" />
        <p v-if="fieldError('app_url')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('app_url') }}</p>
      </label>

      <div class="md:col-span-2 mt-2 border-b border-slate-200 pb-1">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ labels.section_database ?? 'Database' }}</h4>
      </div>

      <label class="text-sm font-semibold text-slate-700">{{ labels.db_connection }}
        <select v-model="form.db.connection" data-field="db.connection" :class="fieldClass('db.connection')">
          <option value="mysql">mysql</option>
          <option value="pgsql">pgsql</option>
          <option value="sqlite">sqlite</option>
        </select>
        <p v-if="fieldError('db.connection')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.connection') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.db_host }}
        <input v-model="form.db.host" data-field="db.host" :class="fieldClass('db.host')" />
        <p v-if="fieldError('db.host')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.host') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.db_port }}
        <input v-model.number="form.db.port" data-field="db.port" type="number" :class="fieldClass('db.port')" />
        <p v-if="fieldError('db.port')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.port') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.db_database }}
        <input v-model="form.db.database" data-field="db.database" :class="fieldClass('db.database')" />
        <p v-if="fieldError('db.database')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.database') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.db_username }}
        <input v-model="form.db.username" data-field="db.username" :class="fieldClass('db.username')" />
        <p v-if="fieldError('db.username')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.username') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.db_password }}
        <input v-model="form.db.password" data-field="db.password" type="password" :class="fieldClass('db.password')" />
        <p v-if="fieldError('db.password')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('db.password') }}</p>
      </label>

      <div class="md:col-span-2 mt-2 flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 pb-1">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ labels.section_mail ?? 'Mail server' }}</h4>
        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
          <input v-model="mailEnabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-ocean focus:ring-ocean" />
          {{ labels.mail_optional_toggle ?? 'Configure mail server (optional)' }}
        </label>
      </div>

      <div v-if="!mailEnabled" class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-600">
        {{ labels.mail_optional_hint ?? 'Mail server setup is optional. A local log mailer will be used for now.' }}
      </div>

      <template v-if="mailEnabled">

      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_mailer }}
        <select v-model="form.mail.mailer" data-field="mail.mailer" :class="fieldClass('mail.mailer')">
          <option value="smtp">smtp</option>
          <option value="log">log</option>
          <option value="array">array</option>
        </select>
        <p v-if="fieldError('mail.mailer')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.mailer') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_host }}
        <input v-model="form.mail.host" data-field="mail.host" :class="fieldClass('mail.host')" />
        <p v-if="fieldError('mail.host')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.host') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_port }}
        <input v-model.number="form.mail.port" data-field="mail.port" type="number" :class="fieldClass('mail.port')" />
        <p v-if="fieldError('mail.port')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.port') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_username }}
        <input v-model="form.mail.username" data-field="mail.username" :class="fieldClass('mail.username')" />
        <p v-if="fieldError('mail.username')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.username') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_password }}
        <input v-model="form.mail.password" data-field="mail.password" type="password" :class="fieldClass('mail.password')" />
        <p v-if="fieldError('mail.password')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.password') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_encryption }}
        <select v-model="form.mail.encryption" data-field="mail.encryption" :class="fieldClass('mail.encryption')">
          <option value="tls">tls</option>
          <option value="ssl">ssl</option>
        </select>
        <p v-if="fieldError('mail.encryption')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.encryption') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_from_address }}
        <input v-model="form.mail.from_address" data-field="mail.from_address" :class="fieldClass('mail.from_address')" />
        <p v-if="fieldError('mail.from_address')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.from_address') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.mail_from_name }}
        <input v-model="form.mail.from_name" data-field="mail.from_name" :class="fieldClass('mail.from_name')" />
        <p v-if="fieldError('mail.from_name')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('mail.from_name') }}</p>
      </label>
      </template>

      <div class="md:col-span-2 mt-2 border-b border-slate-200 pb-1">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ labels.section_admin ?? 'Administrator' }}</h4>
      </div>

      <label class="text-sm font-semibold text-slate-700">{{ labels.admin_name }}
        <input v-model="form.admin.name" data-field="admin.name" :class="fieldClass('admin.name')" />
        <p v-if="fieldError('admin.name')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('admin.name') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.admin_username }}
        <input v-model="form.admin.username" data-field="admin.username" :class="fieldClass('admin.username')" />
        <p v-if="fieldError('admin.username')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('admin.username') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.admin_email }}
        <input v-model="form.admin.email" data-field="admin.email" :class="fieldClass('admin.email')" />
        <p v-if="fieldError('admin.email')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('admin.email') }}</p>
      </label>
      <label class="text-sm font-semibold text-slate-700">{{ labels.admin_password }}
        <input v-model="form.admin.password" data-field="admin.password" type="password" :class="fieldClass('admin.password')" />
        <p v-if="fieldError('admin.password')" class="mt-1 text-xs font-medium text-rose-600">{{ fieldError('admin.password') }}</p>
      </label>
    </div>

    <div class="mt-4">
      <button class="btn-primary" type="button" :disabled="disabled" @click="submit">{{ submitLabel }}</button>
    </div>
  </section>
</template>
