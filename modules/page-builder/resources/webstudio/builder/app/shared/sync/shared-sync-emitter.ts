import type { SyncEmitter } from "../sync-client";

export const getSharedSyncEmitter = (
  currentWindow:
    | (Pick<Window, "parent"> & {
        __webstudioSharedSyncEmitter__?: SyncEmitter;
      })
    | undefined = typeof window === "undefined" ? undefined : window
) => {
  if (currentWindow === undefined) {
    return undefined;
  }

  if (currentWindow.__webstudioSharedSyncEmitter__) {
    return currentWindow.__webstudioSharedSyncEmitter__;
  }

  try {
    return currentWindow.parent?.__webstudioSharedSyncEmitter__;
  } catch {
    return undefined;
  }
};
