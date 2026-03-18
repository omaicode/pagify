import type { Asset } from "@webstudio-is/sdk";
import { toast } from "@webstudio-is/design-system";
import { $assets } from "~/shared/nano-states";
import { serverSyncStore } from "~/shared/sync/sync-stores";
import { invalidateAssets } from "~/shared/resources";
import { fetch } from "~/shared/fetch.client";
import { restAssetDeletePath } from "~/shared/router-utils";

export const deleteAssets = async (assetIds: Asset["id"][]) => {
  try {
    for (const assetId of assetIds) {
      const response = await fetch(restAssetDeletePath(assetId), {
        method: "DELETE",
        headers: {
          Accept: "application/json",
        },
      });

      if (!response.ok) {
        const json = (await response.json().catch(() => null)) as
          | {
              message?: string;
              errors?: string;
            }
          | null;

        throw new Error(
          json?.message ?? json?.errors ?? "Delete asset API failed"
        );
      }

      serverSyncStore.createTransaction([$assets], (assets) => {
        assets.delete(assetId);
      });
    }

    invalidateAssets();
    toast.success(assetIds.length === 1 ? "Asset deleted" : "Assets deleted");
  } catch (error) {
    toast.error(
      error instanceof Error ? error.message : "Unable to delete asset"
    );
  }
};
