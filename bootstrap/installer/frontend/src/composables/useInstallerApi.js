import { ref } from 'vue';

export function useInstallerApi({ apiBase, csrfToken }) {
  const loading = ref(false);
  const lastError = ref(null);

  async function request(path, method = 'GET', body = null) {
    loading.value = true;
    lastError.value = null;

    try {
      const response = await fetch(`${apiBase}${path}`, {
        method,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: body ? JSON.stringify(body) : null,
      });

      const json = await response.json();

      if (!response.ok || json.success === false) {
        throw json;
      }

      return json;
    } catch (error) {
      lastError.value = error;
      throw error;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    lastError,
    request,
  };
}
