import { useEffect, useState } from "react";
import { useStore } from "@nanostores/react";
import { Text, Tooltip, Switch, toast } from "@webstudio-is/design-system";
import { findPageByIdOrPath } from "@webstudio-is/sdk";
import { $selectedPage } from "~/shared/awareness";
import { $authTokenPermissions, $pages } from "~/shared/nano-states";
import { updateWebstudioData } from "~/shared/instance-utils";
import {
  isPersistedPageId,
  updatePageStatusOnServer,
} from "~/builder/features/pages/page-crud-api";

const normalizeStatus = (status: string | undefined) => {
  const normalized = String(status ?? "").trim().toLowerCase();
  return normalized === "published" ? "published" : "draft";
};

export const PublishSwitch = () => {
  const selectedPage = useStore($selectedPage);
  const pages = useStore($pages);
  const authTokenPermissions = useStore($authTokenPermissions);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const canPublish = authTokenPermissions.canPublish;
  const pageId = selectedPage?.id;
  const currentStatus = normalizeStatus(selectedPage?.meta.status);
  const isPublished = currentStatus === "published";
  const isPersisted = pageId !== undefined && isPersistedPageId(pageId);
  const [checked, setChecked] = useState(isPublished);

  useEffect(() => {
    setIsSubmitting(false);
  }, [pageId, currentStatus]);

  useEffect(() => {
    setChecked(isPublished);
  }, [isPublished, pageId]);

  const onToggle = async (checked: boolean) => {
    if (selectedPage === undefined || pages === undefined || isSubmitting) {
      return;
    }

    if (isPersisted === false) {
      toast.error("Please create a page first");
      return;
    }

    const nextStatus = checked ? "published" : "draft";
    if (nextStatus === currentStatus) {
      return;
    }

    setIsSubmitting(true);
    setChecked(checked);

    try {
      const persistedPage = await updatePageStatusOnServer(selectedPage, nextStatus);
      const persistedStatus = normalizeStatus(persistedPage?.status ?? nextStatus);

      updateWebstudioData((data) => {
        const page = findPageByIdOrPath(selectedPage.id, data.pages);
        if (page === undefined) {
          return;
        }
        page.meta.status = persistedStatus;
      });

      setChecked(persistedStatus === "published");

      toast.success(
        persistedStatus === "published" ? "Page published" : "Page set to draft"
      );
    } catch (error) {
      setChecked(isPublished);
      const message =
        error instanceof Error
          ? error.message
          : "Unable to update page publish status";
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const tooltip =
    canPublish === false
      ? "Only owner/admin or editors with publish permission can update publish status"
      : isPersisted === false
        ? "Create a page before publishing"
        : "Toggle publish status for current page";

  return (
    <Tooltip content={tooltip}>
      <label
        style={{
          display: "inline-flex",
          alignItems: "center",
          gap: 8,
          opacity: canPublish ? 1 : 0.6,
        }}
      >
        <Text variant="labels">Publish</Text>
        <Switch
          checked={checked}
          disabled={canPublish === false || isSubmitting}
          onCheckedChange={onToggle}
        />
      </label>
    </Tooltip>
  );
};
