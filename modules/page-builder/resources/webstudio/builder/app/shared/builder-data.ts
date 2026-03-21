import { getStyleDeclKey, type WebstudioData } from "@webstudio-is/sdk";
import type { MarketplaceProduct } from "@webstudio-is/project-build";
import type { Project } from "@webstudio-is/project";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore route type module is generated in upstream Webstudio, may be absent in Pagify workspace indexing
import type { loader } from "~/routes/rest.data.$projectId";
import type { PagifyRegisteredComponent } from "~/shared/pagify-registered-components";
import {
  $project,
  $assets,
  $breakpoints,
  $dataSources,
  $instances,
  $marketplaceProduct,
  $pages,
  $props,
  $resources,
  $styleSourceSelections,
  $styleSources,
  $styles,
} from "./nano-states";
import { fetch } from "~/shared/fetch.client";
import {
  restDataPath,
  restPageDataPath,
  restRegistryPath,
} from "~/shared/router-utils/path-utils";

const BUILDER_DATA_TIMEOUT_MS = 15000;

const createLoadAbortSignal = (parentSignal: AbortSignal, timeoutMs: number) => {
  const controller = new AbortController();
  const timeoutId = window.setTimeout(() => {
    controller.abort(new Error("Builder data request timed out"));
  }, timeoutMs);

  const onParentAbort = () => {
    controller.abort(parentSignal.reason);
  };

  if (parentSignal.aborted) {
    onParentAbort();
  } else {
    parentSignal.addEventListener("abort", onParentAbort, { once: true });
  }

  const cleanup = () => {
    window.clearTimeout(timeoutId);
    parentSignal.removeEventListener("abort", onParentAbort);
  };

  return {
    signal: controller.signal,
    cleanup,
  };
};

export type BuilderData = WebstudioData & {
  marketplaceProduct: undefined | MarketplaceProduct;
  project: Project;
  registeredComponents: PagifyRegisteredComponent[];
};

export type LoadedBuilderData = BuilderData &
  Pick<
    Awaited<ReturnType<typeof loader>>,
    "id" | "version" | "publisherHost" | "projectId"
  >;

export type LoadedBuilderPageData = {
  id: string;
  version: number;
  projectId: string;
  pageId: string;
  instances: LoadedBuilderData["instances"];
  props: LoadedBuilderData["props"];
  dataSources: LoadedBuilderData["dataSources"];
  resources: LoadedBuilderData["resources"];
  breakpoints: LoadedBuilderData["breakpoints"];
  styleSources: LoadedBuilderData["styleSources"];
  styleSourceSelections: LoadedBuilderData["styleSourceSelections"];
  styles: LoadedBuilderData["styles"];
};

type BuilderPageDataResponse = {
  id: string;
  version: number;
  projectId: string;
  pageId: string;
  instances: Awaited<ReturnType<typeof loader>>["instances"];
  props: Awaited<ReturnType<typeof loader>>["props"];
  dataSources: Awaited<ReturnType<typeof loader>>["dataSources"];
  resources: Awaited<ReturnType<typeof loader>>["resources"];
  breakpoints: Awaited<ReturnType<typeof loader>>["breakpoints"];
  styleSources: Awaited<ReturnType<typeof loader>>["styleSources"];
  styleSourceSelections: Awaited<ReturnType<typeof loader>>["styleSourceSelections"];
  styles: Awaited<ReturnType<typeof loader>>["styles"];
};

type BuilderDataResponse = {
  id: Awaited<ReturnType<typeof loader>>["id"];
  version: Awaited<ReturnType<typeof loader>>["version"];
  projectId: Awaited<ReturnType<typeof loader>>["projectId"];
  publisherHost: Awaited<ReturnType<typeof loader>>["publisherHost"];
  project: Awaited<ReturnType<typeof loader>>["project"];
  pages: Awaited<ReturnType<typeof loader>>["pages"];
  assets: Awaited<ReturnType<typeof loader>>["assets"];
  instances: Awaited<ReturnType<typeof loader>>["instances"];
  dataSources: Awaited<ReturnType<typeof loader>>["dataSources"];
  resources: Awaited<ReturnType<typeof loader>>["resources"];
  props: Awaited<ReturnType<typeof loader>>["props"];
  breakpoints: Awaited<ReturnType<typeof loader>>["breakpoints"];
  styleSources: Awaited<ReturnType<typeof loader>>["styleSources"];
  styleSourceSelections: Awaited<ReturnType<typeof loader>>["styleSourceSelections"];
  styles: Awaited<ReturnType<typeof loader>>["styles"];
  marketplaceProduct: Awaited<ReturnType<typeof loader>>["marketplaceProduct"];
  registeredComponents?: PagifyRegisteredComponent[];
};

type RegistryResponse = {
  data?: {
    blocks?: Array<{
      key?: string;
      label?: string;
      description?: string;
      icon?: string;
      owner?: string;
    }>;
  };
};

export const getBuilderData = (): BuilderData => {
  const pages = $pages.get();
  if (pages === undefined) {
    throw Error(`Cannot get webstudio data with empty pages`);
  }
  const project = $project.get();
  if (project === undefined) {
    throw Error(`Cannot get webstudio data with empty project`);
  }
  return {
    pages,
    project,
    instances: $instances.get(),
    props: $props.get(),
    dataSources: $dataSources.get(),
    resources: $resources.get(),
    breakpoints: $breakpoints.get(),
    styleSourceSelections: $styleSourceSelections.get(),
    styleSources: $styleSources.get(),
    styles: $styles.get(),
    assets: $assets.get(),
    marketplaceProduct: $marketplaceProduct.get(),
    registeredComponents: [],
  };
};

export const serializeBuilderDataForPatch = (
  data: BuilderData = getBuilderData()
) => {
  return {
    instances: Array.from(data.instances.values()),
    props: Array.from(data.props.values()),
    dataSources: Array.from(data.dataSources.values()),
    resources: Array.from(data.resources.values()),
    breakpoints: Array.from(data.breakpoints.values()),
    styleSources: Array.from(data.styleSources.values()),
    styleSourceSelections: Array.from(data.styleSourceSelections.values()),
    styles: Array.from(data.styles.values()),
  };
};

const getPair = <Item extends { id: string }>(item: Item) =>
  [item.id, item] as const;

export const loadBuilderData = async ({
  projectId,
  signal,
}: {
  projectId: string;
  signal: AbortSignal;
}): Promise<LoadedBuilderData> => {
  const currentUrl = new URL(location.href);
  const url = new URL(restDataPath(projectId), currentUrl.origin);
  const headers = new Headers();

  const { signal: timedSignal, cleanup } = createLoadAbortSignal(
    signal,
    BUILDER_DATA_TIMEOUT_MS
  );

  let response: Response;

  try {
    response = await fetch(url, { headers, signal: timedSignal });
  } catch (error) {
    cleanup();

    const isTimeout =
      timedSignal.aborted === true &&
      signal.aborted === false &&
      (error instanceof DOMException || error instanceof Error);

    if (isTimeout) {
      throw new Error(
        `Builder data request timed out after ${BUILDER_DATA_TIMEOUT_MS}ms: ${url}`
      );
    }

    throw error;
  }

  cleanup();

  if (response.ok) {
    const data = (await response.json()) as BuilderDataResponse;
    return {
      id: data.id,
      version: data.version,
      projectId: data.projectId,
      project: data.project,
      publisherHost: data.publisherHost,
      assets: new Map(data.assets.map(getPair)),
      instances: new Map(data.instances.map(getPair)),
      dataSources: new Map(data.dataSources.map(getPair)),
      resources: new Map(data.resources.map(getPair)),
      props: new Map(data.props.map(getPair)),
      pages: data.pages,
      breakpoints: new Map(data.breakpoints.map(getPair)),
      styleSources: new Map(data.styleSources.map(getPair)),
      styleSourceSelections: new Map(
        data.styleSourceSelections.map((item: any) => [item.instanceId, item])
      ),
      styles: new Map(
        data.styles.map((item: any) => [getStyleDeclKey(item), item])
      ),
      marketplaceProduct: data.marketplaceProduct,
      registeredComponents: Array.isArray(data.registeredComponents)
        ? data.registeredComponents
        : [],
    };
  }

  const text = await response.text();

  // No toasts available in this context
  alert(
    `Unable to load builder data. Response status: ${response.status}. Response text: ${text}`
  );

  throw Error(
    `Unable to load builder data. Response status: ${response.status}. Response text: ${text}`
  );
};

export const loadBuilderPageData = async ({
  projectId,
  pageId,
  signal,
}: {
  projectId: string;
  pageId: string;
  signal: AbortSignal;
}): Promise<LoadedBuilderPageData> => {
  const currentUrl = new URL(location.href);
  const url = new URL(restPageDataPath(projectId, pageId), currentUrl.origin);
  const headers = new Headers();

  const response = await fetch(url, { headers, signal });

  if (response.ok) {
    const data = (await response.json()) as BuilderPageDataResponse;

    return {
      id: data.id,
      version: data.version,
      projectId: data.projectId,
      pageId: data.pageId,
      instances: new Map(data.instances.map(getPair)),
      props: new Map(data.props.map(getPair)),
      dataSources: new Map(data.dataSources.map(getPair)),
      resources: new Map(data.resources.map(getPair)),
      breakpoints: new Map(data.breakpoints.map(getPair)),
      styleSources: new Map(data.styleSources.map(getPair)),
      styleSourceSelections: new Map(
        data.styleSourceSelections.map((item: any) => [item.instanceId, item])
      ),
      styles: new Map(
        data.styles.map((item: any) => [getStyleDeclKey(item), item])
      ),
    };
  }

  const text = await response.text();
  throw Error(
    `Unable to load page data. Response status: ${response.status}. Response text: ${text}`
  );
};

export const loadRegisteredComponentsFromRegistry = async ({
  signal,
}: {
  signal: AbortSignal;
}): Promise<PagifyRegisteredComponent[]> => {
  const currentUrl = new URL(location.href);
  const url = new URL(restRegistryPath(), currentUrl.origin);

  const response = await fetch(url, {
    method: "get",
    signal,
  });

  if (!response.ok) {
    return [];
  }

  const payload = (await response.json()) as RegistryResponse;
  const blocks = payload.data?.blocks;

  if (!Array.isArray(blocks)) {
    return [];
  }

  return blocks
    .map((item) => ({
      key: typeof item.key === "string" ? item.key : "",
      label:
        typeof item.label === "string" && item.label !== ""
          ? item.label
          : typeof item.key === "string"
            ? item.key
            : "",
      description:
        typeof item.description === "string" ? item.description : undefined,
      icon: typeof item.icon === "string" ? item.icon : undefined,
      owner: typeof item.owner === "string" ? item.owner : undefined,
    }))
    .filter((item) => item.key !== "");
};
