import { useEffect, useMemo, useState } from "react";
import { useStore } from "@nanostores/react";
import {
  Button,
  Dialog,
  DialogContent,
  DialogTitle,
  Flex,
  Text,
  Tooltip,
  toast,
} from "@webstudio-is/design-system";
import { findPageByIdOrPath } from "@webstudio-is/sdk";
import { $selectedPage } from "~/shared/awareness";
import { $authTokenPermissions, $pages } from "~/shared/nano-states";
import { updateWebstudioData } from "~/shared/instance-utils";
import { serializeBuilderDataForPatch } from "~/shared/builder-data";
import {
  isPersistedPageId,
  publishPageOnServer,
} from "~/builder/features/pages/page-crud-api";
import { $pageRootScope } from "~/builder/features/pages/page-utils";
import React from "react";

export const PublishSwitch = () => {
  const selectedPage = useStore($selectedPage);
  const pages = useStore($pages);
  const authTokenPermissions = useStore($authTokenPermissions);
  const pageRootScope = useStore($pageRootScope);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isConfirmOpen, setIsConfirmOpen] = useState(false);

  const canPublish = authTokenPermissions.canPublish;
  const pageId = selectedPage?.id;
  const isPersisted = pageId !== undefined && isPersistedPageId(pageId);
  const pageName = useMemo(() => {
    const name = String(selectedPage?.name ?? "").trim();
    if (name !== "") {
      return name;
    }
    const path = String(selectedPage?.path ?? "").trim();
    return path !== "" ? path : "this page";
  }, [selectedPage?.name, selectedPage?.path]);

  useEffect(() => {
    setIsSubmitting(false);
  }, [pageId]);

  const onConfirmPublish = async () => {
    if (selectedPage === undefined || pages === undefined || isSubmitting) {
      return;
    }

    if (isPersisted === false) {
      toast.error("Please create a page first");
      return;
    }

    setIsSubmitting(true);

    try {
      await publishPageOnServer({
        page: selectedPage,
        interface: {
          ...serializeBuilderDataForPatch(),
          variableValues: Object.fromEntries(pageRootScope.variableValues),
        },
      });

      updateWebstudioData((data) => {
        const page = findPageByIdOrPath(selectedPage.id, data.pages);
        if (page === undefined) {
          return;
        }
        page.meta = page.meta ?? {};
      });

      toast.success("Page published");
      setIsConfirmOpen(false);
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : "Unable to publish page";
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
        : "Publish current page";

  return (
    <>
      <Tooltip content={tooltip}>
        <Button
          color="positive"
          disabled={canPublish === false || isSubmitting || isPersisted === false}
          state={isSubmitting ? "pending" : undefined}
          onClick={() => {
            if (canPublish === false || isPersisted === false || isSubmitting) {
              return;
            }
            setIsConfirmOpen(true);
          }}
        >
          Publish
        </Button>
      </Tooltip>

      <Dialog
        open={isConfirmOpen}
        onOpenChange={(open) => {
          if (isSubmitting) {
            return;
          }
          setIsConfirmOpen(open);
        }}
      >
        <DialogContent css={{ width: 460 }}>
          <DialogTitle>Confirm publish</DialogTitle>
          <Text css={{padding: 16}}>
            You are publishing page <strong>{pageName}</strong>. Continue?
          </Text>
          <Flex justify="end" gap={2} css={{ padding: 16 }}>
            <Button
              color="neutral"
              onClick={() => {
                setIsConfirmOpen(false);
              }}
              disabled={isSubmitting}
            >
              Cancel
            </Button>
            <Button
              color="positive"
              state={isSubmitting ? "pending" : undefined}
              onClick={onConfirmPublish}
            >
              Confirm publish
            </Button>
          </Flex>
        </DialogContent>
      </Dialog>
    </>
  );
};
