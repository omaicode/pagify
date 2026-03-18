import { toast } from "@webstudio-is/design-system";
import { csrfToken } from "./csrf.client";
import { $authToken } from "./nano-states";

/**
 * To avoid fetch interception from the canvas, i.e., `globalThis.fetch = () => console.log('INTERCEPTED');`,
 */
const _fetch = globalThis.fetch;

const resolveCsrfToken = () => {
  if (csrfToken !== undefined && csrfToken !== "") {
    return csrfToken;
  }

  const metaToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute("content");

  if (typeof metaToken === "string" && metaToken !== "") {
    return metaToken;
  }

  return undefined;
};

const isSafeMethod = (method: string) => {
  const upper = method.toUpperCase();
  return upper === "GET" || upper === "HEAD" || upper === "OPTIONS";
};

/**
 * To avoid fetch interception from the canvas, i.e., `globalThis.fetch = () => console.log('INTERCEPTED');`,
 * To add csrf token to the headers.
 */
export const fetch: typeof globalThis.fetch = (requestInfo, requestInit) => {
  const method =
    requestInit?.method ??
    (requestInfo instanceof Request ? requestInfo.method : "GET");

  const headers = new Headers(requestInit?.headers);
  const resolvedCsrfToken = resolveCsrfToken();

  // For initial builder bootstrap GET requests, do not hard-fail when CSRF is missing.
  // Mutating requests still send CSRF when available.
  if (resolvedCsrfToken !== undefined) {
    headers.set("X-CSRF-Token", resolvedCsrfToken);
  } else if (isSafeMethod(method) === false) {
    toast.error("CSRF token is not set.");
    console.warn("Missing CSRF token for mutating request", {
      method,
      requestInfo,
    });
  }

  const authToken = $authToken.get();

  // Do not override the existing x-auth-token header.
  // As some mutations are queue based and they need to be authenticated with the same token as in the queue.
  if (authToken !== undefined && headers.get("x-auth-token") === null) {
    headers.set("x-auth-token", authToken);
  }

  const modifiedInit: RequestInit = {
    ...requestInit,
    method,
    headers,
  };

  return _fetch(requestInfo, modifiedInit);
};
