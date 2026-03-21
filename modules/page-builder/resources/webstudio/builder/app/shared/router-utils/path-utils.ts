import type { AUTH_PROVIDERS } from "~/shared/session";
import { publicStaticEnv } from "~/env/env.static";
import { getAuthorizationServerOrigin } from "./origins";
import type { BuilderMode } from "../nano-states/misc";

const apiBasePath = () => {
  const globalApiBase = (window as typeof window & {
    __pagifyWebstudioApiBase?: string;
  }).__pagifyWebstudioApiBase;

  if (typeof globalApiBase !== "string") {
    return "";
  }

  return globalApiBase.replace(/\/$/, "");
};

const withApiBase = (path: string) => {
  const base = apiBasePath();

  if (base === "") {
    return path;
  }

  return `${base}${path}`;
};

const pageBuilderApiPrefix = () => {
  const pathname = window.location.pathname.replace(/\/+$/, "");
  const marker = "/page-builder/editor-spa";

  const markerIndex = pathname.indexOf(marker);

  if (markerIndex < 0) {
    return "/api/v1";
  }

  const adminBase = pathname.slice(0, markerIndex + "/page-builder".length);

  return `/api/v1${adminBase}`;
};

const withPageBuilderApiPrefix = (path: string) => {
  return withApiBase(`${pageBuilderApiPrefix()}${path}`);
};

const searchParams = (params: Record<string, string | undefined | null>) => {
  const searchParams = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (typeof value === "string") {
      searchParams.set(key, value);
    }
  }
  const asString = searchParams.toString();
  return asString === "" ? "" : `?${asString}`;
};

export const builderPath = ({
  pageId,
  authToken,
  pageHash,
  mode,
  parentOrigin,
  siteId,
  theme,
  safemode = false,
}: {
  pageId?: string;
  authToken?: string;
  pageHash?: string;
  mode?: "preview" | "content";
  parentOrigin?: string;
  siteId?: string;
  theme?: string;
  safemode?: boolean;
} = {}) => {
  return `/${searchParams({
    pageId,
    accessToken: authToken,
    pageHash,
    mode,
    parentOrigin,
    siteId,
    theme,
    safemode: safemode ? "true" : undefined,
  })}`;
};

export const builderUrl = (props: {
  projectId: string;
  pageId?: string;
  origin: string;
  authToken?: string;
  mode?: BuilderMode;
  safemode?: boolean;
}) => {
  const authServerOrigin = getAuthorizationServerOrigin(props.origin);

  const url = new URL(
    builderPath({
      pageId: props.pageId,
      authToken: props.authToken,
      safemode: props.safemode,
    }),
    authServerOrigin
  );

  const fragments = url.host.split(".");
  if (fragments.length <= 3) {
    fragments.splice(0, 0, "p-" + props.projectId);
  } else {
    // staging | development branches
    fragments[0] = "p-" + props.projectId + "-dot-" + fragments[0];
  }

  url.host = fragments.join(".");

  if (props.mode !== undefined) {
    url.searchParams.set("mode", props.mode);
  }

  return url.href;
};

export const dashboardPath = (
  view: "templates" | "search" | "projects" = "projects"
) => {
  if (view === "projects") {
    return `/dashboard`;
  }
  return `/dashboard/${view}`;
};

export const dashboardUrl = (props: { origin: string }) => {
  const authServerOrigin = getAuthorizationServerOrigin(props.origin);

  return `${authServerOrigin}/dashboard`;
};

export const cloneProjectUrl = (props: {
  origin: string;
  sourceAuthToken: string;
}) => {
  const authServerOrigin = getAuthorizationServerOrigin(props.origin);

  const searchParams = new URLSearchParams();
  searchParams.set("projectToCloneAuthToken", props.sourceAuthToken);

  return `${authServerOrigin}/dashboard?${searchParams.toString()}`;
};

export const loginPath = (params: {
  error?: (typeof AUTH_PROVIDERS)[keyof typeof AUTH_PROVIDERS];
  message?: string;
  returnTo?: string;
}) => `/login${searchParams(params)}`;

export const logoutPath = () => "/logout";
export const restLogoutPath = () =>
  withPageBuilderApiPrefix(`/dashboard-logout`);

export const userPlanSubscriptionPath = (subscriptionId?: string) => {
  const urlSearchParams = new URLSearchParams();
  urlSearchParams.set("return_url", window.location.href);
  if (subscriptionId) {
    urlSearchParams.set("subscription", subscriptionId);
  }

  return `/n8n/billing_portal/sessions?${urlSearchParams.toString()}`;
};

export const authCallbackPath = ({
  provider,
}: {
  provider: "google" | "github";
}) => `/auth/${provider}/callback`;

export const authPath = ({
  provider,
}: {
  provider: "google" | "github" | "dev";
}) => `/auth/${provider}`;

export const restAssetsPath = () => {
  return withPageBuilderApiPrefix(`/assets`);
};

export const restAssetDeletePath = (assetId: string) => {
  return withPageBuilderApiPrefix(`/assets/${assetId}`);
};

export const restAssetsUploadPath = ({
  name,
  width,
  height,
}: {
  name: string;
  width?: number | undefined;
  height?: number | undefined;
}) => {
  const urlSearchParams = new URLSearchParams();
  if (width !== undefined) {
    urlSearchParams.set("width", String(width));
  }
  if (height !== undefined) {
    urlSearchParams.set("height", String(height));
  }

  if (urlSearchParams.size > 0) {
    return withPageBuilderApiPrefix(
      `/assets/${name}?${urlSearchParams.toString()}`
    );
  }

  return withPageBuilderApiPrefix(`/assets/${name}`);
};

export const restPatchPath = () => {
  const urlSearchParams = new URLSearchParams();

  urlSearchParams.set("client-version", publicStaticEnv.VERSION);

  const urlSearchParamsString = urlSearchParams.toString();

  return withPageBuilderApiPrefix(`/patch${
    urlSearchParamsString ? `?${urlSearchParamsString}` : ""
  }`);
};

export const getCanvasUrl = () => {
  const currentUrl = new URL(window.location.href);
  const params = new URLSearchParams(currentUrl.search);
  const pathname = currentUrl.pathname.replace(/\/+$/, "");
  const basePath = pathname.endsWith("/canvas")
    ? pathname.slice(0, -"/canvas".length)
    : pathname;

  // pageHash changes often and should not force a full canvas iframe reload
  // (which can trigger repeated /data requests and resource refreshes).
  params.delete("pageHash");
  params.delete("canvas");
  // builder mode is UI state, not canvas identity. Keep it out of iframe src
  // to avoid full canvas reload when toggling preview/design/content.
  params.delete("mode");

  const query = params.toString();
  const canvasPath = `${basePath}/canvas`;

  return query === "" ? canvasPath : `${canvasPath}?${query}`;
};

export const restResourcesLoader = () =>
  withPageBuilderApiPrefix(`/resources-loader`);

export const restRegistryPath = () =>
  withPageBuilderApiPrefix(`/registry`);

export const restDataPath = (projectId: string) =>
  withPageBuilderApiPrefix(`/data/${projectId}`);

export const restPageDataPath = (projectId: string, pageId: string) =>
  withPageBuilderApiPrefix(`/data/${projectId}/pages/${pageId}`);

export const adminFoldersPath = () =>
  withPageBuilderApiPrefix(`/folders`);

export const adminFolderPath = (folderId: string) =>
  withPageBuilderApiPrefix(`/folders/${folderId}`);

export const adminFolderMovePath = () =>
  withPageBuilderApiPrefix(`/folders/move`);

export const trpcPath = () => withPageBuilderApiPrefix(`/trpc`);

export const adminPagesPath = () => withPageBuilderApiPrefix(`/pages`);

export const adminPagePath = (pageId: string) =>
  withPageBuilderApiPrefix(`/pages/${pageId}`);

export const adminPagePublishPath = (pageId: string) =>
  withPageBuilderApiPrefix(`/pages/${pageId}/publish`);

export const marketplacePath = (method: string) =>
  `/builder/marketplace/${method}`;
