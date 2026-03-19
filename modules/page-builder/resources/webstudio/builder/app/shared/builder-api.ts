import { createRecursiveProxy } from "@trpc/server/shared";
import invariant from "tiny-invariant";
import { toast } from "@webstudio-is/design-system";
import { uploadAssets } from "~/builder/shared/assets/upload-assets";
import { showTokenConflictDialog } from "./token-conflict-dialog";

const apiWindowNamespace = "__webstudio__$__builderApi";

type ToastHandler = (message: string) => void;

const isSafeMode = (() => {
  if (typeof window === "undefined") {
    return false;
  }
  return new URLSearchParams(window.location.search).get("safemode") === "true";
})();

const _builderApi = {
  isInitialized: () => true,
  isSafeMode: () => isSafeMode,
  toast: {
    info: toast.info as ToastHandler,
    warn: toast.warn as ToastHandler,
    error: toast.error as ToastHandler,
    success: toast.success as ToastHandler,
  },
  uploadImages: async (srcs: string[]) => {
    const urlToIds = await uploadAssets(
      "image",
      srcs.map((src) => new URL(src))
    );

    return new Map([...urlToIds.entries()].map(([url, id]) => [url.href, id]));
  },
  showTokenConflictDialog,
};

declare global {
  interface Window {
    [apiWindowNamespace]: typeof _builderApi;
  }
}

const getAncestorApi = () => {
  let current: Window | null = window;

  while (current) {
    try {
      const api = current[apiWindowNamespace];
      if (api) {
        return api;
      }

      if (current.parent === current) {
        break;
      }

      current = current.parent;
    } catch {
      // Stop on cross-origin boundaries and use fallback behavior.
      break;
    }
  }

  return;
};

const isKeyOf = <T>(key: unknown, obj: T): key is keyof T => {
  // eslint-disable-next-line @typescript-eslint/ban-ts-comment
  // @ts-ignore
  return key in obj;
};

/**
 * Forwards the call from the builder to the iframe, invoking the original API in the iframe.
 */
export const builderApi = createRecursiveProxy((options) => {
  const api = getAncestorApi();
  const methodPath = options.path.join(".");

  if (api == null) {
    // These methods are polled very early during bootstrap, so return
    // safe defaults until iframe API has finished initialization.
    if (
      methodPath === ("isInitialized" satisfies keyof typeof _builderApi) ||
      methodPath === ("isSafeMode" satisfies keyof typeof _builderApi)
    ) {
      return false;
    }

    console.warn(
      `API not found in the iframe, skipping ${methodPath} call, iframe probably not loaded yet`
    );
    return null;
  }

  let currentMethod = api as unknown;

  for (const key of options.path) {
    invariant(
      isKeyOf(key, currentMethod),
      `API method ${options.path.join(".")} not found`
    );
    invariant(currentMethod != null);
    invariant(
      typeof currentMethod === "object" || typeof currentMethod === "function"
    );

    currentMethod = currentMethod[key];
  }

  invariant(
    typeof currentMethod === "function",
    `API method ${options.path.join(".")} is not a function`
  );

  return currentMethod.call(null, ...options.args);
}) as typeof _builderApi;

/**
 * Initializes the builder API in the window. Must be called in the builder context.
 */
export const initBuilderApi = () => {
  window[apiWindowNamespace] = _builderApi;
  return () => {};
};
