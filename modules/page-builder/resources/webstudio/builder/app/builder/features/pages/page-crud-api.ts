import { fetch } from "~/shared/fetch.client";
import {
  adminPagePublishPath,
  adminPagePath,
  adminPagesPath,
} from "~/shared/router-utils/path-utils";
import type { Page } from "@webstudio-is/sdk";

type PageFormValues = {
  name: string;
  path: string;
  title: string;
  description?: string;
  isHomePage?: boolean;
};

type PersistedPage = {
  id: string;
  title: string;
  slug: string;
};

type PublishPayload = {
  page: Pick<Page, "id" | "name" | "path" | "title" | "meta">;
  interface?: Record<string, unknown>;
  layout?: Record<string, unknown>;
};

const stripExpressionQuotes = (value: string | undefined) => {
  const trimmed = String(value ?? "").trim();

  if (
    trimmed.length >= 2 &&
    ((trimmed.startsWith('"') && trimmed.endsWith('"')) ||
      (trimmed.startsWith("'") && trimmed.endsWith("'")))
  ) {
    return trimmed.slice(1, -1).trim();
  }

  return trimmed;
};

const slugFromPathOrName = (path: string, name: string) => {
  const candidate = path.replace(/^\/+/, "").trim();

  if (candidate !== "") {
    const normalizedPathSlug = candidate
      .replace(/\//g, "-")
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, "-")
      .replace(/-+/g, "-")
      .replace(/^-+|-+$/g, "");

    if (normalizedPathSlug !== "") {
      return normalizedPathSlug;
    }
  }

  return name
    .toLowerCase()
    .replace(/[^a-z0-9_-]/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-+|-+$/g, "");
};

const toPayload = (values: PageFormValues) => {
  const title = values.name.trim() === "" ? "Untitled" : values.name.trim();
  const slug = slugFromPathOrName(values.path, title);

  return {
    title,
    slug,
    ...(values.isHomePage !== undefined
      ? { is_home: values.isHomePage === true }
      : {}),
    seo_meta: {
      title: stripExpressionQuotes(values.title),
      description: stripExpressionQuotes(values.description),
    },
  };
};

const assertResponse = async (response: Response) => {
  const json = (await response.json().catch(() => null)) as
    | {
        success?: boolean;
        data?: PersistedPage;
        message?: string;
      }
    | null;

  if (!response.ok || json?.success !== true || json.data === undefined) {
    throw new Error(json?.message ?? "Page API request failed");
  }

  return json.data;
};

export const isPersistedPageId = (pageId: string) => /^\d+$/.test(pageId);

export const createPageOnServer = async (values: PageFormValues) => {
  const response = await fetch(adminPagesPath(), {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(toPayload(values)),
  });

  return assertResponse(response);
};

export const updatePageOnServer = async (
  pageId: Page["id"],
  values: PageFormValues
) => {
  if (!isPersistedPageId(pageId)) {
    return null;
  }

  const response = await fetch(adminPagePath(pageId), {
    method: "PUT",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(toPayload(values)),
  });

  return assertResponse(response);
};

export const publishPageOnServer = async (payload: PublishPayload) => {
  const page = payload.page;

  if (!isPersistedPageId(page.id)) {
    return null;
  }

  const response = await fetch(adminPagePublishPath(page.id), {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      page,
      interface: payload.interface,
      layout: payload.layout,
    }),
  });

  return assertResponse(response);
};

export const deletePageOnServer = async (pageId: Page["id"]) => {
  if (!isPersistedPageId(pageId)) {
    return;
  }

  const response = await fetch(adminPagePath(pageId), {
    method: "DELETE",
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    const json = (await response.json().catch(() => null)) as
      | {
          message?: string;
        }
      | null;

    throw new Error(json?.message ?? "Delete page API failed");
  }
};
