import { fetch } from "~/shared/fetch.client";
import {
  adminFolderMovePath,
  adminFolderPath,
  adminFoldersPath,
} from "~/shared/router-utils/path-utils";

type FolderPayload = {
  id: string;
  name: string;
  slug: string;
  parentFolderId: string;
};

const request = async (url: string, init: RequestInit) => {
  const response = await fetch(url, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(init.headers ?? {}),
    },
  });

  if (!response.ok) {
    const json = (await response.json().catch(() => null)) as
      | {
          message?: string;
        }
      | null;

    throw new Error(json?.message ?? "Folder API request failed");
  }

  return response;
};

export const createFolderOnServer = async (payload: FolderPayload) => {
  await request(adminFoldersPath(), {
    method: "POST",
    body: JSON.stringify({
      folder_id: payload.id,
      name: payload.name,
      slug: payload.slug,
      parent_folder_id: payload.parentFolderId,
    }),
  });
};

export const updateFolderOnServer = async (
  folderId: string,
  payload: Partial<Omit<FolderPayload, "id">>
) => {
  await request(adminFolderPath(folderId), {
    method: "PUT",
    body: JSON.stringify({
      ...(payload.name !== undefined ? { name: payload.name } : {}),
      ...(payload.slug !== undefined ? { slug: payload.slug } : {}),
      ...(payload.parentFolderId !== undefined
        ? { parent_folder_id: payload.parentFolderId }
        : {}),
    }),
  });
};

export const deleteFolderOnServer = async (folderId: string) => {
  await request(adminFolderPath(folderId), {
    method: "DELETE",
  });
};

export const moveFolderItemOnServer = async (payload: {
  itemType: "folder" | "page";
  itemId: string;
  parentFolderId: string;
  indexWithinChildren?: number;
}) => {
  await request(adminFolderMovePath(), {
    method: "POST",
    body: JSON.stringify({
      item_type: payload.itemType,
      item_id: payload.itemId,
      parent_folder_id: payload.parentFolderId,
      ...(payload.indexWithinChildren !== undefined
        ? { index_within_children: payload.indexWithinChildren }
        : {}),
    }),
  });
};
