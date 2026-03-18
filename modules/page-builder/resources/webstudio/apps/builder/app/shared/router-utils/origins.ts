import { parseBuilderUrl } from "@webstudio-is/http-client";

const getNormalizedPathname = (urlStr: string) => {
  return new URL(urlStr).pathname.replace(/\/+$/, "");
};

const isPagifyEmbeddedBuilderPath = (pathname: string) => {
  if (pathname.endsWith("/page-builder/editor-spa")) {
    return true;
  }

  if (pathname.endsWith("/page-builder/editor-spa/canvas")) {
    return true;
  }

  return false;
};

export const getRequestOrigin = (urlStr: string) => {
  const url = new URL(urlStr);

  return url.origin;
};

export const isCanvas = (urlStr: string): boolean => {
  const url = new URL(urlStr);
  const projectId = url.searchParams.get("projectId");

  return projectId !== null;
};

export const isBuilderUrl = (urlStr: string): boolean => {
  const { projectId } = parseBuilderUrl(urlStr);
  if (projectId !== undefined) {
    return true;
  }

  const pathname = getNormalizedPathname(urlStr);

  return isPagifyEmbeddedBuilderPath(pathname);
};

export const getAuthorizationServerOrigin = (urlStr: string): string => {
  const origin = getRequestOrigin(urlStr);
  const { sourceOrigin } = parseBuilderUrl(origin);
  return sourceOrigin;
};
