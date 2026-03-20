/**
 * The file is named _ui.(builder) instead of _ui._index due to an issue with Vercel.
 * The _ui._index route isn’t recognized on Vercel, even though it works perfectly in other environments.
 */

import { lazy } from "react";
import * as React from "react";
import {
  useLoaderData,
  type ClientLoaderFunctionArgs,
} from "@remix-run/react";
import type { MetaFunction, ShouldRevalidateFunction } from "@remix-run/react";
import { dashboardPath, isBuilder, isDashboard } from "~/shared/router-utils";

import builderStyles from "~/builder/builder.css?url";
import { ClientOnly } from "~/shared/client-only";
export { ErrorBoundary } from "~/shared/error/error-boundary";

export const links = () => {
  return [{ rel: "stylesheet", href: builderStyles }];
};

export const meta: MetaFunction = ({ data }) => {
  const metas: ReturnType<MetaFunction> = [];

  if (data === undefined) {
    return metas;
  }

  // Project title will be set dynamically after data loads
  return metas;
};

const PAGIFY_DEFAULT_PROJECT_ID = "unified";
const ACCESS_TOKEN_JWT_PATTERN =
  /^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/;

const createAccessTokenError = (message: string, description?: string) => {
  return new Response(
    JSON.stringify({
      message,
      description,
    }),
    {
      status: 401,
      headers: {
        "Content-Type": "application/json",
      },
    }
  );
};

const assertValidAccessToken = ({
  accessToken,
}: {
  accessToken: string;
}) => {
  if (ACCESS_TOKEN_JWT_PATTERN.test(accessToken) === false) {
    throw createAccessTokenError(
      "Invalid accessToken.",
      "accessToken must be a valid JWT."
    );
  }
};

const createPagifyLocalBuilderPayload = ({
  projectId,
  authToken,
}: {
  projectId: string;
  authToken: string;
}) => {
  return {
    projectId,
    authToken,
    authTokenPermissions: {
      canClone: true,
      canCopy: true,
      canPublish: true,
    },
    authPermit: "own" as const,
    userPlanFeatures: {
      allowAdditionalPermissions: true,
      allowDynamicData: true,
      allowContentMode: true,
      allowStagingPublish: true,
      maxContactEmails: 10,
      maxDomainsAllowedPerUser: Number.MAX_SAFE_INTEGER,
      maxPublishesAllowedPerUser: Number.MAX_SAFE_INTEGER,
      purchases: [{ planName: "pagify-spa" }],
    },
    stagingUsername: "",
    stagingPassword: "",
  };
};

export const clientLoader = async ({
  request,
}: ClientLoaderFunctionArgs) => {
  if (isDashboard(request)) {
    throw new Response(null, {
      status: 302,
      headers: {
        Location: dashboardPath(),
      },
    });
  }

  if (isBuilder(request) === false) {
    throw new Response("Not Found", {
      status: 404,
    });
  }

  const url = new URL(request.url);

  const projectId =
    url.searchParams.get("projectId") ??
    url.searchParams.get("theme") ??
    PAGIFY_DEFAULT_PROJECT_ID;

  const authToken = url.searchParams.get("accessToken");

  if (authToken === null || authToken === "") {
    throw createAccessTokenError(
      "Missing accessToken.",
      "Please provide accessToken in URL query."
    );
  }

  assertValidAccessToken({
    accessToken: authToken,
  });

  return createPagifyLocalBuilderPayload({ projectId, authToken });
};

clientLoader.hydrate = true;

const Builder = lazy(async () => {
  const { Builder } = await import("~/builder/index.client");
  return { default: Builder };
});

const BuilderRoute = () => {
  const data = useLoaderData<typeof clientLoader>();

  return (
    <ClientOnly>
      {/* Using a key here ensures that certain effects are re-executed inside the builder,
      especially in cases like cloning a project */}
      <Builder key={data.projectId} {...data} />
    </ClientOnly>
  );
};

/**
 * We do not want trpc and other mutations that use the Remix useFetcher hook
 * to cause a reload of all builder data.
 */
export const shouldRevalidate: ShouldRevalidateFunction = ({
  currentUrl,
  nextUrl,
  defaultShouldRevalidate,
}) => {
  const currentUrlCopy = new URL(currentUrl);
  const nextUrlCopy = new URL(nextUrl);
  // prevent revalidating data when pageId changes
  // to not regenerate auth token and preserve canvas url
  currentUrlCopy.searchParams.delete("pageId");
  nextUrlCopy.searchParams.delete("pageId");

  currentUrlCopy.searchParams.delete("mode");
  nextUrlCopy.searchParams.delete("mode");

  currentUrlCopy.searchParams.delete("pageHash");
  nextUrlCopy.searchParams.delete("pageHash");

  return currentUrlCopy.href === nextUrlCopy.href
    ? false
    : defaultShouldRevalidate;
};

export default BuilderRoute;
