import { $authToken } from "./nano-states";

/**
 * To avoid fetch interception from the canvas, i.e., `globalThis.fetch = () => console.log('INTERCEPTED');`,
 */
const _fetch = globalThis.fetch;

/**
 * To avoid fetch interception from the canvas, i.e., `globalThis.fetch = () => console.log('INTERCEPTED');`,
 */
export const fetch: typeof globalThis.fetch = (requestInfo, requestInit) => {
  const headers = new Headers(requestInit?.headers);

  const authToken = $authToken.get();

  // Do not override the existing x-auth-token header.
  // As some mutations are queue based and they need to be authenticated with the same token as in the queue.
  if (authToken !== undefined && headers.get("x-auth-token") === null) {
    headers.set("x-auth-token", authToken);
  }

  const modifiedInit: RequestInit = {
    ...requestInit,
    headers,
  };

  return _fetch(requestInfo, modifiedInit);
};
