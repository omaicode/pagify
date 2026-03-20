import type { Project } from "@webstudio-is/project";
import type { AuthPermit } from "@webstudio-is/trpc-interface/index.server";
import { SyncClient } from "~/shared/sync-client";
import { registerContainers, createObjectPool } from "./sync-stores";
import {
  ServerSyncStorage,
  enqueueProjectDetails,
  getCachedProjectDetails,
  stopPolling,
} from "./project-queue";
import { loadBuilderData, loadBuilderPageData } from "~/shared/builder-data";
import {
  $project,
  $pages,
  $assets,
  $instances,
  $props,
  $dataSources,
  $resources,
  $breakpoints,
  $styleSources,
  $styleSourceSelections,
  $styles,
  $marketplaceProduct,
  $publisherHost,
  resetDataStores,
} from "./data-stores";
import { $selectedPage } from "~/shared/awareness";
import { $isPageDataLoading } from "~/shared/nano-states";

let client: SyncClient | undefined;
let currentProjectId: string | undefined;
let pageSelectionUnsubscribe: undefined | (() => void);
let pageSelectionAbortController: undefined | AbortController;

const sleep = (ms: number) => new Promise((resolve) => window.setTimeout(resolve, ms));

const loadBuilderDataWithRetry = async ({
  projectId,
  signal,
  retries = 1,
}: {
  projectId: Project["id"];
  signal: AbortSignal;
  retries?: number;
}) => {
  let attempt = 0;

  while (true) {
    try {
      return await loadBuilderData({ projectId, signal });
    } catch (error) {
      if (signal.aborted || attempt >= retries) {
        throw error;
      }

      attempt += 1;
      console.warn("Retrying builder data load", {
        projectId,
        attempt,
        retries,
        error,
      });
      await sleep(300);
    }
  }
};

/**
 * Initialize the sync infrastructure and load project data.
 * Can be used from both the builder and dashboard contexts.
 */
export const initializeClientSync = ({
  projectId,
  authPermit,
  authToken,
  signal,
  onReady,
  onError,
}: {
  projectId: Project["id"];
  authPermit: AuthPermit;
  authToken?: string;
  signal: AbortSignal;
  onReady?: () => void;
  onError?: (error: unknown) => void;
}) => {
  // Note: "view" permit will skip transaction synchronization

  // Reset sync client if projectId changed
  if (client && currentProjectId !== projectId) {
    destroyClientSync();
    client = undefined;
  }

  // Only register containers once and create sync client
  if (!client) {
    registerContainers();
    client = new SyncClient({
      role: "leader",
      object: createObjectPool(),
      storages: [new ServerSyncStorage(projectId)],
    });
    currentProjectId = projectId;
  }

  pageSelectionUnsubscribe?.();
  pageSelectionUnsubscribe = $selectedPage.subscribe((selectedPage) => {
    if (
      selectedPage === undefined ||
      currentProjectId !== projectId ||
      /^\d+$/.test(selectedPage.id) === false
    ) {
      return;
    }

    // Avoid redundant request when selected page instances are already in-memory.
    if ($instances.get().has(selectedPage.rootInstanceId)) {
      return;
    }

    pageSelectionAbortController?.abort();
    const controller = new AbortController();
    pageSelectionAbortController = controller;
    $isPageDataLoading.set(true);

    loadBuilderPageData({
      projectId,
      pageId: selectedPage.id,
      signal: controller.signal,
    })
      .then((pageData) => {
        if (controller.signal.aborted) {
          return;
        }

        // Apply per-page snapshot so navigator/inspector immediately reflect selected page.
        $instances.set(pageData.instances);
        $props.set(pageData.props);
        $dataSources.set(pageData.dataSources);
        $resources.set(pageData.resources);
        $breakpoints.set(pageData.breakpoints);
        $styleSources.set(pageData.styleSources);
        $styleSourceSelections.set(pageData.styleSourceSelections);
        $styles.set(pageData.styles);

        if (authPermit !== "view") {
          enqueueProjectDetails({
            projectId,
            buildId: pageData.id,
            version: pageData.version,
            authPermit,
            authToken,
          });
        }
      })
      .catch((error) => {
        if (controller.signal.aborted || signal.aborted) {
          return;
        }
        console.error("Failed to load selected page data:", error);
        onError?.(error);
      })
      .finally(() => {
        if (pageSelectionAbortController === controller) {
          pageSelectionAbortController = undefined;
        }
        $isPageDataLoading.set(false);
      });
  });

  client.connect({
    signal,
    onReady() {
      // Load builder data if we don't have it yet OR if projectId changed
      const currentProjectInStore = $project.get()?.id;
      const needsDataLoad =
        !$pages.get() || currentProjectInStore !== projectId;

      if (needsDataLoad === false) {
        const cachedDetails = getCachedProjectDetails(projectId);

        if (authPermit !== "view" && cachedDetails !== undefined) {
          enqueueProjectDetails({
            projectId,
            buildId: cachedDetails.buildId,
            version: cachedDetails.version,
            authPermit,
            authToken: cachedDetails.authToken ?? authToken,
          });
        }

        if (authPermit === "view" || cachedDetails !== undefined) {
          onReady?.();
          return;
        }
      }

      loadBuilderDataWithRetry({ projectId, signal, retries: 1 })
        .then((data) => {
          if (needsDataLoad) {
            // Set publisherHost from loaded data (needed for $publishedOrigin computed store)
            $publisherHost.set(data.publisherHost);

            // Set all the stores with loaded data
            $project.set(data.project);
            $pages.set(data.pages);
            $assets.set(data.assets);
            $instances.set(data.instances);
            $props.set(data.props);
            $dataSources.set(data.dataSources);
            $resources.set(data.resources);
            $breakpoints.set(data.breakpoints);
            $styleSources.set(data.styleSources);
            $styleSourceSelections.set(data.styleSourceSelections);
            $styles.set(data.styles);
            $marketplaceProduct.set(data.marketplaceProduct);
          }

          // Start project sync with build info from loaded data
          if (authPermit !== "view") {
            enqueueProjectDetails({
              projectId,
              buildId: data.id,
              version: data.version,
              authPermit,
              authToken,
            });
          }

          onReady?.();
        })
        .catch((error) => {
          if (error.name !== "AbortError") {
            console.error("Failed to load project data:", error);
            onError?.(error);
          }
        });
    },
  });
};

/**
 * Destroy sync client and reset all data stores.
 * Call this when closing the builder or switching between projects.
 */
export const destroyClientSync = () => {
  pageSelectionAbortController?.abort();
  pageSelectionAbortController = undefined;
  pageSelectionUnsubscribe?.();
  pageSelectionUnsubscribe = undefined;
  $isPageDataLoading.set(false);
  resetDataStores();
  stopPolling();
};

export const getSyncClient = () => client;
